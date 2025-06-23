<?php
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/SubscriptionManager.php';

$auth = Auth::getInstance();
$auth->requireAuth();

$user = $auth->getCurrentUser();
$subscriptionManager = new SubscriptionManager();
$planInfo = $subscriptionManager->getUserPlanInfo($user->getId());

$currentPage = 'upgrade';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pricing - MorningNewsletter</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <?php include __DIR__ . '/includes/navigation.php'; ?>

    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="px-4 py-6 sm:px-0">
            <h1 class="text-3xl font-bold text-gray-900">Choose Your Plan</h1>
            <p class="mt-2 text-gray-600">Upgrade your account to unlock premium features and get the most out of your morning routine</p>
        </div>

        <!-- Current Plan Notice -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-8">
            <div class="flex items-center">
                <i class="fas fa-info-circle text-blue-500 text-xl mr-3"></i>
                <div>
                    <h3 class="text-lg font-medium text-blue-900">You're currently on the Free plan</h3>
                    <p class="text-blue-700 mt-1">Upgrade to unlock more sources, advanced features, and priority support. All plans include a 7-day free trial!</p>
                </div>
            </div>
        </div>

        <!-- Pricing Cards -->
        <div class="grid grid-cols-1 gap-8 md:grid-cols-3">
            <!-- Starter Plan -->
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden hover:shadow-2xl hover:scale-105 transition-all duration-300 h-full flex flex-col">
                <div class="px-6 py-8 flex-1 flex flex-col">
                    <h3 class="text-2xl font-bold text-gray-900 mb-4">Starter</h3>
                    <p class="mt-4 text-gray-500">Perfect for individuals getting started with morning briefings</p>
                    <p class="mt-8">
                        <span class="text-4xl font-extrabold text-gray-900">$5</span>
                        <span class="text-base font-medium text-gray-500">/month</span>
                    </p>
                    <div class="mt-3 mb-2">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            <i class="fas fa-gift mr-1"></i>
                            7-day free trial
                        </span>
                    </div>
                    <ul class="mt-6 space-y-4 flex-1">
                        <li class="flex items-start">
                            <div class="flex-shrink-0">
                                <i class="fas fa-check text-green-500"></i>
                            </div>
                            <p class="ml-3 text-base text-gray-500">Up to 5 sources</p>
                        </li>
                        <li class="flex items-start">
                            <div class="flex-shrink-0">
                                <i class="fas fa-check text-green-500"></i>
                            </div>
                            <p class="ml-3 text-base text-gray-500">Basic scheduling & customization</p>
                        </li>
                        <li class="flex items-start">
                            <div class="flex-shrink-0">
                                <i class="fas fa-check text-green-500"></i>
                            </div>
                            <p class="ml-3 text-base text-gray-500">Daily newsletter delivery</p>
                        </li>
                        <li class="flex items-start">
                            <div class="flex-shrink-0">
                                <i class="fas fa-check text-green-500"></i>
                            </div>
                            <p class="ml-3 text-base text-gray-500">Email support</p>
                        </li>
                    </ul>
                    <div class="mt-8">
                        <button onclick="subscribeToPlan('starter')" class="block w-full bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-medium rounded-lg text-sm px-5 py-2.5 text-center transition-all duration-300">
                            Start Free Trial
                        </button>
                    </div>
                </div>
            </div>

            <!-- Pro Plan -->
            <div class="bg-white rounded-2xl shadow-lg hover:shadow-2xl hover:scale-105 transition-all duration-300 border-2 border-purple-400 relative h-full flex flex-col">
                <div class="absolute -top-3 left-1/2 transform -translate-x-1/2 z-20">
                    <span class="inline-flex rounded-full bg-gradient-to-r from-purple-600 to-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-lg">
                        Most Popular
                    </span>
                </div>
                <div class="px-6 py-8 pt-10 flex-1 flex flex-col">
                    <h3 class="text-2xl font-bold text-gray-900 mb-4">Pro</h3>
                    <p class="mt-4 text-gray-500">Great for professionals who want more control and features</p>
                    <p class="mt-8">
                        <span class="text-4xl font-extrabold text-gray-900">$15</span>
                        <span class="text-base font-medium text-gray-500">/month</span>
                    </p>
                    <div class="mt-3 mb-2">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            <i class="fas fa-gift mr-1"></i>
                            7-day free trial
                        </span>
                    </div>
                    <ul class="mt-6 space-y-4 flex-1">
                        <li class="flex items-start">
                            <div class="flex-shrink-0">
                                <i class="fas fa-check text-purple-600"></i>
                            </div>
                            <p class="ml-3 text-base text-gray-500">Up to 15 sources</p>
                        </li>
                        <li class="flex items-start">
                            <div class="flex-shrink-0">
                                <i class="fas fa-check text-purple-600"></i>
                            </div>
                            <p class="ml-3 text-base text-gray-500">Advanced scheduling</p>
                        </li>
                        <li class="flex items-start">
                            <div class="flex-shrink-0">
                                <i class="fas fa-check text-purple-600"></i>
                            </div>
                            <p class="ml-3 text-base text-gray-500">Custom layouts & themes</p>
                        </li>
                        <li class="flex items-start">
                            <div class="flex-shrink-0">
                                <i class="fas fa-check text-purple-600"></i>
                            </div>
                            <p class="ml-3 text-base text-gray-500">Priority email support</p>
                        </li>
                        <li class="flex items-start">
                            <div class="flex-shrink-0">
                                <i class="fas fa-check text-purple-600"></i>
                            </div>
                            <p class="ml-3 text-base text-gray-500">Advanced analytics</p>
                        </li>
                    </ul>
                    <div class="mt-8">
                        <button onclick="subscribeToPlan('pro')" class="block w-full bg-gradient-to-r from-purple-600 to-blue-600 hover:from-purple-700 hover:to-blue-700 text-white font-medium rounded-lg text-sm px-5 py-2.5 text-center transition-all duration-300">
                            Start Free Trial
                        </button>
                    </div>
                </div>
            </div>

            <!-- Unlimited Plan -->
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden hover:shadow-2xl hover:scale-105 transition-all duration-300 h-full flex flex-col">
                <div class="px-6 py-8 flex-1 flex flex-col">
                    <h3 class="text-2xl font-bold text-gray-900 mb-4">Unlimited</h3>
                    <p class="mt-4 text-gray-500">Perfect for power users, teams, or content curators</p>
                    <p class="mt-8">
                        <span class="text-4xl font-extrabold text-gray-900">$19</span>
                        <span class="text-base font-medium text-gray-500">/month</span>
                    </p>
                    <div class="mt-3 mb-2">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            <i class="fas fa-gift mr-1"></i>
                            7-day free trial
                        </span>
                    </div>
                    <ul class="mt-6 space-y-4 flex-1">
                        <li class="flex items-start">
                            <div class="flex-shrink-0">
                                <i class="fas fa-check text-green-500"></i>
                            </div>
                            <p class="ml-3 text-base text-gray-500">Unlimited sources</p>
                        </li>
                        <li class="flex items-start">
                            <div class="flex-shrink-0">
                                <i class="fas fa-check text-green-500"></i>
                            </div>
                            <p class="ml-3 text-base text-gray-500">All premium features</p>
                        </li>
                        <li class="flex items-start">
                            <div class="flex-shrink-0">
                                <i class="fas fa-check text-green-500"></i>
                            </div>
                            <p class="ml-3 text-base text-gray-500">Team collaboration</p>
                        </li>
                        <li class="flex items-start">
                            <div class="flex-shrink-0">
                                <i class="fas fa-check text-green-500"></i>
                            </div>
                            <p class="ml-3 text-base text-gray-500">Priority support</p>
                        </li>
                        <li class="flex items-start">
                            <div class="flex-shrink-0">
                                <i class="fas fa-check text-green-500"></i>
                            </div>
                            <p class="ml-3 text-base text-gray-500">Custom integrations</p>
                        </li>
                    </ul>
                    <div class="mt-8">
                        <button onclick="subscribeToPlan('unlimited')" class="block w-full bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-medium rounded-lg text-sm px-5 py-2.5 text-center transition-all duration-300">
                            Start Free Trial
                        </button>
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

                // Create Stripe checkout session
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

                // Redirect to Stripe Checkout
                window.location.href = data.checkout_url;

            } catch (error) {
                // Restore button state
                if (typeof event !== 'undefined' && event.target) {
                    const button = event.target;
                    button.textContent = originalText;
                    button.disabled = false;
                }
                
                console.error('Error creating checkout session:', error);
                alert('Error: ' + error.message);
            }
        }

        // Check if user came from homepage with a selected plan
        window.addEventListener('load', function() {
            const selectedPlan = sessionStorage.getItem('selectedPlan');
            if (selectedPlan) {
                sessionStorage.removeItem('selectedPlan');
                // Auto-start the subscription flow for the selected plan
                subscribeToPlan(selectedPlan);
            }
        });
    </script>
</body>
</html>