<?php
require_once __DIR__ . '/../src/controllers/AuthController.php';

$auth = new AuthController();
$auth->logout();

header('Location: /login.php');
exit();