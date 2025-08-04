<?php
require_once 'inc/db.php';
require_once 'inc/functions.php';

$id = $_GET['id'] ?? '';
if (empty($id)) {
    die('Fehler: Keine Paste-ID angegeben!');
}

try {
    $paste = getPasteById($id);
    
    if (!$paste) {
        die('Paste nicht gefunden oder bereits abgelaufen.');
    }
    
    $show_form = false;
    
    // Passwort-Schutz prüfen
    if ($paste['password_hash']) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $input_pw = $_POST['password'] ?? '';
            if (!password_verify($input_pw, $paste['password_hash'])) {
                $error = 'Falsches Passwort!';
                $show_form = true;
            }
        } else {
            $show_form = true;
        }
    }
    
    if ($show_form) {
        include 'templates/header.php';
        echo '<div class="password-form">';
        echo '<h2>Passwort-geschützte Paste</h2>';
        if (isset($error)) {
            echo '<p class="error">' . sanitizeOutput($error) . '</p>';
        }
        echo '<form method="post">
                <label>Passwort:</label><br>
                <input type="password" name="password" required>
                <input type="submit" value="Anzeigen">
              </form>';
        echo '</div>';
        include 'templates/footer.php';
        exit;
    }
    
    // Inhalt entschlüsseln falls nötig
    try {
        $content = $paste['is_encrypted'] ? decryptContent($paste['content'], $paste['iv']) : $paste['content'];
    } catch (Exception $e) {
        die('Fehler beim Entschlüsseln: Paste möglicherweise beschädigt.');
    }
    
    // Share-Info für Buttons vorbereiten
    $shareInfo = getShareInfo($paste['id']);
    
    include 'templates/header.php';
    
    echo '<div class="paste-view">';
    echo '<div class="paste-meta">';
    echo '<div class="meta-header">';
    echo '<h2>' . sanitizeOutput($paste['title'] ?: 'Unbenannte Paste') . '</h2>';
    
    // Share-Buttons
    echo '<div class="share-buttons">';
    echo '<button class="share-btn" onclick="copyToClipboard(\'' . addslashes($shareInfo['short_link']) . '\')" title="Link kopieren">';
    echo '📋 Kopieren';
    echo '</button>';
    echo '<a href="share.php?id=' . sanitizeOutput($paste['id']) . '" class="share-btn" title="Erweiterte Share-Optionen">';
    echo '📤 Teilen';
    echo '</a>';
    echo '<button class="share-btn" onclick="showQR()" title="QR-Code anzeigen">';
    echo '📱 QR-Code';
    echo '</button>';
    echo '</div>';
    echo '</div>';
    
    echo '<div class="meta-info">';
    echo '<div class="meta-item"><strong>Syntax:</strong> ' . sanitizeOutput($paste['syntax']) . '</div>';
    echo '<div class="meta-item"><strong>Erstellt:</strong> ' . formatTimeAgo($paste['created_at']) . ' ago</div>';
    echo '<div class="meta-item"><strong>Läuft ab:</strong> ' . date('d.m.Y H:i', $paste['expire_at']) . '</div>';
    echo '<div class="meta-item"><strong>Aufrufe:</strong> ' . (int)$paste['views'] . '</div>';
    echo '<div class="meta-item"><strong>Share-Code:</strong> <span class="share-code-display">' . sanitizeOutput($paste['share_code']) . '</span></div>';
    if ($paste['is_encrypted']) {
        echo '<div class="meta-item encryption-badge">🔒 Verschlüsselt</div>';
    }
    echo '</div>';
    
    echo '</div>';
    
    echo '<div class="paste-content">';
    
    // Syntax-Highlighting mit interner Funktion
    $highlightedCode = highlightCode($content, $paste['syntax']);
    echo '<pre><code class="hljs hljs-' . sanitizeOutput($paste['syntax']) . '">' . $highlightedCode . '</code></pre>';
    
    echo '</div>';
    
    // Raw Content Button
    echo '<div class="paste-actions">';
    echo '<a href="?id=' . sanitizeOutput($id) . '&raw=1" class="btn-secondary" target="_blank">📄 Raw anzeigen</a>';
    echo '<a href="index.php" class="btn-secondary">➕ Neue Paste</a>';
    echo '</div>';
    
    echo '</div>';
    
} catch (Exception $e) {
    die('Fehler: ' . sanitizeOutput($e->getMessage()));
}

// Raw-Modus
if (isset($_GET['raw'])) {
    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Disposition: inline; filename="paste-' . $paste['share_code'] . '.txt"');
    echo $content;
    exit;
}
?>

<!-- QR-Code Modal -->
<div id="qr-modal" class="qr-modal">
    <div class="qr-modal-content">
        <span class="qr-close" onclick="hideQR()">&times;</span>
        <h3>📱 QR-Code zum Teilen</h3>
        <div class="qr-code-container">
            <img id="qr-image" src="" alt="QR Code" class="qr-code-large">
        </div>
        <p>Scannen Sie diesen Code um die Paste auf einem mobilen Gerät zu öffnen.</p>
        <div class="qr-actions">
            <button onclick="copyToClipboard('<?= addslashes($shareInfo['short_link']) ?>')" class="btn-primary">
                📋 Link kopieren
            </button>
            <a href="<?= sanitizeOutput($shareInfo['qr_link']) ?>" target="_blank" class="btn-secondary">
                💾 QR-Code herunterladen
            </a>
        </div>
    </div>
</div>

<!-- Copy Notification -->
<div id="copy-notification" class="copy-notification">
    <span class="copy-icon">✅</span>
    <span class="copy-text">Link in Zwischenablage kopiert!</span>
</div>

