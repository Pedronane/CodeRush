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
<main class="container">

    <div class="breadcrumb" style="animation:fade-up .35s ease-out both;">
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

    <div class="page-header" style="animation:fade-up .4s ease-out both;">
        <div style="display:flex;align-items:center;gap:16px;">
            <div class="profile-avatar" style="width:56px;height:56px;font-size:18px;margin:0;animation:pop-in .5s cubic-bezier(.34,1.56,.64,1) both;">
                <?= $initials ?>
            </div>
            <div>
                <h1 style="font-size:22px;"><?= sanitize($studente['nome'].' '.$studente['cognome']) ?></h1>
                <p class="page-subtitle">@<?= sanitize(strtolower($studente['login_id'])) ?> &nbsp;·&nbsp; <span class="badge badge-student">Studente</span></p>
            </div>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger" style="animation:fade-up .3s ease-out both;"><?= implode('<br>', array_map('sanitize', $errors)) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
    <div class="alert alert-success" style="animation:fade-up .3s ease-out both;"><?= sanitize($success) ?></div>
    <?php endif; ?>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;align-items:start;" class="studente-layout">

        <div class="card" style="animation:fade-up .45s ease-out .05s both;">
            <p style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--muted-foreground);margin-bottom:16px;">Modifica dati</p>
            <p style="font-size:12px;color:var(--muted-foreground);margin-bottom:16px;">Lascia vuoto un campo per non modificarlo.</p>
            <form method="POST" novalidate>
                <div class="form-group">
                    <label class="form-label">Matricola</label>
                    <input type="text" class="input-arena" value="<?= sanitize($studente['login_id']) ?>" disabled style="opacity:.6;">
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

        <div style="display:flex;flex-direction:column;gap:16px;animation:fade-up .45s ease-out .1s both;">
            <?php if (!empty($classiStudente)): ?>
            <div class="card">
                <p style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--muted-foreground);margin-bottom:14px;">Classi</p>
                <div style="display:flex;flex-direction:column;gap:8px;">
                    <?php foreach ($classiStudente as $cl): ?>
                    <a href="/CodeRush/pages/classe.php?id=<?= $cl['id'] ?>"
                       style="display:flex;align-items:center;justify-content:space-between;padding:10px 14px;border-radius:10px;border:1px solid var(--border);text-decoration:none;transition:background .15s;"
                       onmouseover="this.style.background='rgba(255,255,255,.04)'" onmouseout="this.style.background=''">
                        <span style="font-weight:700;color:var(--foreground);">
                            <span style="font-family:'JetBrains Mono',monospace;"><?= sanitize($cl['anno'].$cl['sezione']) ?></span>
                            <span style="color:var(--muted-foreground);font-size:13px;"> <?= sanitize($cl['indirizzo']) ?></span>
                        </span>
                        <span style="color:var(--muted-foreground);font-size:12px;">→</span>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="card" style="background:rgba(61,181,64,.04);border-color:rgba(61,181,64,.2);">
                <p style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--brand-green);margin-bottom:12px;">Info account</p>
                <div class="info-row" style="margin-bottom:6px;">
                    <span class="info-label">Ruolo</span>
                    <span class="badge badge-student">Studente</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Classi</span>
                    <span style="font-weight:700;color:var(--brand-green);"><?= count($classiStudente) ?></span>
                </div>
            </div>
        </div>
    </div>
</main>
<style>
@media (max-width:700px) { .studente-layout { grid-template-columns:1fr !important; } }
</style>
<script src="/CodeRush/js/script.js"></script>
</body>
</html>
