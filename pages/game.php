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
</head>
<body style="background:var(--background);min-height:100vh;">

<div class="background-fx" aria-hidden="true">
    <div class="bfx-grid"></div>
    <div class="bfx-blob bfx-blob-green" style="opacity:.12;"></div>
    <div class="bfx-blob bfx-blob-blue"  style="opacity:.1;"></div>
</div>

<!-- Top bar -->
<div class="game-topbar" id="game-topbar">
    <div class="game-topbar-inner">
        <span class="phase-pill" style="background:<?= $phaseBg ?>;"><?= $phaseLabel ?></span>
        <div class="game-timer-big" id="timer-display" style="color:var(--brand-green);"><?= gmdate('i:s',$tempoRimanente) ?></div>
    </div>
    <!-- Progress bar -->
    <div style="height:3px;background:var(--border);">
        <div id="timer-bar" style="height:100%;background:linear-gradient(90deg,var(--brand-green),var(--brand-blue));width:100%;transition:width .5s linear;"></div>
    </div>
</div>

<!-- Phase banner (flash on load) -->
<div class="game-banner" id="game-banner" style="display:none;">
    <div class="game-banner-box" id="game-banner-box"></div>
</div>

<main style="max-width:1100px;margin:0 auto;padding:28px 24px;position:relative;z-index:1;">

    <?php if ($partita['stato'] === 'lettura'): ?>
    <!-- READING PHASE -->
    <div style="max-width:720px;margin:0 auto;animation:fade-up .5s ease-out both;">
        <div class="card">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;gap:12px;">
                <span class="badge badge-host"><?= sanitize($partita['linguaggio_nome']) ?></span>
                <span style="font-size:12px;color:var(--muted-foreground);"><?= sanitize($partita['anno'].$partita['sezione'].' '.$partita['indirizzo']) ?></span>
            </div>
            <h2 style="font-size:22px;font-weight:900;margin-bottom:16px;">Leggi attentamente la consegna</h2>
            <p style="font-size:16px;line-height:1.8;color:var(--foreground);white-space:pre-wrap;"><?= sanitize($partita['domanda_testo']) ?></p>
            <p style="margin-top:20px;font-size:11px;text-transform:uppercase;letter-spacing:.1em;color:var(--muted-foreground);">
                La fase di scrittura inizierà automaticamente.
            </p>
            <?php if (isHost()): ?>
            <div style="margin-top:20px;">
                <button class="btn-primary-lg" onclick="advancePhase()">Avanza alla scrittura →</button>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <?php elseif ($partita['stato'] === 'scrittura'): ?>
    <!-- WRITING PHASE -->
    <div style="display:grid;gap:20px;animation:fade-up .5s ease-out both;" class="writing-grid">

        <?php if (isStudent() && $myTurno): ?>
        <!-- Student: consegna + editor -->
        <aside style="display:flex;flex-direction:column;gap:16px;">
            <div class="card" style="margin-bottom:0;">
                <p style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--muted-foreground);margin-bottom:10px;">Consegna</p>
                <p style="font-size:13px;line-height:1.7;color:rgba(240,244,250,.9);"><?= sanitize($partita['domanda_testo']) ?></p>
            </div>
            <?php if ($codicePrecedente !== null): ?>
            <div class="card" style="margin-bottom:0;">
                <p style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--muted-foreground);margin-bottom:10px;">Turno precedente</p>
                <pre class="code-block" style="font-size:12px;border-radius:10px;"><?= sanitize($codicePrecedente ?: '# (nessun codice)') ?></pre>
            </div>
            <?php endif; ?>
        </aside>

        <section>
            <div style="border-radius:16px;border:2px solid rgba(61,181,64,.4);overflow:hidden;background:var(--card);box-shadow:0 0 40px -12px rgba(61,181,64,.4);transition:border-color .2s;">
                <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 16px;border-bottom:1px solid var(--border);font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--muted-foreground);">
                    <span>editor — <?= strtolower(sanitize($partita['linguaggio_nome'])) ?></span>
                    <?php if ($myTurno['submitted_at']): ?>
                    <span style="color:var(--brand-green);">Consegnato ✓</span>
                    <?php endif; ?>
                </div>
                <form id="formSubmit" onsubmit="submitCode(event)">
                    <textarea
                        id="codeEditor"
                        class="code-editor"
                        style="border:none;border-radius:0;min-height:340px;"
                        placeholder="Scrivi il tuo codice qui..."
                        <?= $myTurno['submitted_at'] ? 'disabled' : '' ?>
                    ><?= sanitize($myTurno['codice'] ?? ($codicePrecedente ?? '')) ?></textarea>
                </form>
            </div>

            <?php if (!$myTurno['submitted_at']): ?>
            <button
                id="btnSubmit"
                onclick="submitCode(event)"
                class="btn-primary-lg btn-block"
                style="margin-top:14px;padding:16px;font-size:15px;border-radius:14px;animation:pulse-soft 2.4s ease-in-out infinite;"
            >
                Consegna ✓
            </button>
            <p id="submit-msg" style="text-align:center;font-size:12px;color:var(--muted-foreground);margin-top:8px;"></p>
            <?php else: ?>
            <div class="alert alert-success" style="margin-top:14px;">Codice consegnato! In attesa degli altri...</div>
            <?php endif; ?>
        </section>

        <?php elseif (isHost()): ?>
        <!-- Host: monitor -->
        <div>
            <div style="border-radius:16px;border:2px solid rgba(61,181,64,.3);overflow:hidden;background:var(--card);">
                <div style="padding:12px 18px;border-bottom:1px solid var(--border);font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--muted-foreground);">
                    Monitor host — gli studenti stanno scrivendo
                </div>
                <div style="padding:16px;display:flex;flex-direction:column;gap:8px;" id="host-status">
                    <?php foreach ($partecipazioni as $p):
                        $t = getTurnoCorrente($partita_id, $p['studente_id'], $partita['round_corrente']);
                        $done = ($t && $t['submitted_at']);
                    ?>
                    <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 14px;border-radius:10px;border:1px solid var(--border);background:rgba(255,255,255,.02);">
                        <span style="font-size:13px;font-weight:600;"><?= sanitize($p['cognome'].' '.$p['nome']) ?></span>
                        <span style="padding:3px 10px;border-radius:20px;font-size:10px;font-weight:900;text-transform:uppercase;letter-spacing:.06em;
                              background:<?= $done ? 'rgba(61,181,64,.18)' : 'rgba(247,148,29,.18)' ?>;
                              color:<?= $done ? 'var(--brand-green)' : 'var(--brand-orange)' ?>;">
                            <?= $done ? 'Consegnato' : 'In corso' ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <button
                onclick="advancePhase()"
                class="btn-primary-lg btn-block"
                style="margin-top:14px;padding:16px;font-size:14px;border-radius:14px;background:linear-gradient(135deg,var(--brand-orange),var(--brand-lime));">
                Forza turno successivo →
            </button>
        </div>

        <div class="card" style="margin-bottom:0;">
            <p style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--muted-foreground);margin-bottom:12px;">Consegna</p>
            <p style="font-size:13px;line-height:1.7;"><?= sanitize($partita['domanda_testo']) ?></p>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</main>

<style>
@media (min-width:900px) {
    .writing-grid { grid-template-columns: 1fr 1.4fr; }
}
</style>

<script src="/CodeRush/js/script.js"></script>
<script>
var PARTITA_ID   = <?= $partita_id ?>;
var IS_HOST      = <?= isHost() ? 'true' : 'false' ?>;
var STATO_INIT   = '<?= $partita['stato'] ?>';
var ROUND_INIT   = <?= $partita['round_corrente'] ?>;
var timerSeconds = <?= $tempoRimanente ?>;
var totalSeconds = <?= $partita['stato'] === 'lettura' ? $partita['tempo_lettura'] : $partita['tempo_turno'] ?>;
var timerInterval = null;

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
        if (timerSeconds <= 0) { clearInterval(timerInterval); pollGameState(); }
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
    var code = document.getElementById('codeEditor').value;
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
            if (document.getElementById('codeEditor')) document.getElementById('codeEditor').disabled = true;
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

startTimer();
updateTimerDisplay();
setInterval(pollGameState, 5000);
</script>
</body>
</html>
