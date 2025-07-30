<?php
require_once __DIR__ . '/core/Auth.php';

$auth = Auth::getInstance();
$isLoggedIn = $auth->isLoggedIn();
$user = $isLoggedIn ? $auth->getCurrentUser() : null;

// Get user statistics for social proof
$displayCount = '9000+';
$todayCount = 0;

try {
    require_once __DIR__ . '/config/UserStats.php';
    $userStats = new UserStats();
    $socialProofData = $userStats->getSocialProofData();
    
    $displayCount = $socialProofData['display_count'];
    $todayCount = $socialProofData['today_signups'];
    
} catch (Exception $e) {
    // Silently fail and use fallback values
    error_log("Error getting user stats: " . $e->getMessage());
}

// Page configuration
$pageTitle = "MorningNewsletter.com - Your Personalized Morning Brief";
$pageDescription = "Start every day informed with personalized morning briefs. Get your business metrics, crypto prices, weather, news, and messages in one beautiful email.";
include __DIR__ . '/includes/page-header.php';
?>
    <link rel="stylesheet" href="/assets/css/landing.css"><!-- Additional landing page styles -->
</head>
<body class="bg-white">
    <?php include __DIR__ . '/includes/navigation.php'; ?>

    <!-- Hero Section -->
    <div class="relative mesh-bg hero-section pt-24 sm:pt-32 lg:pt-40 pb-0">
        <div class="mx-auto max-w-7xl px-6 lg:px-8 relative z-10">
            <div class="mx-auto max-w-4xl text-center">
                <!-- Badge -->
                <div class="mb-8">
                    <span class="inline-flex items-center rounded-full bg-white/60 backdrop-blur-sm px-3 py-1 text-sm font-medium text-primary-dark ring-1 ring-inset ring-primary-dark/10">
                        ✨ AI-Powered Content Curation
                    </span>
                </div>
                
                <!-- Main Heading -->
                <h1 class="hero-title text-4xl font-bold tracking-tight text-gray-900 sm:text-6xl lg:text-7xl">
                    Start Every Day 
                    <span class="gradient-text typewriter-container">
                        <span class="typewriter-text">
                            <span id="typewriter">Informed</span><span class="typewriter-cursor"></span>
                        </span>
                    </span>
                    <br>
                    Not Overwhelmed
                </h1>
                
                <!-- Subtitle -->
                <p class="hero-subtitle mt-6 text-lg leading-8 text-gray-700 sm:text-xl max-w-3xl mx-auto">
                    Wake up to everything that matters to you: your business metrics, crypto prices, weather, news, and important messages. All in one beautiful email that takes 2 minutes to read.
                </p>
                
                <!-- Email Input and CTA -->
                <div class="mt-10">
                    <form id="hero-signup-form" class="email-input-group">
                        <input type="email" 
                               id="hero-email" 
                               placeholder="Enter your email" 
                               class="email-input" 
                               required>
                        <button type="submit" class="btn-primary">
                            Create Your Newsletter
                        </button>
                    </form>
                </div>
                
                <!-- Newsletter Preview in Mac Window -->
                <div class="mac-window">
                    <div class="mac-window-header">
                        <div class="mac-window-buttons">
                            <div class="mac-window-button"></div>
                            <div class="mac-window-button"></div>
                            <div class="mac-window-button"></div>
                        </div>
                        <div class="mac-window-title">Morning Newsletter - 6:00 AM Daily</div>
                    </div>
                    <div class="mac-window-content">
                        <div class="newsletter-preview">
                            <div class="newsletter-preview-header">
                                <h3 class="text-xl font-bold">Your Morning Newsletter</h3>
                                <p class="text-sm opacity-90 mt-1">Delivered daily at 6:00 AM</p>
                            </div>
                            <div class="newsletter-preview-content">
                                <div class="widget-container">
                                    <div class="widget-card">
                                        <h3 class="widget-title">💰 Monthly Revenue</h3>
                                        <p class="widget-value">$45,231</p>
                                        <p class="widget-subtitle">Total revenue this month</p>
                                        <span class="widget-change positive">↑ 12% from last month</span>
                                    </div>
                                    <div class="widget-card">
                                        <h3 class="widget-title">👥 Active Users</h3>
                                        <p class="widget-value">1,429</p>
                                        <p class="widget-subtitle">Currently active</p>
                                        <span class="widget-change positive">↑ 8% from yesterday</span>
                                    </div>
                                    <div class="widget-card">
                                        <h3 class="widget-title">₿ Bitcoin</h3>
                                        <p class="widget-value">$43,567</p>
                                        <p class="widget-subtitle">Current price</p>
                                        <span class="widget-change positive">↑ 3.2% today</span>
                                        <div class="widget-details">
                                            <div class="widget-detail-item">
                                                <span class="widget-detail-label">24h High</span>
                                                <span class="widget-detail-value">$44,234</span>
                                            </div>
                                            <div class="widget-detail-item">
                                                <span class="widget-detail-label">24h Low</span>
                                                <span class="widget-detail-value">$42,100</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="widget-card">
                                        <h3 class="widget-title">☀️ San Francisco Weather</h3>
                                        <p class="widget-value">72°F</p>
                                        <p class="widget-subtitle">Sunny all day</p>
                                        <div class="widget-details">
                                            <div class="widget-detail-item">
                                                <span class="widget-detail-label">High</span>
                                                <span class="widget-detail-value">78°F</span>
                                            </div>
                                            <div class="widget-detail-item">
                                                <span class="widget-detail-label">Low</span>
                                                <span class="widget-detail-value">65°F</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="widget-card full-width">
                                        <h3 class="widget-title">📰 Top News</h3>
                                        <div class="widget-details" style="border-top: none; margin-top: 0; padding-top: 0;">
                                            <div class="widget-detail-item">
                                                <span class="widget-detail-label">Tech</span>
                                                <span class="widget-detail-value">AI Startup Raises $50M Series B</span>
                                            </div>
                                            <div class="widget-detail-item">
                                                <span class="widget-detail-label">Business</span>
                                                <span class="widget-detail-value">Market Hits New All-Time High</span>
                                            </div>
                                            <div class="widget-detail-item">
                                                <span class="widget-detail-label">World</span>
                                                <span class="widget-detail-value">Climate Summit Reaches Agreement</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="widget-card">
                                        <h3 class="widget-title">💼 Stripe Revenue</h3>
                                        <p class="widget-value">$12,450</p>
                                        <p class="widget-subtitle">Last 30 days</p>
                                        <span class="widget-change positive">↑ 23% from last period</span>
                                    </div>
                                    <div class="widget-card">
                                        <h3 class="widget-title">📱 App Store Downloads</h3>
                                        <p class="widget-value">3,241</p>
                                        <p class="widget-subtitle">This week</p>
                                        <span class="widget-change negative">↓ 5% from last week</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Mesh overlay elements -->
        <div class="absolute inset-0 -z-10">
            <div class="absolute top-20 left-20 w-96 h-96 bg-gradient-to-br from-primary-light/40 to-purple-200/40 rounded-full blur-3xl"></div>
            <div class="absolute bottom-20 right-20 w-96 h-96 bg-gradient-to-br from-purple-200/40 to-pink-200/40 rounded-full blur-3xl"></div>
            <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-[800px] h-[600px] bg-gradient-to-br from-primary-light/30 to-purple-100/30 rounded-full blur-3xl"></div>
        </div>
    </div>

    <!-- The Morning Struggle Section -->
    <div class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <h2 class="text-base text-primary font-semibold tracking-wide uppercase mb-4">The Problem</h2>
                <h3 class="text-3xl font-extrabold text-gray-900 sm:text-4xl lg:text-5xl mb-6">
                    Your Morning Routine is <span class="gradient-text">Broken</span>
                </h3>
                <p class="text-xl text-gray-600">
                    Every day starts the same way: scattered information, wasted time, and that nagging feeling you're missing something important.
                </p>
            </div>
            
            <!-- Visual Timeline -->
            <div class="relative max-w-5xl mx-auto">
                <div class="absolute left-1/2 transform -translate-x-1/2 h-full w-1 bg-gradient-to-b from-blue-200 via-indigo-200 to-purple-200"></div>
                
                <div class="space-y-12">
                    <!-- Timeline Item 1 -->
                    <div class="relative flex items-center">
                        <div class="flex items-center justify-center w-full md:w-1/2 md:pr-8">
                            <div class="bg-white rounded-2xl shadow-lg p-6 w-full border border-blue-100">
                                <div class="flex items-center mb-4">
                                    <div class="bg-blue-50 rounded-full p-3 mr-4">
                                        <i class="fas fa-mobile-alt text-blue-500 text-xl"></i>
                                    </div>
                                    <div>
                                        <h4 class="text-lg font-bold text-gray-900">6:45 AM</h4>
                                        <p class="text-sm text-gray-500">The App Marathon Begins</p>
                                    </div>
                                </div>
                                <p class="text-gray-600">Check email, then Slack, then weather, then news, then crypto prices...</p>
                            </div>
                        </div>
                        <div class="absolute left-1/2 transform -translate-x-1/2 w-8 h-8 bg-blue-400 rounded-full border-4 border-white"></div>
                        <div class="hidden md:block md:w-1/2"></div>
                    </div>
                    
                    <!-- Timeline Item 2 -->
                    <div class="relative flex items-center">
                        <div class="hidden md:block md:w-1/2"></div>
                        <div class="absolute left-1/2 transform -translate-x-1/2 w-8 h-8 bg-indigo-400 rounded-full border-4 border-white"></div>
                        <div class="flex items-center justify-center w-full md:w-1/2 md:pl-8">
                            <div class="bg-white rounded-2xl shadow-lg p-6 w-full border border-indigo-100">
                                <div class="flex items-center mb-4">
                                    <div class="bg-indigo-50 rounded-full p-3 mr-4">
                                        <i class="fas fa-clock text-indigo-500 text-xl"></i>
                                    </div>
                                    <div>
                                        <h4 class="text-lg font-bold text-gray-900">7:00 AM</h4>
                                        <p class="text-sm text-gray-500">Lost in the Noise</p>
                                    </div>
                                </div>
                                <p class="text-gray-600">15 minutes gone. Still haven't found that important message from yesterday.</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Timeline Item 3 -->
                    <div class="relative flex items-center">
                        <div class="flex items-center justify-center w-full md:w-1/2 md:pr-8">
                            <div class="bg-white rounded-2xl shadow-lg p-6 w-full border border-purple-100">
                                <div class="flex items-center mb-4">
                                    <div class="bg-purple-50 rounded-full p-3 mr-4">
                                        <i class="fas fa-exclamation-circle text-purple-500 text-xl"></i>
                                    </div>
                                    <div>
                                        <h4 class="text-lg font-bold text-gray-900">7:15 AM</h4>
                                        <p class="text-sm text-gray-500">The Realization</p>
                                    </div>
                                </div>
                                <p class="text-gray-600">Market opened down 5%. That meeting got moved. You're already behind.</p>
                            </div>
                        </div>
                        <div class="absolute left-1/2 transform -translate-x-1/2 w-8 h-8 bg-purple-400 rounded-full border-4 border-white"></div>
                        <div class="hidden md:block md:w-1/2"></div>
                    </div>
                </div>
            </div>
            
            <!-- Stats Bar -->
            <div class="mt-16 bg-gradient-to-r from-blue-50 via-indigo-50 to-purple-50 rounded-2xl p-8">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 text-center">
                    <div>
                        <div class="text-3xl font-bold text-blue-600 mb-2">30+ min</div>
                        <p class="text-gray-600">Wasted every morning</p>
                    </div>
                    <div>
                        <div class="text-3xl font-bold text-indigo-600 mb-2">7+ apps</div>
                        <p class="text-gray-600">To check daily</p>
                    </div>
                    <div>
                        <div class="text-3xl font-bold text-purple-600 mb-2">82%</div>
                        <p class="text-gray-600">Miss important updates</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Transformation Section -->
    <div class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-base text-primary font-semibold tracking-wide uppercase mb-4">Meet MorningNewsletter</h2>
                <h3 class="text-3xl font-extrabold text-gray-900 sm:text-4xl">
                    Transform Your Morning Routine
                </h3>
                <p class="mt-4 text-xl text-gray-600">
                    Start your day with clarity and confidence
                </p>
            </div>
            <div class="grid grid-cols-1 gap-8 md:grid-cols-2 max-w-5xl mx-auto">
                <div class="bg-white rounded-2xl p-8 shadow-lg border border-gray-100">
                    <h3 class="text-2xl font-bold text-gray-900 mb-6">Before MorningNewsletter</h3>
                    <ul class="space-y-4">
                        <li class="flex items-start">
                            <div class="flex-shrink-0 w-6 h-6 rounded-full bg-red-100 flex items-center justify-center mt-0.5">
                                <i class="fas fa-times text-red-500 text-xs"></i>
                            </div>
                            <p class="ml-3 text-gray-600">Scattered information across multiple platforms</p>
                        </li>
                        <li class="flex items-start">
                            <div class="flex-shrink-0 w-6 h-6 rounded-full bg-red-100 flex items-center justify-center mt-0.5">
                                <i class="fas fa-times text-red-500 text-xs"></i>
                            </div>
                            <p class="ml-3 text-gray-600">Constantly switching between different apps</p>
                        </li>
                        <li class="flex items-start">
                            <div class="flex-shrink-0 w-6 h-6 rounded-full bg-red-100 flex items-center justify-center mt-0.5">
                                <i class="fas fa-times text-red-500 text-xs"></i>
                            </div>
                            <p class="ml-3 text-gray-600">Missing important updates and messages</p>
                        </li>
                        <li class="flex items-start">
                            <div class="flex-shrink-0 w-6 h-6 rounded-full bg-red-100 flex items-center justify-center mt-0.5">
                                <i class="fas fa-times text-red-500 text-xs"></i>
                            </div>
                            <p class="ml-3 text-gray-600">Wasting 30+ minutes every morning</p>
                        </li>
                        <li class="flex items-start">
                            <div class="flex-shrink-0 w-6 h-6 rounded-full bg-red-100 flex items-center justify-center mt-0.5">
                                <i class="fas fa-times text-red-500 text-xs"></i>
                            </div>
                            <p class="ml-3 text-gray-600">Starting the day feeling overwhelmed</p>
                        </li>
                    </ul>
                </div>
                <div class="bg-gradient-to-br from-blue-50 to-purple-50 rounded-2xl p-8 shadow-lg border border-blue-100">
                    <h3 class="text-2xl font-bold text-gray-900 mb-6">After MorningNewsletter</h3>
                    <ul class="space-y-4">
                        <li class="flex items-start">
                            <div class="flex-shrink-0 w-6 h-6 rounded-full bg-green-100 flex items-center justify-center mt-0.5">
                                <i class="fas fa-check text-green-600 text-xs"></i>
                            </div>
                            <p class="ml-3 text-gray-600">One concise email with everything you need</p>
                        </li>
                        <li class="flex items-start">
                            <div class="flex-shrink-0 w-6 h-6 rounded-full bg-green-100 flex items-center justify-center mt-0.5">
                                <i class="fas fa-check text-green-600 text-xs"></i>
                            </div>
                            <p class="ml-3 text-gray-600">All your data sources in one place</p>
                        </li>
                        <li class="flex items-start">
                            <div class="flex-shrink-0 w-6 h-6 rounded-full bg-green-100 flex items-center justify-center mt-0.5">
                                <i class="fas fa-check text-green-600 text-xs"></i>
                            </div>
                            <p class="ml-3 text-gray-600">Never miss important updates</p>
                        </li>
                        <li class="flex items-start">
                            <div class="flex-shrink-0 w-6 h-6 rounded-full bg-green-100 flex items-center justify-center mt-0.5">
                                <i class="fas fa-check text-green-600 text-xs"></i>
                            </div>
                            <p class="ml-3 text-gray-600">Get informed in under 5 minutes</p>
                        </li>
                        <li class="flex items-start">
                            <div class="flex-shrink-0 w-6 h-6 rounded-full bg-green-100 flex items-center justify-center mt-0.5">
                                <i class="fas fa-check text-green-600 text-xs"></i>
                            </div>
                            <p class="ml-3 text-gray-600">Start your day focused and productive</p>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Social Proof Section -->
    <div class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-10">
                <h2 class="text-base text-primary font-semibold tracking-wide uppercase mb-3">Trusted by Industry Leaders</h2>
                <p class="text-2xl font-bold text-gray-900 mb-2">
                    Join <?php echo $displayCount; ?> professionals who've transformed their mornings
                </p>
                <?php if ($todayCount > 0): ?>
                    <div class="mt-3 inline-flex items-center text-sm text-green-600 font-medium">
                        <i class="fas fa-arrow-up mr-2"></i>
                        <?php echo $todayCount; ?> joined today
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="relative mb-8">
                <div class="absolute inset-0 flex items-center" aria-hidden="true">
                    <div class="w-full border-t border-gray-200"></div>
                </div>
                <div class="relative flex justify-center">
                    <span class="px-4 bg-white text-sm text-gray-500">
                        Professionals from these companies use MorningNewsletter
                    </span>
                </div>
            </div>
            
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-8 items-center justify-items-center max-w-6xl mx-auto">
                <div class="flex items-center justify-center p-4">
                    <img src="/assets/companies/Logo=google.svg" alt="Google" class="h-8 opacity-50 hover:opacity-70 transition-opacity duration-200">
                </div>
                <div class="flex items-center justify-center p-4">
                    <img src="/assets/companies/Logo=microsoft.svg" alt="Microsoft" class="h-8 opacity-50 hover:opacity-70 transition-opacity duration-200">
                </div>
                <div class="flex items-center justify-center p-4">
                    <img src="/assets/companies/Logo=instagram_word.svg" alt="Instagram" class="h-8 opacity-50 hover:opacity-70 transition-opacity duration-200">
                </div>
                <div class="flex items-center justify-center p-4">
                    <img src="/assets/companies/Press logo=Bloomberg.svg" alt="Bloomberg" class="h-8 opacity-50 hover:opacity-70 transition-opacity duration-200">
                </div>
                <div class="flex items-center justify-center p-4">
                    <img src="/assets/companies/Press logo=Business Insider.svg" alt="Business Insider" class="h-8 opacity-50 hover:opacity-70 transition-opacity duration-200">
                </div>
                <div class="flex items-center justify-center p-4">
                    <img src="/assets/companies/Press logo=The Guardian.svg" alt="The Guardian" class="h-8 opacity-50 hover:opacity-70 transition-opacity duration-200">
                </div>
                <div class="flex items-center justify-center p-4">
                    <img src="/assets/companies/Press logo=The New York Times (TNYT).svg" alt="The New York Times" class="h-8 opacity-50 hover:opacity-70 transition-opacity duration-200">
                </div>
            </div>
        </div>
    </div>

    <!-- Features Section -->
    <div id="features" class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h2 class="text-base text-primary font-semibold tracking-wide uppercase">Features</h2>
                <p class="mt-2 text-3xl leading-8 font-extrabold tracking-tight text-gray-900 sm:text-4xl">
                    Everything you actually care about
                </p>
            </div>

            <div class="mt-16">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-5xl mx-auto">
                    <!-- Feature 1 -->
                    <div class="relative group">
                        <div class="absolute -inset-1 bg-gradient-to-r from-blue-500 to-purple-500 rounded-2xl blur opacity-25 group-hover:opacity-40 transition duration-300"></div>
                        <div class="relative bg-white rounded-2xl p-8 shadow-md hover:shadow-xl transition-all duration-300">
                            <div class="bg-gradient-to-br from-blue-50 to-purple-50 rounded-xl p-4 inline-flex mb-6">
                                <i class="fas fa-chart-line text-2xl text-primary"></i>
                            </div>
                            <h3 class="text-xl font-bold text-gray-900 mb-3">Business Metrics</h3>
                            <p class="text-gray-600 leading-relaxed">
                                Track revenue, user stats, and conversion rates. Get your most important KPIs delivered fresh every morning.
                            </p>
                            <div class="mt-4 flex flex-wrap gap-2">
                                <span class="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded-full">Stripe</span>
                                <span class="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded-full">Analytics</span>
                                <span class="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded-full">Custom APIs</span>
                            </div>
                        </div>
                    </div>

                    <!-- Feature 2 -->
                    <div class="relative group">
                        <div class="absolute -inset-1 bg-gradient-to-r from-green-500 to-blue-500 rounded-2xl blur opacity-25 group-hover:opacity-40 transition duration-300"></div>
                        <div class="relative bg-white rounded-2xl p-8 shadow-md hover:shadow-xl transition-all duration-300">
                            <div class="bg-gradient-to-br from-green-50 to-blue-50 rounded-xl p-4 inline-flex mb-6">
                                <i class="fas fa-coins text-2xl text-green-600"></i>
                            </div>
                            <h3 class="text-xl font-bold text-gray-900 mb-3">Market Updates</h3>
                            <p class="text-gray-600 leading-relaxed">
                                Bitcoin, stocks, forex—track all your investments. Know exactly where the markets stand before trading begins.
                            </p>
                            <div class="mt-4 flex flex-wrap gap-2">
                                <span class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded-full">Crypto</span>
                                <span class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded-full">Stocks</span>
                                <span class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded-full">Real-time</span>
                            </div>
                        </div>
                    </div>

                    <!-- Feature 3 -->
                    <div class="relative group">
                        <div class="absolute -inset-1 bg-gradient-to-r from-orange-500 to-yellow-500 rounded-2xl blur opacity-25 group-hover:opacity-40 transition duration-300"></div>
                        <div class="relative bg-white rounded-2xl p-8 shadow-md hover:shadow-xl transition-all duration-300">
                            <div class="bg-gradient-to-br from-orange-50 to-yellow-50 rounded-xl p-4 inline-flex mb-6">
                                <i class="fas fa-cloud-sun text-2xl text-orange-600"></i>
                            </div>
                            <h3 class="text-xl font-bold text-gray-900 mb-3">Weather & News</h3>
                            <p class="text-gray-600 leading-relaxed">
                                Local weather forecast and breaking news from your area. Know if you need an umbrella or if there's traffic ahead.
                            </p>
                            <div class="mt-4 flex flex-wrap gap-2">
                                <span class="text-xs bg-orange-100 text-orange-700 px-2 py-1 rounded-full">Weather</span>
                                <span class="text-xs bg-orange-100 text-orange-700 px-2 py-1 rounded-full">Local News</span>
                                <span class="text-xs bg-orange-100 text-orange-700 px-2 py-1 rounded-full">Alerts</span>
                            </div>
                        </div>
                    </div>

                    <!-- Feature 4 -->
                    <div class="relative group">
                        <div class="absolute -inset-1 bg-gradient-to-r from-purple-500 to-pink-500 rounded-2xl blur opacity-25 group-hover:opacity-40 transition duration-300"></div>
                        <div class="relative bg-white rounded-2xl p-8 shadow-md hover:shadow-xl transition-all duration-300">
                            <div class="bg-gradient-to-br from-purple-50 to-pink-50 rounded-xl p-4 inline-flex mb-6">
                                <i class="fas fa-comments text-2xl text-purple-600"></i>
                            </div>
                            <h3 class="text-xl font-bold text-gray-900 mb-3">Important Messages</h3>
                            <p class="text-gray-600 leading-relaxed">
                                Never miss critical DMs from Twitter, Slack, or Discord. We'll surface the messages that actually matter.
                            </p>
                            <div class="mt-4 flex flex-wrap gap-2">
                                <span class="text-xs bg-purple-100 text-purple-700 px-2 py-1 rounded-full">Twitter</span>
                                <span class="text-xs bg-purple-100 text-purple-700 px-2 py-1 rounded-full">Slack</span>
                                <span class="text-xs bg-purple-100 text-purple-700 px-2 py-1 rounded-full">Discord</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pricing Section -->
    <div id="pricing" class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-base text-primary font-semibold tracking-wide uppercase mb-4">Simple, Transparent Pricing</h2>
                <h3 class="text-3xl font-extrabold text-gray-900 sm:text-4xl lg:text-5xl mb-6">
                    Choose Your Perfect Plan
                </h3>
                <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                    Start free, upgrade when you need more. Cancel anytime, no questions asked.
                </p>
                <div class="mt-8 inline-flex items-center bg-green-50 text-green-700 px-4 py-2 rounded-full text-sm font-medium">
                    <i class="fas fa-check-circle mr-2"></i>
                    All plans include a 7-day free trial
                </div>
            </div>

            <div class="grid grid-cols-1 gap-8 lg:grid-cols-3 lg:gap-6 max-w-6xl mx-auto">
                <!-- Starter Tier -->
                <div class="relative bg-gray-50 rounded-2xl p-8 shadow-md hover:shadow-xl transition-all duration-300">
                    <div class="mb-8">
                        <h3 class="text-2xl font-bold text-gray-900">Starter</h3>
                        <p class="mt-2 text-gray-600">Perfect for trying out MorningNewsletter</p>
                    </div>
                    
                    <div class="mb-8">
                        <div class="flex items-baseline">
                            <span class="text-5xl font-extrabold text-gray-900">$5</span>
                            <span class="ml-1 text-xl text-gray-500">/month</span>
                        </div>
                        <p class="mt-2 text-sm text-gray-500">Billed monthly</p>
                    </div>
                    
                    <ul class="space-y-4 mb-8">
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="ml-3 text-gray-700">Up to <strong>5 data sources</strong></span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="ml-3 text-gray-700">Daily email delivery</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="ml-3 text-gray-700">Basic customization</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="ml-3 text-gray-700">Email support</span>
                        </li>
                    </ul>
                    
                    <a href="/auth/register.php" class="block w-full bg-gray-900 text-white text-center py-3 rounded-lg font-medium hover:bg-gray-800 transition-colors">
                        Start Free Trial
                    </a>
                </div>

                <!-- Pro Tier -->
                <div class="relative bg-gradient-to-b from-blue-50 to-purple-50 rounded-2xl p-8 shadow-xl hover:shadow-2xl transition-all duration-300 ring-2 ring-blue-500 transform scale-105">
                    <div class="absolute -top-4 left-1/2 transform -translate-x-1/2">
                        <span class="bg-gradient-to-r from-blue-500 to-purple-500 text-white px-4 py-1 rounded-full text-sm font-semibold">
                            MOST POPULAR
                        </span>
                    </div>
                    
                    <div class="mb-8 mt-4">
                        <h3 class="text-2xl font-bold text-gray-900">Pro</h3>
                        <p class="mt-2 text-gray-600">For professionals who need more</p>
                    </div>
                    
                    <div class="mb-8">
                        <div class="flex items-baseline">
                            <span class="text-5xl font-extrabold text-gray-900">$15</span>
                            <span class="ml-1 text-xl text-gray-500">/month</span>
                        </div>
                        <p class="mt-2 text-sm text-gray-500">Billed monthly</p>
                    </div>
                    
                    <ul class="space-y-4 mb-8">
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="ml-3 text-gray-700">Up to <strong>15 data sources</strong></span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="ml-3 text-gray-700">Advanced scheduling options</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="ml-3 text-gray-700">Custom email templates</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="ml-3 text-gray-700">Priority support</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="ml-3 text-gray-700">API access</span>
                        </li>
                    </ul>
                    
                    <a href="/auth/register.php" class="block w-full bg-gradient-to-r from-blue-500 to-purple-500 text-white text-center py-3 rounded-lg font-medium hover:from-blue-600 hover:to-purple-600 transition-all shadow-lg">
                        Start Free Trial
                    </a>
                </div>

                <!-- Unlimited Tier -->
                <div class="relative bg-gray-50 rounded-2xl p-8 shadow-md hover:shadow-xl transition-all duration-300">
                    <div class="mb-8">
                        <h3 class="text-2xl font-bold text-gray-900">Unlimited</h3>
                        <p class="mt-2 text-gray-600">For power users and teams</p>
                    </div>
                    
                    <div class="mb-8">
                        <div class="flex items-baseline">
                            <span class="text-5xl font-extrabold text-gray-900">$19</span>
                            <span class="ml-1 text-xl text-gray-500">/month</span>
                        </div>
                        <p class="mt-2 text-sm text-gray-500">Billed monthly</p>
                    </div>
                    
                    <ul class="space-y-4 mb-8">
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="ml-3 text-gray-700"><strong>Unlimited data sources</strong></span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="ml-3 text-gray-700">Everything in Pro</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="ml-3 text-gray-700">Team collaboration</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="ml-3 text-gray-700">White-label options</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="ml-3 text-gray-700">24/7 phone support</span>
                        </li>
                    </ul>
                    
                    <a href="/auth/register.php" class="block w-full bg-gray-900 text-white text-center py-3 rounded-lg font-medium hover:bg-gray-800 transition-colors">
                        Start Free Trial
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Testimonials Section -->
    <div id="testimonials" class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-base text-primary font-semibold tracking-wide uppercase mb-4">What Users Say</h2>
                <h3 class="text-3xl font-extrabold text-gray-900 sm:text-4xl">
                    Real People, Real Results
                </h3>
            </div>

            <div class="grid grid-cols-1 gap-8 lg:grid-cols-3 max-w-5xl mx-auto">
                <!-- Testimonial 1 -->
                <div class="bg-white rounded-xl p-6 shadow-md">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center text-white font-semibold">
                            SR
                        </div>
                        <div class="ml-3">
                            <h4 class="font-semibold text-gray-900">Sarah Robinson</h4>
                            <p class="text-sm text-gray-600">CEO at TechFlow</p>
                        </div>
                    </div>
                    <p class="text-gray-700">
                        "I used to spend 45 minutes every morning checking different apps. Now? 5 minutes with my coffee and I'm good to go."
                    </p>
                    <div class="mt-4 flex text-yellow-400 text-sm">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                    </div>
                </div>

                <!-- Testimonial 2 -->
                <div class="bg-white rounded-xl p-6 shadow-md">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center text-white font-semibold">
                            MK
                        </div>
                        <div class="ml-3">
                            <h4 class="font-semibold text-gray-900">Marcus Kim</h4>
                            <p class="text-sm text-gray-600">Product Manager</p>
                        </div>
                    </div>
                    <p class="text-gray-700">
                        "My business metrics, crypto portfolio, and important messages all in one place? This is exactly what I needed."
                    </p>
                    <div class="mt-4 flex text-yellow-400 text-sm">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                    </div>
                </div>

                <!-- Testimonial 3 -->
                <div class="bg-white rounded-xl p-6 shadow-md">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 bg-purple-500 rounded-full flex items-center justify-center text-white font-semibold">
                            AJ
                        </div>
                        <div class="ml-3">
                            <h4 class="font-semibold text-gray-900">Alex Johnson</h4>
                            <p class="text-sm text-gray-600">Crypto Trader</p>
                        </div>
                    </div>
                    <p class="text-gray-700">
                        "Caught the Bitcoin pump early thanks to my morning brief. This thing pays for itself."
                    </p>
                    <div class="mt-4 flex text-yellow-400 text-sm">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- FAQ Section -->
    <div id="faq" class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-base text-primary font-semibold tracking-wide uppercase mb-4">Got Questions?</h2>
                <h3 class="text-3xl font-extrabold text-gray-900 sm:text-4xl">
                    Everything You Need to Know
                </h3>
            </div>

            <div class="max-w-3xl mx-auto">
                <div class="space-y-4">
                    <div class="bg-white border border-gray-200 rounded-xl shadow-sm hover:shadow-md transition-shadow duration-200">
                        <button class="w-full px-6 py-5 text-left flex justify-between items-center focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500 rounded-xl" onclick="toggleFAQ(this)">
                            <div class="flex items-center">
                                <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center mr-4">
                                    <i class="fas fa-clock text-blue-600 text-sm"></i>
                                </div>
                                <span class="text-lg font-medium text-gray-900">When do I get my newsletter?</span>
                            </div>
                            <i class="fas fa-plus text-gray-400 transition-transform duration-200"></i>
                        </button>
                        <div class="faq-answer px-6 pl-16 pb-5 text-gray-600" style="display: none;">
                            Your personalized newsletter arrives at your chosen time (default is 6 AM) in your local timezone. You can adjust this anytime in your dashboard settings.
                        </div>
                    </div>
                    
                    <div class="bg-white border border-gray-200 rounded-xl shadow-sm hover:shadow-md transition-shadow duration-200">
                        <button class="w-full px-6 py-5 text-left flex justify-between items-center focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500 rounded-xl" onclick="toggleFAQ(this)">
                            <div class="flex items-center">
                                <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center mr-4">
                                    <i class="fas fa-sliders-h text-green-600 text-sm"></i>
                                </div>
                                <span class="text-lg font-medium text-gray-900">Can I customize my sources?</span>
                            </div>
                            <i class="fas fa-plus text-gray-400 transition-transform duration-200"></i>
                        </button>
                        <div class="faq-answer px-6 pl-16 pb-5 text-gray-600" style="display: none;">
                            Absolutely! Add, remove, or reorder your data sources anytime. Choose from weather, stocks, crypto, news, business metrics, and more. Your newsletter, your way.
                        </div>
                    </div>
                    
                    <div class="bg-white border border-gray-200 rounded-xl shadow-sm hover:shadow-md transition-shadow duration-200">
                        <button class="w-full px-6 py-5 text-left flex justify-between items-center focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500 rounded-xl" onclick="toggleFAQ(this)">
                            <div class="flex items-center">
                                <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center mr-4">
                                    <i class="fas fa-shield-alt text-purple-600 text-sm"></i>
                                </div>
                                <span class="text-lg font-medium text-gray-900">Is my data secure?</span>
                            </div>
                            <i class="fas fa-plus text-gray-400 transition-transform duration-200"></i>
                        </button>
                        <div class="faq-answer px-6 pl-16 pb-5 text-gray-600" style="display: none;">
                            Your security is our priority. All data is encrypted, we never sell your information, and you can delete everything with one click. We're GDPR compliant and transparent about our practices.
                        </div>
                    </div>
                    
                    <div class="bg-white border border-gray-200 rounded-xl shadow-sm hover:shadow-md transition-shadow duration-200">
                        <button class="w-full px-6 py-5 text-left flex justify-between items-center focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500 rounded-xl" onclick="toggleFAQ(this)">
                            <div class="flex items-center">
                                <div class="w-8 h-8 bg-orange-100 rounded-lg flex items-center justify-center mr-4">
                                    <i class="fas fa-credit-card text-orange-600 text-sm"></i>
                                </div>
                                <span class="text-lg font-medium text-gray-900">How does billing work?</span>
                            </div>
                            <i class="fas fa-plus text-gray-400 transition-transform duration-200"></i>
                        </button>
                        <div class="faq-answer px-6 pl-16 pb-5 text-gray-600" style="display: none;">
                            Start with a 7-day free trial, no credit card required. After that, choose a plan that fits your needs. Cancel anytime with no questions asked. We use Stripe for secure payments.
                        </div>
                    </div>
                    
                    <div class="bg-white border border-gray-200 rounded-xl shadow-sm hover:shadow-md transition-shadow duration-200">
                        <button class="w-full px-6 py-5 text-left flex justify-between items-center focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500 rounded-xl" onclick="toggleFAQ(this)">
                            <div class="flex items-center">
                                <div class="w-8 h-8 bg-red-100 rounded-lg flex items-center justify-center mr-4">
                                    <i class="fas fa-mobile-alt text-red-600 text-sm"></i>
                                </div>
                                <span class="text-lg font-medium text-gray-900">Do you have a mobile app?</span>
                            </div>
                            <i class="fas fa-plus text-gray-400 transition-transform duration-200"></i>
                        </button>
                        <div class="faq-answer px-6 pl-16 pb-5 text-gray-600" style="display: none;">
                            Not yet, but our emails are perfectly optimized for mobile. You can read your morning brief on any device, and we're working on native apps for 2025.
                        </div>
                    </div>
                </div>
                
                <div class="mt-8 text-center">
                    <p class="text-gray-600">
                        Still have questions? 
                        <a href="/support" class="text-primary hover:text-primary-dark font-medium">Contact our support team</a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Final CTA Section -->
    <div class="gradient-bg py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl font-extrabold text-gray-900 sm:text-4xl">
                Ready to actually enjoy your mornings?
            </h2>
            <p class="mt-4 text-xl text-gray-600">
                Join thousands who've already ditched the morning chaos.
            </p>
            <div class="mt-8">
                <a href="/auth/register.php" class="btn-primary ripple">
                    Start for Free <i class="fas fa-arrow-right ml-2"></i>
                </a>
            </div>
        </div>
    </div>

    <script>
        // Pass PHP variables to JavaScript
        const isLoggedIn = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;
    </script>
    <script src="/assets/js/landing.js"></script><!-- Keep landing-specific JS -->
    
<?php include __DIR__ . '/includes/page-footer.php'; ?> 