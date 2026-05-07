<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';
if (!isHost()) { header('Location: /CodeRush/login.php'); exit(); }

$db = getDB();
$errors  = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $nome = trim($_POST['nome'] ?? '');
        if (empty($nome)) {
            $errors[] = 'Nome obbligatorio.';
        } elseif (strlen($nome) > 100) {
            $errors[] = 'Nome troppo lungo (max 100 caratteri).';
        } else {
            $chk = $db->prepare('SELECT id FROM linguaggi WHERE nome=?');
            $chk->execute([$nome]);
            if ($chk->fetch()) {
                $errors[] = 'Linguaggio già esistente.';
            } else {
                $db->prepare('INSERT INTO linguaggi (nome) VALUES (?)')->execute([$nome]);
                $success = 'Linguaggio "'.sanitize($nome).'" aggiunto.';
            }
        }
    } elseif ($action === 'edit') {
        $id   = (int)($_POST['id']   ?? 0);
        $nome = trim($_POST['nome']  ?? '');
        if (empty($nome)) {
            $errors[] = 'Nome obbligatorio.';
        } elseif ($id <= 0) {
            $errors[] = 'ID non valido.';
        } else {
            $chk = $db->prepare('SELECT id FROM linguaggi WHERE nome=? AND id!=?');
            $chk->execute([$nome,$id]);
            if ($chk->fetch()) {
                $errors[] = 'Nome già in uso.';
            } else {
                $db->prepare('UPDATE linguaggi SET nome=? WHERE id=?')->execute([$nome,$id]);
                $success = 'Linguaggio aggiornato.';
            }
        }
    }
}

$linguaggi = getAllLinguaggi();
$editId    = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$editLang  = null;
foreach ($linguaggi as $l) {
    if ($l['id'] === $editId) { $editLang = $l; break; }
}

$pageTitle = 'Linguaggi';
require_once __DIR__ . '/../includes/header.php';
?>
<main class="container">

    <div class="breadcrumb" style="animation:fade-up .35s ease-out both;">
        <a href="/CodeRush/">Home</a>
        <span class="breadcrumb-sep">›</span>
        <a href="/CodeRush/pages/consegne.php">Consegne</a>
        <span class="breadcrumb-sep">›</span>
        <span>Linguaggi</span>
    </div>

    <div class="page-header" style="animation:fade-up .4s ease-out both;">
        <div>
            <h1>Linguaggi di programmazione</h1>
            <p class="page-subtitle"><?= count($linguaggi) ?> linguagg<?= count($linguaggi) !== 1 ? 'i' : 'io' ?> disponibil<?= count($linguaggi) !== 1 ? 'i' : 'e' ?></p>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger" style="animation:fade-up .3s ease-out both;"><?= implode('<br>', array_map('sanitize', $errors)) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
    <div class="alert alert-success" style="animation:fade-up .3s ease-out both;"><?= sanitize($success) ?></div>
    <?php endif; ?>

    <div style="display:grid;grid-template-columns:1fr 320px;gap:24px;align-items:start;" class="lang-layout">

        <div class="card card-no-pad" style="animation:fade-up .45s ease-out .05s both;">
            <div style="padding:14px 20px;border-bottom:1px solid var(--border);">
                <span style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--muted-foreground);">Linguaggi disponibili</span>
            </div>
            <?php if (empty($linguaggi)): ?>
            <div class="empty-state"><p>Nessun linguaggio. Aggiungine uno.</p></div>
            <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nome</th>
                        <th style="width:100px;"></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($linguaggi as $i => $lang): ?>
                <tr>
                    <td style="color:var(--muted-foreground);font-family:'JetBrains Mono',monospace;font-size:12px;"><?= $i + 1 ?></td>
                    <td>
                        <span class="badge badge-host" style="font-size:12px;padding:4px 12px;"><?= sanitize($lang['nome']) ?></span>
                    </td>
                    <td>
                        <a href="?edit=<?= $lang['id'] ?>" class="btn btn-sm btn-outline">Modifica</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <div style="display:flex;flex-direction:column;gap:16px;animation:fade-up .45s ease-out .1s both;">
            <?php if ($editLang): ?>
            <div class="card" style="border-color:rgba(74,143,212,.4);">
                <p style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--brand-blue);margin-bottom:14px;">Modifica linguaggio</p>
                <form method="POST" novalidate>
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" value="<?= $editLang['id'] ?>">
                    <div class="form-group">
                        <label class="form-label">Nome</label>
                        <input type="text" name="nome" class="input-arena" value="<?= sanitize($editLang['nome']) ?>" required maxlength="100">
                    </div>
                    <div style="display:flex;gap:8px;">
                        <button type="submit" class="btn-primary-lg">Salva</button>
                        <a href="/CodeRush/pages/linguaggi.php" class="btn-ghost">Annulla</a>
                    </div>
                </form>
            </div>
            <?php endif; ?>

            <div class="card">
                <p style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--muted-foreground);margin-bottom:14px;">Aggiungi linguaggio</p>
                <form method="POST" novalidate id="formLang">
                    <input type="hidden" name="action" value="create">
                    <div class="form-group">
                        <label class="form-label">Nome</label>
                        <input type="text" name="nome" class="input-arena" placeholder="es. TypeScript" required maxlength="100">
                        <div class="error-text" id="err-nome"></div>
                    </div>
                    <button type="submit" class="btn-primary-lg btn-block">Aggiungi</button>
                </form>
            </div>
        </div>
    </div>
</main>
<style>
@media (max-width:700px) { .lang-layout { grid-template-columns:1fr !important; } }
</style>
<script src="/CodeRush/js/script.js"></script>
</body>
</html>
