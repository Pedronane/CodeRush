<?php
require_once __DIR__ . '/config.php';
if (!isset($pageTitle)) $pageTitle = 'CodeRush';
$currentUser = null;
if (isLoggedIn()) {
    $currentUser = getUserById($_SESSION['user_id']);
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($pageTitle) ?> — CodeRush</title>
    <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/img/favicon.svg">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
</head>
<body>

<!-- BackgroundFX -->
<div class="background-fx" aria-hidden="true">
    <div class="bfx-grid"></div>
    <div class="bfx-blob bfx-blob-green"></div>
    <div class="bfx-blob bfx-blob-blue"></div>
    <div class="bfx-blob bfx-blob-orange"></div>
</div>

<!-- Page Transition -->
<div id="page-transition" aria-hidden="true">
    <div id="pt-bg"></div>
    <div id="pt-bar"></div>
    <div id="pt-logo">
        <img src="<?= BASE_URL ?>/img/logo.png" alt="">
    </div>
</div>

<nav class="navbar">
    <a class="navbar-brand" href="<?= BASE_URL ?>/">
        <img src="<?= BASE_URL ?>/img/logo.png" alt="CodeRush" class="logo">
    </a>

    <?php if (isLoggedIn()): ?>
    <ul class="nav-links">
        <li><a href="<?= BASE_URL ?>/" <?= (basename($_SERVER['PHP_SELF']) === 'index.php') ? 'class="active"' : '' ?>>Home</a></li>
        <?php if (isHost()): ?>
        <li><a href="<?= BASE_URL ?>/pages/rush.php"     <?= (basename($_SERVER['PHP_SELF']) === 'rush.php')     ? 'class="active"' : '' ?>>Nuovo Rush</a></li>
        <li><a href="<?= BASE_URL ?>/pages/consegne.php" <?= (basename($_SERVER['PHP_SELF']) === 'consegne.php') ? 'class="active"' : '' ?>>Consegne</a></li>
        <li><a href="<?= BASE_URL ?>/pages/classi.php"   <?= (basename($_SERVER['PHP_SELF']) === 'classi.php')   ? 'class="active"' : '' ?>>Classi</a></li>
        <li><a href="<?= BASE_URL ?>/pages/registra.php" <?= (basename($_SERVER['PHP_SELF']) === 'registra.php') ? 'class="active"' : '' ?>>Utenti</a></li>
        <?php else: ?>
        <li><a href="<?= BASE_URL ?>/pages/partecipa.php" <?= (basename($_SERVER['PHP_SELF']) === 'partecipa.php') ? 'class="active"' : '' ?>>Partecipa al Rush</a></li>
        <li><a href="<?= BASE_URL ?>/pages/profilo.php"   <?= (basename($_SERVER['PHP_SELF']) === 'profilo.php')   ? 'class="active"' : '' ?>>Profilo</a></li>
        <?php endif; ?>
    </ul>

    <div class="nav-user">
        <span class="role-badge badge-<?= $_SESSION['ruolo'] === 'host' ? 'host' : 'student' ?>">
            <?= $_SESSION['ruolo'] === 'host' ? 'Host' : 'Studente' ?>
        </span>
        <a href="<?= BASE_URL ?>/pages/profilo.php" class="nav-avatar">
            <div class="avatar-circle">
                <?= strtoupper(mb_substr($currentUser['nome'], 0, 1) . mb_substr($currentUser['cognome'], 0, 1)) ?>
            </div>
            <span class="avatar-name"><?= sanitize($currentUser['nome']) ?></span>
        </a>
        <a href="<?= BASE_URL ?>/logout.php" class="btn-logout">Esci</a>
    </div>
    <?php else: ?>
    <div class="nav-user">
        <a href="<?= BASE_URL ?>/login.php" class="btn btn-primary">Accedi</a>
    </div>
    <?php endif; ?>
</nav>
