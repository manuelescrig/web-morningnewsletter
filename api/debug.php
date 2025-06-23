<?php
// Most basic test possible
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Basic PHP test: OK\n";

// Test 1: Basic JSON response
header('Content-Type: application/json');
echo json_encode(['test' => 'basic', 'status' => 'working']);
?>