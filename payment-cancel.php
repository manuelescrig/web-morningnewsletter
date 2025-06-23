<?php
require_once __DIR__ . '/core/Auth.php';

$auth = Auth::getInstance();
$isLoggedIn = $auth->isLoggedIn();
$user = $isLoggedIn ? $auth->getCurrentUser() : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Cancelled - MorningNewsletter</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        html {
            scroll-behavior: smooth;
        }
        .gradient-text {
            background: linear-gradient(135deg, #2563eb 0%, #9333ea 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
    </style>
</head>
<body class="bg-white">
    <?php include __DIR__ . '/includes/navigation.php'; ?>

    <!-- Hero Section -->
    <div class="bg-gradient-to-r from-gray-50 to-blue-50 py-20 pt-32">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="text-center">
                <div class="mb-8">
                    <i class="fas fa-times-circle text-6xl text-gray-400"></i>
                </div>
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold text-gray-900 mb-6">
                    Payment <span class="gradient-text">Cancelled</span>
                </h1>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto mb-8">
                    No worries! Your payment was cancelled and you haven't been charged anything. 
                    You can try again whenever you're ready.
                </p>
                
                <div class="bg-white/60 backdrop-blur-sm rounded-2xl p-6 max-w-md mx-auto border border-gray-200">
                    <p class="text-gray-700 text-lg">
                        <i class="fas fa-info-circle mr-2 text-blue-500"></i>
                        No charges were made to your account
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Options Section -->
    <div class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">What would you like to do?</h2>
                <p class="text-xl text-gray-600">Choose your next step</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Try Again -->
                <div class="bg-gray-50 rounded-2xl p-8 text-center hover:shadow-lg transition-shadow">
                    <div class="w-16 h-16 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-redo text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Try Again</h3>
                    <p class="text-gray-600 mb-6">
                        Ready to subscribe? Go back to our pricing plans and choose the one that fits your needs.
                    </p>
                    <a href="/#pricing" 
                       class="inline-flex items-center px-6 py-3 text-white bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg hover:from-blue-700 hover:to-purple-700 transition-all duration-300 font-medium">
                        <i class="fas fa-arrow-left mr-2"></i>
                        View Pricing
                    </a>
                </div>
                
                <!-- Learn More -->
                <div class="bg-gray-50 rounded-2xl p-8 text-center hover:shadow-lg transition-shadow">
                    <div class="w-16 h-16 bg-purple-100 text-purple-600 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-question-circle text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Learn More</h3>
                    <p class="text-gray-600 mb-6">
                        Want to know more about our features before subscribing? Check out what makes us different.
                    </p>
                    <a href="/#features" 
                       class="inline-flex items-center px-6 py-3 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors duration-300 font-medium">
                        <i class="fas fa-info-circle mr-2"></i>
                        See Features
                    </a>
                </div>
                
                <!-- Contact Support -->
                <div class="bg-gray-50 rounded-2xl p-8 text-center hover:shadow-lg transition-shadow">
                    <div class="w-16 h-16 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-headset text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Need Help?</h3>
                    <p class="text-gray-600 mb-6">
                        Having trouble with the payment process? Our support team is here to help you get started.
                    </p>
                    <a href="/support/" 
                       class="inline-flex items-center px-6 py-3 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors duration-300 font-medium">
                        <i class="fas fa-envelope mr-2"></i>
                        Contact Support
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- FAQ Section -->
    <div class="py-20 bg-gray-50">
        <div class="max-w-4xl mx-auto px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">Common Questions</h2>
                <p class="text-xl text-gray-600">Quick answers about our subscription process</p>
            </div>
            
            <div class="space-y-8">
                <div class="bg-white rounded-xl p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Why was my payment cancelled?</h3>
                    <p class="text-gray-600">
                        Payments can be cancelled for various reasons, such as clicking the back button, closing the browser, 
                        or choosing to return to our site without completing the payment.
                    </p>
                </div>
                
                <div class="bg-white rounded-xl p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Will I be charged anything?</h3>
                    <p class="text-gray-600">
                        No, you haven't been charged anything. Payments are only processed when you successfully 
                        complete the entire checkout process.
                    </p>
                </div>
                
                <div class="bg-white rounded-xl p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Can I try subscribing again?</h3>
                    <p class="text-gray-600">
                        Absolutely! You can return to our pricing page and try the subscription process again at any time. 
                        Your account is safe and ready for when you decide to upgrade.
                    </p>
                </div>
                
                <div class="bg-white rounded-xl p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Is my account still active?</h3>
                    <p class="text-gray-600">
                        <?php if ($isLoggedIn): ?>
                            Yes, your account is still active and you can continue using the free features. 
                            You can upgrade to a premium plan whenever you're ready.
                        <?php else: ?>
                            If you have an account, it remains active regardless of payment status. 
                            You can log in and continue using the free features.
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- CTA Section -->
    <div class="py-20 bg-gradient-to-r from-blue-600 to-purple-600">
        <div class="max-w-4xl mx-auto px-6 lg:px-8 text-center">
            <h2 class="text-3xl sm:text-4xl font-bold text-white mb-6">
                Ready to Try Again?
            </h2>
            <p class="text-xl text-white/90 mb-8 max-w-2xl mx-auto">
                When you're ready to upgrade, we'll be here. Start transforming your morning routine today.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="/#pricing" 
                   class="inline-flex items-center justify-center px-8 py-4 text-lg font-semibold text-blue-600 bg-white rounded-xl hover:bg-gray-50 transition-all duration-200 hover:scale-105 shadow-lg">
                    <i class="fas fa-arrow-right mr-3"></i>
                    Choose a Plan
                </a>
                <a href="/" 
                   class="inline-flex items-center justify-center px-8 py-4 text-lg font-semibold text-white border-2 border-white rounded-xl hover:bg-white hover:text-blue-600 transition-all duration-200">
                    <i class="fas fa-home mr-3"></i>
                    Back to Home
                </a>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>