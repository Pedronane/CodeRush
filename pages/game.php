<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';
if (!isLoggedIn()) { header('Location: /CodeRush/login.php'); exit(); }

$partita_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$partita    = $partita_id > 0 ? getPartitaById($partita_id) : null;
if (!$partita) { header('Location: /CodeRush/'); exit(); }

if ($partita['stato'] === 'attesa') {
    header('Location: '.(isHost() ? '/CodeRush/pages/lobby.php?id='.$partita_id : '/CodeRush/pages/waiting.php?partita_id='.$partita_id));
    exit();
}
if ($partita['stato'] === 'finita') {
    header('Location: /CodeRush/pages/risultati.php?id='.$partita_id); exit();
}

$myTurno         = null;
$codicePrecedente = null;
$slotId          = null;

if (isStudent()) {
    $partecipazione = getPartecipazione($partita_id, $_SESSION['user_id']);
    if (!$partecipazione) { header('Location: /CodeRush/'); exit(); }
    $slotId = $partecipazione['id'];
    if ($partita['stato'] === 'scrittura') {
        $myTurno = getTurnoCorrente($partita_id, $_SESSION['user_id'], $partita['round_corrente']);
        if ($myTurno) $codicePrecedente = getPreviousCodice($myTurno['slot_id'], $partita['round_corrente']);
    }
}

$tempoRimanente = getTempoRimanente($partita);
$partecipazioni = getPartecipazioniByPartita($partita_id);
$nStudenti      = count($partecipazioni);

$phaseBg    = $partita['stato'] === 'lettura' ? 'var(--brand-blue)' : 'var(--brand-green)';
$phaseLabel = $partita['stato'] === 'lettura'  ? 'FASE LETTURA'
            : ($partita['stato'] === 'scrittura' ? 'TURNO '.($partita['round_corrente']+1).' DI '.$nStudenti : 'TURNO COMPLETATO');

$pageTitle = 'Rush — '.$partita['domanda_nome'];
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($pageTitle) ?> — CodeRush</title>
    <link rel="stylesheet" href="/CodeRush/css/style.css">
    <link rel="stylesheet" href="/CodeRush/css/pages/game.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/codemirror.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/theme/dracula.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/codemirror.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/python/python.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/javascript/javascript.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/clike/clike.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/xml/xml.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/css/css.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/htmlmixed/htmlmixed.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/sql/sql.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/php/php.min.js"></script>
</head>
<body class="game-body">

<div class="background-fx" aria-hidden="true">
    <div class="bfx-grid"></div>
    <div class="bfx-blob bfx-blob-green game-blob-green"></div>
    <div class="bfx-blob bfx-blob-blue game-blob-blue"></div>
</div>

<!-- Top bar -->
<div class="game-topbar" id="game-topbar">
    <div class="game-topbar-inner">
        <span class="phase-pill" style="background:<?= $phaseBg ?>;"><?= $phaseLabel ?></span>
        <div class="game-timer-big" id="timer-display"><?= gmdate('i:s',$tempoRimanente) ?></div>
    </div>
    <!-- Progress bar -->
    <div class="topbar-style">
        <div id="timer-bar" class="progress-bar"></div>
    </div>
</div>

<!-- Phase banner (flash on load) -->
<div class="game-banner" id="game-banner">
    <div class="game-banner-box" id="game-banner-box"></div>
</div>

