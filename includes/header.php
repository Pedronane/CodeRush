<?php
require_once __DIR__ . '/config.php';
if (!isset($pageTitle)) {
    $pageTitle = 'CodeRush';
}
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
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
</head>
<body>
<nav class="navbar">
    <a class="navbar-brand" href="<?= BASE_URL ?>/">
        <img src="<?= BASE_URL ?>/img/logo.png" alt="CodeRush" class="logo">
        <span>CodeRush</span>
    </a>
    <?php if (isLoggedIn()): ?>
    <ul class="nav-links">
        <li><a href="<?= BASE_URL ?>/" <?= (basename($_SERVER['PHP_SELF']) === 'index.php') ? 'class="active"' : '' ?>>Home</a></li>
        <?php if (isHost()): ?>
        <li><a href="<?= BASE_URL ?>/pages/classi.php" <?= (basename($_SERVER['PHP_SELF']) === 'classi.php') ? 'class="active"' : '' ?>>Classi</a></li>
        <li><a href="<?= BASE_URL ?>/pages/consegne.php" <?= (basename($_SERVER['PHP_SELF']) === 'consegne.php') ? 'class="active"' : '' ?>>Consegne</a></li>
        <li><a href="<?= BASE_URL ?>/pages/rush.php" <?= (basename($_SERVER['PHP_SELF']) === 'rush.php') ? 'class="active"' : '' ?>>Rush</a></li>
        <?php endif; ?>
    </ul>
    <div class="nav-user">
        <span class="nav-username"><?= sanitize($currentUser['nome'] . ' ' . $currentUser['cognome']) ?></span>
        <span class="badge badge-<?= $_SESSION['ruolo'] === 'host' ? 'host' : 'student' ?>"><?= $_SESSION['ruolo'] === 'host' ? 'Host' : 'Studente' ?></span>
        <a href="<?= BASE_URL ?>/pages/profilo.php">Profilo</a>
        <a href="<?= BASE_URL ?>/logout.php" class="btn-logout">Logout</a>
    </div>
    <?php else: ?>
    <div class="nav-user">
        <a href="<?= BASE_URL ?>/login.php" class="btn btn-primary">Login</a>
    </div>
    <?php endif; ?>
</nav>
