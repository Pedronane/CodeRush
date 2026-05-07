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
<main class="container">

    <div class="breadcrumb" style="animation:fade-up .35s ease-out both;">
        <a href="/CodeRush/">Home</a>
        <span class="breadcrumb-sep">›</span>
        <a href="/CodeRush/pages/classi.php">Classi</a>
        <span class="breadcrumb-sep">›</span>
        <a href="/CodeRush/pages/classe.php?id=<?= $partita['classe_id'] ?>"><?= sanitize($partita['anno'].$partita['sezione'].' '.$partita['indirizzo']) ?></a>
        <span class="breadcrumb-sep">›</span>
        <span>Rush <?= date('d/m/Y', strtotime($partita['created_at'])) ?></span>
    </div>

    <div class="page-header" style="animation:fade-up .4s ease-out both;">
        <div>
            <h1>Analisi: <?= sanitize($partita['domanda_nome']) ?></h1>
            <p class="page-subtitle">
                <span class="badge badge-host" style="margin-right:6px;"><?= sanitize($partita['linguaggio_nome']) ?></span>
                <?= $n ?> partecipanti &nbsp;·&nbsp; <?= date('d/m/Y H:i', strtotime($partita['created_at'])) ?>
            </p>
        </div>
        <a href="/CodeRush/pages/classe.php?id=<?= $partita['classe_id'] ?>" class="btn-ghost">← Classe</a>
    </div>

    <!-- Consegna -->
    <div class="card" style="animation:fade-up .45s ease-out .05s both;border-color:rgba(74,143,212,.3);">
        <p style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--brand-blue);margin-bottom:12px;">Consegna originale</p>
        <p style="white-space:pre-wrap;line-height:1.8;font-size:14px;"><?= sanitize($partita['domanda_testo']) ?></p>
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
    <div class="chain-card" style="animation:fade-up .5s ease-out <?= $delay ?>s both;">
        <div class="chain-header">
            <div style="display:flex;align-items:center;gap:12px;">
                <div class="turn-number" style="width:34px;height:34px;font-size:13px;flex-shrink:0;"><?= $pi + 1 ?></div>
                <div>
                    <div style="font-weight:800;font-size:14px;"><?= sanitize($part['cognome'].' '.$part['nome']) ?></div>
                    <div style="font-size:10px;color:var(--muted-foreground);font-weight:600;text-transform:uppercase;letter-spacing:.05em;margin-top:1px;">Catena <?= $part['slot_number'] + 1 ?></div>
                </div>
            </div>
            <?php if ($val): ?>
            <span style="padding:5px 16px;border-radius:20px;font-size:11px;font-weight:900;text-transform:uppercase;letter-spacing:.07em;
                         background:<?= $votoBg ?>;color:<?= $votoColor ?>;">
                <?= ucfirst($voto) ?>
            </span>
            <?php else: ?>
            <span class="badge badge-attesa">Nessuna valutazione</span>
            <?php endif; ?>
        </div>

        <?php if ($val && $val['feedback']): ?>
        <div style="padding:16px 22px;background:rgba(74,143,212,.06);border-bottom:1px solid var(--border);display:flex;gap:12px;align-items:flex-start;">
            <span style="font-size:20px;flex-shrink:0;">🤖</span>
            <div>
                <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--brand-blue);margin-bottom:5px;">Feedback AI</div>
                <p style="font-size:13px;line-height:1.7;"><?= sanitize($val['feedback']) ?></p>
            </div>
        </div>
        <?php endif; ?>

        <?php foreach ($turni as $i => $t): ?>
        <div class="chain-turn">
            <div class="chain-turn-meta">
                <div class="turn-number" style="flex-shrink:0;"><?= $i + 1 ?></div>
                <strong style="color:var(--foreground);"><?= sanitize($t['nome'].' '.$t['cognome']) ?></strong>
                <?php if ($t['submitted_at']): ?>
                <span style="color:var(--muted-foreground);font-size:12px;"><?= date('H:i:s', strtotime($t['submitted_at'])) ?></span>
                <?php endif; ?>
                <?php if ($i === $n - 1): ?>
                <span class="badge badge-corretto" style="margin-left:auto;">Finale</span>
                <?php endif; ?>
            </div>
            <?php if ($t['codice']): ?>
            <pre class="code-block"><?= sanitize($t['codice']) ?></pre>
            <?php else: ?>
            <div class="code-block code-block-empty">Nessun codice consegnato</div>
            <?php endif; ?>
            <?php if ($i > 0 && !empty($turni[$i-1]['codice']) && $t['codice']): ?>
            <details style="margin-top:10px;">
                <summary style="cursor:pointer;font-size:12px;color:var(--muted-foreground);font-weight:600;user-select:none;">↕ Mostra diff rispetto al turno precedente</summary>
                <div id="diff-<?= $part['id'] ?>-<?= $i ?>" style="margin-top:10px;"></div>
            </details>
            <script>
            (function() {
                var prev = <?= json_encode($turni[$i-1]['codice']) ?>;
                var curr = <?= json_encode($t['codice']) ?>;
                var el   = document.getElementById('diff-<?= $part['id'] ?>-<?= $i ?>');
                if (el) el.innerHTML = '<pre class="code-block" style="background:var(--muted);">' + simpleDiff(prev, curr) + '</pre>';
            })();
            </script>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endforeach; ?>

    <div style="margin-top:12px;animation:fade-up .4s ease-out .3s both;">
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
        if (p === null)      html += '<span style="color:var(--brand-green);display:block;">+ ' + escHtml(c) + '</span>';
        else if (c === null) html += '<span style="color:var(--brand-danger);display:block;">- ' + escHtml(p) + '</span>';
        else if (p !== c)    html += '<span style="color:var(--brand-danger);display:block;">- ' + escHtml(p) + '</span><span style="color:var(--brand-green);display:block;">+ ' + escHtml(c) + '</span>';
        else                 html += '<span style="display:block;color:var(--muted-foreground);">  ' + escHtml(p) + '</span>';
    }
    return html;
}
function escHtml(str) { return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
</script>
<script src="/CodeRush/js/script.js"></script>
</body>
</html>
