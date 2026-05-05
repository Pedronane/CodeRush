<?php
session_start();
session_destroy();
header('Location: /CodeRush/login.php');
exit();
