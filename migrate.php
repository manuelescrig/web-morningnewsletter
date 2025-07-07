<?php
/**
 * Database Migration Script
 * Run this script to migrate from the old user-centric structure to the new newsletter-centric structure
 */

// Check if running from command line or web
$isCommandLine = php_sapi_name() === 'cli';
if (!$isCommandLine) {
    header('Content-Type: text/plain');
}

require_once __DIR__ . '/config/database.php';

echo "MorningNewsletter Database Migration\n";
echo "===================================\n\n";

try {
    // Step 1: Initialize basic database structure
    echo "Step 1: Initializing basic database structure...\n";
    $db = Database::getInstance();
    echo "✅ Basic database structure created\n\n";
    
    // Step 2: Check current structure
    echo "Step 2: Checking current database structure...\n";
    $conn = $db->getConnection();
    
    // Check sources table structure
    $stmt = $conn->query("PRAGMA table_info(sources)");
    $columns = $stmt->fetchAll();
    $hasUserIdColumn = false;
    $hasNewsletterIdColumn = false;
    
    foreach ($columns as $column) {
        if ($column['name'] === 'user_id') {
            $hasUserIdColumn = true;
        }
        if ($column['name'] === 'newsletter_id') {
            $hasNewsletterIdColumn = true;
        }
    }
    
    echo "- Sources table has user_id column: " . ($hasUserIdColumn ? "YES" : "NO") . "\n";
    echo "- Sources table has newsletter_id column: " . ($hasNewsletterIdColumn ? "YES" : "NO") . "\n";
    
    // Check if users exist
    $stmt = $conn->query("SELECT COUNT(*) FROM users");
    $userCount = $stmt->fetchColumn();
    echo "- Users in database: $userCount\n";
    
    // Check if newsletters table exists and count newsletters
    $stmt = $conn->query("SELECT name FROM sqlite_master WHERE type='table' AND name='newsletters'");
    $newslettersTableExists = $stmt->fetch() !== false;
    
    if ($newslettersTableExists) {
        $stmt = $conn->query("SELECT COUNT(*) FROM newsletters");
        $newsletterCount = $stmt->fetchColumn();
        echo "- Newsletters in database: $newsletterCount\n\n";
    } else {
        $newsletterCount = 0;
        echo "- Newsletters table: DOES NOT EXIST\n";
        echo "- Newsletter count: 0 (table not created yet)\n\n";
    }
    
    // Step 3: Run newsletter migration if needed
    if ($userCount > 0 && $newsletterCount == 0 && $hasUserIdColumn && !$newslettersTableExists) {
        echo "Step 3: Running newsletter migration...\n";
        $db->runNewsletterMigration();
        echo "✅ Newsletter migration completed\n\n";
    } else {
        echo "Step 3: Newsletter migration not needed\n";
        if ($newsletterCount > 0) {
            echo "- Newsletters already exist\n";
        }
        if ($userCount == 0) {
            echo "- No users to migrate\n";
        }
        if (!$hasUserIdColumn) {
            echo "- Already using new structure\n";
        }
        if ($newslettersTableExists && $newsletterCount == 0) {
            echo "- Newsletter table exists but is empty\n";
        }
        echo "\n";
    }
    
    echo "✅ Migration process completed successfully!\n";
    echo "\nYou can now access your dashboard.\n";
    
} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    echo "\nDebug information:\n";
    echo "- Error on line: " . $e->getLine() . "\n";
    echo "- Error in file: " . $e->getFile() . "\n";
    echo "\nPlease check the error and try again.\n";
}
?>