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

$indirizzi = ['Informatica','Meccanica','Elettronica','Chimica','Costruzioni','Logistica','Turismo','Agraria','Grafica','Moda'];
$sezioni   = range('A', 'K');

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
<main class="container">

    <div class="breadcrumb" style="animation:fade-up .35s ease-out both;">
        <a href="/CodeRush/">Home</a>
        <span class="breadcrumb-sep">›</span>
        <span>Classi</span>
    </div>

    <div class="page-header" style="animation:fade-up .4s ease-out both;">
        <div>
            <h1>Classi</h1>
            <p class="page-subtitle"><?= count($classi) ?> classe<?= count($classi) !== 1 ? 'i' : '' ?> registrata<?= count($classi) !== 1 ? 'e' : '' ?></p>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger" style="animation:fade-up .3s ease-out both;"><?= implode('<br>', array_map('sanitize', $errors)) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
    <div class="alert alert-success" style="animation:fade-up .3s ease-out both;"><?= sanitize($success) ?></div>
    <?php endif; ?>

    <div style="display:grid;grid-template-columns:1fr 340px;gap:24px;align-items:start;" class="classi-layout">

        <!-- Table -->
        <div class="card card-no-pad" style="animation:fade-up .45s ease-out .05s both;">
            <div style="padding:16px 20px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;">
                <span style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--muted-foreground);">Classi registrate</span>
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
                        <th style="width:140px;"></th>
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
                        <span style="font-weight:800;font-family:'JetBrains Mono',monospace;"><?= $cl['anno'].$cl['sezione'] ?></span>
                    </td>
                    <td>
                        <span style="font-size:12px;padding:3px 10px;border-radius:20px;background:rgba(74,143,212,.1);color:var(--brand-blue);font-weight:700;"><?= sanitize($cl['indirizzo']) ?></span>
                    </td>
                    <td>
                        <span style="font-weight:700;color:var(--brand-green);"><?= $nStudenti ?></span>
                        <span style="color:var(--muted-foreground);font-size:12px;"> studenti</span>
                    </td>
                    <td>
                        <div style="display:flex;gap:6px;">
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
        <div style="display:flex;flex-direction:column;gap:20px;animation:fade-up .45s ease-out .1s both;">

            <?php if ($editClasse): ?>
            <div class="card" style="border-color:rgba(74,143,212,.4);">
                <p style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--brand-blue);margin-bottom:16px;">Modifica classe</p>
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
                    <div style="display:flex;gap:8px;margin-top:4px;">
                        <button type="submit" class="btn-primary-lg" style="flex:1;justify-content:center;">Salva</button>
                        <a href="/CodeRush/pages/classi.php" class="btn-ghost">Annulla</a>
                    </div>
                </form>
            </div>
            <?php endif; ?>

            <div class="card">
                <p style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--muted-foreground);margin-bottom:16px;">Crea nuova classe</p>
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
                    <button type="submit" class="btn-primary-lg btn-block" style="margin-top:4px;">Crea classe</button>
                </form>
            </div>
        </div>
    </div>
</main>
<style>
@media (max-width:800px) { .classi-layout { grid-template-columns:1fr !important; } }
</style>
<script src="/CodeRush/js/script.js"></script>
</body>
</html>
