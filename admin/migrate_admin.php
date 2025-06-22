<?php
/**
 * Database Migration Script - Add admin column
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once __DIR__ . '/../config/database.php';
} catch (Exception $e) {
    die("Error loading database: " . $e->getMessage());
}

// Simple security check
$secret = $_GET['secret'] ?? '';
$requiredSecret = 'admin_setup_2024';

if ($secret !== $requiredSecret) {
    http_response_code(403);
    die('Access denied. Invalid secret key.');
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Check if column already exists
    $stmt = $db->query("PRAGMA table_info(users)");
    $columns = $stmt->fetchAll();
    
    $hasAdminColumn = false;
    foreach ($columns as $column) {
        if ($column['name'] === 'is_admin') {
            $hasAdminColumn = true;
            break;
        }
    }
    
    if ($hasAdminColumn) {
        echo "✓ Column 'is_admin' already exists in users table.";
    } else {
        // Add the column
        $db->exec('ALTER TABLE users ADD COLUMN is_admin INTEGER DEFAULT 0');
        echo "✓ Successfully added 'is_admin' column to users table.";
    }
    
    echo "<br><br><a href='list_users.php?secret={$requiredSecret}'>View Users</a>";
    echo " | <a href='promote_admin.php?secret={$requiredSecret}'>Promote Admin</a>";
    
} catch (Exception $e) {
    die("Migration error: " . $e->getMessage());
}
?>