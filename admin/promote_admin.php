<?php
/**
 * Admin Promotion Script
 * 
 * This script allows promoting a user to admin status.
 * Usage: https://domain.com/admin/promote_admin.php?email=user@example.com&action=promote
 * 
 * For security, this should only be used during initial setup or by existing admins.
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once __DIR__ . '/../core/User.php';
} catch (Exception $e) {
    die("Error loading User class: " . $e->getMessage());
}

// Simple security check - require a secret key for promotion
$secret = $_GET['secret'] ?? '';
$requiredSecret = 'admin_setup_2024'; // Change this to something secure

if ($secret !== $requiredSecret) {
    http_response_code(403);
    die('Access denied. Invalid secret key.');
}

$email = $_GET['email'] ?? '';
$action = $_GET['action'] ?? '';

if (empty($email)) {
    die('Error: Email parameter is required.');
}

// URL decode the email and trim whitespace
$email = urldecode(trim($email));

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die('Error: Invalid email format. Received: ' . htmlspecialchars($email));
}

try {
    $user = User::findByEmail($email);
    if (!$user) {
        die('Error: User not found with email: ' . htmlspecialchars($email));
    }
} catch (Exception $e) {
    die('Database error: ' . $e->getMessage());
}

switch ($action) {
    case 'promote':
        if ($user->isAdmin()) {
            echo "User {$email} is already an admin.";
        } else {
            $success = $user->promoteToAdmin();
            if ($success) {
                echo "✓ Successfully promoted {$email} to admin status.";
            } else {
                echo "✗ Failed to promote {$email} to admin.";
            }
        }
        break;
        
    case 'demote':
        if (!$user->isAdmin()) {
            echo "User {$email} is not an admin.";
        } else {
            $success = $user->demoteFromAdmin();
            if ($success) {
                echo "✓ Successfully removed admin status from {$email}.";
            } else {
                echo "✗ Failed to remove admin status from {$email}.";
            }
        }
        break;
        
    case 'check':
        echo "User {$email} admin status: " . ($user->isAdmin() ? 'Yes' : 'No');
        break;
        
    default:
        echo "Available actions:\n";
        echo "- promote: Make user an admin\n";
        echo "- demote: Remove admin status\n";
        echo "- check: Check current admin status\n";
        echo "\nUsage: ?email=user@example.com&action=promote&secret={$requiredSecret}";
        break;
}
?>