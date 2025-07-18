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
    <title>Press - MorningNewsletter</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/assets/css/main.css">
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
            background: linear-gradient(135deg, #468BE6 0%, #9333ea 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
    </style>
</head>
<body class="bg-white">
    <?php include __DIR__ . '/includes/navigation.php'; ?>

    <!-- Hero Section -->
    <div class="bg-gradient-to-r from-blue-50 to-purple-50 py-20 pt-32">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="text-center">
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold text-gray-900 mb-6">
                    Press & <span class="gradient-text">Media</span>
                </h1>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                    Hi, I'm Manuel Escrig, the solo developer behind MorningNewsletter. Here's everything you need if you're covering the project.
                </p>
            </div>
        </div>
    </div>

    <!-- Quick Facts Section -->
    <div class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">Company Overview</h2>
                <p class="text-xl text-gray-600">Key facts and figures about MorningNewsletter</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 mb-16">
                <div class="bg-primary-lightest rounded-2xl p-6 text-center">
                    <div class="text-3xl font-bold text-primary mb-2">2024</div>
                    <div class="text-gray-600">Started</div>
                </div>
                <div class="bg-purple-50 rounded-2xl p-6 text-center">
                    <div class="text-3xl font-bold text-purple-600 mb-2">Solo</div>
                    <div class="text-gray-600">Developer</div>
                </div>
                <div class="bg-green-50 rounded-2xl p-6 text-center">
                    <div class="text-3xl font-bold text-green-600 mb-2">Growing</div>
                    <div class="text-gray-600">User Base</div>
                </div>
                <div class="bg-orange-50 rounded-2xl p-6 text-center">
                    <div class="text-3xl font-bold text-orange-600 mb-2">Spain</div>
                    <div class="text-gray-600">Based</div>
                </div>
            </div>
            
            <div class="bg-gray-50 rounded-2xl p-8">
                <h3 class="text-2xl font-bold text-gray-900 mb-6">About the Project</h3>
                <p class="text-lg text-gray-600 mb-4">
                    MorningNewsletter is a personal project I built to solve my own problem: spending too much time every morning 
                    checking emails, GitHub notifications, tech news, weather, and crypto prices across different apps. 
                    It aggregates the information I care about from multiple sources into one clean email delivered every morning.
                </p>
                <p class="text-lg text-gray-600">
                    Started in 2024 as a side project, it turns out other professionals had the same problem. Now I'm helping 
                    them streamline their mornings too. It's still a solo operation, built with care and continuously improved based on user feedback.
                </p>
            </div>
        </div>
    </div>

    <!-- Press Contact Section -->
    <div class="py-20 bg-gray-50">
        <div class="max-w-4xl mx-auto px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">Press Contact</h2>
                <p class="text-xl text-gray-600">Get in touch for interviews, quotes, or additional information</p>
            </div>
            
            <div class="bg-white rounded-2xl p-8 shadow-lg">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div>
                        <h3 class="text-xl font-bold text-gray-900 mb-4">Media Inquiries</h3>
                        <div class="space-y-3">
                            <div class="flex items-center">
                                <i class="fas fa-envelope text-primary w-5 mr-3"></i>
                                <a href="mailto:hello@morningnewsletter.com" class="text-primary hover:text-primary-dark">
                                    hello@morningnewsletter.com
                                </a>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-clock text-primary w-5 mr-3"></i>
                                <span class="text-gray-600">Response within 24 hours</span>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <h3 class="text-xl font-bold text-gray-900 mb-4">Social Media</h3>
                        <div class="space-y-3">
                            <div class="flex items-center">
                                <i class="fab fa-twitter text-primary w-5 mr-3"></i>
                                <a href="#" class="text-primary hover:text-primary-dark">@MorningNewsletter</a>
                            </div>
                            <div class="flex items-center">
                                <i class="fab fa-linkedin text-primary w-5 mr-3"></i>
                                <a href="#" class="text-primary hover:text-primary-dark">LinkedIn</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Media Kit Section -->
    <div class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">Media Kit</h2>
                <p class="text-xl text-gray-600">Download our brand assets and resources</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="bg-gray-50 rounded-2xl p-6 text-center">
                    <div class="w-16 h-16 bg-primary-lightest text-primary rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-image text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Brand Assets</h3>
                    <p class="text-gray-600 mb-6">High-resolution logos, brand colors, and style guidelines</p>
                    <a href="mailto:hello@morningnewsletter.com?subject=Brand Assets Request" 
                       class="inline-flex items-center text-primary hover:text-primary-dark font-medium">
                        <i class="fas fa-download mr-2"></i>Request Assets
                    </a>
                </div>
                
                <div class="bg-gray-50 rounded-2xl p-6 text-center">
                    <div class="w-16 h-16 bg-purple-100 text-purple-600 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-file-alt text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Fact Sheet</h3>
                    <p class="text-gray-600 mb-6">Company overview, key metrics, and executive bios</p>
                    <a href="mailto:hello@morningnewsletter.com?subject=Fact Sheet Request" 
                       class="inline-flex items-center text-primary hover:text-primary-dark font-medium">
                        <i class="fas fa-download mr-2"></i>Download PDF
                    </a>
                </div>
                
                <div class="bg-gray-50 rounded-2xl p-6 text-center">
                    <div class="w-16 h-16 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-camera text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Screenshots</h3>
                    <p class="text-gray-600 mb-6">Product screenshots and interface examples</p>
                    <a href="mailto:hello@morningnewsletter.com?subject=Screenshots Request" 
                       class="inline-flex items-center text-primary hover:text-primary-dark font-medium">
                        <i class="fas fa-download mr-2"></i>Request Images
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Coverage Section -->
    <div class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">In the News</h2>
                <p class="text-xl text-gray-600">Recent press coverage and mentions</p>
            </div>
            
            <!-- Featured In Logos -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 items-center justify-items-center mb-16">
                <img src="/assets/companies/Press logo=Bloomberg.svg" alt="Bloomberg" class="h-8 opacity-60 filter grayscale">
                <img src="/assets/companies/Press logo=Business Insider.svg" alt="Business Insider" class="h-8 opacity-60 filter grayscale">
                <img src="/assets/companies/Press logo=The Guardian.svg" alt="The Guardian" class="h-8 opacity-60 filter grayscale">
                <img src="/assets/companies/Press logo=The New York Times (TNYT).svg" alt="The New York Times" class="h-8 opacity-60 filter grayscale">
            </div>
            
            <div class="bg-white rounded-2xl p-8 text-center">
                <h3 class="text-2xl font-bold text-gray-900 mb-4">Coverage Coming Soon</h3>
                <p class="text-lg text-gray-600 mb-6">
                    I'm working with publications to share the story of how a simple side project is helping 
                    professionals worldwide start their mornings with less chaos and more clarity.
                </p>
                <p class="text-gray-500">
                    For interview requests or story pitches, contact us at 
                    <a href="mailto:hello@morningnewsletter.com" class="text-primary hover:text-primary-dark">hello@morningnewsletter.com</a>
                </p>
            </div>
        </div>
    </div>

    <!-- Quote Section -->
    <div class="py-20 bg-white">
        <div class="max-w-4xl mx-auto px-6 lg:px-8 text-center">
            <blockquote class="text-2xl font-medium text-gray-900 mb-8">
                "I built MorningNewsletter to solve my own problem—turning information overload into clarity. 
                Every day, it helps professionals start their morning with purpose instead of chaos."
            </blockquote>
            <div class="text-lg text-gray-600">
                <strong>Manuel Escrig</strong><br>
                Creator of MorningNewsletter
            </div>
        </div>
    </div>

    <!-- CTA Section -->
    <div class="py-20 bg-gradient-to-r from-purple-600 to-blue-600">
        <div class="max-w-4xl mx-auto px-6 lg:px-8 text-center">
            <h2 class="text-3xl sm:text-4xl font-bold text-white mb-6">
                Ready to Cover Our Story?
            </h2>
            <p class="text-xl text-white/90 mb-8 max-w-2xl mx-auto">
                I'm always happy to provide quotes, data, or interviews for stories about productivity, 
                morning routines, and the future of personalized information.
            </p>
            <a href="mailto:hello@morningnewsletter.com" 
               class="btn-pill inline-flex items-center px-8 py-4 text-lg font-semibold text-purple-600 bg-white hover:bg-gray-50 transition-all duration-200 hover:scale-105 shadow-lg">
                <i class="fas fa-envelope mr-3"></i>
                Contact Press Team
            </a>
        </div>
    </div>

    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>