<style>
.paste-view {
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin: 20px auto;
    max-width: 1200px;
}

.paste-meta {
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    padding: 25px;
    border-bottom: 1px solid #e1e8ed;
}

.meta-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 20px;
    flex-wrap: wrap;
    gap: 15px;
}

.meta-header h2 {
    margin: 0;
    color: #2c3e50;
    flex: 1;
    min-width: 300px;
}

.share-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.share-btn {
    background: #3498db;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    text-decoration: none;
    cursor: pointer;
    font-size: 0.9rem;
    transition: background-color 0.3s ease;
    display: inline-block;
}

.share-btn:hover {
    background: #2980b9;
    color: white;
}

.meta-info {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    font-size: 0.9rem;
}

.meta-item {
    color: #555;
}

.meta-item strong {
    color: #2c3e50;
}

.share-code-display {
    background: #2c3e50;
    color: white;
    padding: 2px 8px;
    border-radius: 4px;
    font-family: 'Consolas', monospace;
    font-weight: bold;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.share-code-display:hover {
    background: #34495e;
}

.encryption-badge {
    color: #27ae60;
    font-weight: bold;
}

.paste-content {
    padding: 0;
}

.paste-content pre {
    margin: 0;
    padding: 25px;
    background: #f8f9fa;
    overflow-x: auto;
    font-family: 'Consolas', 'Monaco', 'Courier New', monospace;
    font-size: 13px;
    line-height: 1.6;
}

.paste-content code {
    background: transparent;
}

.paste-actions {
    background: #f8f9fa;
    padding: 15px 25px;
    display: flex;
    gap: 15px;
    border-top: 1px solid #e1e8ed;
}

/* Syntax Highlighting Styles */
.hljs-keyword { color: #0000ff; font-weight: bold; }
.hljs-string { color: #008000; }
.hljs-comment { color: #808080; font-style: italic; }
.hljs-variable { color: #800080; }
.hljs-function { color: #000080; font-weight: bold; }
.hljs-tag { color: #800000; }
.hljs-name { color: #800000; font-weight: bold; }
.hljs-attr { color: #ff0000; }
.hljs-value { color: #0000ff; }
.hljs-meta { color: #008080; font-weight: bold; }
.hljs-number { color: #008080; }
.hljs-literal { color: #0000ff; font-weight: bold; }
.hljs-selector-tag { color: #800000; font-weight: bold; }
.hljs-attribute { color: #ff0000; }
.hljs-text { color: #333; }

/* QR Modal */
.qr-modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.7);
    animation: fadeIn 0.3s ease;
}

.qr-modal-content {
    background-color: white;
    margin: 5% auto;
    padding: 30px;
    border-radius: 8px;
    width: 90%;
    max-width: 500px;
    text-align: center;
    position: relative;
    animation: slideIn 0.3s ease;
}

.qr-close {
    position: absolute;
    top: 15px;
    right: 20px;
    color: #aaa;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.qr-close:hover {
    color: #333;
}

.qr-code-container {
    margin: 20px 0;
}

.qr-code-large {
    width: 250px;
    height: 250px;
    border: 2px solid #e1e8ed;
    border-radius: 8px;
}

.qr-actions {
    display: flex;
    gap: 15px;
    justify-content: center;
    margin-top: 20px;
}

/* Copy Notification */
.copy-notification {
    position: fixed;
    top: 20px;
    right: 20px;
    background: #2ecc71;
    color: white;
    padding: 15px 20px;
    border-radius: 6px;
    display: none;
    align-items: center;
    gap: 10px;
    font-weight: 500;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    z-index: 1001;
}

.copy-notification.show {
    display: flex;
    animation: slideInRight 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideIn {
    from { transform: translateY(-50px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

@keyframes slideInRight {
    from { transform: translateX(100%); }
    to { transform: translateX(0); }
}

@media (max-width: 768px) {
    .meta-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .meta-header h2 {
        min-width: 100%;
    }
    
    .share-buttons {
        width: 100%;
        justify-content: flex-start;
    }
    
    .meta-info {
        grid-template-columns: 1fr;
    }
    
    .paste-actions {
        flex-direction: column;
    }
    
    .qr-modal-content {
        margin: 10% auto;
        width: 95%;
        padding: 20px;
    }
    
    .qr-code-large {
        width: 200px;
        height: 200px;
    }
}
</style>

<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showCopyNotification();
    }).catch(() => {
        // Fallback für ältere Browser
        const textarea = document.createElement('textarea');
        textarea.value = text;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
        showCopyNotification();
    });
}

function showCopyNotification() {
    const notification = document.getElementById('copy-notification');
    notification.classList.add('show');
    
    setTimeout(() => {
        notification.classList.remove('show');
    }, 2000);
}

function showQR() {
    const modal = document.getElementById('qr-modal');
    const img = document.getElementById('qr-image');
    img.src = '<?= sanitizeOutput($shareInfo['qr_link']) ?>';
    modal.style.display = 'block';
    
    // ESC-Taste zum Schließen
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            hideQR();
        }
    });
}

function hideQR() {
    document.getElementById('qr-modal').style.display = 'none';
}

// Share-Code klickbar machen
document.addEventListener('DOMContentLoaded', function() {
    const shareCode = document.querySelector('.share-code-display');
    if (shareCode) {
        shareCode.addEventListener('click', function() {
            copyToClipboard(this.textContent);
        });
        shareCode.title = 'Klicken zum Kopieren';
    }
});

// Modal schließen bei Klick außerhalb
window.onclick = function(event) {
    const modal = document.getElementById('qr-modal');
    if (event.target === modal) {
        hideQR();
    }
}
</script>

<?php include 'templates/footer.php'; ?>
