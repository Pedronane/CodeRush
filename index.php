<?php
session_start();
require_once __DIR__ . '/includes/functions.php';

if (!isLoggedIn()) {
    header('Location: /CodeRush/login.php');
    exit();
}

$pageTitle = 'Home';
require_once __DIR__ . '/includes/header.php';
?>
<main class="container">
    <div class="hero">
        <h1>CodeRush</h1>
        <p>Il telefono senza fili della programmazione. Scrivi, passa, migliora.</p>
    </div>

    <?php if (isHost()): ?>
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-value"><?php
                $db = getDB();
                echo $db->query('SELECT COUNT(*) FROM classi')->fetchColumn();
            ?></div>
            <div class="stat-label">Classi</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php
                $stmt = $db->prepare('SELECT COUNT(*) FROM domande WHERE host_id = ?');
                $stmt->execute([$_SESSION['user_id']]);
                echo $stmt->fetchColumn();
            ?></div>
            <div class="stat-label">Consegne</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php
                $stmt = $db->prepare('SELECT COUNT(*) FROM partite WHERE host_id = ? AND stato = "finita"');
                $stmt->execute([$_SESSION['user_id']]);
                echo $stmt->fetchColumn();
            ?></div>
            <div class="stat-label">Rush completati</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php
                $stmt = $db->prepare(
                    'SELECT COUNT(*) FROM users u
                     JOIN studente_classe sc ON sc.studente_id = u.id
                     JOIN classi c ON c.id = sc.classe_id
                     WHERE u.ruolo = "studente"'
                );
                $stmt->execute();
                echo $stmt->fetchColumn();
            ?></div>
            <div class="stat-label">Studenti totali</div>
        </div>
    </div>
    <?php endif; ?>

    <div class="home-features">
        <div class="feature-card">
            <div class="feature-icon">📝</div>
            <div class="feature-title">La consegna</div>
            <div class="feature-desc">Ogni Rush parte da una sfida di programmazione. L'host sceglie il problema, il linguaggio e il livello di difficoltà.</div>
        </div>
        <div class="feature-card">
            <div class="feature-icon">⏱️</div>
            <div class="feature-title">I turni</div>
            <div class="feature-desc">Ogni studente scrive codice per un tempo limitato. Allo scadere del timer, il codice passa al compagno successivo.</div>
        </div>
        <div class="feature-card">
            <div class="feature-icon">🔄</div>
            <div class="feature-title">Il passaggio</div>
            <div class="feature-desc">Come il telefono senza fili: ogni studente lavora sul codice ricevuto, lo capisce e lo migliora.</div>
        </div>
        <div class="feature-card">
            <div class="feature-icon">🤖</div>
            <div class="feature-title">La valutazione</div>
            <div class="feature-desc">Alla fine, un'AI analizza ogni codice finale e valuta se la soluzione è corretta, parziale o sbagliata.</div>
        </div>
    </div>

    <?php if (isStudent()): ?>
    <div class="card" style="margin-top: 32px;">
        <div class="card-title">Partecipa a un Rush</div>
        <p style="color: var(--text-muted); margin-bottom: 16px;">Inserisci il codice fornito dal tuo insegnante per unirti alla partita.</p>
        <form method="GET" action="/CodeRush/pages/waiting.php" style="display: flex; gap: 10px; max-width: 360px;">
            <input type="text" name="code" class="form-control" placeholder="Codice partita (es. AB12CD)" maxlength="10" required style="text-transform: uppercase; letter-spacing: 3px; font-family: monospace; font-size: 16px;">
            <button type="submit" class="btn btn-primary">Entra</button>
        </form>
    </div>
    <?php endif; ?>

    <?php if (isHost()): ?>
    <div class="card" style="margin-top: 20px;">
        <div class="card-title">Accesso rapido</div>
        <div style="display: flex; gap: 12px; flex-wrap: wrap;">
            <a href="/CodeRush/pages/rush.php" class="btn btn-primary">Nuovo Rush</a>
            <a href="/CodeRush/pages/consegne.php" class="btn btn-secondary">Gestisci consegne</a>
            <a href="/CodeRush/pages/classi.php" class="btn btn-secondary">Gestisci classi</a>
            <a href="/CodeRush/pages/registra.php" class="btn btn-secondary">Registra utenti</a>
        </div>
    </div>
    <?php endif; ?>
</main>
</body>
</html>
