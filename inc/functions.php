<?php
require_once __DIR__ . '/config.php';

/**
 * Generiert eine eindeutige alphanumerische ID - Optimized
 */
function generateId(int $length = 8): string {
    // Optimierte ID-Generierung mit random_bytes
    if ($length <= 0) return '';
    
    $bytes = random_bytes(ceil($length * 3/4));
    $id = substr(base64_encode($bytes), 0, $length);
    
    // Replace URL-unsafe characters
    return strtr($id, '+/', 'AB');
}

/**
 * Verschlüsselt Inhalt mit AES-256-CBC - Optimized
 */
function encryptContent(string $content): array {
    if (empty($content)) {
        throw new InvalidArgumentException('Content cannot be empty');
    }
    
    $iv = random_bytes(16);
    $encrypted = openssl_encrypt($content, 'AES-256-CBC', ENCRYPTION_KEY, OPENSSL_RAW_DATA, $iv);
    
    if ($encrypted === false) {
        throw new RuntimeException('Encryption failed');
    }
    
    return [
        'data' => base64_encode($encrypted),
        'iv' => base64_encode($iv)
    ];
}

/**
 * Entschlüsselt Inhalt - Optimized
 */
function decryptContent(string $encryptedData, string $iv): string {
    if (empty($encryptedData) || empty($iv)) {
        return '';
    }
    
    $decrypted = openssl_decrypt(
        base64_decode($encryptedData), 
        'AES-256-CBC',
        ENCRYPTION_KEY,
        OPENSSL_RAW_DATA,
        base64_decode($iv)
    );
    
    return $decrypted !== false ? $decrypted : '';
}

/**
 * Formatiert Zeitstempel zu "vor X Zeit" - Ultra Optimized
 */
function formatTimeAgo(int $timestamp): string {
    static $units = [
        31536000 => 'J',    // Jahr
        2592000 => 'M',     // Monat  
        86400 => 'd',       // Tag
        3600 => 'h',        // Stunde
        60 => 'm'           // Minute
    ];
    
    $diff = time() - $timestamp;
    if ($diff < 60) return "vor {$diff}s";
    
    foreach ($units as $seconds => $unit) {
        if ($diff >= $seconds) {
            return 'vor ' . intval($diff / $seconds) . $unit;
        }
    }
    
    return date('d.m.Y', $timestamp);
}

/**
 * Sanitisiert Output für HTML - Ultra Optimized
 */
function sanitizeOutput(string $text): string {
    static $flags = ENT_QUOTES | ENT_HTML5;
    return htmlspecialchars(trim($text), $flags, 'UTF-8');
}

// ============================================================================
// 📊 OPTIMIZED SYNTAX HIGHLIGHTING
// ============================================================================

/**
 * Optimized Syntax-Highlighting Functions
 */
function highlightCode(string $code, string $language = 'text'): string {
    // Early return for empty code
    if (empty($code)) {
        return '<pre class="hljs"><code></code></pre>';
    }
    
    $code = htmlspecialchars($code);
    
    switch (strtolower($language)) {
        case 'php':
            return highlightPhp($code);
        case 'html':
        case 'htm':
            return highlightHtml($code);
        case 'css':
            return highlightCss($code);
        case 'javascript':
        case 'js':
            return highlightJavaScript($code);
        case 'sql':
            return highlightSql($code);
        case 'json':
            return highlightJson($code);
        default:
            return '<pre class="hljs"><code>' . $code . '</code></pre>';
    }
}

// Helper function to apply highlighting patterns
function applyHighlighting(string $code, array $patterns): string {
    foreach ($patterns as $pattern => $replacement) {
        $code = preg_replace($pattern, $replacement, $code);
    }
    
    return '<pre class="hljs"><code>' . $code . '</code></pre>';
}

function highlightPhp(string $code): string {
    // Optimized regex patterns with combined rules
    static $patterns = [
        '/(&lt;\?php|\?&gt;)/' => '<span class="hljs-meta">$1</span>',
        '/\b(class|function|return|if|else|elseif|endif|while|for|foreach|try|catch|throw|new|public|private|protected|static|const|var|echo|print|include|require|namespace|use)\b/' => '<span class="hljs-keyword">$1</span>',
        '/(\$[a-zA-Z_][a-zA-Z0-9_]*)/' => '<span class="hljs-variable">$1</span>',
        '/(\/\/.*$|\/\*.*?\*\/)/sm' => '<span class="hljs-comment">$1</span>',
        '/(["\'])([^"\']*)\1/' => '<span class="hljs-string">$1$2$1</span>',
    ];
    
    return applyHighlighting($code, $patterns);
}

