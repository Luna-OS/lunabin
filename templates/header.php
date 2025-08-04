<!DOCTYPE html>
<html lang="de" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?>Lunabin - Elegant Code Sharing</title>
    <meta name="description" content="Lunabin - Sicheres und elegantes Code-Sharing mit Ultra-Captcha-Schutz, Verschlüsselung und modernem Design. Dark/Light Mode ohne JavaScript!">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🌙</text></svg>">
    <link rel="stylesheet" href="css/style.css">
</head>
<body data-theme="dark">
    <!-- CSS-Only Theme Toggle -->
    <input type="checkbox" id="theme-toggle" class="theme-toggle-checkbox" hidden>
    
    <header class="main-header">
        <div class="header-container">
            <div class="logo-section">
                <a href="index.php" class="logo-link">
                    <div class="logo-icon">🌙</div>
                    <div class="logo-text">Lunabin</div>
                </a>
                <div class="tagline">Ultra-Secure Code Sharing • No JavaScript</div>
            </div>
            
            <nav class="main-nav">
                <a href="index.php" class="nav-link">
                    <span class="nav-icon">✨</span>
                    New Paste
                </a>
            </nav>
            
            <!-- CSS-Only Theme Toggle Button -->
            <label for="theme-toggle" class="theme-toggle-btn" title="Dark/Light Mode wechseln">
                <div class="toggle-track">
                    <div class="toggle-thumb">
                        <span class="theme-icon dark-icon">🌙</span>
                        <span class="theme-icon light-icon">☀️</span>
                    </div>
                </div>
                <span class="toggle-label">
                    <span class="dark-label">Dark</span>
                    <span class="light-label">Light</span>
                </span>
            </label>
        </div>
    </header>
