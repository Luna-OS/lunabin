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

$pageTitle = 'Paste teilen - ' . $shareInfo['title'];
include 'templates/header.php';
?>

<div class="share-container">
    <div class="share-header">
        <h2>📤 Paste erfolgreich erstellt!</h2>
        <p class="share-title">"<?= sanitizeOutput($shareInfo['title']) ?>"</p>
    </div>

    <div class="share-success">
        <div class="success-icon">🌙</div>
        <h3>Bereit zum Teilen</h3>
        <p>Wählen Sie eine der folgenden Methoden, um Ihre Paste zu teilen:</p>
    </div>

    <div class="share-options">
        <!-- Direkter Link -->
        <div class="share-option">
            <div class="option-header">
                <h4>🔗 Direkter Link</h4>
                <span class="option-desc">Vollständiger Link zur Paste</span>
            </div>
            <div class="link-box">
                <input type="text" id="direct-link" value="<?= sanitizeOutput($shareInfo['direct_link']) ?>" readonly>
                <button class="copy-btn" onclick="copyToClipboard('direct-link')" title="Link kopieren">
                    📋
                </button>
            </div>
        </div>

        <!-- Kurzer Share-Code -->
        <div class="share-option featured">
            <div class="option-header">
                <h4>🎯 Kurzer Share-Code</h4>
                <span class="option-desc">6-stelliger Code zum einfachen Teilen</span>
            </div>
            <div class="code-box">
                <div class="share-code"><?= sanitizeOutput($shareInfo['share_code']) ?></div>
                <button class="copy-btn" onclick="copyToClipboard('share-code-text')" title="Code kopieren">
                    📋
                </button>
                <input type="hidden" id="share-code-text" value="<?= sanitizeOutput($shareInfo['share_code']) ?>">
            </div>
            <div class="code-instruction">
                Andere können diesen Code auf der Lunabin-Startseite eingeben oder den kurzen Link verwenden:
                <br><br>
                <input type="text" id="short-link" value="<?= sanitizeOutput($shareInfo['short_link']) ?>" readonly>
                <button class="copy-btn small" onclick="copyToClipboard('short-link')" title="Kurzen Link kopieren">📋</button>
            </div>
        </div>

        <!-- QR Code -->
        <div class="share-option">
            <div class="option-header">
                <h4>📱 QR-Code</h4>
                <span class="option-desc">Für mobile Geräte scannen</span>
            </div>
            <div class="qr-container">
                <img src="<?= sanitizeOutput($shareInfo['qr_link']) ?>" alt="QR Code" class="qr-code">
                <div class="qr-actions">
                    <a href="<?= sanitizeOutput($shareInfo['qr_link']) ?>" target="_blank" class="btn-secondary">
                        🖼️ QR-Code herunterladen
                    </a>
                </div>
            </div>
        </div>

        <!-- Social Share -->
        <div class="share-option">
            <div class="option-header">
                <h4>🌐 Social Sharing</h4>
                <span class="option-desc">In sozialen Medien oder Apps teilen</span>
            </div>
            <div class="social-buttons">
                <button class="social-btn whatsapp" onclick="shareVia('whatsapp')">
                    💬 WhatsApp
                </button>
                <button class="social-btn telegram" onclick="shareVia('telegram')">
                    ✈️ Telegram
                </button>
                <button class="social-btn email" onclick="shareVia('email')">
                    📧 E-Mail
                </button>
                <button class="social-btn generic" onclick="shareVia('generic')">
                    📤 Native Share
                </button>
            </div>
        </div>
    </div>

    <div class="share-actions">
        <a href="view.php?id=<?= sanitizeOutput($shareInfo['id']) ?>" class="btn-primary">
            👁️ Paste anzeigen
        </a>
        <a href="index.php" class="btn-secondary">
            ➕ Neue Paste erstellen
        </a>
    </div>

    <!-- Kopier-Feedback -->
    <div id="copy-notification" class="copy-notification">
        <span class="copy-icon">✅</span>
        <span class="copy-text">In Zwischenablage kopiert!</span>
    </div>
