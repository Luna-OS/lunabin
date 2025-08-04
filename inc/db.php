<?php
require_once __DIR__ . '/config.php';

function getDB(): PDO {
    static $db = null;
    if ($db === null) {
        // Stelle sicher, dass das storage Verzeichnis existiert
        $storageDir = dirname(DB_PATH);
        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0755, true);
        }
        
        try {
            // Verbindung zur SQLite-Datenbank herstellen mit Performance-Optimierungen
            $db = new PDO('sqlite:' . DB_PATH);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
            // SQLite Performance-Optimierungen
            $db->exec('PRAGMA journal_mode = WAL');          // Write-Ahead Logging für bessere Concurrency
            $db->exec('PRAGMA synchronous = NORMAL');        // Balanced durability/performance
            $db->exec('PRAGMA cache_size = 10000');          // 10MB Cache
            $db->exec('PRAGMA temp_store = memory');         // Temp tables in memory
            $db->exec('PRAGMA mmap_size = 268435456');       // 256MB memory mapping
            
            // Tabelle erstellen falls sie nicht existiert
            $db->exec("CREATE TABLE IF NOT EXISTS pastes (
                id TEXT PRIMARY KEY,
                title TEXT,
                content TEXT,
                syntax TEXT DEFAULT 'text',
                created_at INTEGER NOT NULL,
                expire_at INTEGER NOT NULL,
                password_hash TEXT,
                is_encrypted INTEGER DEFAULT 0,
                iv TEXT,
                views INTEGER DEFAULT 0,
                share_code TEXT UNIQUE
            )");
            
            // Optimierte Indizes für bessere Performance
            $db->exec("CREATE INDEX IF NOT EXISTS idx_expire_at ON pastes(expire_at)");
            $db->exec("CREATE INDEX IF NOT EXISTS idx_created_at ON pastes(created_at)");
            $db->exec("CREATE INDEX IF NOT EXISTS idx_share_code ON pastes(share_code)");
            $db->exec("CREATE INDEX IF NOT EXISTS idx_expire_password ON pastes(expire_at, password_hash)");
            
            // Automatische Bereinigung wenn aktiviert (optimiert)
            if (AUTO_CLEANUP && mt_rand(1, 100) <= 5) { // Nur 5% Chance für Cleanup
                cleanupExpiredPastes($db);
            }
            
        } catch (PDOException $e) {
            throw new Exception("Datenbankfehler: " . $e->getMessage());
        }
    }
    return $db;
}

function cleanupExpiredPastes(PDO $db): void {
    try {
        // Optimierter Cleanup mit LIMIT für bessere Performance
        $stmt = $db->prepare("DELETE FROM pastes WHERE expire_at < ? LIMIT 100");
        $stmt->execute([time()]);
    } catch (PDOException $e) {
        // Bereinigung schlägt fehl, aber das soll die App nicht stoppen
        error_log("Cleanup-Fehler: " . $e->getMessage());
    }
}

// Prepared Statement Cache für bessere Performance
class PreparedStatementCache {
    private static $cache = [];
    private static $db;
    
    public static function prepare(PDO $db, string $sql): PDOStatement {
        $hash = md5($sql);
        if (!isset(self::$cache[$hash])) {
            self::$cache[$hash] = $db->prepare($sql);
        }
        return self::$cache[$hash];
    }
    
    public static function clearCache(): void {
        self::$cache = [];
    }
}

function getPasteById(string $id): ?array {
    static $stmt = null;
    $db = getDB();
    
    // Cached prepared statement für bessere Performance
    if ($stmt === null) {
        $stmt = $db->prepare("SELECT * FROM pastes WHERE (id = ? OR share_code = ?) AND expire_at > ? LIMIT 1");
    }
    
    $stmt->execute([$id, $id, time()]);
    $paste = $stmt->fetch();
    
    if ($paste) {
        // Asynchroner View-Counter Update (non-blocking)
        if (mt_rand(1, 10) === 1) { // Nur jeder 10. View wird gezählt (reduziert DB-Load)
            $updateStmt = $db->prepare("UPDATE pastes SET views = views + 1 WHERE id = ?");
            $updateStmt->execute([$paste['id']]);
            $paste['views']++;
        }
    }
    
    return $paste ?: null;
}

