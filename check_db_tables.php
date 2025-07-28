<?php
// Simple script to check what tables exist in the database

require_once __DIR__ . '/config/database.php';

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // Get all tables
    $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Tables in database:\n";
    foreach ($tables as $table) {
        echo "- $table\n";
    }
    
    // Check specifically for transactional email tables
    echo "\nTransactional email tables:\n";
    $transactional_tables = ['transactional_email_templates', 'transactional_email_logs', 'transactional_email_rules', 'transactional_email_queue'];
    
    foreach ($transactional_tables as $table) {
        if (in_array($table, $tables)) {
            echo "✓ $table exists\n";
        } else {
            echo "✗ $table MISSING\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}