<main class="game-main">

    <?php if ($partita['stato'] === 'lettura'): ?>
    <!-- READING PHASE -->
    <div class="reading-phase-card">
        <div class="card">
            <div class="reading-title">
                <span class="badge badge-host"><?= sanitize($partita['linguaggio_nome']) ?></span>
                <span class="class-info"><?= sanitize($partita['anno'].$partita['sezione'].' '.$partita['indirizzo']) ?></span>
            </div>
            <h2 class="reading-title-text">Leggi attentamente la consegna</h2>
            <p class="reading-content"><?= sanitize($partita['domanda_testo']) ?></p>
            <p class="reading-note">
                La fase di scrittura inizierà automaticamente.
            </p>
            <?php if (isHost()): ?>
            <div class="reading-action">
                <button class="btn-primary-lg" onclick="advancePhase()">Avanza alla scrittura →</button>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <?php elseif ($partita['stato'] === 'scrittura'): ?>
    <!-- WRITING PHASE -->
    <div class="writing-phase writing-grid">

        <?php if (isStudent() && $myTurno): ?>
        <!-- Student: consegna + editor -->
        <aside class="student-aside">
            <div class="card aside-card">
                <p class="aside-label">Consegna</p>
                <p class="aside-content"><?= sanitize($partita['domanda_testo']) ?></p>
            </div>
            <?php if ($codicePrecedente !== null): ?>
            <div class="card aside-card">
                <p class="aside-label">Turno precedente</p>
                <pre class="code-block prev-code"><?= sanitize($codicePrecedente ?: '# (nessun codice)') ?></pre>
            </div>
            <?php endif; ?>
        </aside>

        <section>
            <div class="editor-container">
                <div class="editor-header">
                    <span>editor — <?= strtolower(sanitize($partita['linguaggio_nome'])) ?></span>
                    <?php if ($myTurno['submitted_at']): ?>
                    <span class="editor-submitted">Consegnato ✓</span>
                    <?php endif; ?>
                </div>
                <form id="formSubmit" onsubmit="submitCode(event)">
                    <textarea
                        id="codeEditor"
                        class="code-editor code-editor-textarea"
                        placeholder="Scrivi il tuo codice qui..."
                        <?= $myTurno['submitted_at'] ? 'disabled' : '' ?>
                    ><?= sanitize($myTurno['codice'] ?? ($codicePrecedente ?? '')) ?></textarea>
                </form>
            </div>

            <?php if (!$myTurno['submitted_at']): ?>
            <button
                id="btnSubmit"
                onclick="submitCode(event)"
                class="btn-primary-lg btn-block submit-button"
            >
                Consegna ✓
            </button>
            <p id="submit-msg" class="submit-message"></p>
            <?php else: ?>
            <div class="alert alert-success success-message">Codice consegnato! In attesa degli altri...</div>
            <?php endif; ?>
        </section>

        <?php elseif (isHost()): ?>
        <!-- Host: monitor -->
        <div>
            <div class="host-monitor">
                <div class="host-header">
                    Monitor host — gli studenti stanno scrivendo
                </div>
                <div class="host-status" id="host-status">
                    <?php foreach ($partecipazioni as $p):
                        $t = getTurnoCorrente($partita_id, $p['studente_id'], $partita['round_corrente']);
                        $done = ($t && $t['submitted_at']);
                    ?>
                    <div class="host-student-row">
                        <span class="student-name"><?= sanitize($p['cognome'].' '.$p['nome']) ?></span>
                        <span class="student-status <?= $done ? 'student-done' : 'student-pending' ?>;">
                            <?= $done ? 'Consegnato' : 'In corso' ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <button
                onclick="advancePhase()"
                class="btn-primary-lg btn-block force-next-button">
                Forza turno successivo →
            </button>
        </div>

        <div class="card host-card">
            <p class="aside-label">Consegna</p>
            <p class="aside-content"><?= sanitize($partita['domanda_testo']) ?></p>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</main>

<script src="/CodeRush/js/script.js"></script>
<script>
var PARTITA_ID   = <?= $partita_id ?>;
var IS_HOST      = <?= isHost() ? 'true' : 'false' ?>;
var STATO_INIT   = '<?= $partita['stato'] ?>';
var ROUND_INIT   = <?= $partita['round_corrente'] ?>;
var timerSeconds = <?= $tempoRimanente ?>;
var totalSeconds = <?= $partita['stato'] === 'lettura' ? $partita['tempo_lettura'] : $partita['tempo_turno'] ?>;
var timerInterval = null;
var LINGUAGGIO = <?= json_encode(strtolower($partita['linguaggio_nome'])) ?>;
var cmEditor = null;

function updateTimerDisplay() {
    var el  = document.getElementById('timer-display');
    var bar = document.getElementById('timer-bar');
    var tb  = document.getElementById('game-topbar');
    var mins = Math.floor(timerSeconds/60);
    var secs = timerSeconds % 60;
    el.textContent = String(mins).padStart(2,'0')+':'+String(secs).padStart(2,'0');
    if (timerSeconds <= 10) {
        el.style.color = 'var(--brand-danger)';
        el.style.animation = 'shake .4s ease-in-out infinite';
        tb.classList.add('danger');
    } else if (timerSeconds <= 30) {
        el.style.color = 'var(--brand-orange)';
        el.style.animation = '';
        tb.classList.remove('danger');
    } else {
        el.style.color = 'var(--brand-green)';
        el.style.animation = '';
        tb.classList.remove('danger');
    }
    if (bar && totalSeconds > 0) bar.style.width = (timerSeconds/totalSeconds*100)+'%';
}

