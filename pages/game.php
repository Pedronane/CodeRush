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

if ($partita['stato'] === 'attesa') {
    if (isHost()) {
        header('Location: /CodeRush/pages/lobby.php?id=' . $partita_id);
    } else {
        header('Location: /CodeRush/pages/waiting.php?partita_id=' . $partita_id);
    }
    exit();
}

if ($partita['stato'] === 'finita') {
    header('Location: /CodeRush/pages/risultati.php?id=' . $partita_id);
    exit();
}

$myTurno = null;
$codicePrecedente = null;
$slotId = null;

if (isStudent()) {
    $partecipazione = getPartecipazione($partita_id, $_SESSION['user_id']);
    if (!$partecipazione) {
        header('Location: /CodeRush/');
        exit();
    }
    $slotId = $partecipazione['id'];
    if ($partita['stato'] === 'scrittura') {
        $myTurno = getTurnoCorrente($partita_id, $_SESSION['user_id'], $partita['round_corrente']);
        if ($myTurno) {
            $codicePrecedente = getPreviousCodice($myTurno['slot_id'], $partita['round_corrente']);
        }
    }
}

$tempoRimanente = getTempoRimanente($partita);
$partecipazioni = getPartecipazioniByPartita($partita_id);
$nStudenti = count($partecipazioni);

$pageTitle = 'Rush — ' . $partita['domanda_nome'];
require_once __DIR__ . '/../includes/header.php';
?>
<main class="container">
    <?php if ($partita['stato'] === 'lettura'): ?>
    <div class="phase-banner phase-lettura">Fase di lettura — Studia la consegna</div>
    <?php elseif ($partita['stato'] === 'scrittura'): ?>
    <div class="phase-banner phase-scrittura">
        Turno <?= $partita['round_corrente'] + 1 ?> di <?= $nStudenti ?> — Scrivi il codice
    </div>
    <?php endif; ?>

    <div id="timer-display" class="timer-display"><?= gmdate('i:s', $tempoRimanente) ?></div>

    <div style="display: grid; grid-template-columns: 1fr<?= $partita['stato'] === 'scrittura' ? ' 1fr' : '' ?>; gap: 24px;">
        <div class="card">
            <div class="card-title">Consegna: <?= sanitize($partita['domanda_nome']) ?></div>
            <div style="white-space: pre-wrap; line-height: 1.7;"><?= sanitize($partita['domanda_testo']) ?></div>
            <div style="margin-top: 12px;">
                <span class="badge badge-host"><?= sanitize($partita['linguaggio_nome']) ?></span>
            </div>
        </div>

        <?php if ($partita['stato'] === 'scrittura' && isStudent() && $myTurno): ?>
        <div>
            <?php if ($codicePrecedente !== null): ?>
            <div class="card">
                <div class="card-title">Codice ricevuto (Turno <?= $partita['round_corrente'] ?>)</div>
                <div class="code-block"><?= sanitize($codicePrecedente) ?></div>
            </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-title">
                    <?= $partita['round_corrente'] === 0 ? 'Il tuo codice' : 'Continua / modifica il codice' ?>
                </div>
                <form id="formSubmit" onsubmit="submitCode(event)">
                    <textarea
                        id="codeEditor"
                        class="code-editor"
                        placeholder="Scrivi il tuo codice qui..."
                        <?= $myTurno['submitted_at'] ? 'disabled' : '' ?>
                    ><?= sanitize($myTurno['codice'] ?? ($codicePrecedente ?? '')) ?></textarea>
                    <?php if (!$myTurno['submitted_at']): ?>
                    <div style="margin-top: 12px; display: flex; gap: 10px; align-items: center;">
                        <button type="submit" id="btnSubmit" class="btn btn-success">Consegna codice</button>
                        <span id="submit-msg" style="font-size: 13px; color: var(--text-muted);"></span>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-success" style="margin-top: 12px;">Codice consegnato. In attesa degli altri...</div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <?php if (isHost()): ?>
        <div class="card">
            <div class="card-title">Stato studenti</div>
            <div id="host-status">
                <?php foreach ($partecipazioni as $p): ?>
                <div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid var(--border);">
                    <span><?= sanitize($p['cognome'] . ' ' . $p['nome']) ?></span>
                    <?php if ($partita['stato'] === 'scrittura'): ?>
                    <?php
                        $t = getTurnoCorrente($partita_id, $p['studente_id'], $partita['round_corrente']);
                    ?>
                    <span class="badge <?= ($t && $t['submitted_at']) ? 'badge-corretto' : 'badge-attesa' ?>">
                        <?= ($t && $t['submitted_at']) ? 'Consegnato' : 'Scrive...' ?>
                    </span>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php if ($partita['stato'] === 'lettura'): ?>
            <div style="margin-top: 16px;">
                <button class="btn btn-primary" onclick="advancePhase()">Avanza alla scrittura</button>
            </div>
            <?php elseif ($partita['stato'] === 'scrittura'): ?>
            <div style="margin-top: 16px;">
                <button class="btn btn-warning" onclick="advancePhase()" style="background: var(--warning); color: #000;">Forza turno successivo</button>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</main>
