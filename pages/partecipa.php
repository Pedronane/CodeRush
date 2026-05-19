<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';
if (!isStudent()) { header('Location: /CodeRush/login.php'); exit(); }

$pageTitle = 'Partecipa al Rush';
require_once __DIR__ . '/../includes/header.php';
?>
<link rel="stylesheet" href="/CodeRush/css/pages/partecipa.css">
<main class="partecipa-main">
    <div data-particles="20" class="particles-bg-abs"></div>

    <div class="partecipa-inner">
        <span class="partecipa-badge">Pronto a giocare</span>
        <h1 class="partecipa-title">
            Entra nel <span class="brand-gradient-text">Rush</span>
        </h1>
        <p class="partecipa-subtitle">
            Inserisci il codice partita che ti ha dato il professore
        </p>

        <form method="GET" action="/CodeRush/pages/waiting.php" novalidate class="join-card">
            <input
                type="text"
                name="code"
                class="code-input mb-16"
                placeholder="ABC123"
                maxlength="6"
                required
                autocomplete="off"
                autocapitalize="characters"
                oninput="this.value=this.value.toUpperCase().replace(/[^A-Z0-9]/g,'')"
            >
            <button type="submit" class="btn-primary-lg btn-block input-lg">
                Entra ▸
            </button>
        </form>

        <a href="/CodeRush/" class="back-link">
            ← Torna alla home
        </a>
    </div>
</main>
<script src="/CodeRush/js/script.js"></script>
</body>
</html>
