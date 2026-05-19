<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';
if (!isHost()) { header('Location: /CodeRush/login.php'); exit(); }

$db = getDB();
$errors  = [];
$success = '';
$editId  = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$editClasse = null;
if ($editId > 0) {
    $editClasse = getClasseById($editId);
    if (!$editClasse) $editId = 0;
}

$indirizzi = ['Informatica','Grafica','Meccanica','Telecomunicazioni'];
$sezioni   = range('A', 'C');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action   = $_POST['action'] ?? '';
    $anno     = (int)($_POST['anno'] ?? 0);
    $sezione  = strtoupper(trim($_POST['sezione']  ?? ''));
    $indirizzo = trim($_POST['indirizzo'] ?? '');

    if ($anno < 1 || $anno > 5)                                            $errors[] = 'Anno deve essere tra 1 e 5.';
    if (empty($sezione) || !in_array($sezione, array_map('strtoupper', $sezioni))) $errors[] = 'Sezione non valida.';
    if (empty($indirizzo))                                                  $errors[] = 'Indirizzo obbligatorio.';

    if (empty($errors)) {
        if ($action === 'edit' && $editId > 0) {
            $chk = $db->prepare('SELECT id FROM classi WHERE anno=? AND sezione=? AND indirizzo=? AND id!=?');
            $chk->execute([$anno,$sezione,$indirizzo,$editId]);
            if ($chk->fetch()) {
                $errors[] = 'Questa combinazione esiste già.';
            } else {
                $db->prepare('UPDATE classi SET anno=?,sezione=?,indirizzo=? WHERE id=?')->execute([$anno,$sezione,$indirizzo,$editId]);
                $success = 'Classe aggiornata.';
                $editId = 0; $editClasse = null;
            }
        } else {
            $chk = $db->prepare('SELECT id FROM classi WHERE anno=? AND sezione=? AND indirizzo=?');
            $chk->execute([$anno,$sezione,$indirizzo]);
            if ($chk->fetch()) {
                $errors[] = 'Questa classe esiste già.';
            } else {
                $db->prepare('INSERT INTO classi (anno,sezione,indirizzo) VALUES (?,?,?)')->execute([$anno,$sezione,$indirizzo]);
                $success = 'Classe '.$anno.$sezione.' '.sanitize($indirizzo).' creata.';
            }
        }
    }
}

$classi    = getAllClassi();
$pageTitle = 'Classi';
require_once __DIR__ . '/../includes/header.php';
?>
<link rel="stylesheet" href="/CodeRush/css/pages/classi.css">
<main class="container">

    <div class="breadcrumb page-section-breadcrumb">
        <a href="/CodeRush/">Home</a>
        <span class="breadcrumb-sep">›</span>
        <span>Classi</span>
    </div>

    <div class="page-header page-section-header">
        <div>
            <h1>Classi</h1>
            <p class="page-subtitle"><?= count($classi) ?> classe<?= count($classi) !== 1 ? 'i' : '' ?> registrata<?= count($classi) !== 1 ? 'e' : '' ?></p>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger page-section-alert"><?= implode('<br>', array_map('sanitize', $errors)) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
    <div class="alert alert-success page-section-alert"><?= sanitize($success) ?></div>
    <?php endif; ?>

    <div class="classi-layout">

        <!-- Table -->
        <div class="card card-no-pad page-section-content">
            <div class="card-header">
                <span class="card-header-label">Classi registrate</span>
                <span class="badge badge-host"><?= count($classi) ?></span>
            </div>
            <?php if (empty($classi)): ?>
            <div class="empty-state">
                <p>Nessuna classe. Creane una a destra.</p>
            </div>
            <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Classe</th>
                        <th>Indirizzo</th>
                        <th>Studenti</th>
                        <th class="col-actions"></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($classi as $cl):
                    $stmtCount = $db->prepare('SELECT COUNT(*) FROM studente_classe WHERE classe_id = ?');
                    $stmtCount->execute([$cl['id']]);
                    $nStudenti = $stmtCount->fetchColumn();
                ?>
                <tr>
                    <td>
                        <span class="classi-code-label"><?= $cl['anno'].$cl['sezione'] ?></span>
                    </td>
                    <td>
                        <span class="badge-indirizzo"><?= sanitize($cl['indirizzo']) ?></span>
                    </td>
                    <td>
                        <span class="count-green"><?= $nStudenti ?></span>
                        <span class="text-muted"> studenti</span>
                    </td>
                    <td>
                        <div class="button-group">
                            <a href="/CodeRush/pages/classe.php?id=<?= $cl['id'] ?>" class="btn btn-sm btn-primary">Apri</a>
                            <a href="?edit=<?= $cl['id'] ?>" class="btn btn-sm btn-outline">Modifica</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <!-- Forms -->
        <div class="sidebar-stack page-section-secondary">

            <?php if ($editClasse): ?>
            <div class="card card-accent-blue">
                <p class="section-label-blue">Modifica classe</p>
                <form method="POST" novalidate>
                    <input type="hidden" name="action" value="edit">
                    <div class="form-group">
                        <label class="form-label">Anno</label>
                        <select name="anno" class="input-arena" required>
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                            <option value="<?= $i ?>" <?= $editClasse['anno'] == $i ? 'selected' : '' ?>><?= $i ?>° anno</option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Sezione</label>
                            <select name="sezione" class="input-arena" required>
                                <?php foreach ($sezioni as $s): ?>
                                <option value="<?= $s ?>" <?= $editClasse['sezione'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Indirizzo</label>
                            <select name="indirizzo" class="input-arena" required>
                                <?php foreach ($indirizzi as $ind): ?>
                                <option value="<?= $ind ?>" <?= $editClasse['indirizzo'] === $ind ? 'selected' : '' ?>><?= $ind ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-button-row mt-4">
                        <button type="submit" class="btn-primary-lg btn-flex">Salva</button>
                        <a href="/CodeRush/pages/classi.php" class="btn-ghost">Annulla</a>
                    </div>
                </form>
            </div>
            <?php endif; ?>

            <div class="card">
                <p class="card-header-label">Crea nuova classe</p>
                <form method="POST" novalidate>
                    <input type="hidden" name="action" value="create">
                    <div class="form-group">
                        <label class="form-label">Anno</label>
                        <select name="anno" class="input-arena" required>
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                            <option value="<?= $i ?>"><?= $i ?>° anno</option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Sezione</label>
                            <select name="sezione" class="input-arena" required>
                                <?php foreach ($sezioni as $s): ?>
                                <option value="<?= $s ?>"><?= $s ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Indirizzo</label>
                            <select name="indirizzo" class="input-arena" required>
                                <?php foreach ($indirizzi as $ind): ?>
                                <option value="<?= $ind ?>"><?= $ind ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="btn-primary-lg btn-block mt-4">Crea classe</button>
                </form>
            </div>
        </div>
    </div>
</main>
<script src="/CodeRush/js/script.js"></script>
</body>
</html>
