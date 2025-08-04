<?php
/**
 * 🔧 Lunabin Database Fix Script
 * Dieser Script fügt die fehlende 'iv' Spalte hinzu
 */

require_once 'inc/config.php';

echo "🔧 Repariere Lunabin-Datenbank...\n\n";

try {
    // Verbindung zur SQLite-Datenbank herstellen
    $db = new PDO('sqlite:' . DB_PATH);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Verbindung zur Datenbank hergestellt.\n";
    
    // Prüfe vorhandene Spalten
    $stmt = $db->query("PRAGMA table_info(pastes)");
    $columns = $stmt->fetchAll();
    $columnNames = array_column($columns, 'name');
    
    echo "📋 Vorhandene Spalten: " . implode(', ', $columnNames) . "\n\n";
    
    // Prüfe ob iv Spalte fehlt
    if (!in_array('iv', $columnNames)) {
        echo "🔄 Füge fehlende 'iv' Spalte hinzu...\n";
        $db->exec("ALTER TABLE pastes ADD COLUMN iv TEXT");
        echo "✅ Spalte 'iv' erfolgreich hinzugefügt!\n";
    } else {
        echo "ℹ️  Spalte 'iv' ist bereits vorhanden.\n";
    }
    
    // Verifikation
    $stmt = $db->query("PRAGMA table_info(pastes)");
    $columns = $stmt->fetchAll();
    $columnNames = array_column($columns, 'name');
    
    echo "\n📋 Aktualisierte Spalten: " . implode(', ', $columnNames) . "\n";
    
    // Prüfe ob alle wichtigen Spalten vorhanden sind
    $requiredColumns = ['id', 'content', 'iv', 'is_encrypted', 'share_code'];
    $missingColumns = array_diff($requiredColumns, $columnNames);
    
    if (empty($missingColumns)) {
        echo "\n🎉 Datenbank ist jetzt vollständig und bereit!\n";
        echo "🚀 Das Verschlüsselungssystem sollte jetzt funktionieren.\n";
    } else {
        echo "\n⚠️  Folgende Spalten fehlen noch: " . implode(', ', $missingColumns) . "\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Datenbankfehler: " . $e->getMessage() . "\n";
    echo "Mögliche Lösungen:\n";
    echo "- Stelle sicher, dass die SQLite-Datenbank existiert\n";
    echo "- Prüfe Schreibrechte im storage-Verzeichnis\n";
    echo "- Führe zuerst init_db.php aus\n";
} catch (Exception $e) {
    echo "❌ Allgemeiner Fehler: " . $e->getMessage() . "\n";
}

echo "\n✅ Script beendet.\n";
?> 