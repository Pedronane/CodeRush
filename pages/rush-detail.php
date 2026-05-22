<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
header('Location: /CodeRush/pages/risultati.php' . ($id > 0 ? '?id='.$id : ''));
exit();
