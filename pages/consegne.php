<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';

if (!isHost()) {
    header('Location: /CodeRush/login.php');
    exit();
}

$search = trim($_GET['search'] ?? '');
$linguaggio_id = (int)($_GET['linguaggio'] ?? 0);

$domande = getDomandeByHost($_SESSION['user_id'], $search, $linguaggio_id);
$linguaggi = getAllLinguaggi();

$pageTitle = 'Consegne';
require_once __DIR__ . '/../includes/header.php';
?>
<main class="container">
    <div class="page-header">
        <h1>Consegne</h1>
        <a href="/CodeRush/pages/nuova-domanda.php" class="btn btn-primary">+ Nuova</a>
    </div>

    <form method="GET" class="search-bar">
        <input
            type="text"
            name="search"
            class="form-control"
            placeholder="Cerca per nome..."
            value="<?= sanitize($search) ?>"
        >
        <select name="linguaggio" class="form-control" style="max-width: 200px;">
            <option value="0">Tutti i linguaggi</option>
            <?php foreach ($linguaggi as $lang): ?>
                <option value="<?= $lang['id'] ?>" <?= $linguaggio_id == $lang['id'] ? 'selected' : '' ?>>
                    <?= sanitize($lang['nome']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-secondary">Filtra</button>
        <?php if ($search || $linguaggio_id > 0): ?>
            <a href="/CodeRush/pages/consegne.php" class="btn btn-outline">Reimposta</a>
        <?php endif; ?>
    </form>

    <div class="card" style="padding: 0;">
        <?php if (empty($domande)): ?>
            <div class="empty-state">
                <p>Nessuna consegna trovata.</p>
                <a href="/CodeRush/pages/nuova-domanda.php" class="btn btn-primary">Crea la prima consegna</a>
            </div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Linguaggio</th>
                    <th>Difficoltà</th>
                    <th style="width: 120px;">Azioni</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($domande as $d): ?>
                <tr>
                    <td>
                        <strong><?= sanitize($d['nome']) ?></strong>
                        <div style="font-size: 12px; color: var(--text-muted); margin-top: 2px; max-width: 400px; overflow: hidden; white-space: nowrap; text-overflow: ellipsis;">
                            <?= sanitize($d['testo']) ?>
                        </div>
                    </td>
                    <td><span class="badge badge-host"><?= sanitize($d['linguaggio_nome']) ?></span></td>
                    <td>
                        <?php if ($d['difficolta'] === null): ?>
                            <span style="color: var(--text-muted);">—</span>
                        <?php elseif ($d['difficolta'] == 0): ?>
                            <span class="badge badge-facile">Facile</span>
                        <?php else: ?>
                            <span class="badge badge-difficile">Difficile</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="/CodeRush/pages/nuova-domanda.php?id=<?= $d['id'] ?>" class="btn btn-sm btn-outline">Edit</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <p style="font-size: 13px; color: var(--text-muted);">
        <?= count($domande) ?> consegna<?= count($domande) !== 1 ? 'e' : '' ?> trovata<?= count($domande) !== 1 ? 'e' : '' ?>.
        &nbsp;|&nbsp;
        <a href="/CodeRush/pages/linguaggi.php">Gestisci linguaggi</a>
    </p>
</main>
</body>
</html>
<script src="/CodeRush/js/script.js"></script>
