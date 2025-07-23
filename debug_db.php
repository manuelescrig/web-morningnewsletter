<?php
// Debug database initialization
header('Content-Type: text/plain');
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== DATABASE DEBUG SCRIPT ===\n\n";

// Check if database file exists and size
$dbPath = __DIR__ . '/data/newsletter.db';
echo "Database path: $dbPath\n";
echo "Database exists: " . (file_exists($dbPath) ? 'Yes' : 'No') . "\n";
echo "Database size: " . filesize($dbPath) . " bytes\n";
echo "Database writable: " . (is_writable($dbPath) ? 'Yes' : 'No') . "\n";
echo "Data directory writable: " . (is_writable(dirname($dbPath)) ? 'Yes' : 'No') . "\n\n";

// Try to initialize database
try {
    // Delete empty database to force re-creation
    if (file_exists($dbPath) && filesize($dbPath) == 0) {
        unlink($dbPath);
        echo "Deleted empty database file.\n";
    }
    
    require_once __DIR__ . '/config/database.php';
    
    echo "Creating database instance...\n";
    $db = Database::getInstance();
    $conn = $db->getConnection();
    echo "Database connection successful!\n\n";
    
    // List all tables
    $stmt = $conn->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Tables in database (" . count($tables) . "):\n";
    foreach ($tables as $table) {
        echo "- $table\n";
    }
    echo "\n";
    
    // Check newsletters table structure
    if (in_array('newsletters', $tables)) {
        echo "Newsletters table structure:\n";
        $stmt = $conn->query("PRAGMA table_info(newsletters)");
        $columns = $stmt->fetchAll();
        
        foreach ($columns as $col) {
            echo sprintf("- %-20s %-15s %s\n", 
                $col['name'], 
                $col['type'], 
                $col['dflt_value'] ? "DEFAULT " . $col['dflt_value'] : ""
            );
        }
        echo "\n";
        
        // Check for scheduling columns
        $requiredColumns = ['frequency', 'days_of_week', 'day_of_month', 'months', 'daily_times', 'is_paused'];
        $columnNames = array_column($columns, 'name');
        $missingColumns = array_diff($requiredColumns, $columnNames);
        
        if (!empty($missingColumns)) {
            echo "MISSING COLUMNS in newsletters table:\n";
            foreach ($missingColumns as $col) {
                echo "- $col\n";
            }
        } else {
            echo "All required scheduling columns are present.\n";
        }
    } else {
        echo "ERROR: newsletters table not found!\n";
    }
    
    // Check database file size after operations
    clearstatcache();
    echo "\nFinal database size: " . filesize($dbPath) . " bytes\n";
    
} catch (Exception $e) {
    echo "\nERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== END DEBUG ===\n";