</div>

<style>
.share-container {
    max-width: 900px;
    margin: 0 auto;
    background: var(--primary-white);
    border-radius: var(--radius-xl);
    box-shadow: var(--shadow-xl);
    overflow: hidden;
    border: 1px solid var(--gray-200);
}

.share-header {
    background: linear-gradient(135deg, var(--primary-black), var(--secondary-black));
    color: var(--primary-white);
    padding: var(--spacing-xxl);
    text-align: center;
}

.share-header h2 {
    margin: 0 0 var(--spacing-md) 0;
    font-size: 2.5rem;
    font-weight: 200;
    letter-spacing: -0.025em;
}

.share-title {
    font-size: 1.2rem;
    opacity: 0.9;
    margin: 0;
    font-weight: 300;
}

.share-success {
    background: linear-gradient(135deg, var(--gray-100), var(--gray-200));
    padding: var(--spacing-xl);
    text-align: center;
    border-bottom: 1px solid var(--gray-300);
}

.success-icon {
    font-size: 4rem;
    margin-bottom: var(--spacing-md);
    animation: moonGlow 2s ease-in-out infinite alternate;
}

.share-success h3 {
    margin: 0 0 var(--spacing-md) 0;
    color: var(--primary-black);
    font-weight: 300;
    font-size: 1.5rem;
}

.share-options {
    padding: var(--spacing-xxl);
    display: flex;
    flex-direction: column;
    gap: var(--spacing-xl);
}

.share-option {
    border: 2px solid var(--gray-300);
    border-radius: var(--radius-lg);
    padding: var(--spacing-xl);
    transition: var(--transition-normal);
    background: var(--primary-white);
}

.share-option:hover {
    border-color: var(--primary-black);
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.share-option.featured {
    border-color: var(--primary-black);
    background: linear-gradient(135deg, var(--gray-100), var(--primary-white));
}

.option-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: var(--spacing-lg);
    flex-wrap: wrap;
    gap: var(--spacing-sm);
}

.option-header h4 {
    margin: 0;
    color: var(--primary-black);
    font-size: 1.3rem;
    font-weight: 400;
}

.option-desc {
    color: var(--gray-600);
    font-size: 0.95rem;
    font-weight: 300;
}

.link-box, .code-instruction {
    display: flex;
    gap: var(--spacing-md);
    align-items: center;
    flex-wrap: wrap;
}

.link-box input, .code-instruction input {
    flex: 1;
    min-width: 200px;
    padding: var(--spacing-md);
    border: 2px solid var(--gray-300);
    border-radius: var(--radius-md);
    font-family: var(--font-mono);
    background: var(--gray-100);
    color: var(--gray-800);
    font-size: 0.9rem;
}

.copy-btn {
    background: var(--primary-black);
    color: var(--primary-white);
    border: none;
    padding: var(--spacing-md) var(--spacing-lg);
    border-radius: var(--radius-md);
    cursor: pointer;
    font-size: 1.1rem;
    transition: var(--transition-normal);
    font-weight: 500;
}

.copy-btn:hover {
    background: var(--secondary-black);
    transform: translateY(-1px);
}

.copy-btn.small {
    padding: var(--spacing-sm) var(--spacing-md);
    font-size: 1rem;
}

.code-box {
    display: flex;
    align-items: center;
    gap: var(--spacing-lg);
    margin-bottom: var(--spacing-lg);
    flex-wrap: wrap;
    justify-content: center;
}

.share-code {
    background: var(--primary-black);
    color: var(--primary-white);
    padding: var(--spacing-lg) var(--spacing-xl);
    border-radius: var(--radius-lg);
    font-family: var(--font-mono);
    font-size: 2rem;
    letter-spacing: 0.2em;
    font-weight: bold;
    text-align: center;
    min-width: 200px;
    box-shadow: var(--shadow-md);
}

