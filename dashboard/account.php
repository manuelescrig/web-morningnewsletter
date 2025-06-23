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
    $action = $_POST['action'] ?? '';
    $csrfToken = $_POST['csrf_token'] ?? '';
    
    if (!$auth->validateCSRFToken($csrfToken)) {
        $error = 'Invalid request. Please try again.';
    } else {
        switch ($action) {
            case 'update_profile':
                $name = trim($_POST['name'] ?? '');
                
                $updateData = [];
                if (!empty($name)) {
                    $updateData['name'] = $name;
                }
                
                if (!empty($updateData)) {
                    if ($user->updateProfile($updateData)) {
                        $success = 'Profile updated successfully!';
                        // Refresh user data
                        $user = $auth->getCurrentUser();
                    } else {
                        $error = 'Failed to update profile.';
                    }
                } else {
                    $error = 'Please provide a name to update.';
                }
                break;
                
            case 'change_email':
                $email = trim($_POST['email'] ?? '');
                $password = $_POST['email_password'] ?? '';
                
                if (empty($email)) {
                    $error = 'Email is required.';
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $error = 'Please enter a valid email address.';
                } elseif (empty($password)) {
                    $error = 'Password is required to change email.';
                } else {
                    // Verify password first
                    $stmt = $user->db->prepare("SELECT password_hash FROM users WHERE id = ?");
                    $stmt->execute([$user->getId()]);
                    $userData = $stmt->fetch();
                    
                    if (!$userData || !password_verify($password, $userData['password_hash'])) {
                        $error = 'Incorrect password.';
                    } else {
                        if ($user->updateProfile(['email' => $email])) {
                            $success = 'Email address updated successfully!';
                            // Refresh user data
                            $user = $auth->getCurrentUser();
                        } else {
                            $error = 'Failed to update email address. This email may already be in use.';
                        }
                    }
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

    <div class="max-w-4xl mx-auto py-6 sm:px-6 lg:px-8">
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
                    
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700">Full Name</label>
                            <input type="text" 
                                   name="name" 
                                   id="name" 
                                   value="<?php echo htmlspecialchars($user->getName() ?? ''); ?>"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                   placeholder="Enter your full name">
                            <p class="mt-1 text-sm text-gray-500">Optional - helps personalize your experience</p>
                        </div>
                        
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
                            <input type="email" 
                                   name="email" 
                                   id="email" 
                                   required
                                   value="<?php echo htmlspecialchars($user->getEmail()); ?>"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            <p class="mt-1 text-sm text-gray-500">Your newsletters will be sent to this email</p>
                        </div>
                    </div>
                    
                    <div class="flex justify-end">
                        <button type="submit" 
                                class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <i class="fas fa-save mr-2"></i>
                            Update Profile
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
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>
                    
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <label for="new_password" class="block text-sm font-medium text-gray-700">New Password</label>
                            <input type="password" 
                                   name="new_password" 
                                   id="new_password" 
                                   required
                                   minlength="8"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            <p class="mt-1 text-sm text-gray-500">Minimum 8 characters</p>
                        </div>
                        
                        <div>
                            <label for="confirm_password" class="block text-sm font-medium text-gray-700">Confirm New Password</label>
                            <input type="password" 
                                   name="confirm_password" 
                                   id="confirm_password" 
                                   required
                                   minlength="8"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
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
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500 sm:text-sm">
                        </div>
                        
                        <div>
                            <label for="delete_confirmation" class="block text-sm font-medium text-gray-700">Type "DELETE" to confirm</label>
                            <input type="text" 
                                   name="delete_confirmation" 
                                   id="delete_confirmation" 
                                   required
                                   placeholder="DELETE"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500 sm:text-sm">
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
</body>
</html>