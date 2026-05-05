<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn()) {
    header('Location: /CodeRush/login.php');
    exit();
}

$db = getDB();
$user = getUserById($_SESSION['user_id']);
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_info' && isHost()) {
        $nome = trim($_POST['nome'] ?? '');
        $cognome = trim($_POST['cognome'] ?? '');
        if (empty($nome)) {
            $errors[] = 'Nome obbligatorio.';
        }
        if (empty($cognome)) {
            $errors[] = 'Cognome obbligatorio.';
        }
        if (empty($errors)) {
            $db->prepare(
                'UPDATE users SET nome = ?, cognome = ? WHERE id = ?'
            )->execute([$nome, $cognome, $_SESSION['user_id']]);
            $_SESSION['nome'] = $nome;
            $_SESSION['cognome'] = $cognome;
            $user = getUserById($_SESSION['user_id']);
            $success = 'Profilo aggiornato.';
        }
    } elseif ($action === 'change_password') {
        $old = $_POST['old_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (!password_verify($old, $user['password'])) {
            $errors[] = 'Password attuale non corretta.';
        }
        if (strlen($new) < 6) {
            $errors[] = 'Nuova password minimo 6 caratteri.';
        }
        if ($new !== $confirm) {
            $errors[] = 'Le nuove password non coincidono.';
        }
        if (empty($errors)) {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $db->prepare('UPDATE users SET password = ? WHERE id = ?')
               ->execute([$hash, $_SESSION['user_id']]);
            $success = 'Password aggiornata.';
        }
    } else {
        $errors[] = 'Azione non valida.';
    }
}

$pageTitle = 'Profilo';
require_once __DIR__ . '/../includes/header.php';
?>
<main class="container">
    <div class="page-header">
        <h1>Profilo</h1>
        <span class="badge badge-<?= isHost() ? 'host' : 'student' ?>"><?= isHost() ? 'Host' : 'Studente' ?></span>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger"><?= implode('<br>', array_map('sanitize', $errors)) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?= sanitize($success) ?></div>
    <?php endif; ?>

    <?php if (isHost()): ?>
    <div class="card">
        <div class="card-title">Informazioni personali</div>
        <form method="POST" novalidate>
            <input type="hidden" name="action" value="update_info">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Nome</label>
                    <input type="text" name="nome" class="form-control" value="<?= sanitize($user['nome']) ?>" required minlength="2">
                </div>
                <div class="form-group">
                    <label class="form-label">Cognome</label>
                    <input type="text" name="cognome" class="form-control" value="<?= sanitize($user['cognome']) ?>" required minlength="2">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Username</label>
                <input type="text" class="form-control" value="<?= sanitize($user['login_id']) ?>" disabled>
                <div class="form-text">Lo username non può essere modificato.</div>
            </div>
            <button type="submit" class="btn btn-primary">Salva modifiche</button>
        </form>
    </div>
    <?php else: ?>
    <div class="card">
        <div class="card-title">Informazioni personali</div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div>
                <div class="form-label">Nome</div>
                <div><?= sanitize($user['nome']) ?></div>
            </div>
            <div>
                <div class="form-label">Cognome</div>
                <div><?= sanitize($user['cognome']) ?></div>
            </div>
            <div>
                <div class="form-label">Matricola</div>
                <div><?= sanitize($user['login_id']) ?></div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-title">Cambia password</div>
        <form method="POST" novalidate id="formPassword">
            <input type="hidden" name="action" value="change_password">
            <div class="form-group">
                <label class="form-label">Password attuale</label>
                <input type="password" name="old_password" class="form-control" required>
                <div class="error-text" id="err-old"></div>
            </div>
            <div class="form-group">
                <label class="form-label">Nuova password</label>
                <input type="password" name="new_password" class="form-control" required minlength="6">
                <div class="form-text">Minimo 6 caratteri.</div>
                <div class="error-text" id="err-new"></div>
            </div>
            <div class="form-group">
                <label class="form-label">Conferma nuova password</label>
                <input type="password" name="confirm_password" class="form-control" required>
                <div class="error-text" id="err-confirm"></div>
            </div>
            <button type="submit" class="btn btn-primary">Aggiorna password</button>
        </form>
    </div>
</main>
</body>
</html>
<script src="/CodeRush/js/script.js"></script>
