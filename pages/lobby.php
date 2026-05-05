<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';

if (!isHost()) {
    header('Location: /CodeRush/login.php');
    exit();
}

$partita_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$partita = $partita_id > 0 ? getPartitaById($partita_id) : null;

if (!$partita || $partita['host_id'] != $_SESSION['user_id']) {
    header('Location: /CodeRush/pages/rush.php');
    exit();
}

if ($partita['stato'] !== 'attesa') {
    header('Location: /CodeRush/pages/game.php?id=' . $partita_id);
    exit();
}

$pageTitle = 'Lobby — ' . $partita['codice_accesso'];
require_once __DIR__ . '/../includes/header.php';
?>
<main class="container">
    <div class="page-header">
        <h1>Lobby — In attesa di studenti</h1>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 320px; gap: 24px; align-items: start;">
        <div>
            <div class="card" style="text-align: center;">
                <div class="card-title">Codice partita</div>
                <p style="color: var(--text-muted); margin-bottom: 8px;">Gli studenti entrano usando questo codice</p>
                <div class="game-code"><?= sanitize($partita['codice_accesso']) ?></div>
            </div>

            <div class="card">
                <div class="card-title" style="display: flex; justify-content: space-between;">
                    <span>Studenti connessi</span>
                    <span id="count-badge" class="badge badge-host">0 studenti</span>
                </div>
                <div id="lobby-grid" class="lobby-grid">
                    <div style="color: var(--text-muted); text-align: center; grid-column: 1/-1; padding: 24px;">
                        In attesa...
                    </div>
                </div>
            </div>

            <div style="text-align: center; margin-top: 16px;">
                <button
                    id="btnStart"
                    class="btn btn-success"
                    style="font-size: 18px; padding: 14px 48px;"
                    disabled
                    onclick="startGame()"
                >
                    START
                </button>
                <p id="start-hint" style="color: var(--text-muted); font-size: 13px; margin-top: 8px;">
                    Servono almeno 2 studenti per iniziare.
                </p>
            </div>
        </div>

        <div class="card">
            <div class="card-title">Dettagli partita</div>
            <div style="display: flex; flex-direction: column; gap: 12px;">
                <div>
                    <div class="form-label">Consegna</div>
                    <div><?= sanitize($partita['domanda_nome']) ?></div>
                </div>
                <div>
                    <div class="form-label">Linguaggio</div>
                    <div><?= sanitize($partita['linguaggio_nome']) ?></div>
                </div>
                <div>
                    <div class="form-label">Classe</div>
                    <div><?= sanitize($partita['anno'] . $partita['sezione'] . ' ' . $partita['indirizzo']) ?></div>
                </div>
                <div>
                    <div class="form-label">Tempo lettura</div>
                    <div><?= $partita['tempo_lettura'] ?> secondi</div>
                </div>
                <div>
                    <div class="form-label">Tempo per turno</div>
                    <div><?= $partita['tempo_turno'] ?> secondi</div>
                </div>
            </div>
        </div>
    </div>
</main>
</body>
</html>
<script>
const PARTITA_ID = <?= $partita_id ?>;
let currentStudents = [];

function pollLobby() {
    fetch('/CodeRush/api/api.php?action=lobby_state&id=' + PARTITA_ID)
        .then(r => r.json())
        .then(data => {
            if (data.stato !== 'attesa') {
                window.location.href = '/CodeRush/pages/game.php?id=' + PARTITA_ID;
                return;
            }
            currentStudents = data.studenti || [];
            renderStudenti(currentStudents);
            const btn = document.getElementById('btnStart');
            const hint = document.getElementById('start-hint');
            if (currentStudents.length >= 2) {
                btn.disabled = false;
                hint.textContent = currentStudents.length + ' studenti pronti. Puoi iniziare!';
            } else {
                btn.disabled = true;
                hint.textContent = 'Servono almeno 2 studenti (' + currentStudents.length + '/2).';
            }
        })
        .catch(() => {});
}

function renderStudenti(studenti) {
    const grid = document.getElementById('lobby-grid');
    const badge = document.getElementById('count-badge');
    badge.textContent = studenti.length + ' student' + (studenti.length === 1 ? 'e' : 'i');
    if (studenti.length === 0) {
        grid.innerHTML = '<div style="color: var(--text-muted); text-align: center; grid-column: 1/-1; padding: 24px;">In attesa...</div>';
    } else {
        grid.innerHTML = studenti.map(s => {
            const initials = (s.nome[0] || '') + (s.cognome[0] || '');
            return '<div class="lobby-student"><div class="initials">' + initials.toUpperCase() + '</div>' + s.nome + ' ' + s.cognome + '</div>';
        }).join('');
    }
}

function startGame() {
    document.getElementById('btnStart').disabled = true;
    fetch('/CodeRush/api/api.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({action: 'start_game', partita_id: PARTITA_ID})
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            window.location.href = '/CodeRush/pages/game.php?id=' + PARTITA_ID;
        } else {
            alert(data.error || 'Errore avvio partita.');
            document.getElementById('btnStart').disabled = false;
        }
    });
}

pollLobby();
setInterval(pollLobby, 3000);
</script>
