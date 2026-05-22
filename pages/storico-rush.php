<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';
if (!isHost()) { header('Location: /CodeRush/login.php'); exit(); }

$db     = getDB();
$rushes = getRushesStorico($_SESSION['user_id']);

$nTot      = count($rushes);
$classiSet = array_unique(array_column($rushes, 'classe_id'));
$domandeSet = array_unique(array_column($rushes, 'domanda_id'));

$classi  = getAllClassi();
$domande = getDomandeByHost($_SESSION['user_id']);

// Solo classi/consegne che appaiono nei rush (per pill filter)
$classiInRush  = array_filter($classi,  fn($c) => in_array($c['id'], $classiSet));
$domandeInRush = array_filter($domande, fn($d) => in_array($d['id'], $domandeSet));

// Studenti per classe → JSON per cascading JS
$stmtSC = $db->query(
    'SELECT u.id, u.nome, u.cognome, sc.classe_id
     FROM users u
     JOIN studente_classe sc ON sc.studente_id = u.id
     ORDER BY u.cognome, u.nome'
);
$studentiPerClasse = [];
foreach ($stmtSC->fetchAll() as $s) {
    $studentiPerClasse[$s['classe_id']][] = [
        'id'      => $s['id'],
        'nome'    => $s['nome'],
        'cognome' => $s['cognome'],
    ];
}

$pageTitle = 'Storico Rush';
require_once __DIR__ . '/../includes/header.php';
?>
<link rel="stylesheet" href="/CodeRush/css/pages/storico-rush.css">
<main class="container">

    <div class="breadcrumb page-section-breadcrumb">
        <a href="/CodeRush/">Home</a>
        <span class="breadcrumb-sep">›</span>
        <span>Storico Rush</span>
    </div>

    <div class="page-header page-section-header">
        <div>
            <h1>Storico Rush</h1>
            <p class="page-subtitle"><?= $nTot ?> rush completat<?= $nTot !== 1 ? 'i' : 'o' ?></p>
        </div>
        <a href="/CodeRush/pages/rush.php" class="btn-primary-lg">▶ Nuovo Rush</a>
    </div>

    <?php if ($nTot === 0): ?>
    <div class="sr-empty">
        <div class="sr-empty-icon">🏁</div>
        <div class="sr-empty-title">Nessun rush ancora</div>
        <p class="sr-empty-sub">Crea il tuo primo Rush per iniziare.</p>
        <a href="/CodeRush/pages/rush.php" class="btn-primary-lg">▶ Avvia un Rush</a>
    </div>
    <?php else: ?>

    <!-- Stats -->
    <div class="sr-stats">
        <div class="sr-stat">
            <div class="sr-stat-value"><?= $nTot ?></div>
            <div class="sr-stat-label">Rush totali</div>
        </div>
        <div class="sr-stat">
            <div class="sr-stat-value"><?= count($classiSet) ?></div>
            <div class="sr-stat-label">Classi coinvolte</div>
        </div>
        <div class="sr-stat">
            <div class="sr-stat-value"><?= count($domandeSet) ?></div>
            <div class="sr-stat-label">Consegne usate</div>
        </div>
        <div class="sr-stat">
            <div class="sr-stat-value" id="statVisible"><?= $nTot ?></div>
            <div class="sr-stat-label">Risultati filtro</div>
        </div>
    </div>

    <!-- Filters -->
    <div class="sf-panel">

        <div class="sf-row">
            <span class="sf-label">Classe</span>
            <div class="sf-pills">
                <button class="sf-pill sf-pill-classe active" data-filter="classe" data-val="">Tutte</button>
                <?php foreach ($classiInRush as $cl): ?>
                <button class="sf-pill sf-pill-classe" data-filter="classe" data-val="<?= $cl['id'] ?>"
                        data-nome="<?= sanitize($cl['anno'].$cl['sezione'].' '.$cl['indirizzo']) ?>">
                    <span class="sf-pill-code"><?= $cl['anno'].$cl['sezione'] ?></span>
                    <span class="sf-pill-sub"><?= sanitize($cl['indirizzo']) ?></span>
                </button>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="sf-row">
            <span class="sf-label">Consegna</span>
            <div class="sf-pills">
                <button class="sf-pill sf-pill-domanda active" data-filter="domanda" data-val="">Tutte</button>
                <?php foreach ($domandeInRush as $d): ?>
                <button class="sf-pill sf-pill-domanda" data-filter="domanda" data-val="<?= $d['id'] ?>">
                    <?= sanitize($d['nome']) ?>
                </button>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="sf-row sf-row-studente" id="sfStudenteRow">
            <span class="sf-label" id="sfStudenteLabel">Studente</span>
            <div class="sf-pills" id="sfStudentePills"></div>
        </div>

        <div class="sf-footer">
            <div class="sr-active-badge" id="srActiveBadge"></div>
            <button type="button" id="srReset" class="sf-reset">✕ Azzera filtri</button>
        </div>
    </div>

    <!-- Rush list -->
    <div class="sr-list" id="srList">
        <?php foreach ($rushes as $i => $r):
            $delay = number_format(min($i, 12) * 0.03, 2);
            $classeNome = $r['anno'].$r['sezione'].' '.$r['indirizzo'];
        ?>
        <div class="sr-card"
             data-id="<?= $r['id'] ?>"
             data-classe="<?= $r['classe_id'] ?>"
             data-domanda="<?= $r['domanda_id'] ?>"
             data-studenti="<?= implode(',', $r['studente_ids']) ?>"
             style="animation:fade-up .4s ease-out <?= $delay ?>s both;">
            <div class="sr-card-main">
                <div class="sr-card-top">
                    <span class="sr-classe-badge"><?= sanitize($classeNome) ?></span>
                    <span class="sr-date"><?= date('d/m/Y H:i', strtotime($r['created_at'])) ?></span>
                </div>
                <div class="sr-card-name"><?= sanitize($r['domanda_nome']) ?></div>
                <div class="sr-card-meta">
                    <span class="sr-lang" data-n="<?= sanitize($r['linguaggio_nome']) ?>"><?= sanitize($r['linguaggio_nome']) ?></span>
                    <span>·</span>
                    <span><?= (int)$r['n_partecipanti'] ?> partecipant<?= $r['n_partecipanti'] != 1 ? 'i' : 'e' ?></span>
                </div>
            </div>
            <div class="sr-card-right">
                <a href="/CodeRush/pages/risultati.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-primary">Risultati →</a>
            </div>
        </div>
        <?php endforeach; ?>
        <div class="sr-no-results" id="srNoResults">Nessun rush corrisponde ai filtri selezionati.</div>
    </div>

    <?php endif; ?>

