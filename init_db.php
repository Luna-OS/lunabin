<?php
/**
 * Datenbank-Initialisierung für das Pastebin-System
 * Führe diese Datei einmal aus, um die Datenbank zu erstellen
 */

require_once 'inc/config.php';

echo "🔧 Initialisiere Pastebin-Datenbank...\n\n";

// Stelle sicher, dass das storage Verzeichnis existiert
$storageDir = dirname(DB_PATH);
if (!is_dir($storageDir)) {
    mkdir($storageDir, 0755, true);
    echo "✅ Storage-Verzeichnis erstellt: $storageDir\n";
} else {
    echo "ℹ️  Storage-Verzeichnis existiert bereits: $storageDir\n";
}

try {
    // Verbindung zur SQLite-Datenbank herstellen
    $db = new PDO('sqlite:' . DB_PATH);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    echo "✅ Verbindung zur Datenbank hergestellt: " . DB_PATH . "\n";
    
    // Erstelle/Update Tabelle mit allen notwendigen Spalten
    $sql = "CREATE TABLE IF NOT EXISTS pastes (
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
    )";
    
    $db->exec($sql);
    echo "✅ Tabelle 'pastes' erstellt/überprüft.\n";
    
    // Prüfe und füge fehlende Spalten hinzu (für Upgrades)
    $stmt = $db->query("PRAGMA table_info(pastes)");
    $columns = $stmt->fetchAll();
    $columnNames = array_column($columns, 'name');
    
    if (!in_array('views', $columnNames)) {
        $db->exec("ALTER TABLE pastes ADD COLUMN views INTEGER DEFAULT 0");
        echo "✅ Spalte 'views' hinzugefügt.\n";
    }
    
    if (!in_array('share_code', $columnNames)) {
        $db->exec("ALTER TABLE pastes ADD COLUMN share_code TEXT");
        echo "✅ Spalte 'share_code' hinzugefügt.\n";
    }
    
    if (!in_array('iv', $columnNames)) {
        $db->exec("ALTER TABLE pastes ADD COLUMN iv TEXT");
        echo "✅ Spalte 'iv' für Verschlüsselung hinzugefügt.\n";
    }
    
    // Indizes für bessere Performance erstellen
    $indexes = [
        "CREATE INDEX IF NOT EXISTS idx_expire_at ON pastes(expire_at)",
        "CREATE INDEX IF NOT EXISTS idx_created_at ON pastes(created_at)",
        "CREATE INDEX IF NOT EXISTS idx_share_code ON pastes(share_code)"
    ];
    
    foreach ($indexes as $index) {
        $db->exec($index);
    }
    echo "✅ Datenbankindizes erstellt/überprüft.\n";
    
    // Statistiken anzeigen
    $stmt = $db->query("SELECT COUNT(*) as total FROM pastes");
    $total = $stmt->fetch()['total'];
    
    echo "\n📊 Datenbankstatistiken:\n";
    echo "   - Gesamt Pastes: $total\n";
    
    // Konfiguration anzeigen
    echo "\n⚙️  Konfiguration:\n";
    echo "   - Datenbank: " . DB_PATH . "\n";
    echo "   - Max. Paste-Größe: " . number_format(MAX_PASTE_SIZE/1024) . " KB\n";
    echo "   - Standard-Ablaufzeit: " . (DEFAULT_EXPIRY/3600) . " Stunden\n";
    echo "   - Auto-Bereinigung: " . (AUTO_CLEANUP ? 'Aktiviert' : 'Deaktiviert') . "\n";
    
    echo "\n🎉 Datenbank erfolgreich initialisiert!\n";
    echo "🚀 Share-System ist einsatzbereit!\n";
    echo "Du kannst jetzt das Pastebin-System verwenden.\n";
    
} catch (PDOException $e) {
    echo "❌ Fehler beim Erstellen der Datenbank: " . $e->getMessage() . "\n";
    echo "Stelle sicher, dass PHP SQLite-Unterstützung hat und Schreibrechte im storage-Verzeichnis vorhanden sind.\n";
    exit(1);
} catch (Exception $e) {
    echo "❌ Allgemeiner Fehler: " . $e->getMessage() . "\n";
    exit(1);
} 