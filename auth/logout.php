<?php
require_once __DIR__ . '/../core/Auth.php';

$auth = Auth::getInstance();
$auth->logout();

header('Location: /');
exit;
?>