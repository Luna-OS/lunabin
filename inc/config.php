<?php

// Datenbankpfad - konsistent mit db.php
define('DB_PATH', __DIR__ . '/../storage/db.sqlite');

// Sichererer Verschlüsselungsschlüssel (sollte in Produktion aus ENV kommen)
define('ENCRYPTION_KEY', 'your-super-secret-32-character-key!!');

// Standard-Ablaufzeit (24 Stunden in Sekunden)
define('DEFAULT_EXPIRY', 86400);

// Maximale Paste-Größe (1MB)
define('MAX_PASTE_SIZE', 1048576);

// Automatische Bereinigung aktivieren
define('AUTO_CLEANUP', true);
