<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';
if (!isHost()) { header('Location: /CodeRush/login.php'); exit(); }

$classe_id = isset($_GET['classe_id']) ? (int)$_GET['classe_id'] : 0;
$classe    = $classe_id > 0 ? getClasseById($classe_id) : null;
if (!$classe) { header('Location: /CodeRush/pages/classi.php'); exit(); }

$rushes    = getRushByClasse($classe_id);
$pageTitle = 'Rush — '.$classe['anno'].$classe['sezione'].' '.$classe['indirizzo'];
require_once __DIR__ . '/../includes/header.php';
?>
<main class="container">

    <div class="breadcrumb" style="animation:fade-up .35s ease-out both;">
        <a href="/CodeRush/">Home</a>
        <span class="breadcrumb-sep">›</span>
        <a href="/CodeRush/pages/classi.php">Classi</a>
        <span class="breadcrumb-sep">›</span>
        <a href="/CodeRush/pages/classe.php?id=<?= $classe_id ?>"><?= sanitize($classe['anno'].$classe['sezione'].' '.$classe['indirizzo']) ?></a>
        <span class="breadcrumb-sep">›</span>
        <span>Rush</span>
    </div>

    <div class="page-header" style="animation:fade-up .4s ease-out both;">
        <div>
            <h1>Rush — <?= sanitize($classe['anno'].$classe['sezione'].' '.$classe['indirizzo']) ?></h1>
            <p class="page-subtitle"><?= count($rushes) ?> rush completat<?= count($rushes) !== 1 ? 'i' : 'o' ?></p>
        </div>
        <a href="/CodeRush/pages/rush.php" class="btn-primary-lg">▶ Nuovo Rush</a>
    </div>

    <?php if (empty($rushes)): ?>
    <div class="empty-state" style="animation:fade-up .45s ease-out .05s both;">
        <p>Nessun Rush completato per questa classe.</p>
        <a href="/CodeRush/pages/rush.php" class="btn-primary-lg">Avvia il primo Rush</a>
    </div>
    <?php else: ?>
    <div class="card card-no-pad" style="animation:fade-up .45s ease-out .05s both;">
        <table>
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Consegna</th>
                    <th>Host</th>
                    <th style="width:140px;"></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rushes as $r): ?>
            <tr>
                <td style="color:var(--muted-foreground);font-size:12px;white-space:nowrap;"><?= date('d/m/Y H:i', strtotime($r['created_at'])) ?></td>
                <td style="font-weight:600;"><?= sanitize($r['domanda_nome']) ?></td>
                <td style="color:var(--muted-foreground);"><?= sanitize($r['host_nome'].' '.$r['host_cognome']) ?></td>
                <td>
                    <div style="display:flex;gap:6px;">
                        <a href="/CodeRush/pages/risultati.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-outline">Risultati</a>
                        <a href="/CodeRush/pages/rush-detail.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-primary">Analisi</a>
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
