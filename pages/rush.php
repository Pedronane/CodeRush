<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';
if (!isHost()) { header('Location: /CodeRush/login.php'); exit(); }

$db = getDB();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $classe_id     = (int)($_POST['classe_id']     ?? 0);
    $domanda_id    = (int)($_POST['domanda_id']    ?? 0);
    $tempo_lettura = (int)($_POST['tempo_lettura'] ?? 0);
    $tempo_turno   = (int)($_POST['tempo_turno']   ?? 0);

    if ($classe_id <= 0)                               $errors[] = 'Seleziona una classe.';
    if ($domanda_id <= 0)                              $errors[] = 'Seleziona una consegna.';
    if ($tempo_lettura < 10 || $tempo_lettura > 600)   $errors[] = 'Tempo lettura: da 10 a 600 secondi.';
    if ($tempo_turno  < 30  || $tempo_turno  > 1800)   $errors[] = 'Tempo per turno: da 30 a 1800 secondi.';

    if (empty($errors)) {
        $classe  = getClasseById($classe_id);
        $domanda = getDomandaById($domanda_id);
        if (!$classe || !$domanda) {
            $errors[] = 'Classe o consegna non valida.';
        } else {
            $codice = generateAccessCode();
            $db->prepare(
                'INSERT INTO partite (host_id,classe_id,domanda_id,tempo_lettura,tempo_turno,codice_accesso)
                 VALUES (?,?,?,?,?,?)'
            )->execute([$_SESSION['user_id'],$classe_id,$domanda_id,$tempo_lettura,$tempo_turno,$codice]);
            header('Location: /CodeRush/pages/lobby.php?id='.$db->lastInsertId());
            exit();
        }
    }
}

$classi  = getAllClassi();
$domande = getDomandeByHost($_SESSION['user_id']);

$pageTitle = 'Nuovo Rush';
require_once __DIR__ . '/../includes/header.php';
?>
<link rel="stylesheet" href="/CodeRush/css/pages/rush.css">
<main class="container">

    <div class="breadcrumb page-section-breadcrumb">
        <a href="/CodeRush/">Home</a>
        <span class="breadcrumb-sep">›</span>
        <span>Nuovo Rush</span>
    </div>

    <div class="page-header page-section-header">
        <div>
            <h1>Crea un nuovo Rush</h1>
            <p class="page-subtitle">Configura partita, classe e tempi</p>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger page-section-alert"><?= implode('<br>', array_map('sanitize', $errors)) ?></div>
    <?php endif; ?>

    <?php if (empty($classi)): ?>
    <div class="alert alert-warning">
        Nessuna classe disponibile. <a href="/CodeRush/pages/classi.php">Crea una classe</a> prima di avviare un Rush.
    </div>
    <?php elseif (empty($domande)): ?>
    <div class="alert alert-warning">
        Nessuna consegna disponibile. <a href="/CodeRush/pages/nuova-domanda.php">Crea una consegna</a> prima di avviare un Rush.
    </div>
    <?php else: ?>
    <div class="rush-layout">

        <!-- Form -->
        <div class="card form-card">
            <form method="POST" novalidate id="formRush">

                <div class="form-group">
                    <label class="form-label">Classe</label>
                    <select name="classe_id" id="selectClasse" class="input-arena" required>
                        <option value="0">— Seleziona classe —</option>
                        <?php foreach ($classi as $cl): ?>
                        <option value="<?= $cl['id'] ?>">
                            <?= sanitize($cl['anno'].$cl['sezione'].' '.$cl['indirizzo']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Consegna</label>
                    <select name="domanda_id" id="selectDomanda" class="input-arena" required>
                        <option value="0">— Seleziona consegna —</option>
                        <?php foreach ($domande as $d): ?>
                        <option value="<?= $d['id'] ?>">
                            <?= sanitize($d['nome']) ?> — <?= sanitize($d['linguaggio_nome']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Tempo lettura slider -->
                <div class="form-group">
                    <label class="form-label form-label-split">
                        <span>Tempo lettura</span>
                        <span id="lettura-val" class="form-label-value">60s</span>
                    </label>
                    <input type="range" name="tempo_lettura" id="sliderLettura"
                           min="10" max="600" step="10" value="60"
                           class="slider-range">
                    <div class="slider-labels">
                        <span>10s</span><span>5 minuti</span><span>10 min</span>
                    </div>
                </div>

                <!-- Tempo turno slider -->
                <div class="form-group">
                    <label class="form-label form-label-split">
                        <span>Tempo per turno</span>
                        <span id="turno-val" class="form-label-value-turno">120s</span>
                    </label>
                    <input type="range" name="tempo_turno" id="sliderTurno"
                           min="30" max="1800" step="30" value="120"
                           class="slider-range">
                    <div class="slider-labels">
                        <span>30s</span><span>15 min</span><span>30 min</span>
                    </div>
                </div>

                <button type="submit" class="btn-primary-lg btn-block submit-button">
                    ▶ Avvia il Rush
                </button>
            </form>
        </div>

        <!-- Preview panel -->
        <div class="preview-panel">
            <div class="card preview-card" id="previewCard">
                <p class="preview-label">Anteprima consegna</p>
                <p id="previewDomanda" class="preview-text"></p>
            </div>

            <div class="card info-card">
                <p class="info-label">Come funziona</p>
                <div class="info-steps">
                    <?php foreach ([
                        ['🔵','Lettura','Gli studenti leggono la consegna insieme'],
                        ['✍️','Scrittura','Ogni studente scrive e passa il codice'],
                        ['🏆','Risultati','AI valuta il codice finale della catena'],
                    ] as $step): ?>
                    <div class="info-step">
                        <span class="step-emoji"><?= $step[0] ?></span>
                        <div>
                            <div class="step-title"><?= $step[1] ?></div>
                            <div class="step-desc"><?= $step[2] ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</main>
<script>
var domande = <?= json_encode(array_map(function($d){ return ['id'=>$d['id'],'nome'=>$d['nome'],'testo'=>$d['testo']]; }, $domande)) ?>;

document.addEventListener('DOMContentLoaded', function() {
    var selL = document.getElementById('sliderLettura');
    var selT = document.getElementById('sliderTurno');
    if (selL) selL.addEventListener('input', function() {
        document.getElementById('lettura-val').textContent = this.value + 's';
    });
    if (selT) selT.addEventListener('input', function() {
        var v = parseInt(this.value);
        document.getElementById('turno-val').textContent = v >= 60 ? Math.floor(v/60)+'m'+((v%60)?' '+v%60+'s':'') : v+'s';
    });
    var selD = document.getElementById('selectDomanda');
    var card = document.getElementById('previewCard');
    var prev = document.getElementById('previewDomanda');
    if (selD && card && prev) {
        selD.addEventListener('change', function() {
            var d = domande.find(function(x){ return x.id == selD.value; });
            if (d) { prev.textContent = d.testo; card.style.display = 'block'; }
            else   { card.style.display = 'none'; }
        });
    }
});
</script>
<script src="/CodeRush/js/script.js"></script>
</body>
</html>
