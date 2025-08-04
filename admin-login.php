<?php
require_once 'inc/config.php';
require_once 'inc/db.php';
require_once 'inc/functions.php';

session_start();

// Redirect if already authenticated
if (isAdminAuthenticated()) {
    header('Location: admin.php');
    exit;
}

$error = '';
$success = '';
$isFirstTime = !isAdminPasswordSet();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = trim($_POST['password'] ?? '');
    $confirmPassword = trim($_POST['confirm_password'] ?? '');
    
    if ($isFirstTime) {
        // First-time setup
        if (empty($password)) {
            $error = 'Passwort ist erforderlich.';
        } elseif (strlen($password) < 8) {
            $error = 'Passwort muss mindestens 8 Zeichen lang sein.';
        } elseif ($password !== $confirmPassword) {
            $error = 'Passwörter stimmen nicht überein.';
        } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', $password)) {
            $error = 'Passwort muss mindestens einen Kleinbuchstaben, einen Großbuchstaben und eine Zahl enthalten.';
        } else {
            if (setupAdminPassword($password)) {
                $success = 'Admin-Passwort erfolgreich erstellt! Sie können sich jetzt anmelden.';
                $isFirstTime = false;
            } else {
                $error = 'Fehler beim Erstellen des Admin-Passworts.';
            }
        }
    } else {
        // Regular login
        if (empty($password)) {
            $error = 'Passwort ist erforderlich.';
        } else {
            if (verifyAdminLogin($password)) {
                header('Location: admin.php');
                exit;
            } else {
                $error = 'Falsches Passwort oder zu viele Versuche.';
            }
        }
    }
}

