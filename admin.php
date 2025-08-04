<?php
require_once 'inc/config.php';
require_once 'inc/db.php';
require_once 'inc/functions.php';

session_start();

// Authentication check
if (!isAdminAuthenticated()) {
    header('Location: admin-login.php');
    exit;
}

$message = '';
$messageType = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $csrfToken = $_POST['csrf_token'] ?? '';
    
    // CSRF protection
    if (!validateCSRFToken($csrfToken)) {
        $message = 'Sicherheitsfehler: Ungültiger CSRF-Token.';
        $messageType = 'error';
    } else {
        switch ($action) {
            case 'delete_paste':
                $pasteId = trim($_POST['paste_id'] ?? '');
                if ($pasteId && deletePasteAdmin($pasteId)) {
                    $message = "Paste '$pasteId' erfolgreich gelöscht.";
                    $messageType = 'success';
                } else {
                    $message = 'Fehler beim Löschen des Pastes.';
                    $messageType = 'error';
                }
                break;
                
            case 'cleanup_expired':
                $deletedCount = cleanupExpiredPastesAdmin();
                $message = "$deletedCount abgelaufene Pastes gelöscht.";
                $messageType = 'success';
                break;
                
            case 'change_password':
                $currentPassword = trim($_POST['current_password'] ?? '');
                $newPassword = trim($_POST['new_password'] ?? '');
                $confirmPassword = trim($_POST['confirm_password'] ?? '');
                
                if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                    $message = 'Alle Passwort-Felder sind erforderlich.';
                    $messageType = 'error';
                } elseif ($newPassword !== $confirmPassword) {
                    $message = 'Neues Passwort und Bestätigung stimmen nicht überein.';
                    $messageType = 'error';
                } else {
                    $result = changeAdminPassword($currentPassword, $newPassword);
                    $message = $result['message'];
                    $messageType = $result['success'] ? 'success' : 'error';
                }
                break;
                
            case 'logout':
                adminLogout();
                header('Location: admin-login.php');
                exit;
        }
    }
}

// Handle search and pagination
$searchQuery = trim($_GET['search'] ?? '');
$currentPage = max(1, intval($_GET['page'] ?? 1));
$pastesPerPage = 20;
$offset = ($currentPage - 1) * $pastesPerPage;

if ($searchQuery) {
    $pastes = searchPastesAdmin($searchQuery, 50);
} else {
    $pastes = getAllPastesForAdmin($pastesPerPage, $offset);
}

// Get statistics
$stats = getPasteStats();

