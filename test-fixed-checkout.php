<?php
// Test the fixed checkout endpoint manually
session_start();

// Test POST simulation
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = []; // Simulate POST
$GLOBALS['HTTP_RAW_POST_DATA'] = json_encode(['plan' => 'starter']);

// Capture any output
ob_start();

try {
    // Include the file and see what happens
    include __DIR__ . '/api/fixed-checkout.php';
    
    $output = ob_get_contents();
    echo "SUCCESS - Output: " . $output;
    
} catch (Exception $e) {
    ob_end_clean();
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
} catch (Error $e) {
    ob_end_clean();
    echo "FATAL: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}

ob_end_clean();
?>