$pageTitle = 'Admin Login';
?>
<!DOCTYPE html>
<html lang="de" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Lunabin Admin</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🔒</text></svg>">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .admin-login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--bg-primary), var(--bg-secondary));
            padding: var(--spacing-lg);
        }

        .admin-login-box {
            background: var(--bg-secondary);
            border: 2px solid var(--border-color);
            border-radius: var(--radius-xl);
            padding: var(--spacing-xl);
            box-shadow: var(--shadow-heavy);
            width: 100%;
            max-width: 400px;
            position: relative;
            overflow: hidden;
        }

        .admin-login-box::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(138, 43, 226, 0.1), transparent);
            animation: adminShimmer 3s infinite;
        }

        @keyframes adminShimmer {
            0% { left: -100%; }
            100% { left: 100%; }
        }

        .admin-header {
            text-align: center;
            margin-bottom: var(--spacing-xl);
        }

        .admin-icon {
            font-size: 48px;
            margin-bottom: var(--spacing-md);
            filter: drop-shadow(0 0 20px rgba(138, 43, 226, 0.5));
        }

        .admin-title {
            font-size: 24px;
            font-weight: var(--font-weight-bold);
            color: var(--text-primary);
            margin-bottom: var(--spacing-sm);
        }

        .admin-subtitle {
            color: var(--text-muted);
            font-size: var(--font-size-small);
        }

        .first-time-notice {
            background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary));
            color: white;
            padding: var(--spacing-md);
            border-radius: var(--radius-md);
            margin-bottom: var(--spacing-lg);
            text-align: center;
            font-weight: var(--font-weight-medium);
        }

        .admin-form {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-lg);
        }

        .admin-form-group {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-sm);
        }

        .admin-form-group label {
            font-weight: var(--font-weight-medium);
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
        }

        .admin-form-group input {
            padding: var(--spacing-md);
            border: 2px solid var(--border-color);
            border-radius: var(--radius-md);
            background: var(--input-bg);
            color: var(--text-primary);
            font-size: var(--font-size-base);
            transition: all var(--transition-normal);
            outline: none;
        }

        .admin-form-group input:focus {
            border-color: var(--accent-primary);
            box-shadow: var(--glow-primary);
        }

        .password-requirements {
            background: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            padding: var(--spacing-sm);
            font-size: var(--font-size-small);
            color: var(--text-muted);
        }

        .password-requirements ul {
            margin: 0;
            padding-left: var(--spacing-lg);
        }

        .admin-submit {
            background: var(--btn-primary-bg);
            color: var(--btn-primary-text);
            border: none;
            padding: var(--spacing-md) var(--spacing-lg);
            border-radius: var(--radius-md);
            font-size: var(--font-size-base);
            font-weight: var(--font-weight-bold);
            cursor: pointer;
            transition: all var(--transition-normal);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .admin-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(138, 43, 226, 0.4);
        }

        .admin-submit:active {
            transform: translateY(0);
        }

        .admin-error {
            background: var(--error-bg);
            color: var(--error-text);
            padding: var(--spacing-md);
            border-radius: var(--radius-md);
            border-left: 4px solid #ff6666;
            font-weight: var(--font-weight-medium);
        }

        .admin-success {
            background: var(--success-bg);
            color: var(--success-text);
            padding: var(--spacing-md);
            border-radius: var(--radius-md);
            border-left: 4px solid #66ff66;
            font-weight: var(--font-weight-medium);
        }

        .admin-footer {
            text-align: center;
            margin-top: var(--spacing-lg);
            padding-top: var(--spacing-lg);
            border-top: 1px solid var(--border-color);
        }

        .admin-footer a {
            color: var(--accent-primary);
            text-decoration: none;
            font-size: var(--font-size-small);
        }

        .admin-footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="admin-login-container">
        <div class="admin-login-box">
            <div class="admin-header">
                <div class="admin-icon">🛡️</div>
                <div class="admin-title">
                    <?php echo $isFirstTime ? 'Admin Setup' : 'Admin Login'; ?>
                </div>
                <div class="admin-subtitle">
                    <?php echo $isFirstTime ? 'Erstmalige Einrichtung' : 'Lunabin Administration'; ?>
                </div>
            </div>

            <?php if ($isFirstTime): ?>
            <div class="first-time-notice">
                🎉 Willkommen! Erstellen Sie Ihr Admin-Passwort.
            </div>
            <?php endif; ?>

            <?php if ($error): ?>
            <div class="admin-error">
                ❌ <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="admin-success">
                ✅ <?php echo htmlspecialchars($success); ?>
            </div>
            <?php endif; ?>

            <form method="POST" class="admin-form">
                <div class="admin-form-group">
                    <label for="password">
                        🔑 <?php echo $isFirstTime ? 'Neues Admin-Passwort' : 'Admin-Passwort'; ?>
                    </label>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           required 
                           autocomplete="<?php echo $isFirstTime ? 'new-password' : 'current-password'; ?>"
                           <?php echo $isFirstTime ? 'minlength="8"' : ''; ?>
                           placeholder="<?php echo $isFirstTime ? 'Mindestens 8 Zeichen' : 'Ihr Admin-Passwort'; ?>">
                </div>

                <?php if ($isFirstTime): ?>
                <div class="admin-form-group">
                    <label for="confirm_password">
                        🔒 Passwort bestätigen
                    </label>
                    <input type="password" 
                           id="confirm_password" 
                           name="confirm_password" 
                           required 
                           autocomplete="new-password"
                           minlength="8"
                           placeholder="Passwort wiederholen">
                </div>

                <div class="password-requirements">
                    <strong>Passwort-Anforderungen:</strong>
                    <ul>
                        <li>Mindestens 8 Zeichen</li>
                        <li>Mindestens ein Kleinbuchstabe (a-z)</li>
                        <li>Mindestens ein Großbuchstabe (A-Z)</li>
                        <li>Mindestens eine Zahl (0-9)</li>
                    </ul>
                </div>
                <?php endif; ?>

                <button type="submit" class="admin-submit">
                    <?php echo $isFirstTime ? '🚀 Admin erstellen' : '🔓 Anmelden'; ?>
                </button>
            </form>

            <div class="admin-footer">
                <a href="index.php">← Zurück zu Lunabin</a>
            </div>
        </div>
    </div>
</body>
</html> 