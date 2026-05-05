<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn()) {
    header('Location: /CodeRush/login.php');
    exit();
}

$partita_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$partita = $partita_id > 0 ? getPartitaById($partita_id) : null;

if (!$partita) {
    header('Location: /CodeRush/');
    exit();
}

if ($partita['stato'] !== 'finita') {
    header('Location: /CodeRush/pages/game.php?id=' . $partita_id);
    exit();
}

if (isStudent()) {
    $partecipazione = getPartecipazione($partita_id, $_SESSION['user_id']);
    if (!$partecipazione) {
        header('Location: /CodeRush/');
        exit();
    }
}

$pageTitle = 'Fine Rush';
require_once __DIR__ . '/../includes/header.php';
?>
<main class="container">
    <div class="phase-banner phase-finita">Rush completato!</div>

    <div class="page-header">
        <h1>Risultati: <?= sanitize($partita['domanda_nome']) ?></h1>
        <span class="badge badge-finita">Finita</span>
    </div>

    <div class="alert alert-info">
        Classe <?= sanitize($partita['anno'] . $partita['sezione'] . ' ' . $partita['indirizzo']) ?>
        &nbsp;·&nbsp; <?= sanitize($partita['linguaggio_nome']) ?>
        &nbsp;·&nbsp; <?= date('d/m/Y H:i', strtotime($partita['created_at'])) ?>
    </div>

    <?php if (isHost()): ?>
    <div style="text-align: right; margin-bottom: 16px;">
        <a href="/CodeRush/pages/rush-detail.php?id=<?= $partita_id ?>" class="btn btn-primary">Analisi dettagliata</a>
    </div>
    <?php endif; ?>

    <?php
    $partecipazioni = getPartecipazioniByPartita($partita_id);
    $db = getDB();
    $n = count($partecipazioni);
    foreach ($partecipazioni as $part):
        $stmtVal = $db->prepare('SELECT * FROM valutazioni WHERE slot_id = ?');
        $stmtVal->execute([$part['id']]);
        $val = $stmtVal->fetch();
        $stmtLastTurno = $db->prepare(
            'SELECT t.*, u.nome, u.cognome FROM turni t JOIN users u ON u.id = t.studente_id
             WHERE t.slot_id = ? AND t.numero_turno = ?'
        );
        $stmtLastTurno->execute([$part['id'], $n - 1]);
        $lastTurno = $stmtLastTurno->fetch();
    ?>
    <div class="chain-card">
        <div class="chain-header">
            <span>Catena avviata da: <?= sanitize($part['cognome'] . ' ' . $part['nome']) ?></span>
            <?php if ($val): ?>
            <span class="badge badge-<?= $val['voto'] ?>"><?= ucfirst($val['voto']) ?></span>
            <?php else: ?>
            <span class="badge badge-attesa">Valutazione in corso...</span>
            <?php endif; ?>
        </div>

        <?php if ($val): ?>
        <div style="padding: 12px 20px; background: rgba(88,166,255,0.05); border-bottom: 1px solid var(--border);">
            <strong>Feedback AI:</strong> <?= sanitize($val['feedback']) ?>
        </div>
        <?php endif; ?>

        <?php if ($lastTurno && $lastTurno['codice']): ?>
        <div class="chain-turn">
            <div class="chain-turn-meta">
                <span class="turn-number">&#9654;</span>
                <span>Codice finale (scritto da: <?= sanitize($lastTurno['nome'] . ' ' . $lastTurno['cognome']) ?>)</span>
            </div>
            <div class="code-block"><?= sanitize($lastTurno['codice']) ?></div>
        </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>

    <div style="margin-top: 24px; display: flex; gap: 12px;">
        <a href="/CodeRush/" class="btn btn-secondary">Home</a>
        <?php if (isHost()): ?>
        <a href="/CodeRush/pages/rush-detail.php?id=<?= $partita_id ?>" class="btn btn-primary">Analisi completa</a>
        <a href="/CodeRush/pages/classe.php?id=<?= $partita['classe_id'] ?>" class="btn btn-outline">Torna alla classe</a>
        <?php endif; ?>
    </div>
</main>
</body>
</html>
