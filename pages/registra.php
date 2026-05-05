<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';

if (!isHost()) {
    header('Location: /CodeRush/login.php');
    exit();
}

$db = getDB();
$errors = [];
$success = '';
$activeTab = 'studente';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo = $_POST['tipo'] ?? 'studente';
    $activeTab = $tipo;
    $nome = trim($_POST['nome'] ?? '');
    $cognome = trim($_POST['cognome'] ?? '');
    $login_id = trim($_POST['login_id'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($nome)) {
        $errors[] = 'Nome obbligatorio.';
    }
    if (empty($cognome)) {
        $errors[] = 'Cognome obbligatorio.';
    }
    if (empty($login_id)) {
        $errors[] = $tipo === 'studente' ? 'Matricola obbligatoria.' : 'Username obbligatorio.';
    }
    if (strlen($password) < 6) {
        $errors[] = 'Password minimo 6 caratteri.';
    }
    if (!preg_match('/^[a-zA-Z0-9_.-]+$/', $login_id)) {
        $errors[] = 'Username/matricola: solo lettere, numeri, trattini e punti.';
    }

    if (empty($errors)) {
        $chk = $db->prepare('SELECT id FROM users WHERE login_id = ?');
        $chk->execute([$login_id]);
        if ($chk->fetch()) {
            $errors[] = 'Matricola/Username già in uso.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $ruolo = ($tipo === 'host') ? 'host' : 'studente';
            $db->prepare(
                'INSERT INTO users (login_id, nome, cognome, password, ruolo) VALUES (?, ?, ?, ?, ?)'
            )->execute([$login_id, $nome, $cognome, $hash, $ruolo]);
            $success = 'Account ' . sanitize($nome . ' ' . $cognome) . ' creato con successo.';
        }
    }
}

$pageTitle = 'Registra utente';
require_once __DIR__ . '/../includes/header.php';
?>
<main class="container">
    <div class="page-header">
        <h1>Registra utente</h1>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger"><?= implode('<br>', array_map('sanitize', $errors)) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="tabs">
            <button class="tab-btn <?= $activeTab === 'studente' ? 'active' : '' ?>" data-tab="studente">Nuovo studente</button>
            <button class="tab-btn <?= $activeTab === 'host' ? 'active' : '' ?>" data-tab="host">Nuovo host</button>
        </div>

        <div class="tab-pane <?= $activeTab === 'studente' ? 'active' : '' ?>" id="tab-studente">
            <form method="POST" novalidate id="formStudente">
                <input type="hidden" name="tipo" value="studente">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Nome</label>
                        <input type="text" name="nome" class="form-control" value="<?= $activeTab === 'studente' ? sanitize($_POST['nome'] ?? '') : '' ?>" required minlength="2">
                        <div class="error-text" id="err-nome-s"></div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Cognome</label>
                        <input type="text" name="cognome" class="form-control" value="<?= $activeTab === 'studente' ? sanitize($_POST['cognome'] ?? '') : '' ?>" required minlength="2">
                        <div class="error-text" id="err-cognome-s"></div>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Matricola</label>
                    <input type="text" name="login_id" class="form-control" value="<?= $activeTab === 'studente' ? sanitize($_POST['login_id'] ?? '') : '' ?>" required pattern="[a-zA-Z0-9_.\-]+">
                    <div class="form-text">Usata per il login. Solo lettere, numeri, trattini e punti.</div>
                    <div class="error-text" id="err-login-s"></div>
                </div>
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required minlength="6">
                    <div class="form-text">Minimo 6 caratteri.</div>
                    <div class="error-text" id="err-pass-s"></div>
                </div>
                <button type="submit" class="btn btn-primary">Crea studente</button>
            </form>
        </div>

        <div class="tab-pane <?= $activeTab === 'host' ? 'active' : '' ?>" id="tab-host">
            <div class="alert alert-warning">Solo gli host esistenti possono creare altri account host.</div>
            <form method="POST" novalidate id="formHost">
                <input type="hidden" name="tipo" value="host">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Nome</label>
                        <input type="text" name="nome" class="form-control" value="<?= $activeTab === 'host' ? sanitize($_POST['nome'] ?? '') : '' ?>" required minlength="2">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Cognome</label>
                        <input type="text" name="cognome" class="form-control" value="<?= $activeTab === 'host' ? sanitize($_POST['cognome'] ?? '') : '' ?>" required minlength="2">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Username</label>
                    <input type="text" name="login_id" class="form-control" value="<?= $activeTab === 'host' ? sanitize($_POST['login_id'] ?? '') : '' ?>" required pattern="[a-zA-Z0-9_.\-]+">
                    <div class="form-text">Usato per il login.</div>
                </div>
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required minlength="6">
                    <div class="form-text">Minimo 6 caratteri.</div>
                </div>
                <button type="submit" class="btn btn-primary">Crea host</button>
            </form>
        </div>
    </div>
</main>
</body>
</html>
<script src="/CodeRush/js/script.js"></script>
