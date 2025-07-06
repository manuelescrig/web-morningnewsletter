<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

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
                        
                    case 'promote_plan':
                        $nextPlan = $targetUser->getNextPlan();
                        if (!$nextPlan) {
                            $error = 'User is already on the highest plan.';
                        } else {
                            $success = $targetUser->promotePlan();
                            if ($success) {
                                $success = "Successfully promoted {$targetUser->getEmail()} from {$targetUser->getPreviousPlan()} to {$targetUser->getPlan()}.";
                            } else {
                                $error = 'Failed to promote user plan.';
                            }
                        }
                        break;
                        
                    case 'demote_plan':
                        $previousPlan = $targetUser->getPreviousPlan();
                        if (!$previousPlan) {
                            $error = 'User is already on the lowest plan.';
                        } else {
                            $success = $targetUser->demotePlan();
                            if ($success) {
                                $success = "Successfully demoted {$targetUser->getEmail()} from {$targetUser->getNextPlan()} to {$targetUser->getPlan()}.";
                            } else {
                                $error = 'Failed to demote user plan.';
                            }
                        }
                        break;
                        
                    case 'change_plan':
                        $newPlan = $_POST['new_plan'] ?? '';
                        $validPlans = ['free', 'starter', 'pro', 'unlimited'];
                        if (!in_array($newPlan, $validPlans)) {
                            $error = 'Invalid plan selected.';
                        } elseif ($newPlan === $targetUser->getPlan()) {
                            $error = 'User is already on this plan.';
                        } else {
                            $oldPlan = $targetUser->getPlan();
                            $success = $targetUser->changePlan($newPlan);
                            if ($success) {
                                $success = "Successfully changed {$targetUser->getEmail()}'s plan from {$oldPlan} to {$newPlan}.";
                            } else {
                                $error = 'Failed to change user plan.';
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
                        
                    case 'resend_verification':
                        try {
                            $result = $targetUser->resendVerificationEmail();
                            if ($result['success']) {
                                $success = "Verification email resent to {$targetUser->getEmail()}.";
                            } else {
                                $error = $result['message'];
                            }
                        } catch (Exception $e) {
                            $error = 'Error sending verification email: ' . $e->getMessage();
                            error_log("Resend verification error for user {$targetUser->getId()}: " . $e->getMessage());
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
                        <div class="text-sm font-medium text-gray-500">Unlimited Users</div>
                        <div class="text-2xl font-bold text-gray-900">
                            <?php echo count(array_filter($users, function($u) { return $u['plan'] === 'unlimited'; })); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Plan Distribution -->
        <div class="mb-6 bg-white shadow rounded-lg p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Plan Distribution</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <?php
                $planCounts = [
                    'free' => count(array_filter($users, function($u) { return $u['plan'] === 'free'; })),
                    'starter' => count(array_filter($users, function($u) { return $u['plan'] === 'starter'; })),
                    'pro' => count(array_filter($users, function($u) { return $u['plan'] === 'pro'; })),
                    'unlimited' => count(array_filter($users, function($u) { return $u['plan'] === 'unlimited'; }))
                ];
                $totalUsers = count($users);
                ?>
                
                <?php foreach ($planCounts as $plan => $count): ?>
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-sm font-medium text-gray-500"><?php echo ucfirst($plan); ?> Plan</div>
                            <div class="text-2xl font-bold text-gray-900"><?php echo $count; ?></div>
                        </div>
                        <div class="text-right">
                            <div class="text-lg font-semibold 
                                <?php 
                                switch($plan) {
                                    case 'unlimited': echo 'text-purple-600'; break;
                                    case 'pro': echo 'text-red-600'; break;
                                    case 'starter': echo 'text-blue-600'; break;
                                    default: echo 'text-gray-600';
                                }
                                ?>">
                                <?php echo $totalUsers > 0 ? round(($count / $totalUsers) * 100) : 0; ?>%
                            </div>
                            <div class="text-xs text-gray-500">
                                <?php 
                                $limits = ['free' => '1 source', 'starter' => '5 sources', 'pro' => '15 sources', 'unlimited' => 'Unlimited'];
                                echo $limits[$plan];
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
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
                                                case 'unlimited': echo 'bg-purple-100 text-purple-800'; break;
                                                case 'pro': echo 'bg-red-100 text-red-800'; break;
                                                case 'starter': echo 'bg-blue-100 text-blue-800'; break;
                                                default: echo 'bg-gray-100 text-gray-800';
                                            }
                                            ?>">
                                            <?php echo ucfirst($userData['plan']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php 
                                        $limits = ['free' => 1, 'starter' => 5, 'pro' => 15, 'unlimited' => PHP_INT_MAX];
                                        $limit = $limits[$userData['plan']] ?? 1;
                                        $limitText = $limit === PHP_INT_MAX ? '∞' : $limit;
                                        $isAtLimit = $userData['source_count'] >= $limit && $limit !== PHP_INT_MAX;
                                        ?>
                                        <span class="<?php echo $isAtLimit ? 'text-red-600 font-semibold' : 'text-gray-900'; ?>">
                                            <?php echo $userData['source_count']; ?>/<?php echo $limitText; ?>
                                        </span>
                                        <?php if ($isAtLimit): ?>
                                            <i class="fas fa-exclamation-triangle text-red-500 ml-1" title="Source limit reached"></i>
                                        <?php endif; ?>
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
                                        <div class="relative inline-block text-left">
                                            <button type="button" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" onclick="toggleUserActionDropdown(<?php echo $userData['id']; ?>)">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                                <div id="dropdown-<?php echo $userData['id']; ?>" class="hidden origin-top-right absolute right-0 mt-2 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-10">
                                                    <div class="py-1">
                                                        <!-- Plan Management Section -->
                                                        <?php 
                                                        $currentPlan = $userData['plan'];
                                                        $planHierarchy = ['free', 'starter', 'pro', 'unlimited'];
                                                        $currentIndex = array_search($currentPlan, $planHierarchy);
                                                        ?>
                                                        
                                                        <?php if ($currentIndex < count($planHierarchy) - 1): ?>
                                                            <form method="POST" class="block" onsubmit="return confirm('Promote <?php echo htmlspecialchars($userData['email']); ?> to <?php echo $planHierarchy[$currentIndex + 1]; ?> plan?');">
                                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                                                <input type="hidden" name="action" value="promote_plan">
                                                                <input type="hidden" name="user_id" value="<?php echo $userData['id']; ?>">
                                                                <button type="submit" class="w-full text-left px-4 py-2 text-sm text-green-700 hover:bg-green-50 flex items-center">
                                                                    <i class="fas fa-arrow-up mr-2"></i>
                                                                    Promote to <?php echo ucfirst($planHierarchy[$currentIndex + 1]); ?>
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                        
                                                        <?php if ($currentIndex > 0): ?>
                                                            <form method="POST" class="block" onsubmit="return confirm('Demote <?php echo htmlspecialchars($userData['email']); ?> to <?php echo $planHierarchy[$currentIndex - 1]; ?> plan?');">
                                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                                                <input type="hidden" name="action" value="demote_plan">
                                                                <input type="hidden" name="user_id" value="<?php echo $userData['id']; ?>">
                                                                <button type="submit" class="w-full text-left px-4 py-2 text-sm text-orange-700 hover:bg-orange-50 flex items-center">
                                                                    <i class="fas fa-arrow-down mr-2"></i>
                                                                    Demote to <?php echo ucfirst($planHierarchy[$currentIndex - 1]); ?>
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                        
                                                        <!-- Quick Plan Selector -->
                                                        <div class="px-4 py-2">
                                                            <label class="block text-xs font-medium text-gray-700 mb-1">Quick Plan Change:</label>
                                                            <form method="POST" class="flex items-center space-x-1" onsubmit="return confirm('Change <?php echo htmlspecialchars($userData['email']); ?>\'s plan?');">
                                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                                                <input type="hidden" name="action" value="change_plan">
                                                                <input type="hidden" name="user_id" value="<?php echo $userData['id']; ?>">
                                                                <select name="new_plan" class="text-xs border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 flex-1" onchange="this.form.submit()">
                                                                    <option value="">Select...</option>
                                                                    <?php foreach ($planHierarchy as $plan): ?>
                                                                        <?php if ($plan !== $currentPlan): ?>
                                                                            <option value="<?php echo $plan; ?>"><?php echo ucfirst($plan); ?></option>
                                                                        <?php endif; ?>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </form>
                                                        </div>
                                                        
                                                        <?php if (($currentIndex < count($planHierarchy) - 1) || ($currentIndex > 0)): ?>
                                                            <div class="border-t border-gray-100"></div>
                                                        <?php endif; ?>
                                                        
                                                        <!-- Admin Management Section -->
                                                        <?php if ($userData['is_admin']): ?>
                                                            <form method="POST" class="block" onsubmit="return confirm('Are you sure you want to remove admin access from <?php echo htmlspecialchars($userData['email']); ?>?');">
                                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                                                <input type="hidden" name="action" value="demote">
                                                                <input type="hidden" name="user_id" value="<?php echo $userData['id']; ?>">
                                                                <button type="submit" class="w-full text-left px-4 py-2 text-sm text-orange-700 hover:bg-orange-50 flex items-center">
                                                                    <i class="fas fa-user-minus mr-2"></i>
                                                                    Remove Admin
                                                                </button>
                                                            </form>
                                                        <?php else: ?>
                                                            <form method="POST" class="block" onsubmit="return confirm('Are you sure you want to grant admin access to <?php echo htmlspecialchars($userData['email']); ?>?');">
                                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                                                <input type="hidden" name="action" value="promote">
                                                                <input type="hidden" name="user_id" value="<?php echo $userData['id']; ?>">
                                                                <button type="submit" class="w-full text-left px-4 py-2 text-sm text-blue-700 hover:bg-blue-50 flex items-center">
                                                                    <i class="fas fa-crown mr-2"></i>
                                                                    Make Admin
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                        
                                                        <?php if (!$userData['email_verified']): ?>
                                                            <form method="POST" class="block" onsubmit="return confirm('Resend verification email to <?php echo htmlspecialchars($userData['email']); ?>?');">
                                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                                                <input type="hidden" name="action" value="resend_verification">
                                                                <input type="hidden" name="user_id" value="<?php echo $userData['id']; ?>">
                                                                <button type="submit" class="w-full text-left px-4 py-2 text-sm text-green-700 hover:bg-green-50 flex items-center">
                                                                    <i class="fas fa-envelope mr-2"></i>
                                                                    Resend Verification
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                        
                                                        <?php if ($userData['id'] != $user->getId()): ?>
                                                            <div class="border-t border-gray-100"></div>
                                                            <form method="POST" class="block" onsubmit="return confirm('Are you sure you want to permanently DELETE <?php echo htmlspecialchars($userData['email']); ?>? This will remove all their data including sources and email logs. This action cannot be undone!');">
                                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                                                <input type="hidden" name="action" value="delete">
                                                                <input type="hidden" name="user_id" value="<?php echo $userData['id']; ?>">
                                                                <button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-700 hover:bg-red-50 flex items-center">
                                                                    <i class="fas fa-trash mr-2"></i>
                                                                    Delete User
                                                                </button>
                                                            </form>
                                                        <?php else: ?>
                                                            <div class="border-t border-gray-100"></div>
                                                            <div class="px-4 py-2 text-xs text-blue-600 bg-blue-50">
                                                                <i class="fas fa-info-circle mr-1"></i>
                                                                This is your account
                                                            </div>
                                                            <a href="/dashboard/settings.php" class="w-full text-left px-4 py-2 text-sm text-blue-700 hover:bg-blue-50 flex items-center">
                                                                <i class="fas fa-cog mr-2"></i>
                                                                Go to Settings
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                        </div>
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

    <script>
        function toggleUserActionDropdown(userId) {
            const dropdown = document.getElementById('dropdown-' + userId);
            const allDropdowns = document.querySelectorAll('[id^="dropdown-"]');
            
            // Close all other dropdowns
            allDropdowns.forEach(d => {
                if (d !== dropdown) {
                    d.classList.add('hidden');
                }
            });
            
            // Toggle current dropdown
            dropdown.classList.toggle('hidden');
        }

        // Close dropdowns when clicking outside
        document.addEventListener('click', function(event) {
            if (!event.target.closest('[onclick^="toggleUserActionDropdown"]') && !event.target.closest('[id^="dropdown-"]')) {
                document.querySelectorAll('[id^="dropdown-"]').forEach(d => {
                    d.classList.add('hidden');
                });
            }
        });
    </script>
</body>
</html>