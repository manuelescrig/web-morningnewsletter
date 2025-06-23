<?php
// Simple test endpoint
header('Content-Type: application/json');

try {
    // Basic test
    echo json_encode([
        'status' => 'success',
        'message' => 'API endpoint is working',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Test failed',
        'message' => $e->getMessage()
    ]);
}
?>