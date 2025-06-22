<?php
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/User.php';

$auth = Auth::getInstance();
$auth->requireAuth();

$user = $auth->getCurrentUser();

// Restrict access to admin users only
if (!$user->isAdmin()) {
    header('Location: /dashboard/');
    exit();
}

$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $userId = $_POST['user_id'] ?? '';
    $csrfToken = $_POST['csrf_token'] ?? '';
    
    if (!$auth->validateCSRFToken($csrfToken)) {
        $error = 'Invalid request. Please try again.';
    } else {
        try {
            $targetUser = User::findById($userId);
            if (!$targetUser) {
                $error = 'User not found.';
            } else {
                switch ($action) {
                    case 'promote':
                        if ($targetUser->isAdmin()) {
                            $error = 'User is already an admin.';
                        } else {
                            $success = $targetUser->promoteToAdmin();
                            if ($success) {
                                $success = "Successfully promoted {$targetUser->getEmail()} to admin.";
                            } else {
                                $error = 'Failed to promote user to admin.';
                            }
                        }
                        break;
                        
                    case 'demote':
                        if (!$targetUser->isAdmin()) {
                            $error = 'User is not an admin.';
                        } elseif ($targetUser->getId() === $user->getId()) {
                            $error = 'You cannot demote yourself.';
                        } else {
                            $success = $targetUser->demoteFromAdmin();
                            if ($success) {
                                $success = "Successfully removed admin status from {$targetUser->getEmail()}.";
                            } else {
                                $error = 'Failed to remove admin status.';
                            }
                        }
                        break;
                        
                    case 'delete':
                        if ($targetUser->getId() === $user->getId()) {
                            $error = 'You cannot delete yourself.';
                        } else {
                            try {
                                $email = $targetUser->getEmail();
                                $success = $targetUser->delete();
                                if ($success) {
                                    $success = "Successfully deleted user {$email} and all associated data.";
                                } else {
                                    $error = 'Failed to delete user.';
                                }
                            } catch (Exception $e) {
                                $error = 'Error deleting user: ' . $e->getMessage();
                            }
                        }
                        break;
                        
                    default:
                        $error = 'Invalid action.';
                        break;
                }
            }
        } catch (Exception $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

// Get all users
try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->query("
        SELECT id, email, plan, is_admin, email_verified, created_at,
               (SELECT COUNT(*) FROM sources WHERE user_id = users.id AND is_active = 1) as source_count
        FROM users 
        ORDER BY created_at DESC
    ");
    $users = $stmt->fetchAll();
} catch (Exception $e) {
    $error = 'Failed to load users: ' . $e->getMessage();
    $users = [];
}

$currentPage = 'users';
$csrfToken = $auth->generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - MorningNewsletter</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <?php include __DIR__ . '/includes/navigation.php'; ?>

    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="px-4 py-6 sm:px-0">
            <h1 class="text-3xl font-bold text-gray-900">User Management</h1>
            <p class="mt-2 text-gray-600">Manage user accounts and admin permissions</p>
        </div>

        <!-- Admin Statistics -->
        <div class="mb-6 grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="bg-white shadow rounded-lg p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-users text-blue-600 text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <div class="text-sm font-medium text-gray-500">Total Users</div>
                        <div class="text-2xl font-bold text-gray-900"><?php echo count($users); ?></div>
                    </div>
                </div>
            </div>
            
            <div class="bg-white shadow rounded-lg p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-crown text-red-600 text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <div class="text-sm font-medium text-gray-500">Admin Users</div>
                        <div class="text-2xl font-bold text-gray-900">
                            <?php echo count(array_filter($users, function($u) { return $u['is_admin']; })); ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="bg-white shadow rounded-lg p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-check-circle text-green-600 text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <div class="text-sm font-medium text-gray-500">Verified Users</div>
                        <div class="text-2xl font-bold text-gray-900">
                            <?php echo count(array_filter($users, function($u) { return $u['email_verified']; })); ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="bg-white shadow rounded-lg p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-star text-purple-600 text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <div class="text-sm font-medium text-gray-500">Premium Users</div>
                        <div class="text-2xl font-bold text-gray-900">
                            <?php echo count(array_filter($users, function($u) { return $u['plan'] === 'premium'; })); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($error): ?>
        <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <?php if ($success): ?>
        <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded">
            <i class="fas fa-check-circle mr-2"></i>
            <?php echo htmlspecialchars($success); ?>
        </div>
        <?php endif; ?>

        <!-- Users Table -->
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">All Users</h3>
                
                <?php if (empty($users)): ?>
                    <p class="text-gray-500">No users found.</p>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Plan</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sources</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Joined</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Admin</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($users as $userData): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10">
                                                <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                                                    <i class="fas fa-user text-blue-600"></i>
                                                </div>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?php echo htmlspecialchars($userData['email']); ?>
                                                    <?php if ($userData['id'] == $user->getId()): ?>
                                                        <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                            You
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="text-sm text-gray-500">ID: <?php echo $userData['id']; ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                            <?php 
                                            switch($userData['plan']) {
                                                case 'premium': echo 'bg-purple-100 text-purple-800'; break;
                                                case 'medium': echo 'bg-blue-100 text-blue-800'; break;
                                                default: echo 'bg-gray-100 text-gray-800';
                                            }
                                            ?>">
                                            <?php echo ucfirst($userData['plan']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo $userData['source_count']; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $userData['email_verified'] ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                            <?php echo $userData['email_verified'] ? 'Verified' : 'Unverified'; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo date('M j, Y', strtotime($userData['created_at'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if ($userData['is_admin']): ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                <i class="fas fa-crown mr-1"></i>
                                                Admin
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                User
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <?php if ($userData['id'] != $user->getId()): ?>
                                            <div class="flex space-x-2">
                                                <?php if ($userData['is_admin']): ?>
                                                    <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to remove admin access from <?php echo htmlspecialchars($userData['email']); ?>?');">
                                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                                        <input type="hidden" name="action" value="demote">
                                                        <input type="hidden" name="user_id" value="<?php echo $userData['id']; ?>">
                                                        <button type="submit" class="text-orange-600 hover:text-orange-900">
                                                            <i class="fas fa-user-minus mr-1"></i>
                                                            Demote
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to promote <?php echo htmlspecialchars($userData['email']); ?> to admin?');">
                                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                                        <input type="hidden" name="action" value="promote">
                                                        <input type="hidden" name="user_id" value="<?php echo $userData['id']; ?>">
                                                        <button type="submit" class="text-blue-600 hover:text-blue-900">
                                                            <i class="fas fa-user-plus mr-1"></i>
                                                            Promote
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                
                                                <span class="text-gray-300">|</span>
                                                
                                                <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to permanently DELETE <?php echo htmlspecialchars($userData['email']); ?>? This will remove all their data including sources and email logs. This action cannot be undone!');">
                                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="user_id" value="<?php echo $userData['id']; ?>">
                                                    <button type="submit" class="text-red-600 hover:text-red-900">
                                                        <i class="fas fa-trash mr-1"></i>
                                                        Delete
                                                    </button>
                                                </form>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-gray-400">Current User</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>