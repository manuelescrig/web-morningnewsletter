<?php
// Simple dashboard test
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Test 1: Basic PHP and session
echo "Test 1: Basic PHP setup\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Session status: " . session_status() . "\n\n";

// Test 2: Database
echo "Test 2: Database connection\n";
try {
    require_once __DIR__ . '/config/database.php';
    $db = Database::getInstance()->getConnection();
    echo "Database connected successfully\n\n";
} catch (Exception $e) {
    echo "Database error: " . $e->getMessage() . "\n\n";
}

// Test 3: Classes
echo "Test 3: Loading classes\n";
try {
    require_once __DIR__ . '/core/Auth.php';
    echo "Auth.php loaded\n";
    
    require_once __DIR__ . '/core/Newsletter.php';
    echo "Newsletter.php loaded\n";
    
    require_once __DIR__ . '/core/Scheduler.php';
    echo "Scheduler.php loaded\n\n";
} catch (Exception $e) {
    echo "Class loading error: " . $e->getMessage() . "\n\n";
}

// Test 4: Auth functionality
echo "Test 4: Auth functionality\n";
try {
    // Start session if not started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $auth = Auth::getInstance();
    echo "Auth instance created\n";
    echo "User logged in: " . ($auth->isLoggedIn() ? "Yes" : "No") . "\n";
    
    if ($auth->isLoggedIn()) {
        $user = $auth->getCurrentUser();
        echo "User email: " . $user->getEmail() . "\n";
    }
} catch (Exception $e) {
    echo "Auth error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ", Line: " . $e->getLine() . "\n";
}

echo "\nIf you can see this message, the basic dashboard components are working.\n";