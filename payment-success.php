<?php
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/config/stripe.php';
require_once __DIR__ . '/core/SubscriptionManager.php';

$auth = Auth::getInstance();
$isLoggedIn = $auth->isLoggedIn();
$user = $isLoggedIn ? $auth->getCurrentUser() : null;

// Redirect to login if not authenticated
if (!$isLoggedIn) {
    header('Location: /login.php');
    exit;
}

// Get session information
$sessionId = $_GET['session_id'] ?? null;
$subscriptionInfo = null;
$plan = null;

if ($sessionId) {
    try {
        $stripeHelper = new StripeHelper();
        $session = $stripeHelper->makeStripeRequest('GET', 'checkout/sessions/' . $sessionId);
        
        if ($session && isset($session['metadata']['plan'])) {
            $plan = $session['metadata']['plan'];
            
            // Get user's current subscription info
            $subscriptionManager = new SubscriptionManager();
            $subscriptionInfo = $subscriptionManager->getUserPlanInfo($user->getId());
        }
    } catch (Exception $e) {
        error_log('Error fetching session: ' . $e->getMessage());
    }
}

// Get plan details
$planDetails = [];
if ($plan) {
    $allPlans = StripeConfig::getAllPlans();
    $planDetails = $allPlans[$plan] ?? [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful! 🎉</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .celebration-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .gradient-text {
            background: linear-gradient(135deg, #0041EC 0%, #9333ea 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .confetti {
            position: absolute;
            width: 10px;
            height: 10px;
            background: #f39c12;
            animation: confetti-fall 3s linear infinite;
        }
        @keyframes confetti-fall {
            to { transform: translateY(100vh) rotate(360deg); }
        }
        .bounce-in {
            animation: bounceIn 1s ease-out;
        }
        @keyframes bounceIn {
            0% { transform: scale(0.3); opacity: 0; }
            50% { transform: scale(1.05); }
            70% { transform: scale(0.9); }
            100% { transform: scale(1); opacity: 1; }
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <?php include __DIR__ . '/includes/navigation.php'; ?>

    <!-- Confetti Animation -->
    <div class="fixed inset-0 pointer-events-none overflow-hidden">
        <div class="confetti" style="left: 10%; animation-delay: 0s; background: #e74c3c;"></div>
        <div class="confetti" style="left: 20%; animation-delay: 0.5s; background: #f39c12;"></div>
        <div class="confetti" style="left: 30%; animation-delay: 1s; background: #2ecc71;"></div>
        <div class="confetti" style="left: 40%; animation-delay: 1.5s; background: #3498db;"></div>
        <div class="confetti" style="left: 50%; animation-delay: 2s; background: #9b59b6;"></div>
        <div class="confetti" style="left: 60%; animation-delay: 0.3s; background: #e67e22;"></div>
        <div class="confetti" style="left: 70%; animation-delay: 0.8s; background: #1abc9c;"></div>
        <div class="confetti" style="left: 80%; animation-delay: 1.3s; background: #f1c40f;"></div>
        <div class="confetti" style="left: 90%; animation-delay: 1.8s; background: #e91e63;"></div>
    </div>

    <!-- Hero Section -->
    <div class="celebration-bg py-20">
        <div class="max-w-4xl mx-auto px-6 lg:px-8 text-center">
            <div class="bounce-in">
                <div class="mb-8">
                    <i class="fas fa-check-circle text-6xl text-white mb-4"></i>
                </div>
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold text-white mb-6">
                    Payment Successful! 🎉
                </h1>
                <p class="text-xl sm:text-2xl text-white/90 mb-8 max-w-3xl mx-auto">
                    Thank you for upgrading! You now have access to all premium features and can start transforming your morning routine.
                </p>
                <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-6 max-w-md mx-auto">
                    <p class="text-white/80 text-lg">
                        <i class="fas fa-credit-card mr-2"></i>
                        <?php if ($plan && $planDetails): ?>
                            <?php echo ucfirst($planDetails['name']); ?> subscription activated for <strong><?php echo htmlspecialchars($user->getEmail()); ?></strong>
                        <?php else: ?>
                            Premium subscription activated for <strong><?php echo htmlspecialchars($user->getEmail()); ?></strong>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- What Happens Next Section -->
    <div class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-4">
                    What happens next?
                </h2>
                <p class="text-xl text-gray-600">
                    Here's what you can expect from your MorningNewsletter experience
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Step 1 -->
                <div class="text-center">
                    <div class="w-16 h-16 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-cog text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">1. Set Up Your Preferences</h3>
                    <p class="text-gray-600">
                        Customize what information you want to receive. Add your KPIs, select news sources, and set your delivery time.
                    </p>
                </div>

                <!-- Step 2 -->
                <div class="text-center">
                    <div class="w-16 h-16 bg-purple-100 text-purple-600 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-envelope text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">2. Receive Your First Newsletter</h3>
                    <p class="text-gray-600">
                        Your personalized morning brief will be delivered to your inbox every morning at your preferred time.
                    </p>
                </div>

                <!-- Step 3 -->
                <div class="text-center">
                    <div class="w-16 h-16 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-rocket text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">3. Start Your Day Informed</h3>
                    <p class="text-gray-600">
                        Begin each morning with everything you need to know in one beautifully designed email.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Benefits Section -->
    <div class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-4">
                    You now have access to
                </h2>
                <p class="text-xl text-gray-600">
                    Everything you need to transform your morning routine
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Benefit 1 -->
                <div class="bg-white rounded-xl p-8 shadow-lg">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-blue-500 text-white rounded-lg flex items-center justify-center">
                                <i class="fas fa-chart-line text-xl"></i>
                            </div>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-xl font-bold text-gray-900 mb-2">Custom KPI Tracking</h3>
                            <p class="text-gray-600">Monitor your business metrics and get instant insights delivered to your inbox.</p>
                        </div>
                    </div>
                </div>

                <!-- Benefit 2 -->
                <div class="bg-white rounded-xl p-8 shadow-lg">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-purple-500 text-white rounded-lg flex items-center justify-center">
                                <i class="fas fa-coins text-xl"></i>
                            </div>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-xl font-bold text-gray-900 mb-2">Market Updates</h3>
                            <p class="text-gray-600">Stay informed with real-time financial markets and cryptocurrency prices.</p>
                        </div>
                    </div>
                </div>

                <!-- Benefit 3 -->
                <div class="bg-white rounded-xl p-8 shadow-lg">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-green-500 text-white rounded-lg flex items-center justify-center">
                                <i class="fas fa-cloud-sun text-xl"></i>
                            </div>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-xl font-bold text-gray-900 mb-2">Weather & News</h3>
                            <p class="text-gray-600">Get your local weather forecast and personalized news updates.</p>
                        </div>
                    </div>
                </div>

                <!-- Benefit 4 -->
                <div class="bg-white rounded-xl p-8 shadow-lg">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-indigo-500 text-white rounded-lg flex items-center justify-center">
                                <i class="fas fa-comments text-xl"></i>
                            </div>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-xl font-bold text-gray-900 mb-2">Social Media Integration</h3>
                            <p class="text-gray-600">Never miss important messages from Twitter, Slack, and Discord.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- CTA Section -->
    <div class="py-20 bg-gradient-to-r from-blue-600 to-purple-600">
        <div class="max-w-4xl mx-auto px-6 lg:px-8 text-center">
            <h2 class="text-3xl sm:text-4xl font-bold text-white mb-6">
                Ready to get started?
            </h2>
            <p class="text-xl text-white/90 mb-8 max-w-2xl mx-auto">
                Head to your dashboard to customize your preferences and start receiving your personalized morning brief.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="/dashboard/" 
                   class="inline-flex items-center justify-center px-8 py-4 text-lg font-semibold text-blue-600 bg-white rounded-xl hover:bg-gray-50 transition-all duration-200 hover:scale-105 shadow-lg">
                    <i class="fas fa-tachometer-alt mr-3"></i>
                    Go to Dashboard
                </a>
                <a href="/dashboard/sources.php" 
                   class="inline-flex items-center justify-center px-8 py-4 text-lg font-semibold text-white border-2 border-white rounded-xl hover:bg-white hover:text-blue-600 transition-all duration-200">
                    <i class="fas fa-plus-circle mr-3"></i>
                    Add Sources
                </a>
            </div>
            
            <div class="mt-12 bg-white/10 backdrop-blur-sm rounded-xl p-6 max-w-md mx-auto">
                <h3 class="text-lg font-semibold text-white mb-2">
                    <i class="fas fa-crown mr-2"></i>
                    Premium Activated
                </h3>
                <p class="text-white/80">
                    You now have full access to all premium features including advanced KPIs, crypto tracking, and priority support!
                </p>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/includes/footer.php'; ?>

    <script>
        // Add more confetti dynamically
        function createConfetti() {
            const colors = ['#e74c3c', '#f39c12', '#2ecc71', '#3498db', '#9b59b6', '#e67e22', '#1abc9c', '#f1c40f'];
            
            for (let i = 0; i < 50; i++) {
                const confetti = document.createElement('div');
                confetti.className = 'confetti';
                confetti.style.left = Math.random() * 100 + '%';
                confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
                confetti.style.animationDelay = Math.random() * 3 + 's';
                confetti.style.animationDuration = (Math.random() * 3 + 2) + 's';
                document.querySelector('.fixed.inset-0').appendChild(confetti);
                
                // Remove confetti after animation
                setTimeout(() => {
                    confetti.remove();
                }, 5000);
            }
        }
        
        // Start confetti on page load
        window.addEventListener('load', createConfetti);
    </script>
</body>
</html>