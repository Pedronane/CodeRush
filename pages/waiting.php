<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';
if (!isStudent()) { header('Location: /CodeRush/login.php'); exit(); }

$db = getDB();
$code       = strtoupper(trim($_GET['code']       ?? ''));
$partita_id = isset($_GET['partita_id']) ? (int)$_GET['partita_id'] : 0;
$error      = '';
$partita    = null;

if ($partita_id > 0) {
    $partita = getPartitaById($partita_id);
    if (!$partita) { $error = 'Partita non trovata.'; $partita = null; }
} elseif ($code !== '') {
    $partitaRaw = getPartitaByCode($code);
    if (!$partitaRaw) {
        $error = 'Codice non valido.';
    } elseif ($partitaRaw['stato'] === 'finita') {
        $error = 'Questa partita è già terminata.';
    } elseif ($partitaRaw['stato'] !== 'attesa') {
        $exist = getPartecipazione($partitaRaw['id'], $_SESSION['user_id']);
        if ($exist) { header('Location: /CodeRush/pages/game.php?id='.$partitaRaw['id']); exit(); }
        else { $error = 'La partita è già iniziata.'; }
    } else {
        $partita    = getPartitaById($partitaRaw['id']);
        $partita_id = $partita['id'];
        $exist = getPartecipazione($partita_id, $_SESSION['user_id']);
        if (!$exist) {
            $stmtSlot = $db->prepare('SELECT MAX(slot_number) AS mx FROM partecipazioni WHERE partita_id=?');
            $stmtSlot->execute([$partita_id]);
            $maxSlot  = $stmtSlot->fetchColumn();
            $nextSlot = ($maxSlot === null) ? 0 : (int)$maxSlot + 1;
            $db->prepare('INSERT INTO partecipazioni (partita_id,studente_id,slot_number) VALUES (?,?,?)')->execute([$partita_id,$_SESSION['user_id'],$nextSlot]);
        }
    }
} else {
    $error = 'Codice partita mancante.';
}

if ($partita && $partita['stato'] !== 'attesa') {
    header('Location: /CodeRush/pages/game.php?id='.$partita['id']); exit();
}

$pageTitle = 'In attesa...';
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($pageTitle) ?> — CodeRush</title>
    <link rel="stylesheet" href="/CodeRush/css/style.css">
</head>
<body>

<div class="background-fx" aria-hidden="true">
    <div class="bfx-grid"></div>
    <div class="bfx-blob bfx-blob-green"></div>
    <div class="bfx-blob bfx-blob-blue"></div>
    <div class="bfx-blob bfx-blob-orange"></div>
</div>
<div data-particles="26" style="position:fixed;inset:0;z-index:0;pointer-events:none;"></div>

<div class="waiting-full" style="position:relative;z-index:1;">
    <div class="waiting-inner">

    <?php if ($error): ?>
        <div style="animation:fade-up .5s ease-out both;">
            <div class="alert alert-danger" style="margin-bottom:20px;"><?= sanitize($error) ?></div>
            <a href="/CodeRush/" class="btn-ghost">← Torna alla home</a>
        </div>

    <?php elseif ($partita): ?>
        <!-- Dual-ring spinner -->
        <div class="dual-spinner" style="margin:0 auto;animation:fade-up .4s ease-out both;">
            <div class="ring-outer"></div>
            <div class="ring-inner"></div>
        </div>

        <div style="animation:fade-up .5s ease-out .1s both;">
            <h1 class="waiting-title">
                In attesa del Professor
                <span class="brand-gradient-text"><?= sanitize($partita['host_cognome']) ?></span>
            </h1>
            <p style="color:var(--muted-foreground);font-size:14px;">
                La partita inizierà non appena l'host darà il via<span style="animation:pulse-soft 1s ease-in-out infinite;display:inline-block;">...</span>
            </p>
        </div>

        <!-- Info box -->
        <div class="info-box" style="width:100%;max-width:380px;margin-top:24px;animation:fade-up .5s ease-out .2s both;">
            <p class="info-label" style="margin-bottom:12px;">Info partita</p>
            <div class="info-row">
                <span class="info-label">Codice</span>
                <span class="info-val code-val"><?= sanitize($partita['codice_accesso']) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Linguaggio</span>
                <span class="info-val"><?= sanitize($partita['linguaggio_nome']) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Consegna</span>
                <span class="info-val"><?= sanitize($partita['domanda_nome']) ?></span>
            </div>
        </div>

        <a href="/CodeRush/" style="margin-top:20px;font-size:12px;font-weight:600;color:var(--muted-foreground);">
            ← Torna alla home
        </a>

    <?php else: ?>
        <div style="animation:fade-up .5s ease-out both;">
            <h2 style="font-size:24px;font-weight:900;margin-bottom:16px;">Partita non trovata</h2>
            <a href="/CodeRush/" class="btn-ghost">← Torna alla home</a>
        </div>
    <?php endif; ?>

    </div>
</div>

<script src="/CodeRush/js/script.js"></script>
<?php if ($partita): ?>
<script>
var PARTITA_ID = <?= $partita['id'] ?>;
function pollState() {
    fetch('/CodeRush/api/api.php?action=game_state&id='+PARTITA_ID)
        .then(function(r){ return r.json(); })
        .then(function(d){ if (d.stato && d.stato !== 'attesa') window.location.href='/CodeRush/pages/game.php?id='+PARTITA_ID; })
        .catch(function(){});
}
setInterval(pollState, 2500);
</script>
<?php endif; ?>
</body>
</html>