function startTimer() {
    if (timerInterval) clearInterval(timerInterval);
    timerInterval = setInterval(function() {
        if (timerSeconds > 0) { timerSeconds--; updateTimerDisplay(); }
        if (timerSeconds <= 0) {
            clearInterval(timerInterval);
            if (!IS_HOST && STATO_INIT === 'scrittura') {
                var btn = document.getElementById('btnSubmit');
                if (btn && !btn.disabled) submitCode(null);
            }
            pollGameState();
        }
    }, 1000);
}

function pollGameState() {
    fetch('/CodeRush/api/api.php?action=game_state&id='+PARTITA_ID)
        .then(function(r){return r.json();})
        .then(function(data) {
            if (data.stato === 'finita') window.location.href='/CodeRush/pages/risultati.php?id='+PARTITA_ID;
            else if (data.stato !== STATO_INIT || data.round !== ROUND_INIT) window.location.reload();
            else if (data.tempo_rimanente !== undefined) { timerSeconds = data.tempo_rimanente; updateTimerDisplay(); }
        })
        .catch(function(){});
}

function submitCode(e) {
    if (e) e.preventDefault();
    var code = cmEditor ? cmEditor.getValue() : document.getElementById('codeEditor').value;
    var btn  = document.getElementById('btnSubmit');
    var msg  = document.getElementById('submit-msg');
    if (btn) btn.disabled = true;
    if (msg) msg.textContent = 'Invio in corso...';
    fetch('/CodeRush/api/api.php', {
        method:'POST', headers:{'Content-Type':'application/json'},
        body:JSON.stringify({action:'submit_code',partita_id:PARTITA_ID,codice:code})
    })
    .then(function(r){return r.json();})
    .then(function(data) {
        if (data.success) {
            if (cmEditor) cmEditor.setOption('readOnly', true);
            else if (document.getElementById('codeEditor')) document.getElementById('codeEditor').disabled = true;
            if (msg) { msg.textContent='Consegnato! In attesa degli altri...'; msg.style.color='var(--brand-green)'; }
            if (btn) { btn.style.display='none'; }
            if (data.game_ended) setTimeout(function(){ window.location.href='/CodeRush/pages/risultati.php?id='+PARTITA_ID; },1500);
        } else {
            if (msg) { msg.textContent=data.error||'Errore invio.'; msg.style.color='var(--danger)'; }
            if (btn) btn.disabled=false;
        }
    });
}

function advancePhase() {
    fetch('/CodeRush/api/api.php', {
        method:'POST', headers:{'Content-Type':'application/json'},
        body:JSON.stringify({action:'advance_phase',partita_id:PARTITA_ID})
    })
    .then(function(r){return r.json();})
    .then(function(data) {
        if (data.stato==='finita') window.location.href='/CodeRush/pages/risultati.php?id='+PARTITA_ID;
        else window.location.reload();
    });
}

// Phase banner flash
(function showBanner() {
    var label = '<?= $partita['stato'] === 'lettura' ? 'FASE LETTURA — Concentrati!' : 'FASE SCRITTURA — Vai!' ?>';
    var banner = document.getElementById('game-banner');
    var box    = document.getElementById('game-banner-box');
    if (!banner || !box) return;
    box.textContent = label;
    banner.style.display = 'grid';
    setTimeout(function() { banner.style.display='none'; }, 1800);
})();

(function() {
    var ta = document.getElementById('codeEditor');
    if (!ta || typeof CodeMirror === 'undefined') return;
    var modeMap = {
        'python':'python', 'javascript':'javascript',
        'java':'text/x-java', 'c':'text/x-csrc', 'c++':'text/x-c++src',
        'php':'application/x-httpd-php', 'sql':'text/x-sql',
        'html/css':'htmlmixed', 'html':'htmlmixed', 'css':'css'
    };
    cmEditor = CodeMirror.fromTextArea(ta, {
        mode: modeMap[LINGUAGGIO] || 'text/plain',
        theme: 'dracula',
        lineNumbers: true,
        tabSize: 4,
        indentWithTabs: false,
        indentUnit: 4,
        readOnly: <?= $myTurno && $myTurno['submitted_at'] ? 'true' : 'false' ?>,
        extraKeys: { Tab: function(cm) { cm.replaceSelection('    '); } }
    });
    cmEditor.setSize('100%', '340px');
})();

startTimer();
updateTimerDisplay();
setInterval(pollGameState, 5000);
</script>
</body>
</html>
