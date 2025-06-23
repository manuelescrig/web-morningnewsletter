<?php
// Initialize database tables
require_once __DIR__ . '/config/database.php';

try {
    echo "Initializing database...\n";
    $db = Database::getInstance();
    echo "✅ Database initialized successfully!\n";
    
    // Test if subscription tables exist
    $pdo = $db->getConnection();
    $result = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='subscriptions'");
    if ($result->fetch()) {
        echo "✅ Subscriptions table exists\n";
    } else {
        echo "❌ Subscriptions table missing\n";
    }
    
    $result = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='payments'");
    if ($result->fetch()) {
        echo "✅ Payments table exists\n";
    } else {
        echo "❌ Payments table missing\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>