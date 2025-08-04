<?php
require_once 'inc/config.php';
require_once 'inc/db.php';
require_once 'inc/functions.php';

session_start();

$pageTitle = 'Elegant Code Sharing';

// Get recent public pastes
$recentPastes = getRecentPastes(10);

// Check for error messages
$errorMessage = $_SESSION['error'] ?? '';
unset($_SESSION['error']);

// Check for success messages
$successMessage = $_SESSION['success'] ?? '';
unset($_SESSION['success']);

include 'templates/header.php';
?>

<main class="main-content">
    <div class="content-wrapper">
        <div class="container">
            <div class="main-content-section">
                
                <?php if ($errorMessage): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($errorMessage); ?>
                </div>
                <?php endif; ?>
                
                <?php if ($successMessage): ?>
                <div class="success-message">
                    <?php echo htmlspecialchars($successMessage); ?>
                </div>
                <?php endif; ?>

                <!-- New Paste Form with Integrated Quick Access -->
                <section class="paste-form-section">
                    <div class="section-header">
                        <h2 class="section-title">✨ Lunabin Code Sharing</h2>
                        <p class="section-subtitle">Erstellen Sie einen neuen Paste oder öffnen Sie einen bestehenden</p>
                    </div>

                    <!-- Integrated Quick Access Bar -->
                    <div class="quick-access-bar">
                        <div class="quick-access-content">
                            <div class="quick-access-text">
                                <span class="quick-icon">🔍</span>
                                <span>Bereits einen Paste? Öffnen Sie ihn mit dem 6-stelligen Code:</span>
                            </div>
                            <form class="quick-access-form" action="view.php" method="GET">
                                <input type="text" 
                                       name="id" 
                                       placeholder="ABC123" 
                                       maxlength="6" 
                                       required 
                                       autocomplete="off"
                                       pattern="[A-Z0-9]{6}"
                                       title="6-stelliger Code aus Buchstaben und Zahlen"
                                       class="quick-code-input">
                                <button type="submit" class="quick-open-btn">Öffnen</button>
                            </form>
                        </div>
                    </div>

                    <div class="form-divider">
                        <span class="divider-text">oder neuen Paste erstellen</span>
                    </div>

                    <form action="paste.php" method="POST" class="paste-form" id="paste-form">
                        <div class="form-group">
                            <label for="title">📝 Titel (Optional)</label>
                            <input type="text" 
                                   id="title" 
                                   name="title" 
                                   placeholder="Mein Code-Snippet..." 
                                   maxlength="100"
                                   autocomplete="off">
                        </div>

                        <div class="form-group">
                            <label for="content">💻 Inhalt *</label>
                            <textarea id="content" 
                                      name="content" 
                                      placeholder="Hier Ihren Code einfügen..." 
                                      required
                                      rows="12"
                                      maxlength="<?php echo MAX_PASTE_SIZE; ?>"></textarea>
                            <small>Maximale Größe: <?php echo number_format(MAX_PASTE_SIZE / 1024, 0); ?> KB</small>
                        </div>

                        <div class="form-group">
                            <label for="syntax">🎨 Sprache</label>
                            <select id="syntax" name="syntax">
                                <option value="text">Text</option>
                                <option value="php">PHP</option>
                                <option value="html">HTML</option>
                                <option value="css">CSS</option>
                                <option value="javascript">JavaScript</option>
                                <option value="python">Python</option>
                                <option value="java">Java</option>
                                <option value="c">C</option>
                                <option value="cpp">C++</option>
                                <option value="sql">SQL</option>
                                <option value="json">JSON</option>
                                <option value="xml">XML</option>
                                <option value="bash">Bash</option>
                                <option value="powershell">PowerShell</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="expire">⏰ Ablaufzeit</label>
                            <select id="expire" name="expire">
                                <option value="1hour">1 Stunde</option>
                                <option value="1day" selected>1 Tag</option>
                                <option value="1week">1 Woche</option>
                                <option value="1month">1 Monat</option>
                                <option value="never">Niemals</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="password">🔒 Passwort (Optional)</label>
                            <input type="password" 
                                   id="password" 
                                   name="password" 
                                   placeholder="Leer lassen für öffentlichen Paste"
                                   autocomplete="new-password">
                            <small>Mit Passwort wird der Inhalt automatisch verschlüsselt</small>
                        </div>

                        <!-- Ultra Secure Captcha (No JavaScript) -->
                        <div class="form-group captcha-group">
                            <?php echo getUltraCaptchaField(); ?>
                        </div>

                        <button type="submit" class="btn-primary">
                            🚀 Paste erstellen
                        </button>
</form>
                </section>
            </div>
        </div>
    </div>
    
    <!-- Sidebar -->
    <aside class="sidebar">
        <!-- Recent Public Pastes -->
        <section class="recent-pastes">
            <h3>📋 Neueste öffentliche Pastes</h3>
            <?php if (!empty($recentPastes)): ?>
                <ul>
                    <?php foreach ($recentPastes as $paste): ?>
                        <li>
                            <a href="view.php?id=<?php echo urlencode($paste['id']); ?>">
                                <div class="paste-title">
                                    <?php echo sanitizeOutput($paste['title'] ?: 'Unbenannt'); ?>
                                </div>
                                <div class="meta">
                                    <span class="syntax">💾 <?php echo strtoupper($paste['syntax']); ?></span>
                                    <span class="time">🕒 <?php echo formatTimeAgo($paste['created_at']); ?></span>
                                    <span class="views">👁️ <?php echo $paste['views']; ?></span>
                                    <?php if (!empty($paste['share_code'])): ?>
                                        <span class="mini-share-code">🔗 <?php echo strtoupper($paste['share_code']); ?></span>
                                    <?php endif; ?>
                                </div>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>Noch keine öffentlichen Pastes vorhanden.</p>
            <?php endif; ?>
        </section>

        <!-- Info Box -->
        <section class="info-box">
            <h3>🌙 Lunabin Features</h3>
            <ul>
                <li>🛡️ Intelligente Bot-Erkennung</li>
                <li>🧮 Server-seitige Sicherheitsabfrage</li>
                <li>🔐 Automatische Verschlüsselung</li>
                <li>🚀 6-stellige Share-Codes</li>
                <li>📱 QR-Code Generation</li>
                <li>🌙 Dark/Light Mode</li>
                <li>⚡ Syntax-Highlighting</li>
                <li>🍯 Honeypot Bot-Schutz</li>
                <li>📊 Session-basierte Limits</li>
                <li>🚫 JavaScript-frei</li>
            </ul>
            
            <div class="feature-highlight">
                <h4>🛡️ Intelligente Sicherheitsabfrage</h4>
                <p>Unser benutzerfreundliches Captcha-System funktioniert komplett server-seitig ohne JavaScript und bietet optimalen Bot-Schutz!</p>
            </div>
        </section>
    </aside>
</main>

<?php include 'templates/footer.php'; ?>
