<?php
require_once 'inc/db.php';
require_once 'inc/functions.php';

$id = $_GET['id'] ?? '';
if (empty($id)) {
    die('Fehler: Keine Paste-ID angegeben!');
}

try {
    $shareInfo = getShareInfo($id);
} catch (Exception $e) {
    die('Fehler: ' . sanitizeOutput($e->getMessage()));
}

// QR-Code Parameter
$size = $_GET['size'] ?? '200';
$size = min(500, max(100, (int)$size)); // Begrenzt auf 100-500px

$qrUrl = $shareInfo['short_link'];
$qrApiUrl = "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&data=" . urlencode($qrUrl);

// Content-Type für Bilder setzen
header('Content-Type: image/png');
header('Content-Disposition: attachment; filename="paste-' . $shareInfo['share_code'] . '-qr.png"');
header('Cache-Control: public, max-age=3600'); // 1 Stunde Cache

// QR-Code von der API abrufen und ausgeben
$qrImage = @file_get_contents($qrApiUrl);

if ($qrImage === false) {
    // Fallback: Einfaches QR-Code Bild generieren falls API nicht verfügbar
    header('Content-Type: text/html');
    echo '<!DOCTYPE html>
    <html>
    <head>
        <title>QR-Code nicht verfügbar</title>
        <style>
            body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
            .error { background: #f8d7da; color: #721c24; padding: 20px; border-radius: 4px; display: inline-block; }
            .qr-text { background: #f8f9fa; padding: 20px; margin: 20px; border: 2px dashed #dee2e6; }
        </style>
    </head>
    <body>
        <div class="error">
            <h2>QR-Code Service nicht verfügbar</h2>
            <p>Der QR-Code konnte nicht generiert werden.</p>
        </div>
        
        <div class="qr-text">
            <h3>Teilen Sie stattdessen diesen Link:</h3>
            <p><strong>' . htmlspecialchars($shareInfo['short_link']) . '</strong></p>
            <p>Oder verwenden Sie den Code: <strong>' . htmlspecialchars($shareInfo['share_code']) . '</strong></p>
        </div>
        
        <p>
            <a href="share.php?id=' . htmlspecialchars($shareInfo['id']) . '">← Zurück zur Share-Seite</a> |
            <a href="view.php?id=' . htmlspecialchars($shareInfo['id']) . '">Paste anzeigen</a>
        </p>
    </body>
    </html>';
    exit;
}

echo $qrImage; 