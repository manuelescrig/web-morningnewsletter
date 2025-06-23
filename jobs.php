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
    <title>Careers - MorningNewsletter</title>
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
                    Join Our <span class="gradient-text">Team</span>
                </h1>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                    Help us build the future of personalized morning briefings. We're passionate about creating tools that help professionals start their day with clarity and purpose.
                </p>
            </div>
        </div>
    </div>

    <!-- Company Values Section -->
    <div class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">Why Work With Us?</h2>
                <p class="text-xl text-gray-600">We're building something meaningful together</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="text-center">
                    <div class="w-16 h-16 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-rocket text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Innovation First</h3>
                    <p class="text-gray-600">We're constantly pushing boundaries and exploring new ways to deliver value to our users.</p>
                </div>
                
                <div class="text-center">
                    <div class="w-16 h-16 bg-purple-100 text-purple-600 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-users text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Remote-First</h3>
                    <p class="text-gray-600">Work from anywhere with a distributed team that values flexibility and work-life balance.</p>
                </div>
                
                <div class="text-center">
                    <div class="w-16 h-16 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-chart-line text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Growth Focused</h3>
                    <p class="text-gray-600">Join us in our journey to scale and make a meaningful impact on how people consume information.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Current Openings Section -->
    <div class="py-20 bg-gray-50">
        <div class="max-w-4xl mx-auto px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">Current Openings</h2>
                <p class="text-xl text-gray-600">Explore opportunities to join our growing team</p>
            </div>
            
            <!-- No Openings State -->
            <div class="bg-white rounded-2xl p-12 text-center shadow-lg">
                <div class="w-20 h-20 bg-gray-100 text-gray-400 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-briefcase text-3xl"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-4">No Current Openings</h3>
                <p class="text-lg text-gray-600 mb-8 max-w-2xl mx-auto">
                    We don't have any open positions at the moment, but we're always looking for exceptional talent. 
                    If you're passionate about our mission, we'd love to hear from you.
                </p>
                
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="mailto:careers@morningnewsletter.com" 
                       class="inline-flex items-center px-6 py-3 text-white bg-gradient-to-r from-purple-600 to-blue-600 rounded-lg hover:from-purple-700 hover:to-blue-700 transition-all duration-300 font-medium">
                        <i class="fas fa-envelope mr-2"></i>
                        Send Us Your Resume
                    </a>
                    <a href="https://linkedin.com/company/morningnewsletter" 
                       class="inline-flex items-center px-6 py-3 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors duration-300 font-medium">
                        <i class="fab fa-linkedin mr-2"></i>
                        Follow Us on LinkedIn
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Future Roles Section -->
    <div class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">Roles We're Looking For</h2>
                <p class="text-xl text-gray-600">Future opportunities as we grow</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <div class="bg-gray-50 rounded-xl p-6">
                    <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-lg flex items-center justify-center mb-4">
                        <i class="fas fa-code text-xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Full-Stack Engineers</h3>
                    <p class="text-gray-600">Help us build and scale our platform with modern web technologies.</p>
                </div>
                
                <div class="bg-gray-50 rounded-xl p-6">
                    <div class="w-12 h-12 bg-purple-100 text-purple-600 rounded-lg flex items-center justify-center mb-4">
                        <i class="fas fa-paint-brush text-xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Product Designers</h3>
                    <p class="text-gray-600">Create beautiful, intuitive experiences for our users across all touchpoints.</p>
                </div>
                
                <div class="bg-gray-50 rounded-xl p-6">
                    <div class="w-12 h-12 bg-green-100 text-green-600 rounded-lg flex items-center justify-center mb-4">
                        <i class="fas fa-bullhorn text-xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Marketing Specialists</h3>
                    <p class="text-gray-600">Drive growth and help us reach professionals who need our solution.</p>
                </div>
                
                <div class="bg-gray-50 rounded-xl p-6">
                    <div class="w-12 h-12 bg-yellow-100 text-yellow-600 rounded-lg flex items-center justify-center mb-4">
                        <i class="fas fa-headset text-xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Customer Success</h3>
                    <p class="text-gray-600">Ensure our users get maximum value from their morning briefings.</p>
                </div>
                
                <div class="bg-gray-50 rounded-xl p-6">
                    <div class="w-12 h-12 bg-red-100 text-red-600 rounded-lg flex items-center justify-center mb-4">
                        <i class="fas fa-chart-bar text-xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Data Scientists</h3>
                    <p class="text-gray-600">Help us personalize and optimize content delivery using data insights.</p>
                </div>
                
                <div class="bg-gray-50 rounded-xl p-6">
                    <div class="w-12 h-12 bg-indigo-100 text-indigo-600 rounded-lg flex items-center justify-center mb-4">
                        <i class="fas fa-cogs text-xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">DevOps Engineers</h3>
                    <p class="text-gray-600">Build and maintain the infrastructure that powers our daily newsletters.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- CTA Section -->
    <div class="py-20 bg-gradient-to-r from-purple-600 to-blue-600">
        <div class="max-w-4xl mx-auto px-6 lg:px-8 text-center">
            <h2 class="text-3xl sm:text-4xl font-bold text-white mb-6">
                Ready to Make an Impact?
            </h2>
            <p class="text-xl text-white/90 mb-8 max-w-2xl mx-auto">
                Even if we don't have current openings, we're always interested in connecting with talented individuals who share our vision.
            </p>
            <a href="mailto:careers@morningnewsletter.com" 
               class="inline-flex items-center px-8 py-4 text-lg font-semibold text-purple-600 bg-white rounded-xl hover:bg-gray-50 transition-all duration-200 hover:scale-105 shadow-lg">
                <i class="fas fa-paper-plane mr-3"></i>
                Get In Touch
            </a>
        </div>
    </div>

    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>