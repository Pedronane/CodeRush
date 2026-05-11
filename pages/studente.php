<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';
if (!isHost()) { header('Location: /CodeRush/login.php'); exit(); }

$db         = getDB();
$student_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$studente   = $student_id > 0 ? getUserById($student_id) : null;
if (!$studente || $studente['ruolo'] !== 'studente') { header('Location: /CodeRush/pages/classi.php'); exit(); }

$errors  = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome     = trim($_POST['nome']     ?? '');
    $cognome  = trim($_POST['cognome']  ?? '');
    $password = $_POST['password']      ?? '';

    $updates = []; $params = [];

    if (!empty($nome) && $nome !== $studente['nome']) {
        if (strlen($nome) < 2) $errors[] = 'Nome troppo corto.';
        else { $updates[] = 'nome=?'; $params[] = $nome; }
    }
    if (!empty($cognome) && $cognome !== $studente['cognome']) {
        if (strlen($cognome) < 2) $errors[] = 'Cognome troppo corto.';
        else { $updates[] = 'cognome=?'; $params[] = $cognome; }
    }
    if (!empty($password)) {
        if (strlen($password) < 6) $errors[] = 'Password minimo 6 caratteri.';
        else { $updates[] = 'password=?'; $params[] = password_hash($password, PASSWORD_DEFAULT); }
    }

    if (empty($errors)) {
        if (!empty($updates)) {
            $params[] = $student_id;
            $db->prepare('UPDATE users SET '.implode(',',$updates).' WHERE id=?')->execute($params);
            $studente = getUserById($student_id);
            $success = 'Profilo aggiornato.';
        } else {
            $success = 'Nessuna modifica apportata.';
        }
    }
}

$stmtClassi = $db->prepare('SELECT c.* FROM classi c JOIN studente_classe sc ON sc.classe_id=c.id WHERE sc.studente_id=?');
$stmtClassi->execute([$student_id]);
$classiStudente = $stmtClassi->fetchAll();

$initials  = strtoupper(mb_substr($studente['cognome'],0,1).mb_substr($studente['nome'],0,1));
$pageTitle = 'Studente — '.$studente['cognome'].' '.$studente['nome'];
require_once __DIR__ . '/../includes/header.php';
?>
<link rel="stylesheet" href="/CodeRush/css/pages/studente.css">
<main class="container">

    <div class="breadcrumb page-section-breadcrumb">
        <a href="/CodeRush/">Home</a>
        <span class="breadcrumb-sep">›</span>
        <?php if (!empty($classiStudente)): ?>
        <a href="/CodeRush/pages/classi.php">Classi</a>
        <span class="breadcrumb-sep">›</span>
        <a href="/CodeRush/pages/classe.php?id=<?= $classiStudente[0]['id'] ?>">
            <?= sanitize($classiStudente[0]['anno'].$classiStudente[0]['sezione'].' '.$classiStudente[0]['indirizzo']) ?>
        </a>
        <span class="breadcrumb-sep">›</span>
        <?php endif; ?>
        <span><?= sanitize($studente['cognome'].' '.$studente['nome']) ?></span>
    </div>

    <div class="page-header page-section-header">
        <div class="profile-header">
            <div class="profile-avatar">
                <?= $initials ?>
            </div>
            <div>
                <h1 class="profile-title"><?= sanitize($studente['nome'].' '.$studente['cognome']) ?></h1>
                <p class="page-subtitle profile-subtitle">@<?= sanitize(strtolower($studente['login_id'])) ?> &nbsp;·&nbsp; <span class="badge badge-student">Studente</span></p>
            </div>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger page-section-alert"><?= implode('<br>', array_map('sanitize', $errors)) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
    <div class="alert alert-success page-section-alert"><?= sanitize($success) ?></div>
    <?php endif; ?>

    <div class="studente-layout">

        <div class="card studente-card">
            <p class="text-small"><span class="text-small-label">Modifica dati</span></p>
            <p class="text-muted">Lascia vuoto un campo per non modificarlo.</p>
            <form method="POST" novalidate>
                <div class="form-group">
                    <label class="form-label">Matricola</label>
                    <input type="text" class="input-arena input-disabled" value="<?= sanitize($studente['login_id']) ?>" disabled>
                    <div class="form-text">La matricola non può essere modificata.</div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Nome</label>
                        <input type="text" name="nome" class="input-arena" value="<?= sanitize($studente['nome']) ?>" minlength="2" placeholder="Lascia invariato">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Cognome</label>
                        <input type="text" name="cognome" class="input-arena" value="<?= sanitize($studente['cognome']) ?>" minlength="2" placeholder="Lascia invariato">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Nuova password</label>
                    <input type="password" name="password" class="input-arena" minlength="6" placeholder="Lascia vuoto per non cambiare">
                    <div class="form-text">Minimo 6 caratteri.</div>
                </div>
                <button type="submit" class="btn-primary-lg btn-block">Salva modifiche</button>
            </form>
        </div>

        <div class="studente-sidebar">
            <?php if (!empty($classiStudente)): ?>
            <div class="card">
                <p class="text-small"><span class="text-small-label">Classi</span></p>
                <div class="classi-list">
                    <?php foreach ($classiStudente as $cl): ?>
                    <a href="/CodeRush/pages/classe.php?id=<?= $cl['id'] ?>" class="classe-link">
                        <span class="classe-info">
                            <span class="classe-code"><?= sanitize($cl['anno'].$cl['sezione']) ?></span>
                            <span class="classe-indirizzo"><?= sanitize($cl['indirizzo']) ?></span>
                        </span>
                        <span class="classe-arrow">→</span>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="card info-card">
                <p class="info-label-text">Info account</p>
                <div class="info-row info-row-mb">
                    <span class="info-label">Ruolo</span>
                    <span class="badge badge-student">Studente</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Classi</span>
                    <span class="info-value"><?= count($classiStudente) ?></span>
                </div>
            </div>
        </div>
    </div>
</main>
<script src="/CodeRush/js/script.js"></script>
</body>
</html>
