<?php
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/SubscriptionManager.php';
require_once __DIR__ . '/../config/stripe.php';

$auth = Auth::getInstance();
$auth->requireAuth();

$user = $auth->getCurrentUser();
$subscriptionManager = new SubscriptionManager();

// Get user's subscription information
$planInfo = $subscriptionManager->getUserPlanInfo($user->getId());
$subscriptions = $subscriptionManager->getUserSubscriptions($user->getId());
$payments = $subscriptionManager->getUserPayments($user->getId(), 10);

$currentPage = 'billing';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Billing - MorningNewsletter</title>
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
            <h1 class="text-3xl font-bold text-gray-900 dashboard-title">Billing & Subscription</h1>
            <p class="mt-2 text-gray-600">Manage your subscription, billing, and payment history</p>
        </div>

        <!-- Current Plan Section -->
        <div class="bg-white shadow rounded-lg mb-8">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Current Plan</h3>
                
                <div class="bg-gradient-to-r from-primary-lightest to-purple-50 rounded-lg p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="flex items-center">
                                <i class="fas fa-crown text-yellow-500 text-2xl mr-3"></i>
                                <div>
                                    <h4 class="text-xl font-bold text-gray-900 capitalize">
                                        <?php echo htmlspecialchars($planInfo['plan']); ?> Plan
                                    </h4>
                                    <?php if ($planInfo['subscription_status']): ?>
                                        <p class="text-sm text-gray-600">
                                            Status: 
                                            <span class="pill-badge inline-flex items-center 
                                                <?php 
                                                switch($planInfo['subscription_status']) {
                                                    case 'active':
                                                        echo 'pill-badge-success';
                                                        break;
                                                    case 'trialing':
                                                        echo 'pill-badge-info';
                                                        break;
                                                    case 'past_due':
                                                        echo 'pill-badge-warning';
                                                        break;
                                                    case 'canceled':
                                                        echo 'pill-badge-danger';
                                                        break;
                                                    default:
                                                        echo 'pill-badge-gray';
                                                }
                                                ?>">
                                                <?php echo ucfirst($planInfo['subscription_status']); ?>
                                                <?php if ($planInfo['subscription_status'] === 'trialing'): ?>
                                                    <i class="fas fa-gift ml-1"></i>
                                                <?php endif; ?>
                                            </span>
                                        </p>
                                        <?php if ($planInfo['current_period_end']): ?>
                                            <p class="text-sm text-gray-600 mt-1">
                                                <?php echo $planInfo['subscription_status'] === 'trialing' ? 'Trial ends' : 'Next billing'; ?>: 
                                                <?php echo date('F j, Y', strtotime($planInfo['current_period_end'])); ?>
                                            </p>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex flex-col space-y-2">
                            <?php if ($planInfo['plan'] === 'free'): ?>
                                <a href="/#pricing" class="pill-primary inline-flex items-center">
                                    <i class="fas fa-arrow-up mr-2"></i>
                                    Upgrade Plan
                                </a>
                            <?php else: ?>
                                <?php if ($planInfo['stripe_customer_id']): ?>
                                    <button onclick="manageBilling()" class="pill-secondary inline-flex items-center">
                                        <i class="fas fa-cog mr-2"></i>
                                        Manage Billing
                                    </button>
                                <?php endif; ?>
                                
                                <?php if (!$planInfo['cancel_at_period_end'] && $planInfo['subscription_status'] === 'active'): ?>
                                    <button onclick="cancelSubscription()" class="pill-danger inline-flex items-center">
                                        <i class="fas fa-times mr-2"></i>
                                        Cancel Subscription
                                    </button>
                                <?php elseif ($planInfo['cancel_at_period_end']): ?>
                                    <div class="text-sm text-red-600">
                                        <i class="fas fa-info-circle mr-1"></i>
                                        Subscription will cancel on <?php echo date('F j, Y', strtotime($planInfo['current_period_end'])); ?>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Plan Comparison -->
        <?php if ($planInfo['plan'] === 'free'): ?>
        <div class="bg-white shadow rounded-lg mb-8">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Available Upgrades</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <?php 
                    $plans = [
                        'starter' => ['name' => 'Starter', 'price' => 5, 'sources' => 5],
                        'pro' => ['name' => 'Pro', 'price' => 15, 'sources' => 15, 'popular' => true],
                        'unlimited' => ['name' => 'Unlimited', 'price' => 19, 'sources' => 'Unlimited']
                    ];
                    
                    foreach ($plans as $planKey => $plan): ?>
                        <div class="border rounded-lg p-4 <?php echo isset($plan['popular']) ? 'border-primary bg-primary-lightest' : 'border-gray-200'; ?>">
                            <?php if (isset($plan['popular'])): ?>
                                <div class="text-center mb-2">
                                    <span class="pill-badge pill-badge-info font-semibold">
                                        Most Popular
                                    </span>
                                </div>
                            <?php endif; ?>
                            
                            <div class="text-center">
                                <h4 class="text-lg font-semibold text-gray-900"><?php echo $plan['name']; ?></h4>
                                <div class="mt-2">
                                    <span class="text-3xl font-bold text-gray-900">$<?php echo $plan['price']; ?></span>
                                    <span class="text-gray-500">/month</span>
                                </div>
                                <div class="mt-2">
                                    <span class="pill-badge pill-badge-success inline-flex items-center">
                                        <i class="fas fa-gift mr-1"></i>
                                        7-day free trial
                                    </span>
                                </div>
                                
                                <ul class="mt-4 space-y-2 text-sm text-gray-600">
                                    <li><i class="fas fa-check text-green-500 mr-2"></i><?php echo $plan['sources']; ?> sources</li>
                                    <li><i class="fas fa-check text-green-500 mr-2"></i>Daily newsletter</li>
                                    <li><i class="fas fa-check text-green-500 mr-2"></i>Custom scheduling</li>
                                    <?php if ($planKey !== 'starter'): ?>
                                        <li><i class="fas fa-check text-green-500 mr-2"></i>Priority support</li>
                                    <?php endif; ?>
                                    <?php if ($planKey === 'unlimited'): ?>
                                        <li><i class="fas fa-check text-green-500 mr-2"></i>Team features</li>
                                    <?php endif; ?>
                                </ul>
                                
                                <button onclick="subscribeToPlan('<?php echo $planKey; ?>')" 
                                        class="pill-primary mt-4 w-full inline-flex justify-center items-center">
                                    Start Free Trial
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Payment History -->
        <div class="bg-white shadow rounded-lg mb-8">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Payment History</h3>
                
                <?php if (empty($payments)): ?>
                    <div class="text-center py-8">
                        <i class="fas fa-receipt text-gray-300 text-4xl mb-4"></i>
                        <p class="text-gray-500">No payment history available</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                        <table class="min-w-full divide-y divide-gray-300">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
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
                                        <?php echo htmlspecialchars($payment['description'] ?: ($payment['plan'] ? ucfirst($payment['plan']) . ' Plan' : 'Payment')); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        $<?php echo number_format($payment['amount'] / 100, 2); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                            <?php echo $payment['status'] === 'succeeded' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                            <?php echo ucfirst($payment['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Subscription Details -->
        <?php if (!empty($subscriptions)): ?>
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Subscription History</h3>
                
                <div class="space-y-4">
                    <?php foreach ($subscriptions as $subscription): ?>
                    <div class="border border-gray-200 rounded-lg p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-sm font-medium text-gray-900 capitalize">
                                    <?php echo htmlspecialchars($subscription['plan']); ?> Plan
                                </h4>
                                <p class="text-sm text-gray-500">
                                    Created: <?php echo date('M j, Y', strtotime($subscription['created_at'])); ?>
                                </p>
                                <?php if ($subscription['current_period_start'] && $subscription['current_period_end']): ?>
                                <p class="text-sm text-gray-500">
                                    Period: <?php echo date('M j, Y', strtotime($subscription['current_period_start'])); ?> - 
                                    <?php echo date('M j, Y', strtotime($subscription['current_period_end'])); ?>
                                </p>
                                <?php endif; ?>
                            </div>
                            <span class="pill-badge inline-flex items-center 
                                <?php 
                                switch($subscription['status']) {
                                    case 'active':
                                        echo 'pill-badge-success';
                                        break;
                                    case 'trialing':
                                        echo 'pill-badge-info';
                                        break;
                                    case 'canceled':
                                        echo 'pill-badge-danger';
                                        break;
                                    default:
                                        echo 'pill-badge-gray';
                                }
                                ?>">
                                <?php echo ucfirst($subscription['status']); ?>
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script>
        async function subscribeToPlan(plan) {
            try {
                const button = event.target;
                const originalText = button.textContent;
                button.textContent = 'Loading...';
                button.disabled = true;

                const response = await fetch('/api/checkout', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ plan: plan })
                });

                if (!response.ok) {
                    const errorText = await response.text();
                    throw new Error(`HTTP ${response.status}: ${errorText}`);
                }

                const data = await response.json();

                if (!data.checkout_url) {
                    throw new Error('No checkout URL received');
                }

                window.location.href = data.checkout_url;

            } catch (error) {
                if (typeof event !== 'undefined' && event.target) {
                    const button = event.target;
                    button.textContent = originalText;
                    button.disabled = false;
                }
                
                console.error('Error creating checkout session:', error);
                alert('Error: ' + error.message);
            }
        }

        async function manageBilling() {
            try {
                const response = await fetch('/api/billing-portal', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    }
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.message || data.error || `HTTP ${response.status}`);
                }

                if (data.portal_url) {
                    window.location.href = data.portal_url;
                } else {
                    throw new Error('No portal URL received');
                }

            } catch (error) {
                console.error('Error opening billing portal:', error);
                alert('Error opening billing portal: ' + error.message);
            }
        }

        async function cancelSubscription() {
            if (!confirm('Are you sure you want to cancel your subscription? You will continue to have access until the end of your current billing period.')) {
                return;
            }

            try {
                const response = await fetch('/api/cancel-subscription', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    }
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }

                const data = await response.json();

                if (data.success) {
                    alert('Your subscription has been cancelled. You will continue to have access until the end of your current billing period.');
                    location.reload();
                } else {
                    throw new Error(data.error || 'Unknown error');
                }

            } catch (error) {
                console.error('Error cancelling subscription:', error);
                alert('Error cancelling subscription. Please try again.');
            }
        }
    </script>

    <script src="/assets/js/main.js"></script>
    <script src="/assets/js/dashboard.js"></script>
    <script src="/assets/js/payments.js"></script>

</body>
</html>