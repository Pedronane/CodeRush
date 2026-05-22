<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';
if (!isLoggedIn() || !isStudent()) { header('Location: /CodeRush/login.php'); exit(); }

$rush   = getPartiteByStudente($_SESSION['user_id']);
$nTot   = count($rush);
$nCorr  = count(array_filter($rush, fn($r) => $r['voto'] === 'corretto'));
$nParz  = count(array_filter($rush, fn($r) => $r['voto'] === 'parziale'));
$nSbag  = count(array_filter($rush, fn($r) => $r['voto'] === 'sbagliato'));
$nPend  = $nTot - $nCorr - $nParz - $nSbag;

$lingueUsate = [];
foreach ($rush as $r) {
    $lingueUsate[$r['linguaggio_id']] = $r['linguaggio_nome'];
}

$pageTitle = 'I miei Rush';
require_once __DIR__ . '/../includes/header.php';
?>
<link rel="stylesheet" href="/CodeRush/css/pages/miei-rush.css">
<main class="container">

    <div class="breadcrumb page-section-breadcrumb">
        <a href="/CodeRush/">Home</a>
        <span class="breadcrumb-sep">›</span>
        <span>I miei Rush</span>
    </div>

    <div class="page-header mr-header page-section-header">
        <div>
            <h1>I miei Rush</h1>
            <p class="page-subtitle"><?= $nTot ?> rush completat<?= $nTot !== 1 ? 'i' : 'o' ?></p>
        </div>
        <a href="/CodeRush/pages/partecipa.php" class="btn-primary-lg">▸ Partecipa</a>
    </div>

    <?php if ($nTot === 0): ?>
    <div class="mr-empty">
        <div class="mr-empty-icon">🏁</div>
        <div class="mr-empty-title">Nessun rush ancora</div>
        <p class="mr-empty-sub">Partecipa al tuo primo Rush con il codice che ti dà il professore.</p>
        <a href="/CodeRush/pages/partecipa.php" class="btn-primary-lg">▸ Partecipa al Rush</a>
    </div>
    <?php else: ?>

    <!-- Stats -->
    <div class="mr-stats">
        <div class="mr-stat mr-stat-total">
            <div class="mr-stat-value"><?= $nTot ?></div>
            <div class="mr-stat-label">Totali</div>
        </div>
        <div class="mr-stat mr-stat-ok">
            <div class="mr-stat-value"><?= $nCorr ?></div>
            <div class="mr-stat-label">Corretti</div>
        </div>
        <div class="mr-stat mr-stat-parz">
            <div class="mr-stat-value"><?= $nParz ?></div>
            <div class="mr-stat-label">Parziali</div>
        </div>
        <div class="mr-stat mr-stat-ko">
            <div class="mr-stat-value"><?= $nSbag ?></div>
            <div class="mr-stat-label">Sbagliati</div>
        </div>
    </div>

    <!-- Filters -->
    <div class="mr-filters">
        <div class="mr-filter-row">
            <span class="mr-filter-label">Voto</span>
            <button type="button" class="mr-pill active-all"       data-filter="voto" data-val="">Tutti</button>
            <button type="button" class="mr-pill"                  data-filter="voto" data-val="corretto">Corretto</button>
            <button type="button" class="mr-pill"                  data-filter="voto" data-val="parziale">Parziale</button>
            <button type="button" class="mr-pill"                  data-filter="voto" data-val="sbagliato">Sbagliato</button>
            <?php if ($nPend > 0): ?>
            <button type="button" class="mr-pill"                  data-filter="voto" data-val="pending">In valutazione</button>
            <?php endif; ?>
        </div>
        <?php if (count($lingueUsate) > 1): ?>
        <div class="mr-filter-row" id="langFilterRow">
            <span class="mr-filter-label">Linguaggio</span>
            <button type="button" class="mr-pill active-lang"      data-filter="lang" data-val="">Tutti</button>
            <?php foreach ($lingueUsate as $lid => $lnome): ?>
            <button type="button" class="mr-pill"                  data-filter="lang" data-val="<?= $lid ?>"><?= sanitize($lnome) ?></button>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Rush list -->
    <div class="mr-list" id="mrList">
        <?php foreach ($rush as $i => $r):
            $voto      = $r['voto'] ?? '';
            $badgeCls  = $voto ? 'badge-'.$voto : 'badge-attesa';
            $votoLabel = $voto ? ucfirst($voto) : 'In valutazione';
            $delay     = number_format(min($i, 10) * 0.04, 2);
        ?>
        <div class="mr-card"
             data-voto="<?= $voto ?>"
             data-lang="<?= $r['linguaggio_id'] ?>"
             style="animation:fade-up .4s ease-out <?= $delay ?>s both;">
            <div class="mr-card-main">
                <div class="mr-card-name"><?= sanitize($r['domanda_nome']) ?></div>
                <div class="mr-card-meta">
                    <span class="mr-lang" data-n="<?= sanitize($r['linguaggio_nome']) ?>"><?= sanitize($r['linguaggio_nome']) ?></span>
                    <span class="sep">·</span>
                    <span><?= sanitize($r['anno'].$r['sezione'].' '.$r['indirizzo']) ?></span>
                    <span class="sep">·</span>
                    <span><?= date('d/m/Y', strtotime($r['created_at'])) ?></span>
                </div>
            </div>
            <div class="mr-card-right">
                <span class="badge <?= $badgeCls ?>"><?= $votoLabel ?></span>
                <a href="/CodeRush/pages/risultati.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-outline">Rivedi →</a>
            </div>
        </div>
        <?php endforeach; ?>
        <div class="mr-no-results" id="mrNoResults">Nessun rush corrisponde ai filtri selezionati.</div>
    </div>

    <?php endif; ?>

</main>
<script>
(function () {
    var cards   = document.querySelectorAll('.mr-card');
    var noRes   = document.getElementById('mrNoResults');
    var activeVoto = '';
    var activeLang = '';

    function applyFilters() {
        var visible = 0;
        cards.forEach(function (c) {
            var votoMatch = activeVoto === ''
                ? true
                : activeVoto === 'pending'
                    ? c.dataset.voto === ''
                    : c.dataset.voto === activeVoto;
            var langMatch = activeLang === '' || c.dataset.lang === activeLang;
            var show = votoMatch && langMatch;
            c.style.display = show ? '' : 'none';
            if (show) visible++;
        });
        if (noRes) noRes.style.display = visible === 0 ? 'block' : 'none';
    }

    document.querySelectorAll('.mr-pill').forEach(function (pill) {
        pill.addEventListener('click', function () {
            var filter = pill.dataset.filter;
            var val    = pill.dataset.val;

            document.querySelectorAll('.mr-pill[data-filter="' + filter + '"]').forEach(function (p) {
                p.className = p.className.replace(/\bactive-\S+/g, '').trim();
            });

            if (filter === 'voto') {
                activeVoto = val;
                var cls = val === '' ? 'active-all' : val === 'pending' ? 'active-attesa' : 'active-' + val;
                pill.classList.add(cls);
            } else {
                activeLang = val;
                pill.classList.add(val === '' ? 'active-lang' : 'active-lang');
            }

            applyFilters();
        });
    });
})();
</script>
<script src="/CodeRush/js/script.js"></script>
</body>
</html>
