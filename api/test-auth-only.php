<?php
header('Content-Type: application/json');

try {
    // Test Auth class loading step by step
    echo json_encode(['step' => 1, 'message' => 'Starting']);
    
    $authPath = __DIR__ . '/../core/Auth.php';
    if (!file_exists($authPath)) {
        throw new Exception('Auth.php not found');
    }
    
    require_once $authPath;
    echo json_encode(['step' => 2, 'message' => 'Auth.php loaded']);
    
    $auth = Auth::getInstance();
    echo json_encode(['step' => 3, 'message' => 'Auth instance created']);
    
    $isLoggedIn = $auth->isLoggedIn();
    echo json_encode(['step' => 4, 'message' => 'Auth check complete', 'logged_in' => $isLoggedIn]);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
} catch (Error $e) {
    echo json_encode(['fatal_error' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
}
?>