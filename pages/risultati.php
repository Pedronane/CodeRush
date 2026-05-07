<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';
if (!isLoggedIn()) { header('Location: /CodeRush/login.php'); exit(); }

$partita_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$partita    = $partita_id > 0 ? getPartitaById($partita_id) : null;
if (!$partita) { header('Location: /CodeRush/'); exit(); }
if ($partita['stato'] !== 'finita') { header('Location: /CodeRush/pages/game.php?id='.$partita_id); exit(); }

if (isStudent()) {
    $part = getPartecipazione($partita_id, $_SESSION['user_id']);
    if (!$part) { header('Location: /CodeRush/'); exit(); }
}

$pageTitle = 'Risultati — '.$partita['domanda_nome'];
require_once __DIR__ . '/../includes/header.php';
?>
<main class="container">

    <div class="breadcrumb" style="animation:fade-up .35s ease-out both;">
        <a href="/CodeRush/">Home</a>
        <span class="breadcrumb-sep">›</span>
        <?php if (isHost()): ?>
        <a href="/CodeRush/pages/classe.php?id=<?= $partita['classe_id'] ?>"><?= sanitize($partita['anno'].$partita['sezione'].' '.$partita['indirizzo']) ?></a>
        <span class="breadcrumb-sep">›</span>
        <?php endif; ?>
        <span>Risultati</span>
    </div>

    <div class="page-header" style="animation:fade-up .4s ease-out both;">
        <div>
            <h1><?= sanitize($partita['domanda_nome']) ?></h1>
            <p class="page-subtitle">
                <span class="badge badge-host" style="margin-right:6px;"><?= sanitize($partita['linguaggio_nome']) ?></span>
                <?= sanitize($partita['anno'].$partita['sezione'].' '.$partita['indirizzo']) ?>
                &nbsp;·&nbsp; <?= date('d/m/Y H:i', strtotime($partita['created_at'])) ?>
            </p>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap;">
            <?php if (isHost()): ?>
            <a href="/CodeRush/pages/rush-detail.php?id=<?= $partita_id ?>" class="btn-primary-lg">Analisi completa →</a>
            <?php endif; ?>
            <a href="/CodeRush/" class="btn-ghost">← Home</a>
        </div>
    </div>

    <?php
    $partecipazioni = getPartecipazioniByPartita($partita_id);
    $db = getDB();
    $n  = count($partecipazioni);
    $votoColors = ['corretto'=>'var(--brand-green)','parziale'=>'var(--brand-orange)','sbagliato'=>'var(--brand-danger)'];
    $votoBgs    = ['corretto'=>'rgba(61,181,64,.15)','parziale'=>'rgba(247,148,29,.15)','sbagliato'=>'rgba(232,67,67,.15)'];
    foreach ($partecipazioni as $i => $part):
        $stmtVal = $db->prepare('SELECT * FROM valutazioni WHERE slot_id = ?');
        $stmtVal->execute([$part['id']]);
        $val = $stmtVal->fetch();

        $stmtLast = $db->prepare(
            'SELECT t.*, u.nome, u.cognome FROM turni t JOIN users u ON u.id = t.studente_id
             WHERE t.slot_id = ? AND t.numero_turno = ?'
        );
        $stmtLast->execute([$part['id'], $n - 1]);
        $lastTurno = $stmtLast->fetch();

        $voto      = $val['voto'] ?? '';
        $votoColor = $votoColors[$voto] ?? 'var(--muted-foreground)';
        $votoBg    = $votoBgs[$voto]    ?? 'rgba(154,163,176,.12)';
        $delay     = number_format($i * 0.08, 2);
    ?>
    <div class="chain-card" style="animation:fade-up .5s ease-out <?= $delay ?>s both;">
        <div class="chain-header">
            <div style="display:flex;align-items:center;gap:12px;">
                <div class="turn-number" style="width:34px;height:34px;font-size:13px;flex-shrink:0;"><?= $i + 1 ?></div>
                <div>
                    <div style="font-size:14px;font-weight:800;"><?= sanitize($part['cognome'].' '.$part['nome']) ?></div>
                    <div style="font-size:10px;color:var(--muted-foreground);font-weight:700;text-transform:uppercase;letter-spacing:.06em;margin-top:2px;">Catena iniziale</div>
                </div>
            </div>
            <?php if ($val): ?>
            <span style="padding:5px 16px;border-radius:20px;font-size:11px;font-weight:900;text-transform:uppercase;letter-spacing:.07em;
                         background:<?= $votoBg ?>;color:<?= $votoColor ?>;">
                <?= ucfirst($voto) ?>
            </span>
            <?php else: ?>
            <span class="badge badge-attesa">In valutazione...</span>
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

        <?php if ($lastTurno && $lastTurno['codice']): ?>
        <div class="chain-turn">
            <div class="chain-turn-meta">
                <div class="turn-number">▶</div>
                <span>Codice finale — scritto da
                    <strong style="color:var(--foreground);"><?= sanitize($lastTurno['nome'].' '.$lastTurno['cognome']) ?></strong>
                </span>
            </div>
            <pre class="code-block"><?= sanitize($lastTurno['codice']) ?></pre>
        </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>

    <div style="margin-top:12px;display:flex;gap:12px;flex-wrap:wrap;animation:fade-up .5s ease-out .35s both;">
        <a href="/CodeRush/" class="btn-ghost">← Home</a>
        <?php if (isHost()): ?>
        <a href="/CodeRush/pages/rush-detail.php?id=<?= $partita_id ?>" class="btn-primary-lg">Analisi completa →</a>
        <a href="/CodeRush/pages/classe.php?id=<?= $partita['classe_id'] ?>" class="btn-ghost">Torna alla classe</a>
        <?php endif; ?>
    </div>
</main>
<script src="/CodeRush/js/script.js"></script>
</body>
</html>
