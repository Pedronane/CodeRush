<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';

if (!isHost()) {
    header('Location: /CodeRush/login.php');
    exit();
}

$db = getDB();
$student_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$studente = null;

if ($student_id > 0) {
    $studente = getUserById($student_id);
}

if (!$studente || $studente['ruolo'] !== 'studente') {
    header('Location: /CodeRush/pages/classi.php');
    exit();
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $cognome = trim($_POST['cognome'] ?? '');
    $password = $_POST['password'] ?? '';

    $updates = [];
    $params = [];

    if (!empty($nome) && $nome !== $studente['nome']) {
        if (strlen($nome) < 2) {
            $errors[] = 'Nome troppo corto.';
        } else {
            $updates[] = 'nome = ?';
            $params[] = $nome;
        }
    }
    if (!empty($cognome) && $cognome !== $studente['cognome']) {
        if (strlen($cognome) < 2) {
            $errors[] = 'Cognome troppo corto.';
        } else {
            $updates[] = 'cognome = ?';
            $params[] = $cognome;
        }
    }
    if (!empty($password)) {
        if (strlen($password) < 6) {
            $errors[] = 'Password minimo 6 caratteri.';
        } else {
            $updates[] = 'password = ?';
            $params[] = password_hash($password, PASSWORD_DEFAULT);
        }
    }

    if (empty($errors)) {
        if (!empty($updates)) {
            $params[] = $student_id;
            $db->prepare('UPDATE users SET ' . implode(', ', $updates) . ' WHERE id = ?')
               ->execute($params);
            $studente = getUserById($student_id);
            $success = 'Profilo aggiornato.';
        } else {
            $success = 'Nessuna modifica apportata.';
        }
    }
}

$stmtClassi = $db->prepare(
    'SELECT c.* FROM classi c JOIN studente_classe sc ON sc.classe_id = c.id WHERE sc.studente_id = ?'
);
$stmtClassi->execute([$student_id]);
$classiStudente = $stmtClassi->fetchAll();

$pageTitle = 'Profilo studente';
require_once __DIR__ . '/../includes/header.php';
?>
<main class="container">
    <div class="breadcrumb">
        <?php if (!empty($classiStudente)): ?>
        <a href="/CodeRush/pages/classi.php">Classi</a>
        <span class="breadcrumb-sep">/</span>
        <a href="/CodeRush/pages/classe.php?id=<?= $classiStudente[0]['id'] ?>"><?= sanitize($classiStudente[0]['anno'] . $classiStudente[0]['sezione'] . ' ' . $classiStudente[0]['indirizzo']) ?></a>
        <span class="breadcrumb-sep">/</span>
        <?php endif; ?>
        <span><?= sanitize($studente['cognome'] . ' ' . $studente['nome']) ?></span>
    </div>

    <div class="page-header">
        <h1><?= sanitize($studente['nome'] . ' ' . $studente['cognome']) ?></h1>
        <span class="badge badge-student">Studente</span>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger"><?= implode('<br>', array_map('sanitize', $errors)) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?= sanitize($success) ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-title">Modifica dati studente</div>
        <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 16px;">Lascia vuoto un campo per non modificarlo.</p>
        <form method="POST" novalidate>
            <div class="form-group">
                <label class="form-label">Matricola</label>
                <input type="text" class="form-control" value="<?= sanitize($studente['login_id']) ?>" disabled>
                <div class="form-text">La matricola non può essere modificata.</div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Nome</label>
                    <input type="text" name="nome" class="form-control" value="<?= sanitize($studente['nome']) ?>" minlength="2" placeholder="Lascia invariato">
                </div>
                <div class="form-group">
                    <label class="form-label">Cognome</label>
                    <input type="text" name="cognome" class="form-control" value="<?= sanitize($studente['cognome']) ?>" minlength="2" placeholder="Lascia invariato">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Nuova password</label>
                <input type="password" name="password" class="form-control" minlength="6" placeholder="Lascia vuoto per non cambiare">
                <div class="form-text">Minimo 6 caratteri. Lascia vuoto per non modificare.</div>
            </div>
            <button type="submit" class="btn btn-primary">Salva modifiche</button>
        </form>
    </div>

    <?php if (!empty($classiStudente)): ?>
    <div class="card">
        <div class="card-title">Classi</div>
        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
            <?php foreach ($classiStudente as $cl): ?>
                <a href="/CodeRush/pages/classe.php?id=<?= $cl['id'] ?>" class="badge badge-host" style="font-size: 13px; padding: 4px 12px;">
                    <?= sanitize($cl['anno'] . $cl['sezione'] . ' ' . $cl['indirizzo']) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</main>
</body>
</html>
<script src="/CodeRush/js/script.js"></script>
