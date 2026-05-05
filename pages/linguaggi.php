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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $nome = trim($_POST['nome'] ?? '');
        if (empty($nome)) {
            $errors[] = 'Nome obbligatorio.';
        } elseif (strlen($nome) > 100) {
            $errors[] = 'Nome troppo lungo (max 100 caratteri).';
        } else {
            $chk = $db->prepare('SELECT id FROM linguaggi WHERE nome = ?');
            $chk->execute([$nome]);
            if ($chk->fetch()) {
                $errors[] = 'Linguaggio già esistente.';
            } else {
                $db->prepare('INSERT INTO linguaggi (nome) VALUES (?)')->execute([$nome]);
                $success = 'Linguaggio "' . sanitize($nome) . '" aggiunto.';
            }
        }
    } elseif ($action === 'edit') {
        $id = (int)($_POST['id'] ?? 0);
        $nome = trim($_POST['nome'] ?? '');
        if (empty($nome)) {
            $errors[] = 'Nome obbligatorio.';
        } elseif ($id <= 0) {
            $errors[] = 'ID non valido.';
        } else {
            $chk = $db->prepare('SELECT id FROM linguaggi WHERE nome = ? AND id != ?');
            $chk->execute([$nome, $id]);
            if ($chk->fetch()) {
                $errors[] = 'Nome già in uso da un altro linguaggio.';
            } else {
                $db->prepare('UPDATE linguaggi SET nome = ? WHERE id = ?')->execute([$nome, $id]);
                $success = 'Linguaggio aggiornato.';
            }
        }
    } else {
        $errors[] = 'Azione non valida.';
    }
}

$linguaggi = getAllLinguaggi();
$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$editLang = null;
if ($editId > 0) {
    foreach ($linguaggi as $l) {
        if ($l['id'] === $editId) {
            $editLang = $l;
        }
    }
}

$pageTitle = 'Linguaggi di programmazione';
require_once __DIR__ . '/../includes/header.php';
?>
<main class="container">
    <div class="page-header">
        <h1>Linguaggi di programmazione</h1>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger"><?= implode('<br>', array_map('sanitize', $errors)) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?= sanitize($success) ?></div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 1fr 340px; gap: 24px; align-items: start;">
        <div class="card">
            <div class="card-title">Linguaggi disponibili (<?= count($linguaggi) ?>)</div>
            <?php if (empty($linguaggi)): ?>
                <div class="empty-state"><p>Nessun linguaggio. Aggiungine uno.</p></div>
            <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nome</th>
                        <th style="width: 100px;">Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($linguaggi as $i => $lang): ?>
                    <tr>
                        <td style="color: var(--text-muted);"><?= $i + 1 ?></td>
                        <td><?= sanitize($lang['nome']) ?></td>
                        <td>
                            <a href="?edit=<?= $lang['id'] ?>" class="btn btn-sm btn-outline">Edit</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <div>
            <?php if ($editLang): ?>
            <div class="card">
                <div class="card-title">Modifica linguaggio</div>
                <form method="POST" novalidate>
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" value="<?= $editLang['id'] ?>">
                    <div class="form-group">
                        <label class="form-label">Nome</label>
                        <input type="text" name="nome" class="form-control" value="<?= sanitize($editLang['nome']) ?>" required maxlength="100">
                    </div>
                    <div style="display: flex; gap: 8px;">
                        <button type="submit" class="btn btn-primary">Salva</button>
                        <a href="/CodeRush/pages/linguaggi.php" class="btn btn-secondary">Annulla</a>
                    </div>
                </form>
            </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-title">Aggiungi linguaggio</div>
                <form method="POST" novalidate id="formLang">
                    <input type="hidden" name="action" value="create">
                    <div class="form-group">
                        <label class="form-label">Nome</label>
                        <input type="text" name="nome" class="form-control" placeholder="es. TypeScript" required maxlength="100">
                        <div class="error-text" id="err-nome"></div>
                    </div>
                    <button type="submit" class="btn btn-primary">Aggiungi</button>
                </form>
            </div>
        </div>
    </div>
</main>
</body>
</html>
<script src="/CodeRush/js/script.js"></script>