</main>
<script>
(function () {
    var studentiPerClasse = <?= json_encode($studentiPerClasse) ?>;
    var cards         = document.querySelectorAll('.sr-card');
    var noResults     = document.getElementById('srNoResults');
    var statVis       = document.getElementById('statVisible');
    var activeBadge   = document.getElementById('srActiveBadge');
    var studenteRow   = document.getElementById('sfStudenteRow');
    var studenteLabel = document.getElementById('sfStudenteLabel');
    var studentePills = document.getElementById('sfStudentePills');
    var srReset       = document.getElementById('srReset');

    var activeClasse  = '';
    var activeDomanda = '';
    var activeStudente = '';

    function applyFilters() {
        var visible = 0;
        cards.forEach(function (card) {
            var matchC = !activeClasse   || card.dataset.classe   === activeClasse;
            var matchD = !activeDomanda  || card.dataset.domanda  === activeDomanda;
            var matchS = !activeStudente || (',' + card.dataset.studenti + ',').indexOf(',' + activeStudente + ',') !== -1;
            var show   = matchC && matchD && matchS;
            card.style.display = show ? '' : 'none';
            if (show) visible++;
        });
        if (noResults) noResults.style.display = visible === 0 ? 'block' : 'none';
        if (statVis)   statVis.textContent = visible;
        var n = [activeClasse, activeDomanda, activeStudente].filter(Boolean).length;
        if (activeBadge) {
            activeBadge.textContent  = n > 0 ? n + (n === 1 ? ' filtro attivo' : ' filtri attivi') : '';
            activeBadge.style.display = n > 0 ? 'inline-block' : 'none';
        }
    }

    function buildStudentePills(classeId, classeNome) {
        studentePills.innerHTML = '';
        var studenti = studentiPerClasse[classeId] || [];

        var all = document.createElement('button');
        all.type = 'button';
        all.className = 'sf-pill sf-pill-studente active';
        all.dataset.val = '';
        all.textContent = 'Tutti';
        all.addEventListener('click', function () {
            document.querySelectorAll('.sf-pill-studente').forEach(function(p){ p.classList.remove('active'); });
            all.classList.add('active');
            activeStudente = '';
            applyFilters();
        });
        studentePills.appendChild(all);

        studenti.forEach(function (s) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'sf-pill sf-pill-studente';
            btn.dataset.val = s.id;
            btn.textContent = s.cognome + ' ' + s.nome;
            btn.addEventListener('click', function () {
                document.querySelectorAll('.sf-pill-studente').forEach(function(p){ p.classList.remove('active'); });
                btn.classList.add('active');
                activeStudente = String(s.id);
                applyFilters();
            });
            studentePills.appendChild(btn);
        });

        studenteLabel.textContent = 'Studente — ' + classeNome;
        studenteRow.style.display = '';
    }

    // Classe pills
    document.querySelectorAll('.sf-pill-classe').forEach(function (pill) {
        pill.addEventListener('click', function () {
            document.querySelectorAll('.sf-pill-classe').forEach(function(p){ p.classList.remove('active'); });
            pill.classList.add('active');
            activeClasse   = pill.dataset.val;
            activeStudente = '';

            if (activeClasse) {
                buildStudentePills(activeClasse, pill.dataset.nome || '');
            } else {
                studenteRow.style.display = 'none';
                studentePills.innerHTML   = '';
            }
            applyFilters();
        });
    });

    // Consegna pills
    document.querySelectorAll('.sf-pill-domanda').forEach(function (pill) {
        pill.addEventListener('click', function () {
            document.querySelectorAll('.sf-pill-domanda').forEach(function(p){ p.classList.remove('active'); });
            pill.classList.add('active');
            activeDomanda = pill.dataset.val;
            applyFilters();
        });
    });

    // Reset
    srReset.addEventListener('click', function () {
        activeClasse = activeDomanda = activeStudente = '';
        document.querySelectorAll('.sf-pill').forEach(function(p){ p.classList.remove('active'); });
        document.querySelector('.sf-pill-classe').classList.add('active');
        document.querySelector('.sf-pill-domanda').classList.add('active');
        studenteRow.style.display = 'none';
        studentePills.innerHTML   = '';
        applyFilters();
    });
})();
</script>
<script src="/CodeRush/js/script.js"></script>
</body>
</html>
