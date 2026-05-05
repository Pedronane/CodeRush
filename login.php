<?php
session_start();
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) {
    header('Location: /CodeRush/');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_id = trim($_POST['login_id'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($login_id) || empty($password)) {
        $error = 'Inserisci username e password.';
    } else {
        $user = loginUser($login_id, $password);
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['ruolo'] = $user['ruolo'];
            $_SESSION['nome'] = $user['nome'];
            $_SESSION['cognome'] = $user['cognome'];
            header('Location: /CodeRush/');
            exit();
        } else {
            $error = 'Credenziali non valide.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — CodeRush</title>
    <link rel="stylesheet" href="/CodeRush/css/style.css">
</head>
<body>
<div class="login-wrapper">
    <div class="login-card">
        <img src="/CodeRush/img/logo.png" alt="CodeRush" class="login-logo">
        <h1>CodeRush</h1>
        <p class="login-subtitle">Accedi con le credenziali fornite dal tuo insegnante</p>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= sanitize($error) ?></div>
        <?php endif; ?>

        <form method="POST" novalidate id="loginForm">
            <div class="form-group">
                <label class="form-label" for="login_id">Matricola / Username</label>
                <input
                    type="text"
                    id="login_id"
                    name="login_id"
                    class="form-control"
                    value="<?= sanitize($_POST['login_id'] ?? '') ?>"
                    autocomplete="username"
                    required
                >
                <div class="error-text" id="err-login_id"></div>
            </div>
            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="form-control"
                    autocomplete="current-password"
                    required
                >
                <div class="error-text" id="err-password"></div>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%;">Accedi</button>
        </form>

        <p style="text-align: center; margin-top: 20px; font-size: 13px; color: var(--text-muted);">
            Prima volta? Chiedi al tuo insegnante le credenziali.
        </p>
    </div>
</div>
<script src="/CodeRush/js/script.js"></script>
</body>
</html>
