<?php
// Most basic test possible - headers first!
header('Content-Type: application/json');

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Test 1: Basic JSON response
echo json_encode(['test' => 'basic', 'status' => 'working', 'php_version' => PHP_VERSION]);
?>