<?php
// Session and User Debug
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<pre>";
echo "=== SESSION AND USER DEBUG ===\n\n";

// 1. Check session contents
echo "1. Session Contents:\n";
print_r($_SESSION);
echo "\n";

// 2. Check if user_id exists in session
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    echo "2. User ID in session: $userId\n\n";
    
    // 3. Check database connection
    require_once __DIR__ . '/config/database.php';
    require_once __DIR__ . '/core/User.php';
    
    $db = Database::getInstance()->getConnection();
    
    // 4. Query user directly
    echo "3. Direct database query for user ID $userId:\n";
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $userData = $stmt->fetch();
    
    if ($userData) {
        echo "User found in database:\n";
        // Don't show password hash
        unset($userData['password_hash']);
        print_r($userData);
    } else {
        echo "ERROR: User ID $userId NOT FOUND in database!\n";
        
        // List all users (without passwords)
        echo "\n4. All users in database:\n";
        $stmt = $db->query("SELECT id, email, name, plan FROM users");
        $allUsers = $stmt->fetchAll();
        print_r($allUsers);
    }
    
    // 5. Test User::findById
    echo "\n5. Testing User::findById($userId):\n";
    $user = User::findById($userId);
    if ($user) {
        echo "User object created successfully\n";
        echo "Email: " . $user->getEmail() . "\n";
        echo "ID: " . $user->getId() . "\n";
    } else {
        echo "User::findById returned NULL\n";
    }
    
} else {
    echo "2. No user_id in session - user not logged in\n";
}

// 6. Check Auth class
echo "\n6. Testing Auth class:\n";
require_once __DIR__ . '/core/Auth.php';
$auth = Auth::getInstance();
echo "Is logged in: " . ($auth->isLoggedIn() ? "Yes" : "No") . "\n";

if ($auth->isLoggedIn()) {
    $currentUser = $auth->getCurrentUser();
    if ($currentUser) {
        echo "getCurrentUser() returned user with email: " . $currentUser->getEmail() . "\n";
    } else {
        echo "getCurrentUser() returned NULL\n";
    }
}

echo "\n=== END DEBUG ===\n";
echo "</pre>";