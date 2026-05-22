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
    <div class="sr-filters">
        <div class="sr-filters-grid">
            <div class="sr-filter-group">
                <label class="sr-filter-label">Classe</label>
                <select id="fClasse" class="input-arena">
                    <option value="">Tutte le classi</option>
                    <?php foreach ($classi as $cl): ?>
                    <option value="<?= $cl['id'] ?>"><?= sanitize($cl['anno'].$cl['sezione'].' '.$cl['indirizzo']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="sr-filter-group">
                <label class="sr-filter-label">Consegna</label>
                <select id="fDomanda" class="input-arena">
                    <option value="">Tutte le consegne</option>
                    <?php foreach ($domande as $d): ?>
                    <option value="<?= $d['id'] ?>"><?= sanitize($d['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="sr-filter-group">
                <label class="sr-filter-label">Studente</label>
                <select id="fStudente" class="input-arena" disabled>
                    <option value="">— seleziona prima una classe —</option>
                </select>
            </div>
            <button type="button" id="srReset" class="sr-reset">✕ Reset</button>
        </div>
        <div class="sr-active-badge" id="srActiveBadge"></div>
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
                <a href="/CodeRush/pages/risultati.php?id=<?= $r['id'] ?>"   class="btn btn-sm btn-outline">Risultati</a>
                <a href="/CodeRush/pages/rush-detail.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-primary">Analisi →</a>
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
    var cards      = document.querySelectorAll('.sr-card');
    var fClasse    = document.getElementById('fClasse');
    var fDomanda   = document.getElementById('fDomanda');
    var fStudente  = document.getElementById('fStudente');
    var srReset    = document.getElementById('srReset');
    var noResults  = document.getElementById('srNoResults');
    var statVis    = document.getElementById('statVisible');
    var activeBadge= document.getElementById('srActiveBadge');

    function updateStudentSelect(classeId) {
        fStudente.innerHTML = '';
        if (!classeId) {
            fStudente.disabled = true;
            fStudente.innerHTML = '<option value="">— seleziona prima una classe —</option>';
            return;
        }
        var studenti = studentiPerClasse[classeId] || [];
        var opt = document.createElement('option');
        opt.value = ''; opt.textContent = 'Tutti gli studenti';
        fStudente.appendChild(opt);
        studenti.forEach(function (s) {
            var o = document.createElement('option');
            o.value = s.id;
            o.textContent = s.cognome + ' ' + s.nome;
            fStudente.appendChild(o);
        });
        fStudente.disabled = studenti.length === 0;
    }

    function applyFilters() {
        var cId = fClasse.value;
        var dId = fDomanda.value;
        var sId = fStudente.value;
        var visible = 0;

        cards.forEach(function (card) {
            var matchC = !cId || card.dataset.classe === cId;
            var matchD = !dId || card.dataset.domanda === dId;
            var matchS = !sId || (',' + card.dataset.studenti + ',').indexOf(',' + sId + ',') !== -1;
            var show   = matchC && matchD && matchS;
            card.style.display = show ? '' : 'none';
            if (show) visible++;
        });

        if (noResults)  noResults.style.display  = visible === 0 ? 'block' : 'none';
        if (statVis)    statVis.textContent = visible;

        var active = [cId, dId, sId].filter(Boolean).length;
        if (activeBadge) {
            activeBadge.textContent = active > 0 ? active + (active === 1 ? ' filtro attivo' : ' filtri attivi') : '';
            activeBadge.style.display = active > 0 ? 'inline-block' : 'none';
        }
    }

    fClasse.addEventListener('change', function () {
        fStudente.value = '';
        updateStudentSelect(fClasse.value);
        applyFilters();
    });

    fDomanda.addEventListener('change', applyFilters);
    fStudente.addEventListener('change', applyFilters);

    srReset.addEventListener('click', function () {
        fClasse.value   = '';
        fDomanda.value  = '';
        updateStudentSelect('');
        applyFilters();
    });
})();
</script>
<script src="/CodeRush/js/script.js"></script>
</body>
</html>
