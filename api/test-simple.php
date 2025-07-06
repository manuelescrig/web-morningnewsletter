<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

echo json_encode([
    'success' => true,
    'message' => 'Simple API test successful',
    'timestamp' => time(),
    'query' => $_GET['q'] ?? 'no query'
]);
?> 