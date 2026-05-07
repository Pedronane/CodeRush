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
?>
<main class="container">

    <div class="breadcrumb" style="animation:fade-up .35s ease-out both;">
        <a href="/CodeRush/">Home</a>
        <span class="breadcrumb-sep">›</span>
        <span>Consegne</span>
    </div>

    <div class="page-header" style="animation:fade-up .4s ease-out both;">
        <div>
            <h1>Consegne</h1>
            <p class="page-subtitle"><?= count($domande) ?> consegna<?= count($domande) !== 1 ? 'e' : '' ?> trovata<?= count($domande) !== 1 ? 'e' : '' ?></p>
        </div>
        <a href="/CodeRush/pages/nuova-domanda.php" class="btn-primary-lg">+ Nuova consegna</a>
    </div>

    <!-- Search bar -->
    <form method="GET" style="display:flex;gap:10px;margin-bottom:24px;flex-wrap:wrap;animation:fade-up .45s ease-out .05s both;">
        <input
            type="text"
            name="search"
            class="input-arena"
            placeholder="🔍  Cerca per nome..."
            value="<?= sanitize($search) ?>"
            style="flex:1;min-width:220px;"
        >
        <select name="linguaggio" class="input-arena" style="width:auto;min-width:180px;">
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

    <div class="card card-no-pad" style="animation:fade-up .45s ease-out .1s both;">
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
                    <th style="width:100px;"></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($domande as $d): ?>
            <tr>
                <td>
                    <div style="font-weight:700;"><?= sanitize($d['nome']) ?></div>
                    <div style="font-size:12px;color:var(--muted-foreground);margin-top:2px;max-width:420px;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;">
                        <?= sanitize($d['testo']) ?>
                    </div>
                </td>
                <td><span class="badge badge-host"><?= sanitize($d['linguaggio_nome']) ?></span></td>
                <td>
                    <?php if ($d['difficolta'] === null): ?>
                    <span style="color:var(--muted-foreground);">—</span>
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

    <p style="font-size:12px;color:var(--muted-foreground);margin-top:12px;animation:fade-up .4s ease-out .2s both;">
        <a href="/CodeRush/pages/linguaggi.php" style="color:var(--muted-foreground);text-decoration:underline;text-underline-offset:3px;">Gestisci linguaggi →</a>
    </p>
</main>
<script src="/CodeRush/js/script.js"></script>
</body>
</html>
