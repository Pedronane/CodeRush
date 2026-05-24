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
<link rel="stylesheet" href="/CodeRush/css/pages/rushes.css">
<main class="container">

    <div class="breadcrumb page-section-breadcrumb">
        <a href="/CodeRush/">Home</a>
        <span class="breadcrumb-sep">›</span>
        <a href="/CodeRush/pages/classi.php">Classi</a>
        <span class="breadcrumb-sep">›</span>
        <a href="/CodeRush/pages/classe.php?id=<?= $classe_id ?>"><?= sanitize($classe['anno'].$classe['sezione'].' '.$classe['indirizzo']) ?></a>
        <span class="breadcrumb-sep">›</span>
        <span>Rush</span>
    </div>

    <div class="page-header page-section-header">
        <div>
            <h1>Rush — <?= sanitize($classe['anno'].$classe['sezione'].' '.$classe['indirizzo']) ?></h1>
            <p class="page-subtitle"><?= count($rushes) ?> rush completat<?= count($rushes) !== 1 ? 'i' : 'o' ?></p>
        </div>
        <a href="/CodeRush/pages/rush.php" class="btn-primary-lg">▶ Nuovo Rush</a>
    </div>

    <?php if (empty($rushes)): ?>
    <div class="empty-state page-section-content">
        <p>Nessun Rush completato per questa classe.</p>
        <a href="/CodeRush/pages/rush.php" class="btn-primary-lg">Avvia il primo Rush</a>
    </div>
    <?php else: ?>
    <div class="card card-no-pad page-section-content">
        <table>
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Consegna</th>
                    <th>Host</th>
                    <th class="col-140"></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rushes as $r): ?>
            <tr>
                <td class="td-date"><?= date('d/m/Y H:i', strtotime($r['created_at'])) ?></td>
                <td class="td-name"><?= sanitize($r['domanda_nome']) ?></td>
                <td class="td-muted"><?= sanitize($r['host_nome'].' '.$r['host_cognome']) ?></td>
                <td>
                    <div class="button-group">
                        <a href="/CodeRush/pages/risultati.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-primary">Risultati →</a>
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
