<?php
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/EmailSender.php';
require_once __DIR__ . '/../core/SubscriptionManager.php';
require_once __DIR__ . '/../config/stripe.php';

$auth = Auth::getInstance();
$auth->requireAuth();

$user = $auth->getCurrentUser();
$emailSender = new EmailSender();
$subscriptionManager = new SubscriptionManager();
$error = '';
$success = '';

$currentPage = 'settings';

// Get subscription information
$subscriptionInfo = $subscriptionManager->getUserPlanInfo($user['id']);
$payments = $subscriptionManager->getUserPayments($user['id'], 5);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $csrfToken = $_POST['csrf_token'] ?? '';
    
    if (!$auth->validateCSRFToken($csrfToken)) {
        $error = 'Invalid request. Please try again.';
    } else {
        switch ($action) {
            case 'update_plan':
                $plan = $_POST['plan'] ?? '';
                if (in_array($plan, ['free', 'starter', 'pro', 'unlimited'])) {
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
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="/assets/css/dashboard.css">
</head>
<body class="bg-gray-50">
    <?php include __DIR__ . '/includes/navigation.php'; ?>

    <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Page Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dashboard-title">Account Settings</h1>
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
                            <div class="mt-1 flex items-center space-x-2">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                    <?php 
                                    switch($user->getPlan()) {
                                        case 'unlimited': echo 'bg-purple-100 text-purple-800'; break;
                                        case 'pro': echo 'bg-red-100 text-red-800'; break;
                                        case 'starter': echo 'bg-primary-lightest text-primary-dark'; break;
                                        default: echo 'bg-gray-100 text-gray-800';
                                    }
                                    ?>">
                                    <?php echo ucfirst($user->getPlan()); ?>
                                </span>
                                
                                <?php if ($user->isAdmin()): ?>
                                    <div class="relative inline-block text-left">
                                        <button type="button" class="btn-pill inline-flex items-center px-2 py-1 border border-gray-300 shadow-sm text-xs font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus-ring-primary" onclick="togglePlanDropdown()">
                                            <i class="fas fa-cog mr-1"></i>
                                            Change
                                        </button>
                                        <div id="plan-dropdown" class="hidden origin-top-right absolute right-0 mt-2 w-40 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-10">
                                            <div class="py-1">
                                                <?php 
                                                $allPlans = ['free', 'starter', 'pro', 'unlimited'];
                                                foreach ($allPlans as $plan): 
                                                    if ($plan !== $user->getPlan()):
                                                ?>
                                                    <form method="POST" class="block">
                                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                                        <input type="hidden" name="action" value="update_plan">
                                                        <input type="hidden" name="plan" value="<?php echo $plan; ?>">
                                                        <button type="submit" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center"
                                                                onclick="return confirm('Change your plan to <?php echo ucfirst($plan); ?>?')">
                                                            <i class="fas fa-arrow-right mr-2"></i>
                                                            Switch to <?php echo ucfirst($plan); ?>
                                                        </button>
                                                    </form>
                                                <?php 
                                                    endif;
                                                endforeach; 
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <p class="mt-1 text-xs text-gray-500">
                                <?php if ($subscriptionInfo['subscription_status']): ?>
                                    <i class="fas fa-circle text-green-400 mr-1"></i>Active subscription
                                    <?php if ($subscriptionInfo['cancel_at_period_end']): ?>
                                        <span class="text-yellow-600">(Cancels <?php echo date('M j, Y', strtotime($subscriptionInfo['current_period_end'])); ?>)</span>
                                    <?php elseif ($subscriptionInfo['current_period_end']): ?>
                                        <span class="text-gray-500">(Renews <?php echo date('M j, Y', strtotime($subscriptionInfo['current_period_end'])); ?>)</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <?php 
                                    $sourceLimit = $user->getSourceLimit();
                                    echo $sourceLimit === PHP_INT_MAX ? 'Unlimited sources' : "$sourceLimit source" . ($sourceLimit !== 1 ? 's' : '') . ' allowed';
                                    ?>
                                    <?php if ($user->isAdmin()): ?>
                                        <span class="text-primary ml-2">(Admin: Can change plan freely)</span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Timezone</label>
                            <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($user->getTimezone()); ?></p>
                            <a href="/dashboard/schedule.php" class="mt-1 text-xs text-primary hover:text-primary">Change timezone</a>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Send Time</label>
                            <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($user->getSendTime()); ?></p>
                            <a href="/dashboard/schedule.php" class="mt-1 text-xs text-primary hover:text-primary">Change send time</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Subscription Management -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Subscription Management</h3>
                    
                    <?php if ($subscriptionInfo['subscription_status'] === 'active'): ?>
                        <!-- Active Subscription -->
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                            <div class="flex items-center">
                                <i class="fas fa-check-circle text-green-500 mr-3"></i>
                                <div>
                                    <h4 class="text-lg font-medium text-green-900">Active <?php echo ucfirst($subscriptionInfo['plan']); ?> Subscription</h4>
                                    <p class="text-green-700">
                                        <?php if ($subscriptionInfo['cancel_at_period_end']): ?>
                                            Your subscription will end on <?php echo date('F j, Y', strtotime($subscriptionInfo['current_period_end'])); ?>
                                        <?php else: ?>
                                            Next billing date: <?php echo date('F j, Y', strtotime($subscriptionInfo['current_period_end'])); ?>
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex flex-col sm:flex-row gap-4">
                            <?php if (!$subscriptionInfo['cancel_at_period_end']): ?>
                                <button onclick="cancelSubscription()" 
                                        class="btn-pill inline-flex items-center px-4 py-2 border border-red-300 text-sm font-medium text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                    <i class="fas fa-times mr-2"></i>
                                    Cancel Subscription
                                </button>
                            <?php endif; ?>
                            
                            <?php if ($subscriptionInfo['stripe_customer_id']): ?>
                                <button onclick="manageBilling()" 
                                        class="btn-pill inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus-ring-primary">
                                    <i class="fas fa-credit-card mr-2"></i>
                                    Manage Billing
                                </button>
                            <?php endif; ?>
                        </div>
                        
                    <?php else: ?>
                        <!-- No Active Subscription -->
                        <div class="text-center py-8">
                            <i class="fas fa-crown text-4xl text-gray-300 mb-4"></i>
                            <h4 class="text-lg font-medium text-gray-900 mb-2">Upgrade Your Plan</h4>
                            <p class="text-gray-600 mb-6">Get access to more features with a premium subscription</p>
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                                <!-- Starter Plan -->
                                <div class="border border-gray-200 rounded-lg p-4">
                                    <h5 class="font-medium text-gray-900 mb-2">Starter</h5>
                                    <p class="text-2xl font-bold text-gray-900 mb-2">$5<span class="text-sm font-normal">/month</span></p>
                                    <ul class="text-sm text-gray-600 space-y-1 mb-4">
                                        <li><i class="fas fa-check text-green-500 mr-2"></i>Up to 5 sources</li>
                                        <li><i class="fas fa-check text-green-500 mr-2"></i>Basic scheduling</li>
                                        <li><i class="fas fa-check text-green-500 mr-2"></i>Daily delivery</li>
                                    </ul>
                                    <button onclick="subscribeToPlan('starter')" 
                                            class="btn-pill w-full bg-primary hover-bg-primary-dark text-white font-medium py-2 px-4">
                                        Choose Starter
                                    </button>
                                </div>

                                <!-- Pro Plan -->
                                <div class="border-2 border-primary rounded-lg p-4 relative">
                                    <div class="absolute -top-3 left-1/2 transform -translate-x-1/2">
                                        <span class="bg-primary text-white px-3 py-1 text-sm font-medium rounded-full">Popular</span>
                                    </div>
                                    <h5 class="font-medium text-gray-900 mb-2">Pro</h5>
                                    <p class="text-2xl font-bold text-gray-900 mb-2">$15<span class="text-sm font-normal">/month</span></p>
                                    <ul class="text-sm text-gray-600 space-y-1 mb-4">
                                        <li><i class="fas fa-check text-green-500 mr-2"></i>Up to 15 sources</li>
                                        <li><i class="fas fa-check text-green-500 mr-2"></i>Advanced scheduling</li>
                                        <li><i class="fas fa-check text-green-500 mr-2"></i>Custom layouts</li>
                                        <li><i class="fas fa-check text-green-500 mr-2"></i>Priority support</li>
                                    </ul>
                                    <button onclick="subscribeToPlan('pro')" 
                                            class="btn-pill w-full bg-primary hover-bg-primary-dark text-white font-medium py-2 px-4">
                                        Choose Pro
                                    </button>
                                </div>

                                <!-- Unlimited Plan -->
                                <div class="border border-gray-200 rounded-lg p-4">
                                    <h5 class="font-medium text-gray-900 mb-2">Unlimited</h5>
                                    <p class="text-2xl font-bold text-gray-900 mb-2">$19<span class="text-sm font-normal">/month</span></p>
                                    <ul class="text-sm text-gray-600 space-y-1 mb-4">
                                        <li><i class="fas fa-check text-green-500 mr-2"></i>Unlimited sources</li>
                                        <li><i class="fas fa-check text-green-500 mr-2"></i>All features</li>
                                        <li><i class="fas fa-check text-green-500 mr-2"></i>Priority support</li>
                                        <li><i class="fas fa-check text-green-500 mr-2"></i>Team collaboration</li>
                                    </ul>
                                    <button onclick="subscribeToPlan('unlimited')" 
                                            class="btn-pill w-full bg-primary hover-bg-primary-dark text-white font-medium py-2 px-4">
                                        Choose Unlimited
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($payments)): ?>
            <!-- Billing History -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Recent Payments</h3>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Plan</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($payments as $payment): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo date('M j, Y', strtotime($payment['created_at'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        $<?php echo number_format($payment['amount'] / 100, 2); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 capitalize">
                                        <?php echo $payment['plan'] ?? 'N/A'; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if ($payment['status'] === 'succeeded'): ?>
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                                Paid
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                                <?php echo ucfirst($payment['status']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

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

            <?php if ($user->isAdmin()): ?>
            <!-- Admin Plan Management -->
            <div class="bg-white shadow rounded-lg border-l-4 border-primary">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-primary-darker mb-4">
                        <i class="fas fa-crown mr-2"></i>
                        Admin Plan Management
                    </h3>
                    
                    <div class="bg-primary-lightest border border-primary-light rounded-lg p-4 mb-4">
                        <div class="flex items-center">
                            <i class="fas fa-info-circle text-primary mr-3"></i>
                            <div>
                                <h4 class="text-sm font-medium text-primary-darker">Admin Privileges</h4>
                                <p class="text-primary-dark text-sm">As an admin, you can change your plan freely without payment processing. This is for testing and administration purposes.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <?php 
                        $planDetails = [
                            'free' => ['name' => 'Free', 'sources' => '1 source', 'price' => '$0/month', 'color' => 'gray'],
                            'starter' => ['name' => 'Starter', 'sources' => '5 sources', 'price' => '$5/month', 'color' => 'teal'],
                            'pro' => ['name' => 'Pro', 'sources' => '15 sources', 'price' => '$15/month', 'color' => 'red'],
                            'unlimited' => ['name' => 'Unlimited', 'sources' => 'Unlimited sources', 'price' => '$19/month', 'color' => 'purple']
                        ];
                        
                        foreach ($planDetails as $planKey => $details):
                            $isCurrent = $user->getPlan() === $planKey;
                        ?>
                        <div class="border rounded-lg p-4 <?php echo $isCurrent ? 'border-' . $details['color'] . '-500 bg-' . $details['color'] . '-50' : 'border-gray-200'; ?>">
                            <div class="text-center">
                                <h4 class="font-medium text-gray-900 mb-1">
                                    <?php echo $details['name']; ?>
                                    <?php if ($isCurrent): ?>
                                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-<?php echo $details['color']; ?>-100 text-<?php echo $details['color']; ?>-800">
                                            Current
                                        </span>
                                    <?php endif; ?>
                                </h4>
                                <p class="text-sm text-gray-600 mb-2"><?php echo $details['sources']; ?></p>
                                <p class="text-lg font-bold text-gray-900 mb-3"><?php echo $details['price']; ?></p>
                                
                                <?php if (!$isCurrent): ?>
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                        <input type="hidden" name="action" value="update_plan">
                                        <input type="hidden" name="plan" value="<?php echo $planKey; ?>">
                                        <button type="submit" 
                                                class="btn-pill w-full bg-<?php echo $details['color']; ?>-600 hover:bg-<?php echo $details['color']; ?>-700 text-white font-medium py-2 px-4 text-sm"
                                                onclick="return confirm('Switch to <?php echo $details['name']; ?> plan?')">
                                            Switch to <?php echo $details['name']; ?>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <button disabled class="btn-pill w-full bg-gray-300 text-gray-500 font-medium py-2 px-4 text-sm cursor-not-allowed">
                                        Current Plan
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="mt-4 text-center">
                        <a href="/dashboard/users.php" class="inline-flex items-center text-sm text-primary hover:text-primary">
                            <i class="fas fa-users mr-2"></i>
                            Manage All Users
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>

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
                            <button class="btn-pill inline-flex items-center px-3 py-2 border border-yellow-300 shadow-sm text-sm leading-4 font-medium text-yellow-700 bg-yellow-50 hover:bg-yellow-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500">
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
                            <button class="btn-pill inline-flex items-center px-3 py-2 border border-red-300 shadow-sm text-sm leading-4 font-medium text-red-700 bg-red-50 hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
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

    <script>
        async function subscribeToPlan(plan) {
            try {
                // Show loading state
                const button = event.target;
                const originalText = button.textContent;
                button.textContent = 'Loading...';
                button.disabled = true;

                // Create checkout session
                const response = await fetch('/api/fixed-checkout.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ plan: plan })
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.error || 'Failed to create checkout session');
                }

                // Redirect to Stripe Checkout
                window.location.href = data.checkout_url;

            } catch (error) {
                // Restore button state
                const button = event.target;
                button.textContent = button.textContent.replace('Loading...', 'Choose ' + plan.charAt(0).toUpperCase() + plan.slice(1));
                button.disabled = false;
                
                console.error('Error creating checkout session:', error);
                alert('Error: ' + error.message);
            }
        }

        async function cancelSubscription() {
            if (!confirm('Are you sure you want to cancel your subscription? You will continue to have access until the end of your current billing period.')) {
                return;
            }

            try {
                const response = await fetch('/api/cancel-subscription.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    }
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.error || 'Failed to cancel subscription');
                }

                location.reload();

            } catch (error) {
                console.error('Error cancelling subscription:', error);
                alert('Error: ' + error.message);
            }
        }

        async function manageBilling() {
            try {
                const response = await fetch('/api/billing-portal.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    }
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.error || 'Failed to access billing portal');
                }

                window.location.href = data.url;

            } catch (error) {
                console.error('Error accessing billing portal:', error);
                alert('Error: ' + error.message);
            }
        }

        function togglePlanDropdown() {
            const dropdown = document.getElementById('plan-dropdown');
            dropdown.classList.toggle('hidden');
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('plan-dropdown');
            const button = event.target.closest('[onclick="togglePlanDropdown()"]');
            
            if (!button && !dropdown?.contains(event.target)) {
                dropdown?.classList.add('hidden');
            }
        });
    </script>

    <script src="/assets/js/main.js"></script>
    <script src="/assets/js/dashboard.js"></script>
</body>
</html>