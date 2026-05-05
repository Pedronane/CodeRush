<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';

if (!isHost()) {
    header('Location: /CodeRush/login.php');
    exit();
}

$db = getDB();
$classe_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$classe = $classe_id > 0 ? getClasseById($classe_id) : null;

if (!$classe) {
    header('Location: /CodeRush/pages/classi.php');
    exit();
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'aggiungi_studente') {
        $student_id = (int)($_POST['student_id'] ?? 0);
        if ($student_id <= 0) {
            $errors[] = 'Seleziona uno studente.';
        } else {
            $chk = $db->prepare('SELECT 1 FROM studente_classe WHERE studente_id = ? AND classe_id = ?');
            $chk->execute([$student_id, $classe_id]);
            if ($chk->fetch()) {
                $errors[] = 'Studente già in questa classe.';
            } else {
                $db->prepare('INSERT INTO studente_classe (studente_id, classe_id) VALUES (?, ?)')
                   ->execute([$student_id, $classe_id]);
                $success = 'Studente aggiunto.';
            }
        }
    } elseif ($action === 'rimuovi_studente') {
        $student_id = (int)($_POST['student_id'] ?? 0);
        if ($student_id > 0) {
            $db->prepare('DELETE FROM studente_classe WHERE studente_id = ? AND classe_id = ?')
               ->execute([$student_id, $classe_id]);
            $success = 'Studente rimosso dalla classe.';
        }
    } elseif ($action === 'sposta_studente') {
        $student_id = (int)($_POST['student_id'] ?? 0);
        $nuova_classe = (int)($_POST['nuova_classe'] ?? 0);
        if ($student_id <= 0 || $nuova_classe <= 0) {
            $errors[] = 'Dati mancanti per lo spostamento.';
        } elseif ($nuova_classe === $classe_id) {
            $errors[] = 'Lo studente è già in questa classe.';
        } else {
            $db->prepare('UPDATE studente_classe SET classe_id = ? WHERE studente_id = ? AND classe_id = ?')
               ->execute([$nuova_classe, $student_id, $classe_id]);
            $success = 'Studente spostato.';
        }
    } else {
        $errors[] = 'Azione non valida.';
    }
}

$studenti = getStudentiByClasse($classe_id);
$tuttiStudenti = $db->query('SELECT * FROM users WHERE ruolo = "studente" ORDER BY cognome, nome')->fetchAll();
$stmtInClasse = $db->prepare('SELECT studente_id FROM studente_classe WHERE classe_id = ?');
$stmtInClasse->execute([$classe_id]);
$idInClasse = array_column($stmtInClasse->fetchAll(), 'studente_id');
$studentiDisponibili = array_filter($tuttiStudenti, fn($s) => !in_array($s['id'], $idInClasse));
$altreClassi = array_filter(getAllClassi(), fn($c) => $c['id'] != $classe_id);
$rushes = getRushByClasse($classe_id);

$pageTitle = $classe['anno'] . $classe['sezione'] . ' ' . $classe['indirizzo'];
require_once __DIR__ . '/../includes/header.php';
?>
<main class="container">
    <div class="breadcrumb">
        <a href="/CodeRush/pages/classi.php">Classi</a>
        <span class="breadcrumb-sep">/</span>
        <span><?= sanitize($classe['anno'] . $classe['sezione'] . ' ' . $classe['indirizzo']) ?></span>
    </div>

    <div class="page-header">
        <h1><?= sanitize($classe['anno'] . $classe['sezione'] . ' ' . $classe['indirizzo']) ?></h1>
        <a href="/CodeRush/pages/classi.php?edit=<?= $classe_id ?>" class="btn btn-outline btn-sm">Modifica classe</a>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger"><?= implode('<br>', array_map('sanitize', $errors)) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?= sanitize($success) ?></div>
    <?php endif; ?>

    <div class="card" style="padding: 0;">
        <div class="card-title" style="padding: 16px 20px; margin: 0; display: flex; justify-content: space-between; align-items: center;">
            <span>Studenti (<?= count($studenti) ?>)</span>
        </div>
        <?php if (empty($studenti)): ?>
            <div class="empty-state"><p>Nessuno studente in questa classe.</p></div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Studente</th>
                    <th>Matricola</th>
                    <th style="width: 260px;">Azioni</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($studenti as $s): ?>
                <tr>
                    <td>
                        <a href="/CodeRush/pages/studente.php?id=<?= $s['id'] ?>">
                            <?= sanitize($s['cognome'] . ' ' . $s['nome']) ?>
                        </a>
                    </td>
                    <td style="font-family: monospace;"><?= sanitize($s['login_id']) ?></td>
                    <td>
                        <?php if (!empty($altreClassi)): ?>
                        <form method="POST" style="display: inline-flex; gap: 4px; align-items: center;">
                            <input type="hidden" name="action" value="sposta_studente">
                            <input type="hidden" name="student_id" value="<?= $s['id'] ?>">
                            <select name="nuova_classe" class="form-control" style="width: auto; padding: 3px 8px; font-size: 12px;">
                                <?php foreach ($altreClassi as $ac): ?>
                                    <option value="<?= $ac['id'] ?>"><?= sanitize($ac['anno'] . $ac['sezione'] . ' ' . $ac['indirizzo']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-sm btn-outline">Sposta</button>
                        </form>
                        <?php endif; ?>
                        <form method="POST" style="display: inline;" onsubmit="return confirm('Rimuovere lo studente dalla classe?')">
                            <input type="hidden" name="action" value="rimuovi_studente">
                            <input type="hidden" name="student_id" value="<?= $s['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-danger">Rimuovi</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <?php if (!empty($studentiDisponibili)): ?>
    <div class="card">
        <div class="card-title">Aggiungi studente esistente</div>
        <form method="POST" style="display: flex; gap: 10px; flex-wrap: wrap;">
            <input type="hidden" name="action" value="aggiungi_studente">
            <select name="student_id" class="form-control" style="flex: 1; max-width: 360px;" required>
                <option value="0">— Seleziona studente —</option>
                <?php foreach ($studentiDisponibili as $s): ?>
                    <option value="<?= $s['id'] ?>"><?= sanitize($s['cognome'] . ' ' . $s['nome'] . ' (' . $s['login_id'] . ')') ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary">Aggiungi</button>
        </form>
        <p style="font-size: 13px; color: var(--text-muted); margin-top: 10px;">
            Per creare un nuovo studente: <a href="/CodeRush/pages/registra.php">Registra utente</a>
        </p>
    </div>
    <?php else: ?>
    <div class="card">
        <p style="color: var(--text-muted);">Tutti gli studenti registrati sono già in questa classe.</p>
        <p style="font-size: 13px; margin-top: 8px;"><a href="/CodeRush/pages/registra.php">Crea un nuovo studente</a></p>
    </div>
    <?php endif; ?>

    <?php if (!empty($rushes)): ?>
    <div class="card" style="padding: 0;">
        <div class="card-title" style="padding: 16px 20px; margin: 0;">Rush completati</div>
        <table>
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Consegna</th>
                    <th>Host</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rushes as $r): ?>
                <tr>
                    <td style="color: var(--text-muted);"><?= date('d/m/Y H:i', strtotime($r['created_at'])) ?></td>
                    <td><?= sanitize($r['domanda_nome']) ?></td>
                    <td><?= sanitize($r['host_nome'] . ' ' . $r['host_cognome']) ?></td>
                    <td><a href="/CodeRush/pages/rush-detail.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-primary">Dettagli</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</main>
</body>
</html>
<script src="/CodeRush/js/script.js"></script>
