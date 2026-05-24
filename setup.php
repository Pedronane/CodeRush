<?php
require_once __DIR__ . '/includes/config.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $cognome = trim($_POST['cognome'] ?? '');
    $login_id = trim($_POST['login_id'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    if (empty($nome)) {
        $errors[] = 'Nome obbligatorio.';
    }
    if (empty($cognome)) {
        $errors[] = 'Cognome obbligatorio.';
    }
    if (empty($login_id)) {
        $errors[] = 'Username obbligatorio.';
    }
    if (strlen($password) < 6) {
        $errors[] = 'Password minimo 6 caratteri.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Le password non coincidono.';
    }

    if (empty($errors)) {
        try {
            $pdo = new PDO(
                'mysql:host=' . DB_HOST . ';charset=utf8mb4',
                DB_USER,
                DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );

            // PDO::exec esegue una query alla volta: lo schema va spezzato sui ';'
            $sql = file_get_contents(__DIR__ . '/db/schema.sql');
            $statements = array_filter(array_map('trim', explode(';', $sql)));
            foreach ($statements as $stmt) {
                if (!empty($stmt)) {
                    $pdo->exec($stmt);
                }
            }

            $pdo->exec('USE ' . DB_NAME);

            $stmt = $pdo->prepare('SELECT id FROM users WHERE login_id = ?');
            $stmt->execute([$login_id]);
            if ($stmt->fetch()) {
                $errors[] = 'Username già in uso.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $pdo->prepare(
                    'INSERT INTO users (login_id, nome, cognome, password, ruolo) VALUES (?, ?, ?, ?, "host")'
                )->execute([$login_id, $nome, $cognome, $hash]);
                $success = 'Setup completato. <a href="/CodeRush/login.php">Accedi ora</a>.';
            }
        } catch (PDOException $e) {
            $errors[] = 'Errore DB: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup — CodeRush</title>
    <link rel="stylesheet" href="/CodeRush/css/style.css">
</head>
<body>
<div class="login-wrapper">
    <div class="login-card">
        <h1>Setup iniziale</h1>
        <p class="login-subtitle">Crea il primo account host per CodeRush</p>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger"><?= implode('<br>', array_map('htmlspecialchars', $errors)) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php else: ?>
        <form method="POST" novalidate>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Nome</label>
                    <input type="text" name="nome" class="form-control" value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Cognome</label>
                    <input type="text" name="cognome" class="form-control" value="<?= htmlspecialchars($_POST['cognome'] ?? '') ?>" required>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Username host</label>
                <input type="text" name="login_id" class="form-control" value="<?= htmlspecialchars($_POST['login_id'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Conferma password</label>
                <input type="password" name="confirm" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%">Crea account e setup DB</button>
        </form>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
