<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';
if (!isLoggedIn()) { header('Location: /CodeRush/login.php'); exit(); }

$db   = getDB();
$user = getUserById($_SESSION['user_id']);
$errors  = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_info' && isHost()) {
        $nome    = trim($_POST['nome']    ?? '');
        $cognome = trim($_POST['cognome'] ?? '');
        if (empty($nome))    $errors[] = 'Nome obbligatorio.';
        if (empty($cognome)) $errors[] = 'Cognome obbligatorio.';
        if (empty($errors)) {
            $db->prepare('UPDATE users SET nome=?,cognome=? WHERE id=?')->execute([$nome,$cognome,$_SESSION['user_id']]);
            $_SESSION['nome']    = $nome;
            $_SESSION['cognome'] = $cognome;
            $user    = getUserById($_SESSION['user_id']);
            $success = 'Profilo aggiornato.';
        }
    } elseif ($action === 'change_password') {
        $old     = $_POST['old_password']     ?? '';
        $new     = $_POST['new_password']     ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        if (!password_verify($old, $user['password'])) $errors[] = 'Password attuale non corretta.';
        if (strlen($new) < 6)    $errors[] = 'Nuova password minimo 6 caratteri.';
        if ($new !== $confirm)   $errors[] = 'Le nuove password non coincidono.';
        if (empty($errors)) {
            $db->prepare('UPDATE users SET password=? WHERE id=?')->execute([password_hash($new,PASSWORD_DEFAULT),$_SESSION['user_id']]);
            $success = 'Password aggiornata.';
        }
    }
}

$initials = strtoupper(mb_substr($user['nome'],0,1).mb_substr($user['cognome'],0,1));
$isHost   = isHost();
$pageTitle = 'Profilo';
require_once __DIR__ . '/../includes/header.php';
?>
<link rel="stylesheet" href="/CodeRush/css/pages/profilo.css">
<main class="container">

    <div class="breadcrumb">
        <a href="/CodeRush/">Home</a>
        <span class="breadcrumb-sep">›</span>
        <span>Profilo</span>
    </div>

    <div class="page-header page-section-header">
        <div>
            <h1>Il tuo profilo</h1>
            <p class="page-subtitle">Identità e credenziali</p>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger"><?= implode('<br>', array_map('sanitize',$errors)) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
    <div class="alert alert-success"><?= sanitize($success) ?></div>
    <?php endif; ?>

    <div class="profile-section anim-fade-up-slow">

        <!-- Left: avatar card -->
        <div class="card text-center">
            <div class="profile-avatar"><?= $initials ?></div>
            <h2 class="profile-name"><?= sanitize($user['nome'].' '.$user['cognome']) ?></h2>
            <p class="profile-username">@<?= sanitize(strtolower($user['login_id'])) ?></p>
            <div class="mt-12">
                <span class="pill" style="background:<?= $isHost ? 'rgba(61,181,64,.18)' : 'rgba(74,143,212,.18)' ?>;color:<?= $isHost ? 'var(--brand-green)' : 'var(--brand-blue)' ?>;">
                    <?= $isHost ? 'Host' : 'Studente' ?>
                </span>
            </div>
        </div>

        <!-- Right: forms -->
        <div class="profile-sections">

            <!-- Dati personali -->
            <div class="card">
                <div class="section-header-row">
                    <h3 class="section-title">Dati personali</h3>
                    <?php if (!$isHost): ?>
                    <span class="badge-warning">
                        🔒 Non modificabile
                    </span>
                    <?php endif; ?>
                </div>

                <?php if ($isHost): ?>
                <form method="POST" novalidate>
                    <input type="hidden" name="action" value="update_info">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Nome</label>
                            <input type="text" name="nome" class="input-arena" value="<?= sanitize($user['nome']) ?>" required minlength="2">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Cognome</label>
                            <input type="text" name="cognome" class="input-arena" value="<?= sanitize($user['cognome']) ?>" required minlength="2">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Username</label>
                        <input type="text" class="input-arena opacity-60" value="<?= sanitize($user['login_id']) ?>" disabled>
                        <div class="form-text">Lo username non può essere modificato.</div>
                    </div>
                    <button type="submit" class="btn-primary-lg">Salva modifiche</button>
                </form>
                <?php else: ?>
                <div class="warning-box">
                    <div class="form-row">
                        <div class="form-group mb-0">
                            <label class="form-label">Nome</label>
                            <input type="text" class="input-arena input-readonly" value="<?= sanitize($user['nome']) ?>" disabled>
                        </div>
                        <div class="form-group mb-0">
                            <label class="form-label">Cognome</label>
                            <input type="text" class="input-arena input-readonly" value="<?= sanitize($user['cognome']) ?>" disabled>
                        </div>
                    </div>
                    <div class="form-group form-group-top">
                        <label class="form-label">Matricola</label>
                        <input type="text" class="input-arena input-readonly" value="<?= sanitize($user['login_id']) ?>" disabled>
                    </div>
                    <p class="warning-note">
                        ⚠️ I dati personali possono essere modificati solo dal tuo professore.
                    </p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Cambia password -->
            <div class="card">
                <h3 class="section-title-mb">Cambia password</h3>
                <form method="POST" novalidate id="formPassword">
                    <input type="hidden" name="action" value="change_password">
                    <div class="form-group">
                        <label class="form-label">Password attuale</label>
                        <input type="password" name="old_password" class="input-arena" required>
                        <div class="error-text" id="err-old_password"></div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nuova password</label>
                        <input type="password" name="new_password" id="new_password" class="input-arena" required minlength="6">
                        <!-- Password strength bar -->
                        <div class="pwd-strength" id="pwd-strength">
                            <div class="pwd-seg"></div>
                            <div class="pwd-seg"></div>
                            <div class="pwd-seg"></div>
                            <div class="pwd-seg"></div>
                        </div>
                        <div class="pwd-label"></div>
                        <div class="error-text" id="err-new_password"></div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Conferma nuova password</label>
                        <input type="password" name="confirm_password" class="input-arena" required>
                        <div class="error-text" id="err-confirm_password"></div>
                    </div>
                    <button type="submit" class="btn-primary-lg">Aggiorna password</button>
                </form>
            </div>

        </div><!-- end right -->
    </div><!-- end profile-section -->
</main>
<script src="/CodeRush/js/script.js"></script>
<script>
// show strength bar when user starts typing
document.getElementById('new_password').addEventListener('input', function () {
    var bar = document.getElementById('pwd-strength');
    bar.style.display = this.value ? 'flex' : 'none';
});
</script>
</body>
</html>
