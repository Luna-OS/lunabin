<?php
/**
 * 🔍 Lunabin Debug Script - Enhanced
 * Prüft spezifische Paste und zeigt Datenbankstruktur
 */

require_once 'inc/config.php';
require_once 'inc/db.php';
require_once 'inc/functions.php';

$searchId = $_GET['id'] ?? '';

echo "<h2>🔍 Lunabin Debug Information</h2>";
echo "<hr>";

try {
    $db = getDB();
    
    // 1. Datenbankstruktur prüfen
    echo "<h3>📋 Datenbankstruktur:</h3>";
    $stmt = $db->query("PRAGMA table_info(pastes)");
    $columns = $stmt->fetchAll();
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Spaltenname</th><th>Typ</th><th>Not Null</th><th>Default</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td><strong>" . $column['name'] . "</strong></td>";
        echo "<td>" . $column['type'] . "</td>";
        echo "<td>" . ($column['notnull'] ? 'Ja' : 'Nein') . "</td>";
        echo "<td>" . ($column['dflt_value'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 2. Alle Pastes anzeigen
    echo "<h3>📊 Alle Pastes in der Datenbank:</h3>";
    $stmt = $db->query("SELECT id, title, share_code, created_at, expire_at, is_encrypted, iv FROM pastes ORDER BY created_at DESC LIMIT 10");
    $allPastes = $stmt->fetchAll();
    
    if (empty($allPastes)) {
        echo "<p><strong>❌ Keine Pastes in der Datenbank gefunden!</strong></p>";
    } else {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Titel</th><th>Share Code</th><th>Erstellt</th><th>Läuft ab</th><th>Verschlüsselt</th><th>IV</th><th>Test Links</th></tr>";
        foreach ($allPastes as $paste) {
            $isExpired = $paste['expire_at'] < time();
            $rowStyle = $isExpired ? 'style="background-color: #ffcccc;"' : '';
            
            echo "<tr $rowStyle>";
            echo "<td><strong>" . htmlspecialchars($paste['id']) . "</strong></td>";
            echo "<td>" . htmlspecialchars($paste['title'] ?: 'Ohne Titel') . "</td>";
            echo "<td>" . htmlspecialchars($paste['share_code']) . "</td>";
            echo "<td>" . date('d.m.Y H:i', $paste['created_at']) . "</td>";
            echo "<td>" . date('d.m.Y H:i', $paste['expire_at']) . ($isExpired ? ' <strong>(ABGELAUFEN)</strong>' : '') . "</td>";
            echo "<td>" . ($paste['is_encrypted'] ? 'Ja' : 'Nein') . "</td>";
            echo "<td>" . ($paste['iv'] ? '✓' : '❌') . "</td>";
            echo "<td>";
            echo "<a href='view.php?id=" . htmlspecialchars($paste['id']) . "' target='_blank'>ID-Link</a> | ";
            echo "<a href='view.php?id=" . htmlspecialchars($paste['share_code']) . "' target='_blank'>Share-Code-Link</a>";
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // 3. Spezifische Paste suchen (wenn ID angegeben)
    if (!empty($searchId)) {
        echo "<h3>🔍 Suche nach Paste: <code>$searchId</code></h3>";
        $stmt = $db->prepare("SELECT * FROM pastes WHERE id = ? OR share_code = ?");
        $stmt->execute([$searchId, $searchId]);
        $specificPaste = $stmt->fetch();
        
        if ($specificPaste) {
            echo "<p><strong>✅ Paste gefunden!</strong></p>";
            echo "<table border='1' cellpadding='5'>";
            foreach ($specificPaste as $key => $value) {
                echo "<tr>";
                echo "<td><strong>$key</strong></td>";
                echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            // Prüfe Ablaufzeit
            if ($specificPaste['expire_at'] < time()) {
                echo "<p><strong>⚠️ Diese Paste ist bereits abgelaufen!</strong></p>";
            } else {
                echo "<p><strong>✅ Paste ist noch gültig.</strong></p>";
                
                // Teste Share-Info Generierung
                try {
                    $shareInfo = getShareInfo($specificPaste['id']);
                    echo "<h4>📤 Share-Info Test:</h4>";
                    echo "<table border='1' cellpadding='5'>";
                    foreach ($shareInfo as $key => $value) {
                        echo "<tr>";
                        echo "<td><strong>$key</strong></td>";
                        echo "<td>";
                        if (strpos($key, 'link') !== false) {
                            echo "<a href='" . htmlspecialchars($value) . "' target='_blank'>" . htmlspecialchars($value) . "</a>";
                        } else {
                            echo htmlspecialchars($value);
                        }
                        echo "</td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                } catch (Exception $e) {
                    echo "<p><strong>❌ Fehler bei Share-Info: " . htmlspecialchars($e->getMessage()) . "</strong></p>";
                }
            }
        } else {
            echo "<p><strong>❌ Paste '$searchId' wurde nicht gefunden!</strong></p>";
            echo "<p>Mögliche Ursachen:</p>";
            echo "<ul>";
            echo "<li>Die Paste wurde nicht korrekt erstellt</li>";
            echo "<li>Die Paste ist bereits abgelaufen</li>";
            echo "<li>Fehler beim Speichern in der Datenbank</li>";
            echo "<li>Die Datenbank wurde zurückgesetzt</li>";
            echo "</ul>";
        }
    }
    
    // 4. Share-Code Generator Test
    echo "<h3>🎲 Share-Code Generator Test:</h3>";
    echo "<p>Teste die Share-Code Generierung:</p>";
    for ($i = 0; $i < 5; $i++) {
        try {
            $testCode = generateShareCode();
            echo "<span style='font-family: monospace; background: #f0f0f0; padding: 2px 5px; margin: 2px; border: 1px solid #ccc;'>$testCode</span> ";
        } catch (Exception $e) {
            echo "<span style='color: red;'>ERROR: " . htmlspecialchars($e->getMessage()) . "</span> ";
        }
    }
    echo "<br><br>";
    
    // 5. Aktuelle Zeit anzeigen
    echo "<h3>🕒 Zeitinformation:</h3>";
    echo "<p><strong>Aktuelle Zeit:</strong> " . date('d.m.Y H:i:s') . " (Unix: " . time() . ")</p>";
    
} catch (Exception $e) {
    echo "<p><strong>❌ Fehler:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr>";
echo "<p><a href='index.php'>← Zurück zur Hauptseite</a></p>";
echo "<p><a href='fix_database.php'>🔧 Datenbank reparieren</a></p>";
echo "<p><a href='?'>🔄 Debug ohne ID</a></p>";
?> 