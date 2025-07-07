<?php
/**
 * Database Migration Script
 * Run this script to migrate from the old user-centric structure to the new newsletter-centric structure
 */

require_once __DIR__ . '/config/database.php';

echo "Starting database migration...\n";

try {
    // Initialize database (this will create tables and run migrations)
    $db = Database::getInstance();
    echo "✅ Database initialized successfully\n";
    echo "✅ Migration completed successfully\n";
    echo "\nYou can now access your dashboard.\n";
} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    echo "\nPlease check the error and try again.\n";
}
?>