function highlightHtml(string $code): string {
    static $patterns = [
        '/(&lt;\/?)([a-zA-Z][a-zA-Z0-9]*)(.*?)(&gt;)/' => '<span class="hljs-tag">$1<span class="hljs-name">$2</span>$3$4</span>',
        '/(\s)([a-zA-Z-]+)(=)(["\'])([^"\']*)\4/' => '$1<span class="hljs-attr">$2</span>$3<span class="hljs-string">$4$5$4</span>',
        '/(&lt;!--.*?--&gt;)/s' => '<span class="hljs-comment">$1</span>',
    ];
    
    return applyHighlighting($code, $patterns);
}

function highlightCss(string $code): string {
    static $patterns = [
        '/([.#]?[a-zA-Z][a-zA-Z0-9_-]*)\s*\{/' => '<span class="hljs-selector-tag">$1</span> {',
        '/([a-zA-Z-]+)\s*:/' => '<span class="hljs-attribute">$1</span>:',
        '/:\s*([^;{]+)/' => ': <span class="hljs-value">$1</span>',
        '/(\/\*.*?\*\/)/s' => '<span class="hljs-comment">$1</span>',
        '/(["\'])([^"\']*)\1/' => '<span class="hljs-string">$1$2$1</span>',
    ];
    
    return applyHighlighting($code, $patterns);
}

function highlightJavaScript(string $code): string {
    static $patterns = [
        '/\b(var|let|const|function|return|if|else|for|while|do|switch|case|break|continue|try|catch|finally|throw|new|this|typeof|instanceof)\b/' => '<span class="hljs-keyword">$1</span>',
        '/\b(console|document|window|Array|Object|String|Number|Boolean|Date|Math|JSON)\b/' => '<span class="hljs-built_in">$1</span>',
        '/(\/\/.*$|\/\*.*?\*\/)/sm' => '<span class="hljs-comment">$1</span>',
        '/(["\'])([^"\']*)\1/' => '<span class="hljs-string">$1$2$1</span>',
        '/\b(\d+)\b/' => '<span class="hljs-number">$1</span>',
    ];
    
    return applyHighlighting($code, $patterns);
}

function highlightSql(string $code): string {
    static $patterns = [
        '/\b(SELECT|FROM|WHERE|INSERT|UPDATE|DELETE|CREATE|DROP|ALTER|TABLE|INDEX|PRIMARY|KEY|FOREIGN|REFERENCES|JOIN|LEFT|RIGHT|INNER|OUTER|GROUP|ORDER|BY|HAVING|LIMIT|OFFSET|UNION|AND|OR|NOT|NULL|IS|IN|LIKE|BETWEEN)\b/i' => '<span class="hljs-keyword">$1</span>',
        '/\b(VARCHAR|INT|INTEGER|TEXT|DATETIME|TIMESTAMP|BOOLEAN|FLOAT|DOUBLE|DECIMAL)\b/i' => '<span class="hljs-type">$1</span>',
        '/(["\'])([^"\']*)\1/' => '<span class="hljs-string">$1$2$1</span>',
        '/(--.*)$/m' => '<span class="hljs-comment">$1</span>',
        '/\b(\d+)\b/' => '<span class="hljs-number">$1</span>',
    ];
    
    return applyHighlighting($code, $patterns);
}

function highlightJson(string $code): string {
    static $patterns = [
        '/(["\'])([^"\']*)\1\s*:/' => '<span class="hljs-attr">$1$2$1</span>:',
        '/:\s*(["\'])([^"\']*)\1/' => ': <span class="hljs-string">$1$2$1</span>',
        '/:\s*(true|false|null)\b/' => ': <span class="hljs-literal">$1</span>',
        '/:\s*(\d+(?:\.\d+)?)/' => ': <span class="hljs-number">$1</span>',
    ];
    
    return applyHighlighting($code, $patterns);
}

// ============================================================================
// 🛡️ ESSENTIAL ADMIN FUNCTIONS
// ============================================================================

/**
 * 🔍 Prüft ob Admin-Passwort bereits gesetzt ist
 */
function isAdminPasswordSet(): bool {
    static $checked = null;
    if ($checked === null) {
        $checked = file_exists(__DIR__ . '/../storage/.admin');
    }
    return $checked;
}

/**
 * 🔒 Admin Authentication Check
 */
