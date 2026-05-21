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
<script>
function lcsOps(a, b) {
    var m = a.length, n = b.length;
    var dp = [];
    for (var i = 0; i <= m; i++) { dp[i] = new Array(n+1).fill(0); }
    for (var i = 1; i <= m; i++)
        for (var j = 1; j <= n; j++)
            dp[i][j] = a[i-1] === b[j-1] ? dp[i-1][j-1]+1 : Math.max(dp[i-1][j], dp[i][j-1]);
    var ops = [], i = m, j = n;
    while (i > 0 || j > 0) {
        if (i > 0 && j > 0 && a[i-1] === b[j-1]) { ops.unshift({type:'same', line:a[i-1]}); i--; j--; }
        else if (j > 0 && (i === 0 || dp[i][j-1] >= dp[i-1][j])) { ops.unshift({type:'add', line:b[j-1]}); j--; }
        else { ops.unshift({type:'remove', line:a[i-1]}); i--; }
    }
    return ops;
}
function mergeModify(ops) {
    var out = [], i = 0;
    while (i < ops.length) {
        if (ops[i].type === 'remove') {
            var rem = [], add = [];
            while (i < ops.length && ops[i].type === 'remove') rem.push(ops[i++]);
            while (i < ops.length && ops[i].type === 'add')    add.push(ops[i++]);
            var min = Math.min(rem.length, add.length);
            for (var k = 0; k < min; k++)
                out.push({type:'modify', oldLine:rem[k].line, line:add[k].line});
            for (var k = min; k < rem.length; k++) out.push(rem[k]);
            for (var k = min; k < add.length; k++) out.push(add[k]);
        } else { out.push(ops[i++]); }
    }
    return out;
}
function githubDiff(prev, curr) {
    var prevLines = prev.split('\n'), currLines = curr.split('\n');
    if (prevLines.length && prevLines[prevLines.length-1] === '') prevLines.pop();
    if (currLines.length && currLines[currLines.length-1] === '') currLines.pop();
    var ops = mergeModify(lcsOps(prevLines, currLines));
    var palette = {
        same:   {bg:'transparent',          gut:'#161b22',             sign:'#484f58'},
        remove: {bg:'rgba(248,81,73,.16)',  gut:'rgba(248,81,73,.32)', sign:'#f85149'},
        add:    {bg:'rgba(63,185,80,.16)',  gut:'rgba(63,185,80,.32)', sign:'#3fb950'},
        modify: {bg:'rgba(210,153,34,.18)', gut:'rgba(210,153,34,.34)',sign:'#d29922'}
    };
    var signMap = {add:'+', remove:'-', modify:'~', same:''};
    var rowCss  = 'display:flex;align-items:stretch;min-height:22px;';
    var lnCss   = 'flex:0 0 42px;padding:1px 10px;text-align:right;color:#8b949e;user-select:none;border-right:1px solid #30363d;font-size:11px;line-height:20px;';
    var signCss = 'flex:0 0 24px;padding:1px 0;text-align:center;font-weight:700;user-select:none;line-height:20px;';
    var codeCss = 'flex:1;padding:1px 14px;white-space:pre;tab-size:4;color:#c9d1d9;line-height:20px;';
    var lpn = 0, lcn = 0, rowsHtml = '';
    for (var x = 0; x < ops.length; x++) {
        var op = ops[x], left = '', right = '';
        if (op.type === 'add')         { lcn++; right = lcn; }
        else if (op.type === 'remove') { lpn++; left = lpn; }
        else                           { lpn++; lcn++; left = lpn; right = lcn; }
        var p = palette[op.type];
        rowsHtml += '<div style="'+rowCss+'background:'+p.bg+'">'
            + '<span style="'+lnCss+'background:'+p.gut+'">'+left+'</span>'
            + '<span style="'+lnCss+'background:'+p.gut+'">'+right+'</span>'
            + '<span style="'+signCss+'color:'+p.sign+'">'+signMap[op.type]+'</span>'
            + '<span style="'+codeCss+'">'+escHtml(op.line)+'</span>'
            + '</div>';
    }
    return '<div style="font-family:\'JetBrains Mono\',\'Courier New\',monospace;font-size:12.5px;'
        + 'background:#0d1117;border:1px solid #30363d;border-radius:8px;overflow-x:auto;">'
        + '<div style="display:flex;flex-direction:column;width:max-content;min-width:100%;">'
        + rowsHtml + '</div></div>';
}
function escHtml(str) { return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
</script>
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
                if (el) el.innerHTML = githubDiff(prev, curr);
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
<script src="/CodeRush/js/script.js"></script>
</body>
</html>
