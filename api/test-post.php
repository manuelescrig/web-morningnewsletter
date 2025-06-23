<?php
header('Content-Type: application/json');

// Simple POST test
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Not POST', 'method' => $_SERVER['REQUEST_METHOD']]);
    exit;
}

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);

echo json_encode([
    'success' => true,
    'raw_length' => strlen($rawInput),
    'decoded_data' => $input,
    'json_error' => json_last_error_msg()
]);
?>