$pageTitle = 'Admin Dashboard';
?>
<!DOCTYPE html>
<html lang="de" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Lunabin Admin</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>👨‍💻</text></svg>">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .admin-container {
            min-height: 100vh;
            background: var(--bg-primary);
            padding: var(--spacing-lg);
        }

        .admin-header {
            background: var(--bg-secondary);
            border: 2px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: var(--spacing-lg);
            margin-bottom: var(--spacing-lg);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: var(--spacing-md);
        }

        .admin-title {
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
        }

        .admin-title h1 {
            font-size: 28px;
            font-weight: var(--font-weight-bold);
            color: var(--text-primary);
            margin: 0;
        }

        .admin-actions {
            display: flex;
            gap: var(--spacing-md);
        }

        .admin-btn {
            background: var(--btn-secondary-bg);
            color: var(--btn-secondary-text);
            border: none;
            padding: var(--spacing-sm) var(--spacing-md);
            border-radius: var(--radius-md);
            font-size: var(--font-size-small);
            font-weight: var(--font-weight-medium);
            cursor: pointer;
            transition: all var(--transition-normal);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: var(--spacing-xs);
        }

        .admin-btn:hover {
            background: var(--btn-primary-bg);
            color: var(--btn-primary-text);
            transform: translateY(-2px);
        }

        .admin-btn.danger {
            background: var(--error-bg);
            color: var(--error-text);
        }

        .admin-btn.danger:hover {
            background: #cc0000;
        }

        .admin-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: var(--spacing-lg);
            margin-bottom: var(--spacing-lg);
        }

        .stat-card {
            background: var(--bg-secondary);
            border: 2px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: var(--spacing-lg);
            text-align: center;
            transition: all var(--transition-normal);
        }

        .stat-card:hover {
            border-color: var(--accent-primary);
            transform: translateY(-4px);
            box-shadow: var(--glow-primary);
        }

        .stat-icon {
            font-size: 32px;
            margin-bottom: var(--spacing-sm);
        }

        .stat-value {
            font-size: 24px;
            font-weight: var(--font-weight-bold);
            color: var(--accent-primary);
            margin-bottom: var(--spacing-xs);
        }

        .stat-label {
            color: var(--text-muted);
            font-size: var(--font-size-small);
        }

        .admin-search {
            background: var(--bg-secondary);
            border: 2px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: var(--spacing-lg);
            margin-bottom: var(--spacing-lg);
        }

        .search-form {
            display: flex;
            gap: var(--spacing-md);
            align-items: center;
        }

        .search-form input {
            flex: 1;
            padding: var(--spacing-md);
            border: 2px solid var(--border-color);
            border-radius: var(--radius-md);
            background: var(--input-bg);
            color: var(--text-primary);
            font-size: var(--font-size-base);
        }

        .search-form input:focus {
            border-color: var(--accent-primary);
            box-shadow: var(--glow-primary);
            outline: none;
        }

        .admin-table-container {
            background: var(--bg-secondary);
            border: 2px solid var(--border-color);
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-medium);
        }

        .admin-table {
            width: 100%;
            border-collapse: collapse;
        }

        .admin-table th,
        .admin-table td {
            padding: var(--spacing-md);
            text-align: left;
            border-bottom: 1px solid var(--border-color);
            vertical-align: middle;
        }

        .admin-table th {
            background: var(--bg-tertiary);
            font-weight: var(--font-weight-bold);
            color: var(--text-primary);
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .admin-table tr:hover {
            background: var(--bg-tertiary);
        }

        .paste-id {
            font-family: var(--font-mono);
            font-size: var(--font-size-small);
            color: var(--accent-primary);
        }

        .paste-title {
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .paste-meta {
            font-size: var(--font-size-small);
            color: var(--text-muted);
        }

        .paste-actions {
            display: flex;
            gap: var(--spacing-xs);
        }

        .paste-actions button {
            padding: var(--spacing-xs) var(--spacing-sm);
            border: none;
            border-radius: var(--radius-sm);
            font-size: var(--font-size-small);
            cursor: pointer;
            transition: all var(--transition-normal);
        }

        .btn-view {
            background: var(--btn-secondary-bg);
            color: var(--btn-secondary-text);
        }

        .btn-delete {
            background: var(--error-bg);
            color: var(--error-text);
        }

        .btn-view:hover,
        .btn-delete:hover {
            transform: scale(1.05);
        }

        .admin-message {
            margin-bottom: var(--spacing-lg);
            padding: var(--spacing-md);
            border-radius: var(--radius-md);
            font-weight: var(--font-weight-medium);
        }

        .admin-message.success {
            background: var(--success-bg);
            color: var(--success-text);
            border-left: 4px solid #66ff66;
        }

        .admin-message.error {
            background: var(--error-bg);
            color: var(--error-text);
            border-left: 4px solid #ff6666;
        }

        .no-pastes {
            text-align: center;
            padding: var(--spacing-xl);
            color: var(--text-muted);
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: var(--spacing-sm);
            margin-top: var(--spacing-lg);
        }

        .pagination a {
            padding: var(--spacing-sm) var(--spacing-md);
            background: var(--bg-secondary);
            color: var(--text-primary);
            text-decoration: none;
            border-radius: var(--radius-sm);
            border: 1px solid var(--border-color);
            transition: all var(--transition-normal);
        }

        .pagination a:hover,
        .pagination a.current {
            background: var(--accent-primary);
            color: white;
        }

        /* Mobile responsive */
        @media (max-width: 768px) {
            .admin-container {
                padding: var(--spacing-md);
            }
            
            .admin-header {
                flex-direction: column;
                align-items: stretch;
            }
            
            .admin-actions {
                justify-content: center;
            }
            
            .admin-stats {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .search-form {
                flex-direction: column;
            }
            
            .admin-table-container {
                overflow-x: auto;
            }
            
            .admin-table {
                min-width: 800px;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <div class="admin-title">
                <span style="font-size: 32px;">👨‍💻</span>
                <div>
                    <h1>Lunabin Admin</h1>
                    <div style="font-size: 14px; color: var(--text-muted);">
                        Angemeldet seit <?php echo formatTimeAgo($_SESSION['admin_login_time']); ?>
                    </div>
                </div>
            </div>
            <div class="admin-actions">
                <a href="index.php" class="admin-btn" target="_blank">
                    🌙 Frontend
                </a>
                <button type="button" class="admin-btn" onclick="showPasswordModal()">
                    🔑 Passwort ändern
                </button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <button type="submit" name="action" value="cleanup_expired" class="admin-btn" 
                            onclick="return confirm('Wirklich alle abgelaufenen Pastes löschen?')">
                        🧹 Cleanup
                    </button>
                </form>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <button type="submit" name="action" value="logout" class="admin-btn danger">
                        🚪 Logout
                    </button>
                </form>
            </div>
        </div>

        <?php if ($message): ?>
        <div class="admin-message <?php echo $messageType; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="admin-stats">
            <div class="stat-card">
                <div class="stat-icon">📊</div>
                <div class="stat-value"><?php echo number_format($stats['total']); ?></div>
                <div class="stat-label">Gesamt Pastes</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📅</div>
                <div class="stat-value"><?php echo number_format($stats['today']); ?></div>
                <div class="stat-label">Heute</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📈</div>
                <div class="stat-value"><?php echo number_format($stats['week']); ?></div>
                <div class="stat-label">Diese Woche</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🔒</div>
                <div class="stat-value"><?php echo number_format($stats['protected']); ?></div>
                <div class="stat-label">Geschützt</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">⏰</div>
                <div class="stat-value"><?php echo number_format($stats['expired']); ?></div>
                <div class="stat-label">Abgelaufen</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">👁️</div>
                <div class="stat-value"><?php echo number_format($stats['total_views']); ?></div>
                <div class="stat-label">Views</div>
            </div>
        </div>

        <!-- Search -->
        <div class="admin-search">
            <form method="GET" class="search-form">
                <input type="text" 
                       name="search" 
                       placeholder="Suche nach ID, Title oder Share-Code..." 
                       value="<?php echo htmlspecialchars($searchQuery); ?>">
                <button type="submit" class="admin-btn">🔍 Suchen</button>
                <?php if ($searchQuery): ?>
                <a href="admin.php" class="admin-btn">✖️ Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Pastes Table -->
        <div class="admin-table-container">
            <?php if (empty($pastes)): ?>
            <div class="no-pastes">
                <div style="font-size: 48px; margin-bottom: 16px;">📭</div>
                <h3>Keine Pastes gefunden</h3>
                <p><?php echo $searchQuery ? 'Keine Suchergebnisse für "' . htmlspecialchars($searchQuery) . '"' : 'Noch keine Pastes vorhanden.'; ?></p>
            </div>
            <?php else: ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID / Share-Code</th>
                        <th>Titel</th>
                        <th>Sprache</th>
                        <th>Erstellt</th>
                        <th>Läuft ab</th>
                        <th>Views</th>
                        <th>Größe</th>
                        <th>Status</th>
                        <th>Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pastes as $paste): ?>
                    <tr>
                        <td>
                            <div class="paste-id"><?php echo htmlspecialchars($paste['id']); ?></div>
                            <?php if ($paste['share_code']): ?>
                            <div class="paste-meta">📎 <?php echo htmlspecialchars($paste['share_code']); ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="paste-title">
                                <?php echo htmlspecialchars($paste['title'] ?: 'Unbenannt'); ?>
                            </div>
                        </td>
                        <td>
                            <span style="text-transform: uppercase; font-family: var(--font-mono);">
                                <?php echo htmlspecialchars($paste['syntax']); ?>
                            </span>
                        </td>
                        <td>
                            <div><?php echo date('d.m.Y', $paste['created_at']); ?></div>
                            <div class="paste-meta"><?php echo date('H:i', $paste['created_at']); ?></div>
                        </td>
                        <td>
                            <?php if ($paste['expire_at'] == 0): ?>
                            <span style="color: var(--success-bg);">∞ Nie</span>
                            <?php elseif ($paste['expire_at'] > time()): ?>
                            <div><?php echo date('d.m.Y', $paste['expire_at']); ?></div>
                            <div class="paste-meta"><?php echo formatTimeAgo($paste['expire_at']); ?></div>
                            <?php else: ?>
                            <span style="color: var(--error-bg);">❌ Abgelaufen</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo number_format($paste['views']); ?></td>
                        <td>
                            <div><?php echo number_format($paste['content_size']); ?> B</div>
                            <div class="paste-meta"><?php echo number_format($paste['content_size'] / 1024, 1); ?> KB</div>
                        </td>
                        <td>
                            <?php if ($paste['is_protected']): ?>
                            <span style="color: var(--warning-bg);">🔒 Geschützt</span>
                            <?php else: ?>
                            <span style="color: var(--success-bg);">🌐 Öffentlich</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="paste-actions">
                                <a href="view.php?id=<?php echo urlencode($paste['id']); ?>" 
                                   target="_blank" 
                                   class="btn-view"
                                   title="Paste anzeigen">👁️</a>
                                <form method="POST" style="display: inline;" 
                                      onsubmit="return confirm('Paste wirklich löschen?')">
                                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                    <input type="hidden" name="action" value="delete_paste">
                                    <input type="hidden" name="paste_id" value="<?php echo htmlspecialchars($paste['id']); ?>">
                                    <button type="submit" class="btn-delete" title="Paste löschen">🗑️</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <!-- Pagination (nur wenn nicht gesucht wird) -->
        <?php if (!$searchQuery && count($pastes) === 50): ?>
        <div class="pagination">
            <?php if ($currentPage > 1): ?>
            <a href="?page=<?php echo $currentPage - 1; ?>">← Zurück</a>
            <?php endif; ?>
            
            <a href="?page=<?php echo $currentPage; ?>" class="current"><?php echo $currentPage; ?></a>
            
            <a href="?page=<?php echo $currentPage + 1; ?>">Weiter →</a>
        </div>
        <?php endif; ?>
    </div>

    <!-- Password Change Modal -->
    <div id="passwordModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 1000; align-items: center; justify-content: center;">
        <div style="background: var(--bg-secondary); border: 2px solid var(--border-color); border-radius: var(--radius-xl); padding: var(--spacing-xl); max-width: 500px; width: 90%; box-shadow: var(--shadow-heavy);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-lg);">
                <h3 style="margin: 0; color: var(--text-primary); display: flex; align-items: center; gap: var(--spacing-sm);">
                    🔑 Passwort ändern
                </h3>
                <button type="button" onclick="hidePasswordModal()" style="background: none; border: none; color: var(--text-muted); font-size: 24px; cursor: pointer; padding: 0; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center;">×</button>
            </div>
            
            <form method="POST" id="passwordForm" style="display: flex; flex-direction: column; gap: var(--spacing-lg);">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" name="action" value="change_password">
                
                <div class="admin-form-group">
                    <label for="current_password" style="font-weight: var(--font-weight-medium); color: var(--text-primary); margin-bottom: var(--spacing-sm); display: block;">
                        🔐 Aktuelles Passwort
                    </label>
                    <input type="password" 
                           id="current_password" 
                           name="current_password" 
                           required 
                           placeholder="Ihr aktuelles Passwort"
                           style="width: 100%; padding: var(--spacing-md); border: 2px solid var(--border-color); border-radius: var(--radius-md); background: var(--input-bg); color: var(--text-primary); font-size: var(--font-size-base); transition: all var(--transition-normal); outline: none; box-sizing: border-box;">
                </div>
                
                <div class="admin-form-group">
                    <label for="new_password" style="font-weight: var(--font-weight-medium); color: var(--text-primary); margin-bottom: var(--spacing-sm); display: block;">
                        🆕 Neues Passwort
                    </label>
                    <input type="password" 
                           id="new_password" 
                           name="new_password" 
                           required 
                           minlength="6"
                           placeholder="Mindestens 6 Zeichen"
                           style="width: 100%; padding: var(--spacing-md); border: 2px solid var(--border-color); border-radius: var(--radius-md); background: var(--input-bg); color: var(--text-primary); font-size: var(--font-size-base); transition: all var(--transition-normal); outline: none; box-sizing: border-box;">
                </div>
                
                <div class="admin-form-group">
                    <label for="confirm_password" style="font-weight: var(--font-weight-medium); color: var(--text-primary); margin-bottom: var(--spacing-sm); display: block;">
                        ✅ Passwort bestätigen
                    </label>
                    <input type="password" 
                           id="confirm_password" 
                           name="confirm_password" 
                           required 
                           minlength="6"
                           placeholder="Neues Passwort wiederholen"
                           style="width: 100%; padding: var(--spacing-md); border: 2px solid var(--border-color); border-radius: var(--radius-md); background: var(--input-bg); color: var(--text-primary); font-size: var(--font-size-base); transition: all var(--transition-normal); outline: none; box-sizing: border-box;">
                </div>
                
                <div style="display: flex; gap: var(--spacing-md); justify-content: flex-end; margin-top: var(--spacing-lg);">
                    <button type="button" onclick="hidePasswordModal()" class="admin-btn" style="background: var(--btn-secondary-bg); color: var(--btn-secondary-text);">
                        ❌ Abbrechen
                    </button>
                    <button type="submit" class="admin-btn" style="background: var(--btn-primary-bg); color: var(--btn-primary-text);">
                        💾 Passwort ändern
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showPasswordModal() {
            document.getElementById('passwordModal').style.display = 'flex';
            document.getElementById('current_password').focus();
        }
        
        function hidePasswordModal() {
            document.getElementById('passwordModal').style.display = 'none';
            document.getElementById('passwordForm').reset();
        }
        
        // Close modal when clicking outside
        document.getElementById('passwordModal').addEventListener('click', function(e) {
            if (e.target === this) {
                hidePasswordModal();
            }
        });
        
        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && document.getElementById('passwordModal').style.display === 'flex') {
                hidePasswordModal();
            }
        });
        
        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (newPassword && confirmPassword && newPassword !== confirmPassword) {
                this.style.borderColor = 'var(--error-bg)';
            } else {
                this.style.borderColor = 'var(--border-color)';
            }
        });
    </script>
</body>
</html> 