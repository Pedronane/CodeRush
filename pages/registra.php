<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';
if (!isHost()) { header('Location: /CodeRush/login.php'); exit(); }

$db = getDB();
$errors    = [];
$success   = '';
$activeTab = 'studente';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo     = $_POST['tipo']     ?? 'studente';
    $activeTab = $tipo;
    $nome     = trim($_POST['nome']     ?? '');
    $cognome  = trim($_POST['cognome']  ?? '');
    $login_id = trim($_POST['login_id'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($nome))     $errors[] = 'Nome obbligatorio.';
    if (empty($cognome))  $errors[] = 'Cognome obbligatorio.';
    if (empty($login_id)) $errors[] = $tipo === 'studente' ? 'Matricola obbligatoria.' : 'Username obbligatorio.';
    if (strlen($password) < 6) $errors[] = 'Password minimo 6 caratteri.';
    if (!preg_match('/^[a-zA-Z0-9_.\-]+$/', $login_id)) $errors[] = 'Username/matricola: solo lettere, numeri, trattini e punti.';

    if (empty($errors)) {
        $chk = $db->prepare('SELECT id FROM users WHERE login_id = ?');
        $chk->execute([$login_id]);
        if ($chk->fetch()) {
            $errors[] = 'Matricola/Username già in uso.';
        } else {
            $ruolo = ($tipo === 'host') ? 'host' : 'studente';
            $db->prepare('INSERT INTO users (login_id,nome,cognome,password,ruolo) VALUES (?,?,?,?,?)')
               ->execute([$login_id,$nome,$cognome,password_hash($password,PASSWORD_DEFAULT),$ruolo]);
            $success = 'Account '.sanitize($nome.' '.$cognome).' creato con successo.';
        }
    }
}

$pageTitle = 'Registra utente';
require_once __DIR__ . '/../includes/header.php';
<link rel="stylesheet" href="/CodeRush/css/pages/registra.css">
?>
<main class="container">

    <div class="breadcrumb page-section-breadcrumb">
        <a href="/CodeRush/">Home</a>
        <span class="breadcrumb-sep">›</span>
        <span>Registra utente</span>
    </div>

    <div class="page-header page-section-header">
        <div>
            <h1>Registra utente</h1>
            <p class="page-subtitle">Crea account studente o host</p>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger page-section-alert"><?= implode('<br>', array_map('sanitize', $errors)) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
    <div class="alert alert-success page-section-alert"><?= $success ?></div>
    <?php endif; ?>

    <div class="registra-form-wrap">
        <div class="card">
            <div class="tabs">
                <button class="tab-btn <?= $activeTab === 'studente' ? 'active' : '' ?>" data-tab="studente">👤 Nuovo studente</button>
                <button class="tab-btn <?= $activeTab === 'host'     ? 'active' : '' ?>" data-tab="host">🎓 Nuovo host</button>
            </div>

            <!-- Studente -->
            <div class="tab-pane <?= $activeTab === 'studente' ? 'active' : '' ?>" id="tab-studente">
                <form method="POST" novalidate id="formStudente">
                    <input type="hidden" name="tipo" value="studente">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Nome</label>
                            <input type="text" name="nome" class="input-arena"
                                   value="<?= $activeTab === 'studente' ? sanitize($_POST['nome'] ?? '') : '' ?>"
                                   required minlength="2" placeholder="Marco">
                            <div class="error-text" id="err-nome"></div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Cognome</label>
                            <input type="text" name="cognome" class="input-arena"
                                   value="<?= $activeTab === 'studente' ? sanitize($_POST['cognome'] ?? '') : '' ?>"
                                   required minlength="2" placeholder="Rossi">
                            <div class="error-text" id="err-cognome"></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Matricola</label>
                        <input type="text" name="login_id" class="input-arena"
                               value="<?= $activeTab === 'studente' ? sanitize($_POST['login_id'] ?? '') : '' ?>"
                               required pattern="[a-zA-Z0-9_.\-]+" placeholder="MAT123456">
                        <div class="form-text">Usata per il login. Solo lettere, numeri, trattini e punti.</div>
                        <div class="error-text" id="err-login_id"></div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" id="new_password" class="input-arena" required minlength="6">
                        <div class="pwd-strength" id="pwd-strength">
                            <div class="pwd-seg"></div>
                            <div class="pwd-seg"></div>
                            <div class="pwd-seg"></div>
                            <div class="pwd-seg"></div>
                        </div>
                        <div class="pwd-label"></div>
                        <div class="form-text">Minimo 6 caratteri.</div>
                    </div>
                    <button type="submit" class="btn-primary-lg btn-block btn-padded">Crea studente</button>
                </form>
            </div>

            <!-- Host -->
            <div class="tab-pane <?= $activeTab === 'host' ? 'active' : '' ?>" id="tab-host">
                <div class="alert alert-warning page-section-alert">Solo gli host esistenti possono creare altri account host.</div>
                <form method="POST" novalidate id="formHost">
                    <input type="hidden" name="tipo" value="host">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Nome</label>
                            <input type="text" name="nome" class="input-arena"
                                   value="<?= $activeTab === 'host' ? sanitize($_POST['nome'] ?? '') : '' ?>"
                                   required minlength="2" placeholder="Mario">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Cognome</label>
                            <input type="text" name="cognome" class="input-arena"
                                   value="<?= $activeTab === 'host' ? sanitize($_POST['cognome'] ?? '') : '' ?>"
                                   required minlength="2" placeholder="Bianchi">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Username</label>
                        <input type="text" name="login_id" class="input-arena"
                               value="<?= $activeTab === 'host' ? sanitize($_POST['login_id'] ?? '') : '' ?>"
                               required pattern="[a-zA-Z0-9_.\-]+" placeholder="prof.bianchi">
                        <div class="form-text">Usato per il login.</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="input-arena" required minlength="6">
                        <div class="form-text">Minimo 6 caratteri.</div>
                    </div>
                    <button type="submit" class="btn-primary-lg btn-block btn-padded">Crea host</button>
                </form>
            </div>
        </div>
    </div>
</main>
<script>
document.getElementById('new_password')?.addEventListener('input', function() {
    var bar = document.getElementById('pwd-strength');
    if (bar) bar.style.display = this.value ? 'flex' : 'none';
});
</script>
<script src="/CodeRush/js/script.js"></script>
</body>
</html>
