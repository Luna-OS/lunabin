# 🌙 Lunabin - Pastebin System

Ein modernes, sicheres Pastebin-System mit Dark/Light Mode, Syntax-Highlighting und Admin-Panel.

## 🚀 Installation & Setup

1. **Dateien kopieren**
   - Lade alle Lunabin-Dateien in dein Webserver-Verzeichnis hoch
   - Stelle sicher, dass PHP 7.4+ verfügbar ist

2. **Datenbank initialisieren**
   ```bash
   php init_db.php
   ```

3. **Berechtigungen setzen**
   - Das `storage/` Verzeichnis muss beschreibbar sein (755 oder 777)

## 🔐 Admin-Zugang

### Standard-Passwort
**⚠️ WICHTIG**: Das Standard-Admin-Passwort ist:
```
123456
```

### 🚨 SICHERHEITSWARNUNG
**Das Passwort "123456" ist extrem unsicher!**

**Sie MÜSSEN das Passwort ändern, bevor Sie Lunabin produktiv nutzen:**

1. Gehen Sie zu: `http://ihr-domain.de/Lunabin/admin-login.php`
2. Loggen Sie sich mit dem Passwort `123456` ein
3. Klicken Sie auf **"🔑 Passwort ändern"** im Admin-Panel
4. Setzen Sie ein starkes, sicheres Passwort

### Sicheres Passwort erstellen
Verwenden Sie mindestens:
- 8-12 Zeichen
- Kombination aus Groß- und Kleinbuchstaben
- Zahlen
- Sonderzeichen (!@#$%^&*)

**Beispiele für starke Passwörter:**
- `MySecure2024!`
- `L0ngP@ssw0rd#`
- `B1nSecur3$2024`

## 📋 Features

### Für Benutzer
- 🌙 **Dark/Light Mode** - CSS-only Theme-Switching
- 🎨 **Syntax Highlighting** - Unterstützt viele Programmiersprachen
- 🔒 **Passwort-Schutz** - Optional für private Pastes
- ⏰ **Ablaufzeiten** - Automatische Bereinigung
- 📱 **Responsive Design** - Funktioniert auf allen Geräten
- 🔗 **Share-Codes** - Kurze, benutzerfreundliche Links
- 📊 **QR-Codes** - Einfaches Teilen auf Mobile

### Für Admins
- 📊 **Dashboard** - Übersicht über alle Pastes
- 🔍 **Suche** - Nach ID, Titel oder Share-Code
- 🗑️ **Paste-Management** - Löschen und Verwalten
- 🧹 **Cleanup** - Automatische Bereinigung abgelaufener Pastes
- 📈 **Statistiken** - Views, Anzahl, etc.
- 🔑 **Passwort-Änderung** - Sichere Passwort-Verwaltung

## 🛡️ Sicherheitsfeatures

- **Admin-Authentifizierung** mit Argon2ID-Hashing
- **CSRF-Schutz** für alle Admin-Aktionen
- **XSS-Schutz** durch Output-Sanitization
- **Rate Limiting** für Login-Versuche
- **Sichere Verschlüsselung** für geschützte Pastes
- **Automatische Session-Verwaltung**

## 🔧 Konfiguration

### Wichtige Dateien
- `inc/config.php` - Hauptkonfiguration
- `storage/.admin` - Admin-Authentifizierung
- `storage/db.sqlite` - SQLite-Datenbank
- `css/style.css` - Theme-Styling

### Anpassbare Einstellungen
```php
// In inc/config.php
define('DEFAULT_EXPIRY', 86400);    // 24 Stunden Standard-Ablaufzeit
define('MAX_PASTE_SIZE', 1048576);  // 1MB maximale Paste-Größe
define('AUTO_CLEANUP', true);       // Automatische Bereinigung
```

## 📁 Verzeichnisstruktur

```
Lunabin/
├── admin.php              # Admin-Dashboard
├── admin-login.php        # Admin-Login
├── index.php              # Hauptseite
├── paste.php              # Paste-Erstellung
├── view.php               # Paste-Anzeige
├── share.php              # Share-Funktionen
├── qr.php                 # QR-Code Generator
├── css/style.css          # Haupt-Stylesheet
├── inc/                   # PHP-Includes
│   ├── config.php         # Konfiguration
│   ├── db.php             # Datenbank-Funktionen
│   └── functions.php      # Hilfsfunktionen
└── storage/               # Datenverzeichnis
    ├── db.sqlite          # SQLite-Datenbank
    └── .admin             # Admin-Daten
```

## 🚀 Nach der Installation

1. **Passwort sofort ändern**: Verwenden Sie das Admin-Panel
2. **SSL aktivieren**: Für Produktions-Umgebungen
3. **Backups einrichten**: Regelmäßige Sicherung der SQLite-Datenbank
4. **Updates prüfen**: Halten Sie das System aktuell

## 🔄 Wartung

### Manuelle Bereinigung
```bash
php cleanup_expired.php  # Abgelaufene Pastes löschen
```

### Datenbank-Reparatur
```bash
php fix_database.php     # Bei Problemen mit der DB-Struktur
```

### Debug-Informationen
```bash
php debug_paste.php?id=PASTE_ID  # Paste-spezifische Informationen
```

## 📞 Support

Bei Problemen:
1. Prüfen Sie die Berechtigung des `storage/` Verzeichnisses
2. Stellen Sie sicher, dass PHP SQLite-Unterstützung hat
3. Kontrollieren Sie die Apache/Nginx Error-Logs

## ⚡ Performance-Tipps

- **APCu aktivieren** für besseres Caching
- **SQLite WAL-Mode** ist bereits aktiviert
- **Regelmäßige Cleanup** verhindert übermäßiges DB-Wachstum

---

**🔐 Vergessen Sie nicht, das Standard-Passwort "123456" zu ändern!**

Lunabin ist bereit für den Einsatz, aber nur mit einem sicheren Admin-Passwort! 🚀