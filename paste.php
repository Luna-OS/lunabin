<?php
require_once 'inc/config.php';
require_once 'inc/db.php';
require_once 'inc/functions.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // 🍯 1. HONEYPOT VALIDATION (First line of defense)
        if (!validateHoneypot($_POST)) {
            // Silent failure for bot detection
            http_response_code(403);
            exit('Access denied');
        }
        
        // 🛡️ 2. INTELLIGENTE SICHERHEITSABFRAGE
        $captchaAnswer = trim($_POST['captcha_answer'] ?? '');
        
        // Verify server-side captcha
        if (!verifyUltraCaptcha($captchaAnswer)) {
            $_SESSION['error'] = 'Die Antwort ist nicht korrekt. Bitte versuchen Sie es nochmal.';
            header('Location: index.php');
            exit;
        }
        
        // 📝 3. INPUT VALIDATION & SANITIZATION
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $syntax = trim($_POST['syntax'] ?? 'text');
        $expire = trim($_POST['expire'] ?? '1day');
        $password = trim($_POST['password'] ?? '');
        
        // Validate required fields
        if (empty($content)) {
            $_SESSION['error'] = 'Inhalt ist erforderlich.';
            header('Location: index.php');
            exit;
        }
        
        // Check content size limit
        if (strlen($content) > MAX_PASTE_SIZE) {
            $_SESSION['error'] = 'Inhalt ist zu groß. Maximum: ' . number_format(MAX_PASTE_SIZE / 1024, 0) . ' KB';
            header('Location: index.php');
            exit;
        }
        
        // Validate syntax
        $allowedSyntax = [
            'text', 'php', 'html', 'css', 'javascript', 'python', 
            'java', 'c', 'cpp', 'sql', 'json', 'xml', 'bash', 'powershell'
        ];
        if (!in_array($syntax, $allowedSyntax)) {
            $syntax = 'text';
        }
        
        // Validate expire time
        $allowedExpires = ['1hour', '1day', '1week', '1month', 'never'];
        if (!in_array($expire, $allowedExpires)) {
            $expire = '1day';
}

        // 🔐 4. PREPARE PASTE DATA
$id = generateId();
        $shareCode = generateShareCode();
        $createdAt = time();
        
        // Calculate expiration
        $expireAt = match($expire) {
            '1hour' => $createdAt + 3600,
            '1day' => $createdAt + 86400,
            '1week' => $createdAt + 604800,
            '1month' => $createdAt + 2592000,
            'never' => $createdAt + (50 * 365 * 24 * 3600), // 50 Jahre
            default => $createdAt + 86400
        };
        
        // 🔒 5. ENCRYPTION & PASSWORD HANDLING
        $passwordHash = null;
        $isEncrypted = 0;
        $encryptedContent = $content;
        $encryptedIv = null;
        
        if (!empty($password)) {
            // Password protection
            $passwordHash = password_hash($password, PASSWORD_ARGON2ID, [
                'memory_cost' => 65536,
                'time_cost' => 4,
                'threads' => 3
            ]);
            
            // Encrypt content
            $encryption = encryptContent($content);
            $encryptedContent = $encryption['data'];
            $encryptedIv = $encryption['iv'];
            $isEncrypted = 1;
        }
        
        // 💾 6. SAVE TO DATABASE
        $pasteData = [
            'id' => $id,
            'title' => $title,
            'content' => $encryptedContent,
            'syntax' => $syntax,
            'created_at' => $createdAt,
            'expire_at' => $expireAt,
            'password_hash' => $passwordHash,
            'is_encrypted' => $isEncrypted,
            'iv' => $encryptedIv,
            'views' => 0,
            'share_code' => $shareCode
        ];

$db = getDB();
        $success = createPaste($pasteData);

        if ($success) {
            // Redirect to share page
            header('Location: share.php?id=' . $id);
exit;
        } else {
            throw new Exception('Fehler beim Speichern des Pastes');
        }
        
    } catch (Exception $e) {
        error_log("Paste creation error: " . $e->getMessage());
        $_SESSION['error'] = 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.';
        header('Location: index.php');
        exit;
    }
} else {
    // Redirect to index if not POST
    header('Location: index.php');
    exit;
}
