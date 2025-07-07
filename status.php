<?php
/**
 * Database Status Check
 * Use this page to check the current database structure and migration status
 */

header('Content-Type: text/plain');

echo "MorningNewsletter Database Status Check\n";
echo "======================================\n\n";

try {
    require_once __DIR__ . '/config/database.php';
    
    echo "✅ Database connection successful\n\n";
    
    $db = Database::getInstance()->getConnection();
    
    // Check if tables exist
    $tables = ['users', 'newsletters', 'newsletter_recipients', 'sources', 'email_logs'];
    
    echo "TABLE STATUS:\n";
    echo "-------------\n";
    
    foreach ($tables as $table) {
        try {
            $stmt = $db->query("SELECT COUNT(*) FROM $table");
            $count = $stmt->fetchColumn();
            echo "✅ $table: $count records\n";
        } catch (Exception $e) {
            echo "❌ $table: Table does not exist\n";
        }
    }
    
    echo "\nSOURCES TABLE STRUCTURE:\n";
    echo "------------------------\n";
    
    try {
        $stmt = $db->query("PRAGMA table_info(sources)");
        $columns = $stmt->fetchAll();
        
        foreach ($columns as $column) {
            echo "Column: {$column['name']} ({$column['type']})\n";
        }
        
        // Check for specific columns
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
        
        echo "\nStructure Analysis:\n";
        echo "- Has user_id column: " . ($hasUserIdColumn ? "YES" : "NO") . "\n";
        echo "- Has newsletter_id column: " . ($hasNewsletterIdColumn ? "YES" : "NO") . "\n";
        
        if ($hasUserIdColumn && !$hasNewsletterIdColumn) {
            echo "\n⚠️  Database is in OLD structure - migration needed\n";
        } elseif (!$hasUserIdColumn && $hasNewsletterIdColumn) {
            echo "\n✅ Database is in NEW structure - migration complete\n";
        } elseif ($hasUserIdColumn && $hasNewsletterIdColumn) {
            echo "\n🔄 Database is in TRANSITION - migration in progress\n";
        } else {
            echo "\n❌ Database structure is invalid\n";
        }
        
    } catch (Exception $e) {
        echo "❌ Could not check sources table: " . $e->getMessage() . "\n";
    }
    
    echo "\nUSERS WITH NEWSLETTERS:\n";
    echo "-----------------------\n";
    
    try {
        $stmt = $db->query("SELECT COUNT(*) FROM users");
        $userCount = $stmt->fetchColumn();
        echo "Total users: $userCount\n";
        
        $stmt = $db->query("SELECT COUNT(*) FROM newsletters");
        $newsletterCount = $stmt->fetchColumn();
        echo "Total newsletters: $newsletterCount\n";
        
        if ($userCount > 0 && $newsletterCount == 0) {
            echo "⚠️  Users exist but no newsletters - migration needed\n";
        }
        
    } catch (Exception $e) {
        echo "Could not check user/newsletter counts: " . $e->getMessage() . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    echo "\nSuggested actions:\n";
    echo "1. Make sure the web server has write permissions to the project directory\n";
    echo "2. Run the migration script: php migrate.php\n";
    echo "3. Check if SQLite is properly installed\n";
}

echo "\n\nIf you see migration issues, please:\n";
echo "1. Visit /migrate.php in your browser\n";
echo "2. Or run: php migrate.php from the command line\n";
echo "3. Then check this status page again\n";
?>