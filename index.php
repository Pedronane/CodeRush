<?php
session_start();
require_once __DIR__ . '/includes/functions.php';
if (!isLoggedIn()) { header('Location: /CodeRush/login.php'); exit(); }

$pageTitle = 'Home';
require_once __DIR__ . '/includes/header.php';
?>
<link rel="stylesheet" href="/CodeRush/css/pages/home.css">
<main class="container">

<?php if (isHost()):
    $db = getDB();
    $nClassi   = $db->query('SELECT COUNT(*) FROM classi')->fetchColumn();
    $stmtD = $db->prepare('SELECT COUNT(*) FROM domande WHERE host_id = ?'); $stmtD->execute([$_SESSION['user_id']]); $nConsegne = $stmtD->fetchColumn();
    $stmtR = $db->prepare('SELECT COUNT(*) FROM partite WHERE host_id = ? AND stato = "finita"'); $stmtR->execute([$_SESSION['user_id']]); $nRush = $stmtR->fetchColumn();
    $stmtS = $db->query('SELECT COUNT(*) FROM users WHERE ruolo = "studente"'); $nStudenti = $stmtS->fetchColumn();
?>
    <!-- Hero Host -->
    <div class="hero hero-with-particles">
        <div data-particles="14" style="position:absolute;inset:0;pointer-events:none;"></div>
        <span class="hero-tag">Quartier Generale</span>
        <h1>Benvenuto, <?= sanitize($currentUser['nome']) ?>!</h1>
        <p>CodeRush trasforma le tue classi in arene di coding a turni. Crea un Rush, condividi il codice partita, vivi la competizione in tempo reale.</p>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card"><div class="stat-value"><?= $nClassi ?></div><div class="stat-label">Classi</div></div>
        <div class="stat-card"><div class="stat-value"><?= $nConsegne ?></div><div class="stat-label">Consegne</div></div>
        <div class="stat-card"><div class="stat-value"><?= $nRush ?></div><div class="stat-label">Rush completati</div></div>
        <div class="stat-card"><div class="stat-value"><?= $nStudenti ?></div><div class="stat-label">Studenti totali</div></div>
    </div>

    <!-- Quick actions -->
    <div class="card" style="margin-top:8px;">
        <div class="card-title">Azioni rapide</div>
        <div style="display:flex;gap:12px;flex-wrap:wrap;">
            <a href="/CodeRush/pages/rush.php"      class="btn-primary-lg">🚀 Nuovo Rush</a>
            <a href="/CodeRush/pages/consegne.php"  class="btn-ghost">📋 Consegne</a>
            <a href="/CodeRush/pages/classi.php"    class="btn-ghost">🎓 Classi</a>
            <a href="/CodeRush/pages/registra.php"  class="btn-ghost">👤 Utenti</a>
        </div>
    </div>

<?php else: ?>
    <!-- Hero Studente -->
    <div class="hero hero-with-particles">
        <div data-particles="14" style="position:absolute;inset:0;pointer-events:none;"></div>
        <span class="hero-tag">Studente</span>
        <h1>Ciao, <?= sanitize($currentUser['nome']) ?>!</h1>
        <p>Quando il tuo professore avvia un Rush ti darà un codice partita: usalo per entrare e iniziare a programmare.</p>
        <a href="/CodeRush/pages/partecipa.php" class="hero-cta">▸ Partecipa al Rush</a>
    </div>

    <!-- How it works -->
    <div style="display:grid;gap:16px;" class="how-steps">
        <?php foreach ([
            ['1','Ricevi il codice','Il tuo professore ti darà un codice partita.'],
            ['2','Leggi la consegna','Hai un timer per capire il problema.'],
            ['3','Scrivi il tuo turno','Continua il codice del compagno successivo.'],
        ] as $s): ?>
        <div class="card" style="display:flex;gap:16px;align-items:flex-start;animation:fade-up .4s ease-out both;">
            <div style="width:36px;height:36px;border-radius:10px;background:rgba(61,181,64,.15);color:var(--brand-green);font-weight:900;display:flex;align-items:center;justify-content:center;flex-shrink:0;"><?= $s[0] ?></div>
            <div><div style="font-weight:700;margin-bottom:4px;"><?= $s[1] ?></div><div style="font-size:13px;color:var(--muted-foreground);"><?= $s[2] ?></div></div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

    <!-- Come funziona -->
    <h2 style="font-size:20px;font-weight:800;margin:32px 0 16px;animation:fade-up .4s ease-out both;">Come funziona</h2>
    <div class="home-features">
        <?php foreach ([
            ['📝','La consegna','Ogni Rush parte da una sfida di programmazione. L\'host sceglie il problema, il linguaggio e il livello di difficoltà.'],
            ['⏱️','I turni','Ogni studente scrive codice per un tempo limitato. Allo scadere del timer, il codice passa al compagno successivo.'],
            ['🔄','Il passaggio','Come il telefono senza fili: ogni studente lavora sul codice ricevuto, lo capisce e lo migliora.'],
            ['🤖','La valutazione','Alla fine, un\'AI analizza ogni codice finale e valuta se la soluzione è corretta, parziale o sbagliata.'],
        ] as $f): ?>
        <div class="feature-card">
            <span class="feature-icon"><?= $f[0] ?></span>
            <div class="feature-title"><?= $f[1] ?></div>
            <div class="feature-desc"><?= $f[2] ?></div>
        </div>
        <?php endforeach; ?>
    </div>

</main>
<script src="/CodeRush/js/script.js"></script>
</body>
</html>
