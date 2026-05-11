<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';
if (!isHost()) { header('Location: /CodeRush/login.php'); exit(); }

$search       = trim($_GET['search']    ?? '');
$linguaggio_id = (int)($_GET['linguaggio'] ?? 0);

$domande   = getDomandeByHost($_SESSION['user_id'], $search, $linguaggio_id);
$linguaggi = getAllLinguaggi();

$pageTitle = 'Consegne';
require_once __DIR__ . '/../includes/header.php';
<link rel="stylesheet" href="/CodeRush/css/pages/consegne.css">
?>
<main class="container">

    <div class="breadcrumb page-section-breadcrumb">
        <a href="/CodeRush/">Home</a>
        <span class="breadcrumb-sep">›</span>
        <span>Consegne</span>
    </div>

    <div class="page-header page-section-header">
        <div>
            <h1>Consegne</h1>
            <p class="page-subtitle"><?= count($domande) ?> consegna<?= count($domande) !== 1 ? 'e' : '' ?> trovata<?= count($domande) !== 1 ? 'e' : '' ?></p>
        </div>
        <a href="/CodeRush/pages/nuova-domanda.php" class="btn-primary-lg">+ Nuova consegna</a>
    </div>

    <!-- Search bar -->
    <form method="GET" class="filter-row page-section-content">
        <input
            type="text"
            name="search"
            class="input-arena filter-input"
            placeholder="🔍  Cerca per nome..."
            value="<?= sanitize($search) ?>"
        >
        <select name="linguaggio" class="input-arena filter-select">
            <option value="0">Tutti i linguaggi</option>
            <?php foreach ($linguaggi as $lang): ?>
            <option value="<?= $lang['id'] ?>" <?= $linguaggio_id == $lang['id'] ? 'selected' : '' ?>>
                <?= sanitize($lang['nome']) ?>
            </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn-primary-lg">Filtra</button>
        <?php if ($search || $linguaggio_id > 0): ?>
        <a href="/CodeRush/pages/consegne.php" class="btn-ghost">Reimposta</a>
        <?php endif; ?>
    </form>

    <div class="card card-no-pad page-section-secondary">
        <?php if (empty($domande)): ?>
        <div class="empty-state">
            <p>Nessuna consegna trovata.</p>
            <a href="/CodeRush/pages/nuova-domanda.php" class="btn-primary-lg">Crea la prima consegna</a>
        </div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Linguaggio</th>
                    <th>Difficoltà</th>
                    <th class="col-100"></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($domande as $d): ?>
            <tr>
                <td>
                    <div class="table-cell-name"><?= sanitize($d['nome']) ?></div>
                    <div class="table-desc">
                        <?= sanitize($d['testo']) ?>
                    </div>
                </td>
                <td><span class="badge badge-host"><?= sanitize($d['linguaggio_nome']) ?></span></td>
                <td>
                    <?php if ($d['difficolta'] === null): ?>
                    <span class="text-muted-td">—</span>
                    <?php elseif ($d['difficolta'] == 0): ?>
                    <span class="badge badge-facile">Facile</span>
                    <?php else: ?>
                    <span class="badge badge-difficile">Difficile</span>
                    <?php endif; ?>
                </td>
                <td>
                    <a href="/CodeRush/pages/nuova-domanda.php?id=<?= $d['id'] ?>" class="btn btn-sm btn-outline">Modifica</a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <p class="page-footer-note">
        <a href="/CodeRush/pages/linguaggi.php" class="link-muted">Gestisci linguaggi →</a>
    </p>
</main>
<script src="/CodeRush/js/script.js"></script>
</body>
</html>
