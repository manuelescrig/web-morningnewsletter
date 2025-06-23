<?php
// Test Auth loading step by step
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    echo json_encode(['step' => 1, 'message' => 'Starting auth test']);
    
    // Test file exists
    $authFile = __DIR__ . '/../core/Auth.php';
    if (!file_exists($authFile)) {
        throw new Exception('Auth.php not found at: ' . $authFile);
    }
    
    echo json_encode(['step' => 2, 'message' => 'Auth.php file exists']);
    
    // Try to include it
    require_once $authFile;
    
    echo json_encode(['step' => 3, 'message' => 'Auth.php included successfully']);
    
    // Try to instantiate
    $auth = Auth::getInstance();
    
    echo json_encode(['step' => 4, 'message' => 'Auth instance created', 'logged_in' => $auth->isLoggedIn()]);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
} catch (Error $e) {
    echo json_encode(['fatal_error' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
}
?>