function isAdminAuthenticated(): bool {
    if (!isset($_SESSION)) session_start();
    
    if (empty($_SESSION['admin_authenticated']) || 
        empty($_SESSION['admin_login_time']) ||
        (time() - $_SESSION['admin_login_time']) > 7200) {
        
        adminLogout();
        return false;
    }
    
    return true;
}

/**
 * ✅ Admin Login Verification
 */
function verifyAdminLogin(string $password): bool {
    if (!isAdminPasswordSet()) {
        return false;
    }
    
    $adminFile = __DIR__ . '/../storage/.admin';
    $adminData = json_decode(file_get_contents($adminFile), true);
    
    if (!$adminData || !isset($adminData['password_hash'])) {
        return false;
    }
    
    $isValid = password_verify($password, $adminData['password_hash']);
    
    if ($isValid) {
        if (!isset($_SESSION)) session_start();
        $_SESSION['admin_authenticated'] = true;
        $_SESSION['admin_login_time'] = time();
        $_SESSION['admin_csrf_token'] = $adminData['csrf_token'] ?? bin2hex(random_bytes(16));
        
        // Update last login
        $adminData['last_login'] = time();
        file_put_contents($adminFile, json_encode($adminData, JSON_UNESCAPED_UNICODE), LOCK_EX);
        
        return true;
    }
    
    return false;
}

/**
 * 🚪 Admin Logout
 */
function adminLogout(): void {
    if (!isset($_SESSION)) session_start();
    
    unset($_SESSION['admin_authenticated'], $_SESSION['admin_login_time'], $_SESSION['admin_csrf_token']);
}

/**
 * 🔐 Admin Passwort Setup
 */
function setupAdminPassword(string $password): bool {
    if (isAdminPasswordSet()) {
        return false;
    }
    
    if (strlen($password) < 8) {
        return false;
    }
    
    $adminFile = __DIR__ . '/../storage/.admin';
    
    $hashedPassword = password_hash($password, PASSWORD_ARGON2ID, [
        'memory_cost' => 32768,
        'time_cost' => 4,
        'threads' => 2
    ]);
    
    $adminData = [
        'password_hash' => $hashedPassword,
        'created_at' => time(),
        'last_login' => null,
        'csrf_token' => bin2hex(random_bytes(16))
    ];
    
    $success = file_put_contents(
        $adminFile, 
        json_encode($adminData, JSON_UNESCAPED_UNICODE),
        LOCK_EX
    );
    
    if ($success) {
        chmod($adminFile, 0600);
        return true;
    }
    
    return false;
}

/**
 * 🔄 Change Admin Password
 * Ändert das Admin-Passwort nach Verifizierung des aktuellen Passworts
 */
function changeAdminPassword(string $currentPassword, string $newPassword): array {
    $result = [
        'success' => false,
        'message' => ''
    ];
    
    // Verifiziere aktuelles Passwort
    if (!verifyAdminLogin($currentPassword)) {
        $result['message'] = 'Aktuelles Passwort ist falsch.';
        return $result;
    }
    
    // Validiere neues Passwort
    if (strlen($newPassword) < 6) {
        $result['message'] = 'Neues Passwort muss mindestens 6 Zeichen lang sein.';
        return $result;
    }
    
    $adminFile = __DIR__ . '/../storage/.admin';
    
    try {
        // Lade aktuelle Admin-Daten
        $currentData = json_decode(file_get_contents($adminFile), true);
        if (!$currentData) {
            $result['message'] = 'Fehler beim Laden der Admin-Daten.';
            return $result;
        }
        
        // Erstelle neues gehashtes Passwort
        $hashedPassword = password_hash($newPassword, PASSWORD_ARGON2ID, [
            'memory_cost' => 32768,
            'time_cost' => 4,
            'threads' => 2
        ]);
        
        // Aktualisiere nur das Passwort, behalte andere Daten
        $currentData['password_hash'] = $hashedPassword;
        $currentData['password_changed_at'] = time();
        
        // Speichere aktualisierte Daten
        $success = file_put_contents(
            $adminFile, 
            json_encode($currentData, JSON_UNESCAPED_UNICODE),
            LOCK_EX
        );
        
        if ($success) {
            chmod($adminFile, 0600);
            $result['success'] = true;
            $result['message'] = 'Passwort erfolgreich geändert!';
        } else {
            $result['message'] = 'Fehler beim Speichern des neuen Passworts.';
        }
        
    } catch (Exception $e) {
        $result['message'] = 'Fehler: ' . $e->getMessage();
    }
    
    return $result;
}

