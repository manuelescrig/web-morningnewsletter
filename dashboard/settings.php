<?php
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/EmailSender.php';

$auth = Auth::getInstance();
$auth->requireAuth();

$user = $auth->getCurrentUser();
$emailSender = new EmailSender();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $csrfToken = $_POST['csrf_token'] ?? '';
    
    if (!$auth->validateCSRFToken($csrfToken)) {
        $error = 'Invalid request. Please try again.';
    } else {
        switch ($action) {
            case 'update_plan':
                $plan = $_POST['plan'] ?? '';
                if (in_array($plan, ['free', 'medium', 'premium'])) {
                    if ($user->updateProfile(['plan' => $plan])) {
                        $success = 'Plan updated successfully!';
                        // Refresh user data
                        $user = $auth->getCurrentUser();
                    } else {
                        $error = 'Failed to update plan.';
                    }
                } else {
                    $error = 'Invalid plan selected.';
                }
                break;
        }
    }
}

// Get email statistics
$emailStats = $emailSender->getEmailStats($user->getId());
$csrfToken = $auth->generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - MorningNewsletter</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="/" class="text-2xl font-bold text-blue-600">MorningNewsletter</a>
                    <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                        <a href="/dashboard/" class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            Dashboard
                        </a>
                        <a href="/dashboard/sources.php" class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            Sources
                        </a>
                        <a href="/dashboard/schedule.php" class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            Schedule
                        </a>
                        <a href="/dashboard/settings.php" class="border-blue-500 text-gray-900 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            Settings
                        </a>
                        <?php if ($user->isAdmin()): ?>
                        <a href="/dashboard/cron_status.php" class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            Cron Status
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-gray-700">Welcome, <?php echo htmlspecialchars($user->getEmail()); ?></span>
                    <a href="/auth/logout.php" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-4xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="px-4 py-6 sm:px-0">
            <h1 class="text-3xl font-bold text-gray-900">Account Settings</h1>
            <p class="mt-2 text-gray-600">Manage your account preferences and subscription</p>
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

        <div class="space-y-6">
            <!-- Account Information -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Account Information</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Email Address</label>
                            <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($user->getEmail()); ?></p>
                            <p class="mt-1 text-xs text-gray-500">
                                <?php if ($user->isEmailVerified()): ?>
                                    <i class="fas fa-check-circle text-green-500 mr-1"></i>Verified
                                <?php else: ?>
                                    <i class="fas fa-exclamation-triangle text-yellow-500 mr-1"></i>Not verified
                                <?php endif; ?>
                            </p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Current Plan</label>
                            <p class="mt-1 text-sm text-gray-900 capitalize"><?php echo htmlspecialchars($user->getPlan()); ?></p>
                            <p class="mt-1 text-xs text-gray-500">
                                <?php 
                                $sourceLimit = $user->getSourceLimit();
                                echo $sourceLimit === PHP_INT_MAX ? 'Unlimited sources' : "$sourceLimit source" . ($sourceLimit !== 1 ? 's' : '') . ' allowed';
                                ?>
                            </p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Timezone</label>
                            <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($user->getTimezone()); ?></p>
                            <a href="/dashboard/schedule.php" class="mt-1 text-xs text-blue-600 hover:text-blue-500">Change timezone</a>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Send Time</label>
                            <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($user->getSendTime()); ?></p>
                            <a href="/dashboard/schedule.php" class="mt-1 text-xs text-blue-600 hover:text-blue-500">Change send time</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Subscription Plan -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Subscription Plan</h3>
                    
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                        <input type="hidden" name="action" value="update_plan">
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <!-- Free Plan -->
                            <div class="border border-gray-200 rounded-lg p-4 <?php echo $user->getPlan() === 'free' ? 'ring-2 ring-blue-500 bg-blue-50' : ''; ?>">
                                <div class="flex items-center mb-2">
                                    <input type="radio" id="plan_free" name="plan" value="free" 
                                           <?php echo $user->getPlan() === 'free' ? 'checked' : ''; ?>
                                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300">
                                    <label for="plan_free" class="ml-2 text-sm font-medium text-gray-900">Free</label>
                                </div>
                                <p class="text-2xl font-bold text-gray-900 mb-2">$0<span class="text-sm font-normal">/month</span></p>
                                <ul class="text-sm text-gray-600 space-y-1">
                                    <li><i class="fas fa-check text-green-500 mr-2"></i>1 data source</li>
                                    <li><i class="fas fa-check text-green-500 mr-2"></i>Daily newsletters</li>
                                    <li><i class="fas fa-check text-green-500 mr-2"></i>Basic support</li>
                                </ul>
                            </div>

                            <!-- Medium Plan -->
                            <div class="border border-gray-200 rounded-lg p-4 <?php echo $user->getPlan() === 'medium' ? 'ring-2 ring-blue-500 bg-blue-50' : ''; ?>">
                                <div class="flex items-center mb-2">
                                    <input type="radio" id="plan_medium" name="plan" value="medium"
                                           <?php echo $user->getPlan() === 'medium' ? 'checked' : ''; ?>
                                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300">
                                    <label for="plan_medium" class="ml-2 text-sm font-medium text-gray-900">Medium</label>
                                </div>
                                <p class="text-2xl font-bold text-gray-900 mb-2">$5<span class="text-sm font-normal">/month</span></p>
                                <ul class="text-sm text-gray-600 space-y-1">
                                    <li><i class="fas fa-check text-green-500 mr-2"></i>5 data sources</li>
                                    <li><i class="fas fa-check text-green-500 mr-2"></i>Daily newsletters</li>
                                    <li><i class="fas fa-check text-green-500 mr-2"></i>Priority support</li>
                                    <li><i class="fas fa-check text-green-500 mr-2"></i>Custom integrations</li>
                                </ul>
                            </div>

                            <!-- Premium Plan -->
                            <div class="border border-gray-200 rounded-lg p-4 <?php echo $user->getPlan() === 'premium' ? 'ring-2 ring-blue-500 bg-blue-50' : ''; ?>">
                                <div class="flex items-center mb-2">
                                    <input type="radio" id="plan_premium" name="plan" value="premium"
                                           <?php echo $user->getPlan() === 'premium' ? 'checked' : ''; ?>
                                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300">
                                    <label for="plan_premium" class="ml-2 text-sm font-medium text-gray-900">Premium</label>
                                </div>
                                <p class="text-2xl font-bold text-gray-900 mb-2">$10<span class="text-sm font-normal">/month</span></p>
                                <ul class="text-sm text-gray-600 space-y-1">
                                    <li><i class="fas fa-check text-green-500 mr-2"></i>Unlimited sources</li>
                                    <li><i class="fas fa-check text-green-500 mr-2"></i>Daily newsletters</li>
                                    <li><i class="fas fa-check text-green-500 mr-2"></i>24/7 support</li>
                                    <li><i class="fas fa-check text-green-500 mr-2"></i>All integrations</li>
                                    <li><i class="fas fa-check text-green-500 mr-2"></i>No branding</li>
                                </ul>
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit"
                                    class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <i class="fas fa-save mr-2"></i>
                                Update Plan
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Email Statistics -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Email Statistics (Last 30 Days)</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="text-center">
                            <div class="text-3xl font-bold text-green-600 mb-2"><?php echo $emailStats['sent']; ?></div>
                            <p class="text-sm text-gray-600">Emails Sent Successfully</p>
                        </div>
                        
                        <div class="text-center">
                            <div class="text-3xl font-bold text-red-600 mb-2"><?php echo $emailStats['failed']; ?></div>
                            <p class="text-sm text-gray-600">Failed Deliveries</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Danger Zone -->
            <div class="bg-white shadow rounded-lg border-l-4 border-red-500">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-red-900 mb-4">Danger Zone</h3>
                    
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-sm font-medium text-gray-900">Pause Newsletter</h4>
                                <p class="text-sm text-gray-600">Temporarily stop receiving newsletters without losing your configuration.</p>
                            </div>
                            <button class="inline-flex items-center px-3 py-2 border border-yellow-300 shadow-sm text-sm leading-4 font-medium rounded-md text-yellow-700 bg-yellow-50 hover:bg-yellow-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500">
                                <i class="fas fa-pause mr-2"></i>
                                Pause
                            </button>
                        </div>
                        
                        <hr class="border-gray-200">
                        
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-sm font-medium text-gray-900">Delete Account</h4>
                                <p class="text-sm text-gray-600">Permanently delete your account and all associated data. This action cannot be undone.</p>
                            </div>
                            <button class="inline-flex items-center px-3 py-2 border border-red-300 shadow-sm text-sm leading-4 font-medium rounded-md text-red-700 bg-red-50 hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                                    onclick="return confirm('Are you sure you want to delete your account? This action cannot be undone.')">
                                <i class="fas fa-trash mr-2"></i>
                                Delete Account
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>