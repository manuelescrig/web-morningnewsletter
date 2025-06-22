<?php
/**
 * User List Script - for debugging admin setup
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
    $stmt = $db->query("SELECT id, email, is_admin, email_verified, created_at FROM users ORDER BY created_at DESC");
    $users = $stmt->fetchAll();
} catch (Exception $e) {
    die("Database error: " . $e->getMessage());
}

echo "<h2>All Users in System</h2>\n";
echo "<table border='1' cellpadding='5'>\n";
echo "<tr><th>ID</th><th>Email</th><th>Admin</th><th>Verified</th><th>Created</th></tr>\n";

foreach ($users as $user) {
    echo "<tr>";
    echo "<td>{$user['id']}</td>";
    echo "<td>{$user['email']}</td>";
    echo "<td>" . ($user['is_admin'] ? 'Yes' : 'No') . "</td>";
    echo "<td>" . ($user['email_verified'] ? 'Yes' : 'No') . "</td>";
    echo "<td>{$user['created_at']}</td>";
    echo "</tr>\n";
}

echo "</table>\n";
echo "<br><a href='promote_admin.php?secret={$requiredSecret}'>Back to Admin Promotion</a>";
?>