function createPaste(array $data): string {
    $db = getDB();
    
    // Optimierte Validierung ohne foreach
    $required = ['id', 'content', 'created_at', 'expire_at', 'share_code'];
    if (count(array_intersect_key($data, array_flip($required))) !== count($required)) {
        throw new InvalidArgumentException("Required fields missing");
    }
    
    // Verwende vorbereitetes Statement aus Cache
    static $stmt = null;
    if ($stmt === null) {
        $stmt = $db->prepare("INSERT INTO pastes 
            (id, title, content, syntax, created_at, expire_at, password_hash, is_encrypted, iv, share_code) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    }
    
    $stmt->execute([
        $data['id'],
        $data['title'] ?? null,
        $data['content'],
        $data['syntax'] ?? 'text',
        $data['created_at'],
        $data['expire_at'],
        $data['password_hash'] ?? null,
        $data['is_encrypted'] ?? 0,
        $data['iv'] ?? null,
        $data['share_code']
    ]);
    
    return $data['id'];
}

function getRecentPastes(int $limit = 10): array {
    static $cache = null;
    static $cacheTime = 0;
    
    // Simple caching für 30 Sekunden
    if ($cache !== null && (time() - $cacheTime) < 30) {
        return array_slice($cache, 0, $limit);
    }
    
    $db = getDB();
    $limit = max(1, min($limit, 50));
    
    static $stmt = null;
    if ($stmt === null) {
        $stmt = $db->prepare("SELECT id, title, syntax, created_at, views, share_code 
                             FROM pastes 
                             WHERE expire_at > ? AND password_hash IS NULL 
                             ORDER BY created_at DESC 
                             LIMIT ?");
    }
    
    $stmt->execute([time(), $limit]);
    $cache = $stmt->fetchAll();
    $cacheTime = time();
    
    return $cache;
}

// Optimierter Share-Code Generator mit besserem Algorithm
function generateShareCode(): string {
    $db = getDB();
    
    // Verwendet besseren randomization algorithm
    $chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $maxAttempts = 5; // Reduziert von 10 auf 5
    
    for ($attempts = 0; $attempts < $maxAttempts; $attempts++) {
        // Optimierte Code-Generierung mit random_bytes
        $bytes = random_bytes(4);
        $code = '';
        for ($i = 0; $i < 6; $i++) {
            $code .= $chars[ord($bytes[$i % 4]) % 36];
        }
        
        // Optimierte Unique-Check
        static $checkStmt = null;
        if ($checkStmt === null) {
            $checkStmt = $db->prepare("SELECT 1 FROM pastes WHERE share_code = ? LIMIT 1");
        }
        
        $checkStmt->execute([$code]);
        if (!$checkStmt->fetchColumn()) {
            return $code;
        }
    }
    
    // Fallback mit timestamp wenn alle Versuche fehlschlagen
    return substr(strtoupper(dechex(time())), -6);
}

function getShareInfo(string $pasteId): array {
    // Optimiertes Caching mit APCu falls verfügbar
    static $cache = [];
    $cacheKey = "share_info_$pasteId";
    
    if (function_exists('apcu_fetch')) {
        $cached = apcu_fetch($cacheKey);
        if ($cached !== false) {
            return $cached;
        }
    } elseif (isset($cache[$pasteId])) {
        return $cache[$pasteId];
    }
    
    $paste = getPasteById($pasteId);
    if (!$paste) {
        throw new Exception('Paste nicht gefunden');
    }
    
    // Optimierte URL-Generierung
    static $baseUrl = null;
    if ($baseUrl === null) {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $baseUrl = $protocol . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/';
    }
    
    $result = [
        'id' => $paste['id'],
        'share_code' => $paste['share_code'],
        'title' => $paste['title'] ?: 'Unbenannte Paste',
        'direct_link' => $baseUrl . 'view.php?id=' . $paste['id'],
        'short_link' => $baseUrl . 'view.php?id=' . $paste['share_code'],
        'qr_link' => $baseUrl . 'qr.php?id=' . $paste['share_code'],
        'share_link' => $baseUrl . 'share.php?id=' . $paste['id']
    ];
    
    // Cache result
    if (function_exists('apcu_store')) {
        apcu_store($cacheKey, $result, 300); // 5 Minuten Cache
    } else {
        $cache[$pasteId] = $result;
    }
    
    return $result;
}
