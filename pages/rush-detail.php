<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';
if (!isHost()) { header('Location: /CodeRush/login.php'); exit(); }

$partita_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$partita    = $partita_id > 0 ? getPartitaById($partita_id) : null;
if (!$partita || $partita['stato'] !== 'finita') { header('Location: /CodeRush/pages/classi.php'); exit(); }

$db          = getDB();
$partecipazioni = getPartecipazioniByPartita($partita_id);
$n           = count($partecipazioni);

$pageTitle = 'Analisi Rush — '.$partita['domanda_nome'];
require_once __DIR__ . '/../includes/header.php';
?>
<link rel="stylesheet" href="/CodeRush/css/pages/rush-detail.css">
<main class="container">

    <div class="breadcrumb page-section-breadcrumb">
        <a href="/CodeRush/">Home</a>
        <span class="breadcrumb-sep">›</span>
        <a href="/CodeRush/pages/classi.php">Classi</a>
        <span class="breadcrumb-sep">›</span>
        <a href="/CodeRush/pages/classe.php?id=<?= $partita['classe_id'] ?>"><?= sanitize($partita['anno'].$partita['sezione'].' '.$partita['indirizzo']) ?></a>
        <span class="breadcrumb-sep">›</span>
        <span>Rush <?= date('d/m/Y', strtotime($partita['created_at'])) ?></span>
    </div>

    <div class="page-header page-section-header">
        <div>
            <h1>Analisi: <?= sanitize($partita['domanda_nome']) ?></h1>
            <p class="page-subtitle">
                <span class="badge badge-host phase-badge"><?= sanitize($partita['linguaggio_nome']) ?></span>
                <?= $n ?> partecipanti &nbsp;·&nbsp; <?= date('d/m/Y H:i', strtotime($partita['created_at'])) ?>
            </p>
        </div>
        <a href="/CodeRush/pages/classe.php?id=<?= $partita['classe_id'] ?>" class="btn-ghost">← Classe</a>
    </div>

    <!-- Consegna -->
    <div class="card assignment-card page-section-assignment">
        <p class="assignment-label">Consegna originale</p>
        <p class="assignment-content"><?= sanitize($partita['domanda_testo']) ?></p>
    </div>

    <?php
    $votoColors = ['corretto'=>'var(--brand-green)','parziale'=>'var(--brand-orange)','sbagliato'=>'var(--brand-danger)'];
    $votoBgs    = ['corretto'=>'rgba(61,181,64,.15)','parziale'=>'rgba(247,148,29,.15)','sbagliato'=>'rgba(232,67,67,.15)'];

    foreach ($partecipazioni as $pi => $part):
        $stmtVal = $db->prepare('SELECT * FROM valutazioni WHERE slot_id = ?');
        $stmtVal->execute([$part['id']]);
        $val = $stmtVal->fetch();

        $stmtTurni = $db->prepare(
            'SELECT t.*, u.nome, u.cognome FROM turni t
             JOIN users u ON u.id = t.studente_id
             WHERE t.slot_id = ? ORDER BY t.numero_turno ASC'
        );
        $stmtTurni->execute([$part['id']]);
        $turni = $stmtTurni->fetchAll();

        $voto      = $val['voto'] ?? '';
        $votoColor = $votoColors[$voto] ?? 'var(--muted-foreground)';
        $votoBg    = $votoBgs[$voto]    ?? 'rgba(154,163,176,.12)';
        $delay     = number_format($pi * 0.07, 2);
    ?>
    <div class="chain-card chain-item" style="animation-delay:<?= $delay ?>s;">
        <div class="chain-header">
            <div class="chain-participant">
                <div class="turn-number participant-avatar"><?= $pi + 1 ?></div>
                <div class="participant-info">
                    <div class="participant-name"><?= sanitize($part['cognome'].' '.$part['nome']) ?></div>
                    <div class="participant-chain">Catena <?= $part['slot_number'] + 1 ?></div>
                </div>
            </div>
            <?php if ($val): ?>
            <span class="participant-grade" style="background:<?= $votoBg ?>;color:<?= $votoColor ?>;"><?= ucfirst($voto) ?></span>
            <?php else: ?>
            <span class="badge badge-attesa">Nessuna valutazione</span>
            <?php endif; ?>
        </div>

        <?php if ($val && $val['feedback']): ?>
        <div class="feedback-container">
            <span class="feedback-icon">🤖</span>
            <div class="feedback-content">
                <div class="feedback-label">Feedback AI</div>
                <p class="feedback-text"><?= sanitize($val['feedback']) ?></p>
            </div>
        </div>
        <?php endif; ?>

        <?php foreach ($turni as $i => $t): ?>
        <div class="chain-turn">
            <div class="chain-turn-meta">
                <div class="turn-number turn-number-meta"><?= $i + 1 ?></div>
                <strong class="text-fg"><?= sanitize($t['nome'].' '.$t['cognome']) ?></strong>
                <?php if ($t['submitted_at']): ?>
                <span class="turn-time"><?= date('H:i:s', strtotime($t['submitted_at'])) ?></span>
                <?php endif; ?>
                <?php if ($i === $n - 1): ?>
                <span class="badge badge-corretto turn-final">Finale</span>
                <?php endif; ?>
            </div>
            <?php if ($t['codice']): ?>
            <pre class="code-block"><?= sanitize($t['codice']) ?></pre>
            <?php else: ?>
            <div class="code-block code-block-empty">Nessun codice consegnato</div>
            <?php endif; ?>
            <?php if ($i > 0 && !empty($turni[$i-1]['codice']) && $t['codice']): ?>
            <details class="diff-section">
                <summary class="diff-summary">↕ Mostra diff rispetto al turno precedente</summary>
                <div id="diff-<?= $part['id'] ?>-<?= $i ?>" class="diff-content"></div>
            </details>
            <script>
            (function() {
                var prev = <?= json_encode($turni[$i-1]['codice']) ?>;
                var curr = <?= json_encode($t['codice']) ?>;
                var el   = document.getElementById('diff-<?= $part['id'] ?>-<?= $i ?>');
                if (el) el.innerHTML = '<pre class="code-block diff-code">' + simpleDiff(prev, curr) + '</pre>';
            })();
            </script>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endforeach; ?>

    <div class="page-section-footer">
        <a href="/CodeRush/pages/classe.php?id=<?= $partita['classe_id'] ?>" class="btn-ghost">← Torna alla classe</a>
    </div>
</main>
<script>
function simpleDiff(prev, curr) {
    var prevLines = prev.split('\n'), currLines = curr.split('\n'), html = '';
    var maxLen = Math.max(prevLines.length, currLines.length);
    for (var i = 0; i < maxLen; i++) {
        var p = prevLines[i] !== undefined ? prevLines[i] : null;
        var c = currLines[i] !== undefined ? currLines[i] : null;
        if (p === null)      html += '<span class="diff-add">+ ' + escHtml(c) + '</span>';
        else if (c === null) html += '<span class="diff-remove">- ' + escHtml(p) + '</span>';
        else if (p !== c)    html += '<span class="diff-remove">- ' + escHtml(p) + '</span><span class="diff-add">+ ' + escHtml(c) + '</span>';
        else                 html += '<span class="diff-unchanged">  ' + escHtml(p) + '</span>';
    }
    return html;
}
function escHtml(str) { return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
</script>
<script src="/CodeRush/js/script.js"></script>
</body>
</html>
