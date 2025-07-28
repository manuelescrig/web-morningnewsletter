<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Debugging Transactional Email Page</h1>";

try {
    echo "<h2>Step 1: Loading dependencies</h2>";
    
    echo "<p>Loading database...</p>";
    require_once __DIR__ . '/../config/database.php';
    echo "<p style='color: green;'>✓ Database loaded</p>";
    
    echo "<p>Loading Auth...</p>";
    require_once __DIR__ . '/../core/Auth.php';
    echo "<p style='color: green;'>✓ Auth loaded</p>";
    
    echo "<p>Loading User...</p>";
    require_once __DIR__ . '/../core/User.php';
    echo "<p style='color: green;'>✓ User loaded</p>";
    
    echo "<p>Loading TransactionalEmailManager...</p>";
    require_once __DIR__ . '/../core/TransactionalEmailManager.php';
    echo "<p style='color: green;'>✓ TransactionalEmailManager loaded</p>";
    
    echo "<h2>Step 2: Checking authentication</h2>";
    $auth = Auth::getInstance();
    if (!$auth->isLoggedIn()) {
        echo "<p style='color: red;'>✗ Not logged in - would redirect to login</p>";
    } else {
        echo "<p style='color: green;'>✓ User is logged in</p>";
        
        $user = $auth->getUser();
        echo "<p>User ID: " . $user->getId() . "</p>";
        echo "<p>User Email: " . $user->getEmail() . "</p>";
        echo "<p>Is Admin: " . ($user->isAdmin() ? 'Yes' : 'No') . "</p>";
        
        if (!$user->isAdmin()) {
            echo "<p style='color: red;'>✗ User is not admin - would redirect to dashboard</p>";
        } else {
            echo "<p style='color: green;'>✓ User is admin</p>";
        }
    }
    
    echo "<h2>Step 3: Testing TransactionalEmailManager</h2>";
    $transactionalManager = new TransactionalEmailManager();
    echo "<p style='color: green;'>✓ TransactionalEmailManager instance created</p>";
    
    echo "<h3>Testing getTemplates():</h3>";
    try {
        $templates = $transactionalManager->getTemplates();
        echo "<p style='color: green;'>✓ getTemplates() succeeded - Found " . count($templates) . " templates</p>";
        if (count($templates) > 0) {
            echo "<pre>" . print_r($templates[0], true) . "</pre>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ getTemplates() failed: " . $e->getMessage() . "</p>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
    
    echo "<h3>Testing getRules():</h3>";
    try {
        $rules = $transactionalManager->getRules();
        echo "<p style='color: green;'>✓ getRules() succeeded - Found " . count($rules) . " rules</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ getRules() failed: " . $e->getMessage() . "</p>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
    
    echo "<h3>Testing getEmailLogs():</h3>";
    try {
        $logs = $transactionalManager->getEmailLogs(50);
        echo "<p style='color: green;'>✓ getEmailLogs() succeeded - Found " . count($logs) . " logs</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ getEmailLogs() failed: " . $e->getMessage() . "</p>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
    
    echo "<h3>Testing getQueueItems():</h3>";
    try {
        $queueItems = $transactionalManager->getQueueItems(null, 50);
        echo "<p style='color: green;'>✓ getQueueItems() succeeded - Found " . count($queueItems) . " items</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ getQueueItems() failed: " . $e->getMessage() . "</p>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
    
    echo "<h2 style='color: green;'>If all tests passed, the transactional email page should work!</h2>";
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>Fatal Error:</h2>";
    echo "<p style='color: red;'>" . $e->getMessage() . "</p>";
    echo "<pre style='color: red;'>" . $e->getTraceAsString() . "</pre>";
}