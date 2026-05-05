<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';

if (!isHost()) {
    header('Location: /CodeRush/login.php');
    exit();
}

$classe_id = isset($_GET['classe_id']) ? (int)$_GET['classe_id'] : 0;
$classe = $classe_id > 0 ? getClasseById($classe_id) : null;

if (!$classe) {
    header('Location: /CodeRush/pages/classi.php');
    exit();
}

$rushes = getRushByClasse($classe_id);

$pageTitle = 'Rush — ' . $classe['anno'] . $classe['sezione'] . ' ' . $classe['indirizzo'];
require_once __DIR__ . '/../includes/header.php';
?>
<main class="container">
    <div class="breadcrumb">
        <a href="/CodeRush/pages/classi.php">Classi</a>
        <span class="breadcrumb-sep">/</span>
        <a href="/CodeRush/pages/classe.php?id=<?= $classe_id ?>"><?= sanitize($classe['anno'] . $classe['sezione'] . ' ' . $classe['indirizzo']) ?></a>
        <span class="breadcrumb-sep">/</span>
        <span>Rush</span>
    </div>

    <div class="page-header">
        <h1>Rush — <?= sanitize($classe['anno'] . $classe['sezione'] . ' ' . $classe['indirizzo']) ?></h1>
        <a href="/CodeRush/pages/rush.php" class="btn btn-primary">Nuovo Rush</a>
    </div>

    <?php if (empty($rushes)): ?>
        <div class="empty-state">
            <p>Nessun Rush completato per questa classe.</p>
            <a href="/CodeRush/pages/rush.php" class="btn btn-primary">Avvia il primo Rush</a>
        </div>
    <?php else: ?>
    <div class="card" style="padding: 0;">
        <table>
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Consegna</th>
                    <th>Host</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rushes as $r): ?>
                <tr>
                    <td style="color: var(--text-muted); white-space: nowrap;"><?= date('d/m/Y H:i', strtotime($r['created_at'])) ?></td>
                    <td><?= sanitize($r['domanda_nome']) ?></td>
                    <td><?= sanitize($r['host_nome'] . ' ' . $r['host_cognome']) ?></td>
                    <td><a href="/CodeRush/pages/rush-detail.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-primary">Dettagli</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</main>
</body>
</html>
