<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';
if (!isStudent()) { header('Location: /CodeRush/login.php'); exit(); }

$pageTitle = 'Partecipa al Rush';
require_once __DIR__ . '/../includes/header.php';
?>
<main class="partecipa-main" style="position:relative;">
    <div data-particles="20" style="position:absolute;inset:0;pointer-events:none;z-index:0;"></div>

    <div style="position:relative;z-index:1;animation:fade-up .5s ease-out both;">
        <span class="partecipa-badge">Pronto a giocare</span>
        <h1 class="partecipa-title">
            Entra nel <span class="brand-gradient-text">Rush</span>
        </h1>
        <p style="color:var(--muted-foreground);margin-top:12px;margin-bottom:32px;">
            Inserisci il codice partita che ti ha dato il professore
        </p>

        <form method="GET" action="/CodeRush/pages/waiting.php" novalidate
              style="background:var(--card);border:1px solid var(--border);border-radius:20px;padding:28px;box-shadow:0 20px 60px rgba(0,0,0,.4);">
            <input
                type="text"
                name="code"
                class="code-input"
                placeholder="ABC123"
                maxlength="6"
                required
                autocomplete="off"
                autocapitalize="characters"
                oninput="this.value=this.value.toUpperCase().replace(/[^A-Z0-9]/g,'')"
                style="margin-bottom:16px;"
            >
            <button type="submit"
                class="btn-primary-lg btn-block"
                style="padding:16px;font-size:16px;border-radius:14px;">
                Entra ▸
            </button>
        </form>

        <a href="/CodeRush/" style="display:inline-block;margin-top:20px;font-size:12px;font-weight:600;color:var(--muted-foreground);">
            ← Torna alla home
        </a>
    </div>
</main>
<script src="/CodeRush/js/script.js"></script>
</body>
</html>
