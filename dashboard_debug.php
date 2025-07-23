<?php
// Dashboard error diagnostic
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start output buffering to prevent header issues
ob_start();

echo "=== DASHBOARD ERROR DIAGNOSTIC ===\n\n";

try {
    echo "1. Testing includes and requires...\n";
    
    // Test each require one by one
    echo "- Loading Auth.php... ";
    require_once __DIR__ . '/core/Auth.php';
    echo "OK\n";
    
    echo "- Loading Newsletter.php... ";
    require_once __DIR__ . '/core/Newsletter.php';
    echo "OK\n";
    
    echo "- Loading Scheduler.php... ";
    require_once __DIR__ . '/core/Scheduler.php';
    echo "OK\n";
    
    echo "\n2. Testing Auth instance...\n";
    $auth = Auth::getInstance();
    echo "- Auth instance created\n";
    
    // Don't require auth for this test
    echo "- Checking if user is logged in: " . ($auth->isLoggedIn() ? "Yes" : "No") . "\n";
    
    if ($auth->isLoggedIn()) {
        echo "\n3. Testing User methods...\n";
        $user = $auth->getCurrentUser();
        echo "- User ID: " . $user->getId() . "\n";
        echo "- User Email: " . $user->getEmail() . "\n";
        
        echo "\n4. Testing Newsletter methods...\n";
        try {
            $newsletters = $user->getNewsletters();
            echo "- getNewsletters() returned " . count($newsletters) . " newsletters\n";
            
            foreach ($newsletters as $newsletter) {
                echo "  - Newsletter ID: " . $newsletter->getId() . ", Title: " . $newsletter->getTitle() . "\n";
            }
        } catch (Exception $e) {
            echo "- ERROR in getNewsletters(): " . $e->getMessage() . "\n";
            echo "  Stack trace:\n" . $e->getTraceAsString() . "\n";
        }
        
        echo "\n5. Testing Scheduler...\n";
        try {
            $scheduler = new Scheduler();
            echo "- Scheduler instance created\n";
            
            if (!empty($newsletters)) {
                $scheduleStatus = $scheduler->getScheduleStatus($newsletters[0]);
                echo "- Schedule status retrieved for first newsletter\n";
            }
        } catch (Exception $e) {
            echo "- ERROR in Scheduler: " . $e->getMessage() . "\n";
        }
    } else {
        echo "\n- User not logged in, skipping user-specific tests\n";
    }
    
    echo "\n6. Checking PHP version and extensions...\n";
    echo "- PHP Version: " . PHP_VERSION . "\n";
    echo "- PDO SQLite: " . (extension_loaded('pdo_sqlite') ? 'Loaded' : 'NOT LOADED') . "\n";
    echo "- Session support: " . (function_exists('session_start') ? 'Yes' : 'No') . "\n";
    
    echo "\n7. Testing database connection directly...\n";
    require_once __DIR__ . '/config/database.php';
    $db = Database::getInstance();
    $conn = $db->getConnection();
    echo "- Direct database connection: OK\n";
    
    echo "\n=== DIAGNOSTIC COMPLETE ===\n";
    echo "\nIf you see this message, the basic components are working.\n";
    echo "The 500 error might be in the HTML rendering or session handling.\n";
    
} catch (Exception $e) {
    echo "\n*** CRITICAL ERROR ***\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}

// Send output
header('Content-Type: text/plain');
ob_end_flush();