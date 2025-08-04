<?php
// SQLite Datenbank erstellen und testen

$dbFile = __DIR__ . '/testdb.sqlite';

$status = '';
$error = '';
$users = [];

try {
    // Verbindung zur SQLite-Datenbank herstellen
    $pdo = new PDO('sqlite:' . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Tabelle erstellen
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        email TEXT NOT NULL UNIQUE
    )");

    // Testdaten einfügen (nur wenn noch kein Eintrag existiert)
    $count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ($count == 0) {
        $stmt = $pdo->prepare("INSERT INTO users (name, email) VALUES (?, ?)");
        $stmt->execute(['Max Mustermann', 'max@example.com']);
    }

    // Daten abfragen
    $result = $pdo->query("SELECT * FROM users");
    foreach ($result as $row) {
        $users[] = $row;
    }

    $status = "SQLite Datenbank funktioniert!";
} catch (PDOException $e) {
    $error = "Fehler: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>SQLite Test Interface</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 2em; }
        .ok { color: green; }
        .error { color: red; }
        table { border-collapse: collapse; margin-top: 1em; }
        th, td { border: 1px solid #ccc; padding: 0.5em; }
    </style>
</head>
<body>
    <h1>SQLite Test Interface</h1>
    <?php if ($status): ?>
        <p class="ok"><?= htmlspecialchars($status) ?></p>
    <?php endif; ?>
    <?php if ($error): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <h2>Benutzer in der Datenbank</h2>
    <?php if (count($users)): ?>
        <table>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
            </tr>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= htmlspecialchars($user['id']) ?></td>
                    <td><?= htmlspecialchars($user['name']) ?></td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p>Keine Benutzer gefunden.</p>
    <?php endif; ?>
</body>
</html>