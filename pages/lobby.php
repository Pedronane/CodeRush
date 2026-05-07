<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';
if (!isHost()) { header('Location: /CodeRush/login.php'); exit(); }

$partita_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$partita    = $partita_id > 0 ? getPartitaById($partita_id) : null;
if (!$partita || $partita['host_id'] != $_SESSION['user_id']) { header('Location: /CodeRush/pages/rush.php'); exit(); }
if ($partita['stato'] !== 'attesa') { header('Location: /CodeRush/pages/game.php?id='.$partita_id); exit(); }

$pageTitle = 'Lobby — '.$partita['codice_accesso'];
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
<div data-particles="22" style="position:fixed;inset:0;z-index:0;pointer-events:none;"></div>

<div class="lobby-full">
    <div class="lobby-inner" style="position:relative;z-index:1;">

        <!-- Left: code + students -->
        <div>
            <p class="lobby-game-code-label" style="animation:fade-up .3s ease-out both;">Codice partita</p>
            <div class="game-code" style="animation:fade-up .4s ease-out both;"><?= sanitize($partita['codice_accesso']) ?></div>
            <p style="color:var(--muted-foreground);font-size:14px;margin-top:12px;animation:fade-up .45s ease-out both;">
                Condividi questo codice con la classe. Gli studenti appariranno qui sotto.
            </p>

            <div class="lobby-count" id="count-display" style="animation:fade-up .5s ease-out both;">
                <span class="lobby-count-num" id="count-num">0</span>
                <span style="font-size:14px;color:var(--muted-foreground);">studenti connessi</span>
            </div>

            <div id="lobby-grid" class="lobby-grid">
                <p style="color:var(--muted-foreground);grid-column:1/-1;text-align:center;padding:40px 0;">
                    In attesa che gli studenti si uniscano<span id="dots" style="animation:pulse-soft 1s ease-in-out infinite;display:inline-block;">...</span>
                </p>
            </div>
        </div>

        <!-- Right: info + start -->
        <aside>
            <div class="lobby-info-card">
                <p class="lobby-info-title">Info partita</p>
                <div style="display:flex;flex-direction:column;gap:8px;">
                    <?php foreach ([
                        ['Consegna',      $partita['domanda_nome']],
                        ['Linguaggio',    $partita['linguaggio_nome']],
                        ['Classe',        $partita['anno'].$partita['sezione'].' '.$partita['indirizzo']],
                        ['Tempo lettura', $partita['tempo_lettura'].'s'],
                        ['Tempo turno',   $partita['tempo_turno'].'s'],
                    ] as $row): ?>
                    <div class="info-row">
                        <span class="info-label"><?= $row[0] ?></span>
                        <span class="info-val" <?= $row[0]==='Linguaggio' ? 'style="color:var(--brand-green);"' : '' ?>><?= sanitize($row[1]) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php if ($partita['domanda_testo']): ?>
                <p style="margin-top:12px;font-size:12px;color:var(--muted-foreground);display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden;">
                    <?= sanitize($partita['domanda_testo']) ?>
                </p>
                <?php endif; ?>
            </div>

            <button
                id="btnStart"
                class="lobby-start-btn"
                disabled
                onclick="startGame()"
            >
                ▶ Avvia il Rush
                <span id="start-hint" style="display:block;font-size:11px;font-weight:600;letter-spacing:.02em;margin-top:4px;opacity:.9;">
                    Servono almeno 2 studenti
                </span>
            </button>
        </aside>

    </div>
</div>

<script src="/CodeRush/js/script.js"></script>
<script>
var PARTITA_ID = <?= $partita_id ?>;
var currentStudents = [];

function pollLobby() {
    fetch('/CodeRush/api/api.php?action=lobby_state&id='+PARTITA_ID)
        .then(function(r){ return r.json(); })
        .then(function(data) {
            if (data.stato !== 'attesa') {
                window.location.href = '/CodeRush/pages/game.php?id='+PARTITA_ID;
                return;
            }
            currentStudents = data.studenti || [];
            renderStudenti(currentStudents);
            var btn  = document.getElementById('btnStart');
            var hint = document.getElementById('start-hint');
            var countNum = document.getElementById('count-num');
            countNum.textContent = currentStudents.length;
            countNum.style.animation = 'none';
            countNum.offsetHeight;
            countNum.style.animation = 'pop-in .45s cubic-bezier(.34,1.56,.64,1)';
            if (currentStudents.length >= 2) {
                btn.disabled = false;
                hint.textContent = currentStudents.length + ' studenti pronti. Puoi iniziare!';
            } else {
                btn.disabled = true;
                hint.textContent = 'Servono almeno 2 studenti ('+currentStudents.length+'/2).';
            }
        })
        .catch(function(){});
}

function renderStudenti(studenti) {
    var grid = document.getElementById('lobby-grid');
    if (studenti.length === 0) {
        grid.innerHTML = '<p style="color:var(--muted-foreground);grid-column:1/-1;text-align:center;padding:40px 0;">In attesa che gli studenti si uniscano...</p>';
    } else {
        grid.innerHTML = studenti.map(function(s) {
            var ini = (s.nome[0]||'')+(s.cognome[0]||'');
            return '<div class="lobby-student">' +
                '<div class="initials">'+ini.toUpperCase()+'</div>' +
                '<span class="name">'+s.nome+' '+s.cognome+'</span></div>';
        }).join('');
    }
}

function startGame() {
    document.getElementById('btnStart').disabled = true;
    fetch('/CodeRush/api/api.php', {
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body:JSON.stringify({action:'start_game',partita_id:PARTITA_ID})
    })
    .then(function(r){ return r.json(); })
    .then(function(data) {
        if (data.success) window.location.href='/CodeRush/pages/game.php?id='+PARTITA_ID;
        else { alert(data.error||'Errore avvio partita.'); document.getElementById('btnStart').disabled=false; }
    });
}

pollLobby();
setInterval(pollLobby, 3000);
</script>
</body>
</html>
