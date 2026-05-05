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
$editId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$domanda = null;
$isEdit = false;

if ($editId > 0) {
    $domanda = getDomandaById($editId);
    if ($domanda && $domanda['host_id'] == $_SESSION['user_id']) {
        $isEdit = true;
    } else {
        header('Location: /CodeRush/pages/consegne.php');
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $testo = trim($_POST['testo'] ?? '');
    $linguaggio_id = (int)($_POST['linguaggio_id'] ?? 0);
    $difficolta_raw = $_POST['difficolta'] ?? '';
    $difficolta = ($difficolta_raw === '' || $difficolta_raw === 'null') ? null : (int)$difficolta_raw;

    if (empty($nome)) {
        $errors[] = 'Nome obbligatorio.';
    }
    if (empty($testo)) {
        $errors[] = 'Testo della consegna obbligatorio.';
    }
    if ($linguaggio_id <= 0) {
        $errors[] = 'Seleziona un linguaggio.';
    }

    if (empty($errors)) {
        if ($isEdit) {
            $db->prepare(
                'UPDATE domande SET nome = ?, testo = ?, linguaggio_id = ?, difficolta = ? WHERE id = ?'
            )->execute([$nome, $testo, $linguaggio_id, $difficolta, $editId]);
            $success = 'Consegna aggiornata.';
            $domanda = getDomandaById($editId);
        } else {
            $db->prepare(
                'INSERT INTO domande (nome, testo, linguaggio_id, difficolta, host_id) VALUES (?, ?, ?, ?, ?)'
            )->execute([$nome, $testo, $linguaggio_id, $difficolta, $_SESSION['user_id']]);
            $success = 'Consegna creata con successo.';
            $nome = '';
            $testo = '';
            $linguaggio_id = 0;
            $difficolta = null;
        }
    }
} else {
    $nome = $isEdit ? ($domanda['nome'] ?? '') : '';
    $testo = $isEdit ? ($domanda['testo'] ?? '') : '';
    $linguaggio_id = $isEdit ? ($domanda['linguaggio_id'] ?? 0) : 0;
    $difficolta = $isEdit ? $domanda['difficolta'] : null;
}

$linguaggi = getAllLinguaggi();
$pageTitle = $isEdit ? 'Modifica consegna' : 'Nuova consegna';
require_once __DIR__ . '/../includes/header.php';
?>
<main class="container">
    <div class="breadcrumb">
        <a href="/CodeRush/pages/consegne.php">Consegne</a>
        <span class="breadcrumb-sep">/</span>
        <span><?= $isEdit ? sanitize($domanda['nome']) : 'Nuova' ?></span>
    </div>

    <div class="page-header">
        <h1><?= $isEdit ? 'Modifica consegna' : 'Nuova consegna' ?></h1>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger"><?= implode('<br>', array_map('sanitize', $errors)) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?= sanitize($success) ?></div>
    <?php endif; ?>

    <div class="card">
        <form method="POST" novalidate id="formDomanda">
            <div class="form-group">
                <label class="form-label">Nome della consegna</label>
                <input type="text" name="nome" class="form-control" value="<?= sanitize($nome) ?>" required maxlength="200" placeholder="es. Funzione fattoriale">
                <div class="error-text" id="err-nome"></div>
            </div>
            <div class="form-group">
                <label class="form-label">Testo della consegna</label>
                <textarea name="testo" class="form-control" rows="8" required placeholder="Scrivi qui la descrizione dettagliata del problema..."><?= sanitize($testo) ?></textarea>
                <div class="error-text" id="err-testo"></div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Linguaggio di programmazione</label>
                    <select name="linguaggio_id" class="form-control" required>
                        <option value="0">— Seleziona —</option>
                        <?php foreach ($linguaggi as $lang): ?>
                            <option value="<?= $lang['id'] ?>" <?= $linguaggio_id == $lang['id'] ? 'selected' : '' ?>>
                                <?= sanitize($lang['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">
                        <a href="/CodeRush/pages/linguaggi.php">Aggiungi nuovo linguaggio</a>
                    </div>
                    <div class="error-text" id="err-lang"></div>
                </div>
                <div class="form-group">
                    <label class="form-label">Difficoltà <span style="color: var(--text-muted);">(facoltativa)</span></label>
                    <select name="difficolta" class="form-control">
                        <option value="null" <?= $difficolta === null ? 'selected' : '' ?>>— Non specificata —</option>
                        <option value="0" <?= $difficolta === 0 ? 'selected' : '' ?>>Facile</option>
                        <option value="1" <?= $difficolta === 1 ? 'selected' : '' ?>>Difficile</option>
                    </select>
                </div>
            </div>
            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Salva modifiche' : 'Crea consegna' ?></button>
                <a href="/CodeRush/pages/consegne.php" class="btn btn-secondary">Annulla</a>
            </div>
        </form>
    </div>
</main>
</body>
</html>
<script src="/CodeRush/js/script.js"></script>
