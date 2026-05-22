<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';
if (!isLoggedIn()) { header('Location: /CodeRush/login.php'); exit(); }

$partita_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$partita    = $partita_id > 0 ? getPartitaById($partita_id) : null;
if (!$partita) { header('Location: /CodeRush/'); exit(); }
if ($partita['stato'] !== 'finita') { header('Location: /CodeRush/pages/game.php?id='.$partita_id); exit(); }

// Uno studente vede i risultati solo se ha davvero partecipato a questa partita
if (isStudent()) {
    $part = getPartecipazione($partita_id, $_SESSION['user_id']);
    if (!$part) { header('Location: /CodeRush/'); exit(); }
}

$pageTitle = 'Risultati — '.$partita['domanda_nome'];
require_once __DIR__ . '/../includes/header.php';
?>
<link rel="stylesheet" href="/CodeRush/css/pages/risultati.css">
<script>
function lcsOps(a,b){var m=a.length,n=b.length,dp=[],i,j;for(i=0;i<=m;i++){dp[i]=new Array(n+1).fill(0);}for(i=1;i<=m;i++)for(j=1;j<=n;j++)dp[i][j]=a[i-1]===b[j-1]?dp[i-1][j-1]+1:Math.max(dp[i-1][j],dp[i][j-1]);var ops=[],ii=m,jj=n;while(ii>0||jj>0){if(ii>0&&jj>0&&a[ii-1]===b[jj-1]){ops.unshift({type:'same',line:a[ii-1]});ii--;jj--;}else if(jj>0&&(ii===0||dp[ii][jj-1]>=dp[ii-1][jj])){ops.unshift({type:'add',line:b[jj-1]});jj--;}else{ops.unshift({type:'remove',line:a[ii-1]});ii--;}}return ops;}
function mergeModify(ops){var out=[],i=0;while(i<ops.length){if(ops[i].type==='remove'){var rem=[],add=[];while(i<ops.length&&ops[i].type==='remove')rem.push(ops[i++]);while(i<ops.length&&ops[i].type==='add')add.push(ops[i++]);var min=Math.min(rem.length,add.length);for(var k=0;k<min;k++)out.push({type:'modify',oldLine:rem[k].line,line:add[k].line});for(var k=min;k<rem.length;k++)out.push(rem[k]);for(var k=min;k<add.length;k++)out.push(add[k]);}else{out.push(ops[i++]);}}return out;}
function githubDiff(prev,curr){var prevLines=prev.split('\n'),currLines=curr.split('\n');if(prevLines.length&&prevLines[prevLines.length-1]==='')prevLines.pop();if(currLines.length&&currLines[currLines.length-1]==='')currLines.pop();var ops=mergeModify(lcsOps(prevLines,currLines));var palette={same:{bg:'transparent',gut:'#161b22',sign:'#484f58'},remove:{bg:'rgba(248,81,73,.16)',gut:'rgba(248,81,73,.32)',sign:'#f85149'},add:{bg:'rgba(63,185,80,.16)',gut:'rgba(63,185,80,.32)',sign:'#3fb950'},modify:{bg:'rgba(210,153,34,.18)',gut:'rgba(210,153,34,.34)',sign:'#d29922'}};var signMap={add:'+',remove:'-',modify:'~',same:''};var rowCss='display:flex;align-items:stretch;min-height:22px;';var lnCss='flex:0 0 42px;padding:1px 10px;text-align:right;color:#8b949e;user-select:none;border-right:1px solid #30363d;font-size:11px;line-height:20px;';var signCss='flex:0 0 24px;padding:1px 0;text-align:center;font-weight:700;user-select:none;line-height:20px;';var codeCss='flex:1;padding:1px 14px;white-space:pre;tab-size:4;color:#c9d1d9;line-height:20px;';var lpn=0,lcn=0,rowsHtml='';for(var x=0;x<ops.length;x++){var op=ops[x],left='',right='';if(op.type==='add'){lcn++;right=lcn;}else if(op.type==='remove'){lpn++;left=lpn;}else{lpn++;lcn++;left=lpn;right=lcn;}var p=palette[op.type];rowsHtml+='<div style="'+rowCss+'background:'+p.bg+'">'+'<span style="'+lnCss+'background:'+p.gut+'">'+left+'</span>'+'<span style="'+lnCss+'background:'+p.gut+'">'+right+'</span>'+'<span style="'+signCss+'color:'+p.sign+'">'+signMap[op.type]+'</span>'+'<span style="'+codeCss+'">'+escHtml(op.line)+'</span>'+'</div>';}return'<div style="font-family:\'JetBrains Mono\',\'Courier New\',monospace;font-size:12.5px;background:#0d1117;border:1px solid #30363d;border-radius:8px;overflow-x:auto;"><div style="display:flex;flex-direction:column;width:max-content;min-width:100%;">'+rowsHtml+'</div></div>';}
function escHtml(str){return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}
</script>
<main class="container">

    <div class="breadcrumb page-section-breadcrumb">
        <a href="/CodeRush/">Home</a>
        <span class="breadcrumb-sep">›</span>
        <?php if (isHost()): ?>
        <a href="/CodeRush/pages/classe.php?id=<?= $partita['classe_id'] ?>"><?= sanitize($partita['anno'].$partita['sezione'].' '.$partita['indirizzo']) ?></a>
        <span class="breadcrumb-sep">›</span>
        <?php endif; ?>
        <span>Risultati</span>
    </div>

    <div class="page-header page-section-header">
        <div>
            <h1><?= sanitize($partita['domanda_nome']) ?></h1>
            <p class="page-subtitle">
                <span class="badge badge-host badge-lang"><?= sanitize($partita['linguaggio_nome']) ?></span>
                <?= sanitize($partita['anno'].$partita['sezione'].' '.$partita['indirizzo']) ?>
                &nbsp;·&nbsp; <?= date('d/m/Y H:i', strtotime($partita['created_at'])) ?>
            </p>
        </div>
        <div class="action-row">
            <?php if (isHost()): ?>
            <a href="/CodeRush/pages/rush-detail.php?id=<?= $partita_id ?>" class="btn-primary-lg">Analisi completa →</a>
            <?php endif; ?>
            <a href="/CodeRush/" class="btn-ghost">← Home</a>
        </div>
    </div>

    <?php
    $partecipazioni = getPartecipazioniByPartita($partita_id);
    $db = getDB();
    $n  = count($partecipazioni);
    $votoColors = ['corretto'=>'var(--brand-green)','parziale'=>'var(--brand-orange)','sbagliato'=>'var(--brand-danger)'];
    $votoBgs    = ['corretto'=>'rgba(61,181,64,.15)','parziale'=>'rgba(247,148,29,.15)','sbagliato'=>'rgba(232,67,67,.15)'];
    foreach ($partecipazioni as $i => $part):
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
        $delay     = number_format($i * 0.08, 2);
    ?>
    <div class="chain-card" style="animation:fade-up .5s ease-out <?= $delay ?>s both;">
        <div class="chain-header">
            <div class="chain-participant">
                <div class="turn-number participant-avatar"><?= $i + 1 ?></div>
                <div class="participant-info">
                    <div class="participant-name"><?= sanitize($part['cognome'].' '.$part['nome']) ?></div>
                    <div class="chain-label">Catena <?= $i + 1 ?></div>
                </div>
            </div>
            <?php if ($val): ?>
            <span class="chain-status-badge" style="background:<?= $votoBg ?>;color:<?= $votoColor ?>;">
                <?= ucfirst($voto) ?>
            </span>
            <?php else: ?>
            <span class="badge badge-attesa">In valutazione...</span>
            <?php endif; ?>
        </div>

        <?php if ($val && $val['feedback']): ?>
        <div class="ai-feedback-box">
            <span class="ai-icon">🤖</span>
            <div>
                <div class="ai-label">Feedback AI</div>
                <p class="ai-text"><?= sanitize($val['feedback']) ?></p>
            </div>
        </div>
        <?php endif; ?>

        <?php foreach ($turni as $ti => $t): ?>
        <div class="chain-turn">
            <div class="chain-turn-meta">
                <div class="turn-number" style="flex-shrink:0;"><?= $ti + 1 ?></div>
                <strong class="text-fg"><?= sanitize($t['nome'].' '.$t['cognome']) ?></strong>
                <?php if ($t['submitted_at']): ?>
                <span class="turn-time"><?= date('H:i:s', strtotime($t['submitted_at'])) ?></span>
                <?php endif; ?>
                <?php if ($ti === $n - 1): ?>
                <span class="badge badge-corretto" style="margin-left:auto;">Finale</span>
                <?php endif; ?>
            </div>
            <?php if ($t['codice']): ?>
            <pre class="code-block"><?= sanitize($t['codice']) ?></pre>
            <?php else: ?>
            <div class="code-block code-block-empty">Nessun codice consegnato</div>
            <?php endif; ?>
            <?php if ($ti > 0 && !empty($turni[$ti - 1]['codice']) && $t['codice']): ?>
            <details class="diff-section">
                <summary class="diff-summary">↕ Mostra diff rispetto al turno precedente</summary>
                <div id="rdiff-<?= $part['id'] ?>-<?= $ti ?>" class="diff-content"></div>
            </details>
            <script>
            (function(){
                var prev = <?= json_encode($turni[$ti - 1]['codice']) ?>;
                var curr = <?= json_encode($t['codice']) ?>;
                var el   = document.getElementById('rdiff-<?= $part['id'] ?>-<?= $ti ?>');
                if (el) el.innerHTML = githubDiff(prev, curr);
            })();
            </script>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endforeach; ?>

    <div class="results-actions">
        <a href="/CodeRush/" class="btn-ghost">← Home</a>
        <?php if (isHost()): ?>
        <a href="/CodeRush/pages/rush-detail.php?id=<?= $partita_id ?>" class="btn-primary-lg">Analisi completa →</a>
        <a href="/CodeRush/pages/classe.php?id=<?= $partita['classe_id'] ?>" class="btn-ghost">Torna alla classe</a>
        <?php endif; ?>
    </div>
</main>
<script src="/CodeRush/js/script.js"></script>
</body>
</html>
