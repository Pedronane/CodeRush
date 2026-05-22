<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';
if (!isHost()) { header('Location: /CodeRush/login.php'); exit(); }

$db = getDB();
$classe_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$classe    = $classe_id > 0 ? getClasseById($classe_id) : null;
if (!$classe) { header('Location: /CodeRush/pages/classi.php'); exit(); }

$errors  = [];
$success = '';

// Tre azioni sulla stessa pagina, distinte dal campo nascosto "action"
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'aggiungi_studente') {
        $student_id = (int)($_POST['student_id'] ?? 0);
        if ($student_id <= 0) {
            $errors[] = 'Seleziona uno studente.';
        } else {
            $chk = $db->prepare('SELECT 1 FROM studente_classe WHERE studente_id=? AND classe_id=?');
            $chk->execute([$student_id,$classe_id]);
            if ($chk->fetch()) {
                $errors[] = 'Studente già in questa classe.';
            } else {
                $db->prepare('INSERT INTO studente_classe (studente_id,classe_id) VALUES (?,?)')->execute([$student_id,$classe_id]);
                $success = 'Studente aggiunto.';
            }
        }
    } elseif ($action === 'rimuovi_studente') {
        $student_id = (int)($_POST['student_id'] ?? 0);
        if ($student_id > 0) {
            $db->prepare('DELETE FROM studente_classe WHERE studente_id=? AND classe_id=?')->execute([$student_id,$classe_id]);
            $success = 'Studente rimosso dalla classe.';
        }
    } elseif ($action === 'sposta_studente') {
        $student_id  = (int)($_POST['student_id']  ?? 0);
        $nuova_classe = (int)($_POST['nuova_classe'] ?? 0);
        if ($student_id <= 0 || $nuova_classe <= 0) {
            $errors[] = 'Dati mancanti per lo spostamento.';
        } elseif ($nuova_classe === $classe_id) {
            $errors[] = 'Lo studente è già in questa classe.';
        } else {
            $db->prepare('UPDATE studente_classe SET classe_id=? WHERE studente_id=? AND classe_id=?')->execute([$nuova_classe,$student_id,$classe_id]);
            $success = 'Studente spostato.';
        }
    }
}

$studenti          = getStudentiByClasse($classe_id);
$tuttiStudenti     = $db->query('SELECT * FROM users WHERE ruolo="studente" ORDER BY cognome,nome')->fetchAll();
$stmtInClasse      = $db->prepare('SELECT studente_id FROM studente_classe WHERE classe_id=?');
$stmtInClasse->execute([$classe_id]);
$idInClasse        = array_column($stmtInClasse->fetchAll(), 'studente_id');
// Studenti selezionabili: quelli non ancora iscritti a questa classe
$studentiDisponibili = array_filter($tuttiStudenti, fn($s) => !in_array($s['id'], $idInClasse));
$altreClassi       = array_filter(getAllClassi(), fn($c) => $c['id'] != $classe_id);
$rushes            = getRushByClasse($classe_id);

