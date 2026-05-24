<?php
session_start();
require_once __DIR__ . '/includes/functions.php';
// Chi è già loggato non vede la pagina di login
if (isLoggedIn()) { header('Location: /CodeRush/'); exit(); }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_id = trim($_POST['login_id'] ?? '');
    $password = $_POST['password'] ?? '';
    if (empty($login_id) || empty($password)) {
        $error = 'Inserisci username e password.';
    } else {
        $user = loginUser($login_id, $password);
        if ($user) {
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['ruolo']    = $user['ruolo'];
            $_SESSION['nome']     = $user['nome'];
            $_SESSION['cognome']  = $user['cognome'];
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
    <title>Accedi — CodeRush</title>
    <link rel="stylesheet" href="/CodeRush/css/style.css">
</head>
<body style="cursor:default;">

<!-- BackgroundFX -->
<div class="background-fx" aria-hidden="true">
    <div class="bfx-grid"></div>
    <div class="bfx-blob bfx-blob-green"></div>
    <div class="bfx-blob bfx-blob-blue"></div>
    <div class="bfx-blob bfx-blob-orange"></div>
</div>

<!-- Code Particles (full page) -->
<div data-particles="28" style="position:fixed;inset:0;z-index:0;pointer-events:none;"></div>

<div class="login-wrapper" style="position:relative;z-index:1;">
    <div class="login-card">

        <img src="/CodeRush/img/logo.png" alt="CodeRush" class="login-logo">
        <h1 style="text-align:center;font-size:22px;margin-bottom:6px;">Accedi</h1>
        <p class="login-subtitle">Entra per giocare o gestire i tuoi Rush</p>

        <?php if ($error): ?>
        <div class="alert alert-danger" style="animation:shake .4s ease-in-out;"><?= sanitize($error) ?></div>
        <?php endif; ?>

        <!-- Role toggle — identico all'arena -->
        <div class="role-toggle">
            <button type="button" class="role-toggle-btn active" data-role="studente">Studente</button>
            <button type="button" class="role-toggle-btn"        data-role="host">Host</button>
        </div>
        <input type="hidden" id="role-input" name="_role_display" value="studente">

        <form method="POST" novalidate id="loginForm">
            <div class="form-group">
                <label class="form-label" id="login-id-label" for="login_id">Matricola</label>
                <input
                    type="text"
                    id="login_id"
                    name="login_id"
                    class="input-arena"
                    value="<?= sanitize($_POST['login_id'] ?? '') ?>"
                    autocomplete="username"
                    placeholder="12345"
                    required
                >
                <div class="error-text" id="err-login_id"></div>
            </div>
            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <div style="position:relative;">
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="input-arena"
                        autocomplete="current-password"
                        placeholder="••••••••"
                        required
                        style="padding-right:48px;"
                    >
                    <button type="button" id="togglePwd"
                        style="position:absolute;right:12px;top:50%;transform:translateY(-50%);
                               background:none;border:none;cursor:pointer;font-size:16px;
                               color:var(--muted-foreground);padding:4px;">👁</button>
                </div>
                <div class="error-text" id="err-password"></div>
            </div>

            <button type="submit"
                class="btn-primary-lg btn-block"
                style="margin-top:8px;padding:13px 18px;font-size:14px;">
                Accedi →
            </button>
        </form>

        <p style="text-align:center;margin-top:22px;font-size:12px;color:var(--muted-foreground);">
            Prima volta? Chiedi al tuo insegnante le credenziali.
        </p>
    </div>
</div>

<script src="/CodeRush/js/script.js"></script>
<script>
initRoleToggle();
// toggle password visibility
document.getElementById('togglePwd').addEventListener('click', function () {
    var inp = document.getElementById('password');
    inp.type = inp.type === 'password' ? 'text' : 'password';
    this.textContent = inp.type === 'password' ? '👁' : '🙈';
});
</script>
</body>
</html>
