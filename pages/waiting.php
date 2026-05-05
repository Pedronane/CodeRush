<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';

if (!isStudent()) {
    header('Location: /CodeRush/login.php');
    exit();
}

$db = getDB();
$code = strtoupper(trim($_GET['code'] ?? ''));
$partita_id = isset($_GET['partita_id']) ? (int)$_GET['partita_id'] : 0;
$error = '';

if ($partita_id > 0) {
    $partita = getPartitaById($partita_id);
    if (!$partita) {
        $error = 'Partita non trovata.';
        $partita = null;
    }
} elseif ($code !== '') {
    $partitaRaw = getPartitaByCode($code);
    if (!$partitaRaw) {
        $error = 'Codice non valido.';
        $partita = null;
    } elseif ($partitaRaw['stato'] === 'finita') {
        $error = 'Questa partita è già terminata.';
        $partita = null;
    } elseif ($partitaRaw['stato'] !== 'attesa') {
        $exist = getPartecipazione($partitaRaw['id'], $_SESSION['user_id']);
        if ($exist) {
            header('Location: /CodeRush/pages/game.php?id=' . $partitaRaw['id']);
            exit();
        } else {
            $error = 'La partita è già iniziata.';
            $partita = null;
        }
    } else {
        $partita = getPartitaById($partitaRaw['id']);
        $partita_id = $partita['id'];
        $exist = getPartecipazione($partita_id, $_SESSION['user_id']);
        if (!$exist) {
            $stmtSlot = $db->prepare('SELECT MAX(slot_number) AS mx FROM partecipazioni WHERE partita_id = ?');
            $stmtSlot->execute([$partita_id]);
            $maxSlot = $stmtSlot->fetchColumn();
            $nextSlot = ($maxSlot === null) ? 0 : (int)$maxSlot + 1;
            $db->prepare('INSERT INTO partecipazioni (partita_id, studente_id, slot_number) VALUES (?, ?, ?)')
               ->execute([$partita_id, $_SESSION['user_id'], $nextSlot]);
        }
    }
} else {
    $partita = null;
    $error = 'Codice partita mancante.';
}

if ($partita && $partita['stato'] !== 'attesa') {
    header('Location: /CodeRush/pages/game.php?id=' . $partita['id']);
    exit();
}

$pageTitle = 'In attesa...';
require_once __DIR__ . '/../includes/header.php';
?>
<main class="container">
    <?php if ($error): ?>
        <div class="waiting-screen">
            <div class="alert alert-danger"><?= sanitize($error) ?></div>
            <a href="/CodeRush/" class="btn btn-secondary">Torna alla home</a>
        </div>
    <?php elseif ($partita): ?>
        <div class="waiting-screen">
            <div class="waiting-spinner"></div>
            <h2>In attesa del professor <?= sanitize($partita['host_nome'] . ' ' . $partita['host_cognome']) ?></h2>
            <p style="color: var(--text-muted);">
                Consegna: <strong><?= sanitize($partita['domanda_nome']) ?></strong>
                &nbsp;·&nbsp;
                <?= sanitize($partita['linguaggio_nome']) ?>
            </p>
            <p style="color: var(--text-muted); font-size: 13px;">La partita inizierà non appena l'host darà il via.</p>
            <div style="margin-top: 8px; font-size: 13px; color: var(--text-muted);">
                Codice: <span style="font-family: monospace; font-size: 15px; color: var(--accent);"><?= sanitize($partita['codice_accesso']) ?></span>
            </div>
        </div>
    <?php else: ?>
        <div class="waiting-screen">
            <h2>Partita non trovata</h2>
            <a href="/CodeRush/" class="btn btn-secondary">Torna alla home</a>
        </div>
    <?php endif; ?>
</main>
</body>
</html>
<?php if ($partita): ?>
<script>
const PARTITA_ID = <?= $partita['id'] ?>;
function pollState() {
    fetch('/CodeRush/api/api.php?action=game_state&id=' + PARTITA_ID)
        .then(r => r.json())
        .then(data => {
            if (data.stato && data.stato !== 'attesa') {
                window.location.href = '/CodeRush/pages/game.php?id=' + PARTITA_ID;
            }
        })
        .catch(() => {});
}
setInterval(pollState, 2500);
</script>
<?php endif; ?>