$pageTitle = $classe['anno'].$classe['sezione'].' '.$classe['indirizzo'];
require_once __DIR__ . '/../includes/header.php';
?>
<link rel="stylesheet" href="/CodeRush/css/pages/classe.css">
<main class="container">

    <div class="breadcrumb page-section">
        <a href="/CodeRush/">Home</a>
        <span class="breadcrumb-sep">›</span>
        <a href="/CodeRush/pages/classi.php">Classi</a>
        <span class="breadcrumb-sep">›</span>
        <span><?= sanitize($classe['anno'].$classe['sezione'].' '.$classe['indirizzo']) ?></span>
    </div>

    <div class="page-header page-section-header">
        <div>
            <h1><?= sanitize($classe['anno'].$classe['sezione'].' '.$classe['indirizzo']) ?></h1>
            <p class="page-subtitle"><?= count($studenti) ?> studenti iscritti</p>
        </div>
        <div class="button-group">
            <a href="/CodeRush/pages/rush.php" class="btn-primary-lg">▶ Nuovo Rush</a>
            <a href="/CodeRush/pages/classi.php?edit=<?= $classe_id ?>" class="btn-ghost">Modifica</a>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger page-section-alert"><?= implode('<br>', array_map('sanitize', $errors)) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
    <div class="alert alert-success page-section-alert"><?= sanitize($success) ?></div>
    <?php endif; ?>

    <!-- Students table -->
    <div class="card card-no-pad page-section-students">
        <div class="card-header">
            <span class="card-header-label">Studenti</span>
            <span class="badge badge-host"><?= count($studenti) ?></span>
        </div>
        <?php if (empty($studenti)): ?>
        <div class="empty-state">
            <p>Nessuno studente in questa classe.</p>
            <a href="/CodeRush/pages/registra.php" class="btn-primary-lg">Crea studente</a>
        </div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Studente</th>
                    <th>Matricola</th>
                    <th class="col-280">Azioni</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($studenti as $s): ?>
            <tr>
                <td>
                    <div class="student-row">
                        <div class="student-avatar">
                            <?= strtoupper(mb_substr($s['cognome'],0,1).mb_substr($s['nome'],0,1)) ?>
                        </div>
                        <a href="/CodeRush/pages/studente.php?id=<?= $s['id'] ?>" class="student-name-link">
                            <?= sanitize($s['cognome'].' '.$s['nome']) ?>
                        </a>
                    </div>
                </td>
                <td><code class="student-id"><?= sanitize($s['login_id']) ?></code></td>
                <td>
                    <div class="student-actions">
                        <?php if (!empty($altreClassi)): ?>
                        <form method="POST" class="form-move-student">
                            <input type="hidden" name="action"     value="sposta_studente">
                            <input type="hidden" name="student_id" value="<?= $s['id'] ?>">
                            <select name="nuova_classe" class="input-arena form-move-select">
                                <?php foreach ($altreClassi as $ac): ?>
                                <option value="<?= $ac['id'] ?>"><?= sanitize($ac['anno'].$ac['sezione'].' '.$ac['indirizzo']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-sm btn-outline">Sposta</button>
                        </form>
                        <?php endif; ?>
                        <form method="POST" class="form-remove-student" onsubmit="return confirm('Rimuovere lo studente dalla classe?')">
                            <input type="hidden" name="action"     value="rimuovi_studente">
                            <input type="hidden" name="student_id" value="<?= $s['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-danger">Rimuovi</button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <!-- Add student -->
    <?php if (!empty($studentiDisponibili)): ?>
    <div class="card page-section-add">
        <p class="card-header-label card-header-label-mb">Aggiungi studente esistente</p>
        <form method="POST" class="add-student-form">
            <input type="hidden" name="action" value="aggiungi_studente">
            <select name="student_id" class="input-arena add-student-select" required>
                <option value="0">— Seleziona studente —</option>
                <?php foreach ($studentiDisponibili as $s): ?>
                <option value="<?= $s['id'] ?>"><?= sanitize($s['cognome'].' '.$s['nome'].' ('.$s['login_id'].')') ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn-primary-lg">Aggiungi</button>
        </form>
        <p class="add-student-note">
            Per creare un nuovo studente: <a href="/CodeRush/pages/registra.php">Registra utente →</a>
        </p>
    </div>
    <?php else: ?>
    <div class="card no-students-card">
        <p class="no-students-text">Tutti gli studenti registrati sono già in questa classe.</p>
        <p class="no-students-text-margin"><a href="/CodeRush/pages/registra.php">Crea un nuovo studente →</a></p>
    </div>
    <?php endif; ?>

    <!-- Rush history -->
    <?php if (!empty($rushes)): ?>
    <div class="card card-no-pad page-section-rushes">
        <div class="table-header">
            <span class="table-header-label">Rush completati</span>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Consegna</th>
                    <th>Host</th>
                    <th class="col-100"></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rushes as $r): ?>
            <tr>
                <td class="rush-table-date"><?= date('d/m/Y H:i', strtotime($r['created_at'])) ?></td>
                <td class="rush-table-nome"><?= sanitize($r['domanda_nome']) ?></td>
                <td class="rush-table-host"><?= sanitize($r['host_nome'].' '.$r['host_cognome']) ?></td>
                <td>
                    <div class="rush-table-actions">
                        <a href="/CodeRush/pages/risultati.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-primary">Risultati →</a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</main>
<script src="/CodeRush/js/script.js"></script>
</body>
</html>
