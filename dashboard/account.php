<?php
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/SubscriptionManager.php';

$auth = Auth::getInstance();
$auth->requireAuth();

$user = $auth->getCurrentUser();
$error = '';
$success = '';

$currentPage = 'account';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("POST data received: " . json_encode($_POST));
    
    // Check database connection
    try {
        require_once __DIR__ . '/../config/database.php';
        $db = Database::getInstance()->getConnection();
        error_log("Database connection successful");
    } catch (Exception $e) {
        error_log("Database connection failed: " . $e->getMessage());
    }
    
    // Check user object
    if ($user) {
        error_log("User object exists - ID: " . $user->getId() . ", Email: " . $user->getEmail());
    } else {
        error_log("User object is null");
    }
    
    $action = $_POST['action'] ?? '';
    $csrfToken = $_POST['csrf_token'] ?? '';
    
    if (!$auth->validateCSRFToken($csrfToken)) {
        $error = 'Invalid request. Please try again.';
    } else {
        switch ($action) {
            case 'update_profile':
                try {
                    error_log("Processing update_profile action");
                    $name = trim($_POST['name'] ?? '');
                    error_log("Name received: " . $name);
                    
                    $updateData = [];
                    if (!empty($name)) {
                        $updateData['name'] = $name;
                    }
                    
                    if (!empty($updateData)) {
                        error_log("Attempting to update profile with data: " . json_encode($updateData));
                        
                        // Check if user object exists and has the method
                        if (!$user) {
                            throw new Exception("User object is null");
                        }
                        
                        if (!method_exists($user, 'updateProfile')) {
                            throw new Exception("updateProfile method does not exist on User class");
                        }
                        
                        $result = $user->updateProfile($updateData);
                        error_log("updateProfile returned: " . ($result ? 'true' : 'false'));
                        
                        if ($result) {
                            $success = 'Profile updated successfully!';
                            error_log("Profile update successful");
                            // Refresh user data
                            $user = $auth->getCurrentUser();
                        } else {
                            $error = 'Failed to update profile.';
                            error_log("Profile update failed - updateProfile returned false");
                        }
                    } else {
                        $error = 'Please provide a name to update.';
                        error_log("No name provided for update");
                    }
                } catch (Exception $e) {
                    $error = 'An error occurred while updating your profile. Please try again.';
                    error_log("Exception in update_profile: " . $e->getMessage());
                    error_log("Stack trace: " . $e->getTraceAsString());
                }
                break;
                
            case 'change_email':
                $email = trim($_POST['email'] ?? '');
                
                if (empty($email)) {
                    $error = 'Email is required.';
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $error = 'Please enter a valid email address.';
                } elseif ($email === $user->getEmail()) {
                    $error = 'The new email address is the same as your current email address.';
                } else {
                    $result = $user->requestEmailChange($email);
                    if ($result['success']) {
                        $success = $result['message'];
                    } else {
                        $error = $result['message'];
                    }
                }
                break;
                
            case 'cancel_email_change':
                if ($user->cancelEmailChange()) {
                    $success = 'Email change request cancelled successfully.';
                } else {
                    $error = 'No pending email change request found.';
                }
                break;
                
            case 'change_password':
                $currentPassword = $_POST['current_password'] ?? '';
                $newPassword = $_POST['new_password'] ?? '';
                $confirmPassword = $_POST['confirm_password'] ?? '';
                
                if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                    $error = 'All password fields are required.';
                } elseif ($newPassword !== $confirmPassword) {
                    $error = 'New passwords do not match.';
                } elseif (strlen($newPassword) < 8) {
                    $error = 'New password must be at least 8 characters long.';
                } else {
                    if ($user->changePassword($currentPassword, $newPassword)) {
                        $success = 'Password changed successfully!';
                    } else {
                        $error = 'Current password is incorrect.';
                    }
                }
                break;
                
            case 'delete_account':
                $password = $_POST['delete_password'] ?? '';
                $confirmation = $_POST['delete_confirmation'] ?? '';
                
                if ($confirmation !== 'DELETE') {
                    $error = 'Please type "DELETE" to confirm account deletion.';
                } elseif (empty($password)) {
                    $error = 'Password is required to delete your account.';
                } else {
                    if ($user->deleteAccount($password)) {
                        // Log out and redirect
                        $auth->logout();
                        header('Location: /?deleted=1');
                        exit;
                    } else {
                        $error = 'Incorrect password. Account deletion failed.';
                    }
                }
                break;
        }
    }
}