/**
 * 🛡️ CSRF Token Generation
 */
function generateCSRFToken(): string {
    if (!isset($_SESSION)) session_start();
    
    if (!isset($_SESSION['admin_csrf_token'])) {
        $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(16));
    }
    
    return $_SESSION['admin_csrf_token'];
}

/**
 * ✅ CSRF Token Validation
 */
function validateCSRFToken(string $token): bool {
    if (!isset($_SESSION)) session_start();
    
    return isset($_SESSION['admin_csrf_token']) && 
           hash_equals($_SESSION['admin_csrf_token'], $token);
}

// ============================================================================
// 📊 ADMIN DATABASE FUNCTIONS
// ============================================================================

/**
 * 📋 Get all pastes for admin panel
 */
function getAllPastesForAdmin(int $limit = 50, int $offset = 0): array {
    try {
        $db = getDB();
        
        // Simplified query first to test
        $stmt = $db->prepare("
            SELECT id, title, syntax, share_code, created_at, expire_at, views, 
                   password_hash, is_encrypted, content,
                   (CASE WHEN password_hash IS NOT NULL THEN 1 ELSE 0 END) as is_protected,
                   (CASE WHEN expire_at > 0 AND expire_at <= strftime('%s', 'now') THEN 1 ELSE 0 END) as is_expired
            FROM pastes 
            ORDER BY created_at DESC 
            LIMIT ? OFFSET ?
        ");
        
        $stmt->execute([$limit, $offset]);
        $results = $stmt->fetchAll();
        
        // Add content_size manually to avoid potential SQLite issues
        foreach ($results as &$result) {
            $result['content_size'] = strlen($result['content'] ?? '');
            // Remove content from result to save memory
            unset($result['content']);
        }
        
        return $results;
    } catch (Exception $e) {
        error_log("Admin getAllPastes error: " . $e->getMessage());
        // Return empty array with debug info
        return [];
    }
}

/**
 * 📊 Get paste statistics for admin
 */
function getPasteStats(): array {
    try {
        $db = getDB();
        
        // Total pastes
        $stmt = $db->query("SELECT COUNT(*) FROM pastes");
        $total = (int)$stmt->fetchColumn();
        
        // Active pastes (not expired)
        $stmt = $db->query("
            SELECT COUNT(*) 
            FROM pastes 
            WHERE expire_at = 0 OR expire_at > strftime('%s', 'now')
        ");
        $active = (int)$stmt->fetchColumn();
        
        // Protected pastes
        $stmt = $db->query("
            SELECT COUNT(*) 
            FROM pastes 
            WHERE password_hash IS NOT NULL
        ");
        $protected = (int)$stmt->fetchColumn();
        
        // Expired pastes
        $stmt = $db->query("
            SELECT COUNT(*) 
            FROM pastes 
            WHERE expire_at > 0 AND expire_at <= strftime('%s', 'now')
        ");
        $expired = (int)$stmt->fetchColumn();
        
        // Today's pastes
        $stmt = $db->query("
            SELECT COUNT(*) 
            FROM pastes 
            WHERE created_at >= strftime('%s', 'now', 'start of day')
        ");
        $today = (int)$stmt->fetchColumn();
        
        // This week's pastes
        $stmt = $db->query("
            SELECT COUNT(*) 
            FROM pastes 
            WHERE created_at >= strftime('%s', 'now', 'start of day', '-6 days')
        ");
        $week = (int)$stmt->fetchColumn();
        
        // Total views
        $stmt = $db->query("SELECT COALESCE(SUM(views), 0) FROM pastes");
        $totalViews = (int)$stmt->fetchColumn();
        
        return [
            'total' => $total,
            'total_pastes' => $total,
            'active_pastes' => $active,
            'expired' => $expired,
            'expired_pastes' => $expired,
            'protected' => $protected,
            'protected_pastes' => $protected,
            'today' => $today,
            'week' => $week,
            'total_views' => $totalViews
        ];
    } catch (Exception $e) {
        error_log("Admin getPasteStats error: " . $e->getMessage());
        return [
            'total' => 0,
            'total_pastes' => 0,
            'active_pastes' => 0,
            'expired' => 0,
            'expired_pastes' => 0,
            'protected' => 0,
            'protected_pastes' => 0,
            'today' => 0,
            'week' => 0,
            'total_views' => 0
        ];
    }
}

/**
 * 🗑️ Delete paste by admin
 */
function deletePasteAdmin(string $id): bool {
    try {
        $db = getDB();
        $stmt = $db->prepare("DELETE FROM pastes WHERE id = :id");
        $stmt->bindValue(':id', $id, PDO::PARAM_STR);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    } catch (Exception $e) {
        error_log("Admin deletePaste error: " . $e->getMessage());
        return false;
    }
}

/**
 * 🧹 Cleanup expired pastes (admin version)
 */
function cleanupExpiredPastesAdmin(): int {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            DELETE FROM pastes 
            WHERE expire_at > 0 AND expire_at <= strftime('%s', 'now')
        ");
        $stmt->execute();
        
        return $stmt->rowCount();
    } catch (Exception $e) {
        error_log("Admin cleanupExpiredPastes error: " . $e->getMessage());
        return 0;
    }
}

/**
 * 🔍 Search pastes for admin
 */
function searchPastesAdmin(string $query, int $limit = 50): array {
    try {
        $db = getDB();
        $searchTerm = '%' . $query . '%';
        
        $stmt = $db->prepare("
            SELECT id, title, syntax, share_code, created_at, expire_at, views,
                   password_hash, is_encrypted,
                   (CASE WHEN password_hash IS NOT NULL THEN 1 ELSE 0 END) as is_protected,
                   (CASE WHEN expire_at > 0 AND expire_at <= strftime('%s', 'now') THEN 1 ELSE 0 END) as is_expired,
                   LENGTH(content) as content_size
            FROM pastes 
            WHERE title LIKE :query OR id LIKE :query OR share_code LIKE :query
            ORDER BY created_at DESC 
            LIMIT :limit
        ");
        $stmt->bindValue(':query', $searchTerm, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Admin searchPastes error: " . $e->getMessage());
        return [];
    }
}

// ============================================================================
// 🛡️ ESSENTIAL CAPTCHA SYSTEM (MINIMAL)
// ============================================================================

/**
 * 🛡️ Einfache Sicherheitsabfrage - Essential Only
 */
function generateUltraCaptcha(): array {
    if (!isset($_SESSION)) session_start();
    
    // Einfache Challenge generieren
    $a = mt_rand(1, 10);
    $b = mt_rand(1, 10);
    $question = "$a + $b = ?";
    $answer = $a + $b;
    
    $_SESSION['simple_captcha'] = [
        'answer' => (string)$answer,
        'question' => $question,
        'attempts' => 0
    ];
    
    return [
        'question' => $question,
        'difficulty' => 'easy'
    ];
}

/**
 * 🔍 Einfache CAPTCHA Verification
 */
function verifyUltraCaptcha(string $userAnswer): bool {
    if (!isset($_SESSION['simple_captcha'])) {
        return false;
    }
    
    $captcha = $_SESSION['simple_captcha'];
    
    if ($captcha['attempts'] >= 3) {
        unset($_SESSION['simple_captcha']);
        return false;
    }
    
    $_SESSION['simple_captcha']['attempts']++;
    
    $expectedAnswer = trim($captcha['answer']);
    $userAnswerClean = trim($userAnswer);
    
    $isValid = $expectedAnswer === $userAnswerClean;
    
    if ($isValid || $_SESSION['simple_captcha']['attempts'] >= 3) {
        unset($_SESSION['simple_captcha']);
    }
    
    return $isValid;
}

/**
 * 🍯 Honeypot Validation - Essential
 */
function validateHoneypot(array $postData): bool {
    // Einfache Honeypot-Validierung ohne komplexe Regex
    foreach ($postData as $key => $value) {
        if (strpos($key, 'website_') === 0 || 
            strpos($key, 'homepage_') === 0 || 
            strpos($key, 'url_') === 0 || 
            strpos($key, 'email_confirm_') === 0) {
            if (!empty($value)) {
                return false; // Bot detected
            }
        }
    }
    return true;
}

/**
 * 🎯 Einfaches Captcha Field
 */
function getUltraCaptchaField(): string {
    $captchaData = generateUltraCaptcha();
    
    return '<div class="ultra-captcha-container">' .
           '<div class="captcha-field">' .
           '<label class="captcha-label">🛡️ Sicherheitsabfrage:</label>' .
           '<div class="captcha-question">' . sanitizeOutput($captchaData['question']) . '</div>' .
           '<input type="text" name="captcha_answer" class="captcha-input" required autocomplete="off" placeholder="Antwort eingeben..." />' .
           '</div>' .
           '<div class="captcha-info">' .
           '<span style="color: var(--success-bg);">✅ Einfache Sicherheitsabfrage</span>' .
           '</div>' .
           '</div>';
}