</body>
</html>
<script>
const PARTITA_ID = <?= $partita_id ?>;
const IS_HOST = <?= isHost() ? 'true' : 'false' ?>;
const STATO_INIT = '<?= $partita['stato'] ?>';
let timerSeconds = <?= $tempoRimanente ?>;
let timerInterval = null;

function updateTimerDisplay() {
    const el = document.getElementById('timer-display');
    const mins = Math.floor(timerSeconds / 60);
    const secs = timerSeconds % 60;
    el.textContent = String(mins).padStart(2, '0') + ':' + String(secs).padStart(2, '0');
    el.className = 'timer-display';
    if (timerSeconds <= 10) el.classList.add('danger');
    else if (timerSeconds <= 30) el.classList.add('warning');
}

function startTimer() {
    if (timerInterval) clearInterval(timerInterval);
    timerInterval = setInterval(() => {
        if (timerSeconds > 0) {
            timerSeconds--;
            updateTimerDisplay();
        }
        if (timerSeconds <= 0) {
            clearInterval(timerInterval);
            pollGameState();
        }
    }, 1000);
}

function pollGameState() {
    fetch('/CodeRush/api/api.php?action=game_state&id=' + PARTITA_ID)
        .then(r => r.json())
        .then(data => {
            if (data.stato === 'finita') {
                window.location.href = '/CodeRush/pages/risultati.php?id=' + PARTITA_ID;
            } else if (data.stato !== STATO_INIT || data.round !== <?= $partita['round_corrente'] ?>) {
                window.location.reload();
            } else {
                if (data.tempo_rimanente !== undefined) {
                    timerSeconds = data.tempo_rimanente;
                    updateTimerDisplay();
                }
            }
        })
        .catch(() => {});
}

function submitCode(e) {
    e.preventDefault();
    const code = document.getElementById('codeEditor').value;
    const btn = document.getElementById('btnSubmit');
    const msg = document.getElementById('submit-msg');
    btn.disabled = true;
    msg.textContent = 'Invio in corso...';
    fetch('/CodeRush/api/api.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({action: 'submit_code', partita_id: PARTITA_ID, codice: code})
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.getElementById('codeEditor').disabled = true;
            msg.textContent = 'Consegnato! In attesa degli altri...';
            msg.style.color = 'var(--success)';
            if (data.game_ended) {
                setTimeout(() => window.location.href = '/CodeRush/pages/risultati.php?id=' + PARTITA_ID, 1500);
            }
        } else {
            msg.textContent = data.error || 'Errore invio.';
            msg.style.color = 'var(--danger)';
            btn.disabled = false;
        }
    });
}

function advancePhase() {
    fetch('/CodeRush/api/api.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({action: 'advance_phase', partita_id: PARTITA_ID})
    })
    .then(r => r.json())
    .then(data => {
        if (data.stato === 'finita') {
            window.location.href = '/CodeRush/pages/risultati.php?id=' + PARTITA_ID;
        } else {
            window.location.reload();
        }
    });
}

startTimer();
setInterval(pollGameState, 5000);
</script>