.code-instruction {
    background: var(--gray-100);
    padding: var(--spacing-lg);
    border-radius: var(--radius-md);
    color: var(--gray-700);
    line-height: 1.6;
    flex-direction: column;
    align-items: stretch;
    gap: var(--spacing-md);
}

.qr-container {
    display: flex;
    align-items: center;
    gap: var(--spacing-xl);
    flex-wrap: wrap;
    justify-content: center;
}

.qr-code {
    width: 200px;
    height: 200px;
    border: 2px solid var(--gray-300);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-sm);
}

.social-buttons {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: var(--spacing-md);
}

.social-btn {
    padding: var(--spacing-md) var(--spacing-lg);
    border: none;
    border-radius: var(--radius-md);
    cursor: pointer;
    font-weight: 500;
    transition: var(--transition-normal);
    font-size: 0.95rem;
}

.social-btn:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.social-btn.whatsapp { background: #25d366; color: var(--primary-white); }
.social-btn.telegram { background: #0088cc; color: var(--primary-white); }
.social-btn.email { background: var(--gray-800); color: var(--primary-white); }
.social-btn.generic { background: var(--gray-600); color: var(--primary-white); }

.share-actions {
    background: var(--gray-100);
    padding: var(--spacing-xl) var(--spacing-xxl);
    display: flex;
    gap: var(--spacing-lg);
    justify-content: center;
    border-top: 1px solid var(--gray-300);
    flex-wrap: wrap;
}

@media (max-width: 768px) {
    .share-container {
        margin: var(--spacing-md);
    }
    
    .option-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .qr-container {
        flex-direction: column;
        text-align: center;
    }
    
    .share-actions {
        flex-direction: column;
    }
    
    .code-box {
        flex-direction: column;
    }
    
    .share-code {
        font-size: 1.5rem;
    }
}
</style>

<script>
const shareData = {
    title: '<?= addslashes($shareInfo['title']) ?>',
    directLink: '<?= addslashes($shareInfo['direct_link']) ?>',
    shortLink: '<?= addslashes($shareInfo['short_link']) ?>',
    shareCode: '<?= addslashes($shareInfo['share_code']) ?>'
};

function copyToClipboard(elementId) {
    const element = document.getElementById(elementId);
    const text = element.value || element.textContent;
    
    navigator.clipboard.writeText(text).then(() => {
        showCopyNotification();
    }).catch(() => {
        // Fallback für ältere Browser
        element.select();
        document.execCommand('copy');
        showCopyNotification();
    });
}

function showCopyNotification() {
    const notification = document.getElementById('copy-notification');
    notification.classList.add('show');
    
    setTimeout(() => {
        notification.classList.remove('show');
    }, 2500);
}

function shareVia(platform) {
    const text = `Check out this paste on Lunabin: "${shareData.title}" - ${shareData.shortLink}`;
    const encodedText = encodeURIComponent(text);
    const encodedUrl = encodeURIComponent(shareData.shortLink);
    
    switch(platform) {
        case 'whatsapp':
            window.open(`https://wa.me/?text=${encodedText}`, '_blank');
            break;
        case 'telegram':
            window.open(`https://t.me/share/url?url=${encodedUrl}&text=${encodeURIComponent(shareData.title)}`, '_blank');
            break;
        case 'email':
            window.open(`mailto:?subject=${encodeURIComponent('Lunabin Paste: ' + shareData.title)}&body=${encodedText}`, '_blank');
            break;
        case 'generic':
            if (navigator.share) {
                navigator.share({
                    title: 'Lunabin Paste: ' + shareData.title,
                    text: `Check out this paste on Lunabin: "${shareData.title}"`,
                    url: shareData.shortLink
                });
            } else {
                copyToClipboard('short-link');
            }
            break;
    }
}

// Auto-focus auf direkten Link für schnelles Kopieren
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('direct-link').focus();
    document.getElementById('direct-link').select();
});
</script>

<?php include 'templates/footer.php'; ?> 