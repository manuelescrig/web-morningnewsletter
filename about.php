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
    <title>About - MorningNewsletter</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        html {
            scroll-behavior: smooth;
        }
        .gradient-bg {
            background: linear-gradient(135deg, #ffffff 0%, #f3f4f6 100%);
        }
        .nav-scrolled {
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
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
    <div class="bg-gradient-to-r from-blue-50 to-purple-50 py-20">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="text-center">
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold text-gray-900 mb-6">
                    About <span class="gradient-text">MorningNewsletter</span>
                </h1>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                    We're on a mission to help professionals start their day with clarity, focus, and all the information they need to succeed.
                </p>
            </div>
        </div>
    </div>

    <!-- Mission Section -->
    <div class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <div>
                    <h2 class="text-3xl font-bold text-gray-900 mb-6">Our Mission</h2>
                    <p class="text-lg text-gray-600 mb-6">
                        Every morning, millions of professionals wake up to information overload. Multiple apps, countless notifications, 
                        scattered updates across platforms—it's overwhelming and inefficient.
                    </p>
                    <p class="text-lg text-gray-600 mb-6">
                        MorningNewsletter changes that. We believe your morning routine should energize you, not exhaust you. 
                        That's why we've created a personalized morning brief that brings together everything you need to know in one beautiful, 
                        easy-to-digest email.
                    </p>
                    <p class="text-lg text-gray-600">
                        Our goal is simple: help you start each day informed, focused, and ready to tackle what matters most.
                    </p>
                </div>
                <div class="lg:text-center">
                    <div class="bg-gradient-to-br from-blue-100 to-purple-100 rounded-2xl p-8">
                        <div class="grid grid-cols-2 gap-6">
                            <div class="text-center">
                                <div class="text-3xl font-bold text-blue-600 mb-2">10K+</div>
                                <div class="text-sm text-gray-600">Active Users</div>
                            </div>
                            <div class="text-center">
                                <div class="text-3xl font-bold text-purple-600 mb-2">50K+</div>
                                <div class="text-sm text-gray-600">Emails Delivered</div>
                            </div>
                            <div class="text-center">
                                <div class="text-3xl font-bold text-green-600 mb-2">99.9%</div>
                                <div class="text-sm text-gray-600">Uptime</div>
                            </div>
                            <div class="text-center">
                                <div class="text-3xl font-bold text-orange-600 mb-2">4.8★</div>
                                <div class="text-sm text-gray-600">User Rating</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Story Section -->
    <div class="py-20 bg-gray-50">
        <div class="max-w-4xl mx-auto px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">Our Story</h2>
                <p class="text-xl text-gray-600">How MorningNewsletter came to life</p>
            </div>
            
            <div class="space-y-12">
                <div class="bg-white rounded-2xl p-8 shadow-lg">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-lg flex items-center justify-center">
                                <i class="fas fa-lightbulb text-xl"></i>
                            </div>
                        </div>
                        <div class="ml-6">
                            <h3 class="text-xl font-bold text-gray-900 mb-3">The Problem</h3>
                            <p class="text-gray-600">
                                As busy professionals ourselves, we were spending 30+ minutes every morning checking emails, 
                                social media, news apps, weather, market updates, and project management tools. It was chaotic, 
                                time-consuming, and often left us feeling overwhelmed before the day even began.
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-2xl p-8 shadow-lg">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-purple-100 text-purple-600 rounded-lg flex items-center justify-center">
                                <i class="fas fa-cogs text-xl"></i>
                            </div>
                        </div>
                        <div class="ml-6">
                            <h3 class="text-xl font-bold text-gray-900 mb-3">The Solution</h3>
                            <p class="text-gray-600">
                                We built MorningNewsletter to solve our own problem—and realized thousands of other professionals 
                                had the same struggle. Our platform aggregates all the information you need from multiple sources 
                                and delivers it as one beautiful, personalized email that takes just 5 minutes to read.
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-2xl p-8 shadow-lg">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-green-100 text-green-600 rounded-lg flex items-center justify-center">
                                <i class="fas fa-rocket text-xl"></i>
                            </div>
                        </div>
                        <div class="ml-6">
                            <h3 class="text-xl font-bold text-gray-900 mb-3">The Future</h3>
                            <p class="text-gray-600">
                                We're just getting started. Our vision is to become the definitive morning routine platform for 
                                professionals worldwide, using AI and machine learning to make each morning brief more personalized, 
                                relevant, and valuable than the last.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Values Section -->
    <div class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">Our Values</h2>
                <p class="text-xl text-gray-600">The principles that guide everything we do</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="text-center">
                    <div class="w-16 h-16 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-clock text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Respect Your Time</h3>
                    <p class="text-gray-600">
                        We believe your time is precious. Every feature we build is designed to save you time and 
                        make your morning routine more efficient.
                    </p>
                </div>
                
                <div class="text-center">
                    <div class="w-16 h-16 bg-purple-100 text-purple-600 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-shield-alt text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Privacy First</h3>
                    <p class="text-gray-600">
                        Your data is yours. We use industry-leading security practices and never share your 
                        personal information with third parties.
                    </p>
                </div>
                
                <div class="text-center">
                    <div class="w-16 h-16 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-heart text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Human-Centered</h3>
                    <p class="text-gray-600">
                        Technology should serve people, not the other way around. We build tools that feel natural, 
                        intuitive, and genuinely helpful.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- CTA Section -->
    <div class="py-20 bg-gradient-to-r from-purple-600 to-blue-600">
        <div class="max-w-4xl mx-auto px-6 lg:px-8 text-center">
            <h2 class="text-3xl sm:text-4xl font-bold text-white mb-6">
                Ready to Transform Your Morning?
            </h2>
            <p class="text-xl text-white/90 mb-8 max-w-2xl mx-auto">
                Join thousands of professionals who have already streamlined their morning routine with MorningNewsletter.
            </p>
            <a href="#pricing" 
               class="inline-flex items-center px-8 py-4 text-lg font-semibold text-purple-600 bg-white rounded-xl hover:bg-gray-50 transition-all duration-200 hover:scale-105 shadow-lg">
                <i class="fas fa-arrow-right mr-3"></i>
                Get Started Today
            </a>
        </div>
    </div>

    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>