$csrfToken = $auth->generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings - MorningNewsletter</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <?php include __DIR__ . '/includes/navigation.php'; ?>

    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="px-4 py-6 sm:px-0">
            <h1 class="text-3xl font-bold text-gray-900">Account Settings</h1>
            <p class="mt-2 text-gray-600">Manage your account information and preferences</p>
        </div>

        <!-- Alert Messages -->
        <?php if ($error): ?>
        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-md">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <?php if ($success): ?>
        <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-md">
            <i class="fas fa-check-circle mr-2"></i>
            <?php echo htmlspecialchars($success); ?>
        </div>
        <?php endif; ?>

        <!-- Profile Information -->
        <div class="bg-white shadow rounded-lg mb-8">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Profile Information</h3>
                
                <form method="POST" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    <input type="hidden" name="action" value="update_profile">
                    
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Full Name</label>
                        <input type="text" 
                               name="name" 
                               id="name" 
                               value="<?php echo htmlspecialchars($user->getName() ?? ''); ?>"
                               class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm px-3 py-2"
                               placeholder="Enter your full name">
                    </div>
                    
                    <div class="flex justify-end">
                        <button type="submit" 
                                class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <i class="fas fa-user mr-2"></i>
                            Update Name
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Change Email Address -->
        <div class="bg-white shadow rounded-lg mb-8">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Email Address</h3>
                <p class="text-sm text-gray-600 mb-6">
                    Current email: <strong><?php echo htmlspecialchars($user->getEmail()); ?></strong>
                </p>
                
                <?php 
                $pendingEmailChange = $user->getPendingEmailChange();
                if ($pendingEmailChange): 
                ?>
                <div class="bg-yellow-50 border border-yellow-200 rounded-md p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-clock text-yellow-400"></i>
                        </div>
                        <div class="ml-3 flex-1">
                            <h4 class="text-sm font-medium text-yellow-800">Pending Email Change</h4>
                            <p class="text-sm text-yellow-700 mt-1">
                                You have a pending email change to <strong><?php echo htmlspecialchars($pendingEmailChange['new_email']); ?></strong>. 
                                Please check your new email inbox for the verification link.
                            </p>
                            <p class="text-xs text-yellow-600 mt-2">
                                Expires: <?php echo date('M j, Y g:i A', strtotime($pendingEmailChange['expires_at'])); ?>
                            </p>
                        </div>
                        <div class="ml-4 flex-shrink-0">
                            <form method="POST" class="inline">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                <input type="hidden" name="action" value="cancel_email_change">
                                <button type="submit" 
                                        onclick="return confirm('Are you sure you want to cancel the email change request?')"
                                        class="text-sm text-yellow-800 hover:text-yellow-900 font-medium">
                                    Cancel
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <form method="POST" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    <input type="hidden" name="action" value="change_email">
                    
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">New Email Address</label>
                        <input type="email" 
                               name="email" 
                               id="email" 
                               required
                               class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm px-3 py-2"
                               placeholder="Enter new email address">
                    </div>
                    
                    <div class="bg-blue-50 border border-blue-200 rounded-md p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-info-circle text-blue-400"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-blue-700">
                                    We'll send a verification email to your new address. Click the link in that email to complete the change.
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex justify-end">
                        <button type="submit" 
                                class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <i class="fas fa-envelope mr-2"></i>
                            Send Verification Email
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Change Password -->
        <div class="bg-white shadow rounded-lg mb-8">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Change Password</h3>
                
                <form method="POST" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    <input type="hidden" name="action" value="change_password">
                    
                    <div>
                        <label for="current_password" class="block text-sm font-medium text-gray-700">Current Password</label>
                        <input type="password" 
                               name="current_password" 
                               id="current_password" 
                               required
                               class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm px-3 py-2">
                    </div>
                    
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <label for="new_password" class="block text-sm font-medium text-gray-700">New Password</label>
                            <input type="password" 
                                   name="new_password" 
                                   id="new_password" 
                                   required
                                   minlength="8"
                                   class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm px-3 py-2">
                            <p class="mt-1 text-sm text-gray-500">Minimum 8 characters</p>
                        </div>
                        
                        <div>
                            <label for="confirm_password" class="block text-sm font-medium text-gray-700">Confirm New Password</label>
                            <input type="password" 
                                   name="confirm_password" 
                                   id="confirm_password" 
                                   required
                                   minlength="8"
                                   class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm px-3 py-2">
                        </div>
                    </div>
                    
                    <div class="flex justify-end">
                        <button type="submit" 
                                class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <i class="fas fa-key mr-2"></i>
                            Change Password
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Delete Account -->
        <div class="bg-white shadow rounded-lg border-l-4 border-red-400">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-red-900 mb-4">Delete Account</h3>
                <p class="text-sm text-red-700 mb-6">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    This action cannot be undone. All your data, including newsletters, sources, and subscription history will be permanently deleted.
                </p>
                
                <form method="POST" class="space-y-6" onsubmit="return confirmDeletion()">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    <input type="hidden" name="action" value="delete_account">
                    
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <label for="delete_password" class="block text-sm font-medium text-gray-700">Password</label>
                            <input type="password" 
                                   name="delete_password" 
                                   id="delete_password" 
                                   required
                                   class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-red-500 focus:border-red-500 sm:text-sm px-3 py-2">
                        </div>
                        
                        <div>
                            <label for="delete_confirmation" class="block text-sm font-medium text-gray-700">Type "DELETE" to confirm</label>
                            <input type="text" 
                                   name="delete_confirmation" 
                                   id="delete_confirmation" 
                                   required
                                   placeholder="DELETE"
                                   class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-red-500 focus:border-red-500 sm:text-sm px-3 py-2">
                        </div>
                    </div>
                    
                    <div class="flex justify-end">
                        <button type="submit" 
                                class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                            <i class="fas fa-trash mr-2"></i>
                            Delete Account
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function confirmDeletion() {
            return confirm('Are you absolutely sure you want to delete your account? This action cannot be undone and all your data will be permanently lost.');
        }
        
        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (newPassword !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>

    <script src="/assets/js/main.js"></script>
    <script src="/assets/js/dashboard.js"></script>
</body>
</html>