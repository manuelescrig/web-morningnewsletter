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
    <div class="py-12 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-8">
                <p class="text-gray-600 font-medium mb-4">Featured in</p>
                <p class="text-gray-500 mb-2">Trusted by <?php echo $displayCount; ?> professionals worldwide</p>
                <?php if ($todayCount > 0): ?>
                    <p class="text-sm text-primary font-medium">
                        <i class="fas fa-user-plus mr-1"></i>
                        <?php echo $todayCount; ?> <?php echo $todayCount === 1 ? 'professional joined' : 'professionals joined'; ?> today
                    </p>
                <?php endif; ?>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-7 gap-6 items-center justify-items-center max-w-5xl mx-auto">
                <img src="/assets/companies/Logo=google.svg" alt="Google" class="trusted-by-logos h-8 opacity-60">
                <img src="/assets/companies/Logo=microsoft.svg" alt="Microsoft" class="trusted-by-logos h-8 opacity-60">
                <img src="/assets/companies/Logo=instagram_word.svg" alt="Instagram" class="trusted-by-logos h-8 opacity-60">
                <img src="/assets/companies/Press logo=Bloomberg.svg" alt="Bloomberg" class="trusted-by-logos h-8 opacity-60">
                <img src="/assets/companies/Press logo=Business Insider.svg" alt="Business Insider" class="trusted-by-logos h-8 opacity-60">
                <img src="/assets/companies/Press logo=The Guardian.svg" alt="The Guardian" class="trusted-by-logos h-8 opacity-60">
                <img src="/assets/companies/Press logo=The New York Times (TNYT).svg" alt="The New York Times" class="trusted-by-logos h-8 opacity-60">
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

            <div class="mt-20">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
                    <!-- Feature 1 -->
                    <div class="bg-white rounded-2xl p-8 shadow-lg hover:shadow-xl transition-shadow">
                        <div class="flex items-center mb-4">
                            <div class="flex-shrink-0">
                                <div class="flex items-center justify-center h-12 w-12 rounded-md bg-primary text-white">
                                    <i class="fas fa-chart-line text-xl"></i>
                                </div>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-xl font-bold text-gray-900">Custom KPIs</h3>
                            </div>
                        </div>
                        <p class="mt-4 text-gray-600">
                            Your revenue, user stats, conversion rates—whatever metrics matter to you. We'll fetch them and serve them fresh.
                        </p>
                    </div>

                    <!-- Feature 2 -->
                    <div class="bg-white rounded-2xl p-8 shadow-lg hover:shadow-xl transition-shadow">
                        <div class="flex items-center mb-4">
                            <div class="flex-shrink-0">
                                <div class="flex items-center justify-center h-12 w-12 rounded-md bg-primary text-white">
                                    <i class="fas fa-coins text-xl"></i>
                                </div>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-xl font-bold text-gray-900">Finance & Crypto Markets</h3>
                            </div>
                        </div>
                        <p class="mt-4 text-gray-600">
                            Bitcoin pumping? Stocks dumping? We've got you covered with real-time updates on everything you're tracking.
                        </p>
                    </div>

                    <!-- Feature 3 -->
                    <div class="bg-white rounded-2xl p-8 shadow-lg hover:shadow-xl transition-shadow">
                        <div class="flex items-center mb-4">
                            <div class="flex-shrink-0">
                                <div class="flex items-center justify-center h-12 w-12 rounded-md bg-primary text-white">
                                    <i class="fas fa-cloud-sun text-xl"></i>
                                </div>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-xl font-bold text-gray-900">Weather & Local News</h3>
                            </div>
                        </div>
                        <p class="mt-4 text-gray-600">
                            Should you grab an umbrella? What's happening in your city? We'll let you know so you can plan your day.
                        </p>
                    </div>

                    <!-- Feature 4 -->
                    <div class="bg-white rounded-2xl p-8 shadow-lg hover:shadow-xl transition-shadow">
                        <div class="flex items-center mb-4">
                            <div class="flex-shrink-0">
                                <div class="flex items-center justify-center h-12 w-12 rounded-md bg-primary text-white">
                                    <i class="fas fa-comments text-xl"></i>
                                </div>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-xl font-bold text-gray-900">Social Media DMs</h3>
                            </div>
                        </div>
                        <p class="mt-4 text-gray-600">
                            Important DMs from Twitter, Slack messages, Discord pings—we'll make sure nothing important slips through the cracks.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pricing Section -->
    <div id="pricing" class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h2 class="text-base text-primary font-semibold tracking-wide uppercase">Pricing</h2>
                <p class="mt-2 text-3xl leading-8 font-extrabold tracking-tight text-gray-900 sm:text-4xl">
                    Pricing that makes sense
                </p>
                <p class="mt-4 text-xl text-gray-500">
                    Pick what works for you. No hidden fees, no BS.
                </p>
            </div>

            <div class="mt-16 grid grid-cols-1 gap-8 md:grid-cols-3">
                <!-- Starter Tier -->
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden hover:shadow-2xl hover:scale-105 transition-all duration-300 h-full flex flex-col mt-6">
                    <div class="px-6 py-8 flex-1 flex flex-col">
                        <h3 class="text-2xl font-bold text-gray-900 mb-4">Starter</h3>
                        <p class="mt-4 text-gray-500">Ideal for personal use or trying out the service</p>
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
                                    <i class="fas fa-check text-purple-600"></i>
                                </div>
                                <p class="ml-3 text-base text-gray-500">Up to 5 sources</p>
                            </li>
                            <li class="flex items-start">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-check text-purple-600"></i>
                                </div>
                                <p class="ml-3 text-base text-gray-500">Basic scheduling & customization</p>
                            </li>
                            <li class="flex items-start">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-check text-purple-600"></i>
                                </div>
                                <p class="ml-3 text-base text-gray-500">Daily newsletter delivery</p>
                            </li>
                        </ul>
                        <div class="mt-8">
                            <a href="/auth/register.php" class="btn-primary block w-full text-center">
                                Start for Free
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Pro Tier -->
                <div class="bg-white rounded-2xl shadow-lg hover:shadow-2xl hover:scale-105 transition-all duration-300 border-2 border-purple-400 relative h-full flex flex-col mt-6">
                    <span class="popular-tag">
                        Popular
                    </span>
                    <div class="px-6 py-8 pt-10 flex-1 flex flex-col">
                        <h3 class="text-2xl font-bold text-gray-900 mb-4">Pro</h3>
                        <p class="mt-4 text-gray-500">Great for professionals who want more control</p>
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
                                <p class="ml-3 text-base text-gray-500">Custom layouts</p>
                            </li>
                            <li class="flex items-start">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-check text-purple-600"></i>
                                </div>
                                <p class="ml-3 text-base text-gray-500">Priority email support</p>
                            </li>
                        </ul>
                        <div class="mt-8">
                            <a href="/auth/register.php" class="btn-primary block w-full text-center">
                                Start for Free
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Unlimited Tier -->
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden hover:shadow-2xl hover:scale-105 transition-all duration-300 h-full flex flex-col mt-6">
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
                                    <i class="fas fa-check text-purple-600"></i>
                                </div>
                                <p class="ml-3 text-base text-gray-500">Unlimited sources</p>
                            </li>
                            <li class="flex items-start">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-check text-purple-600"></i>
                                </div>
                                <p class="ml-3 text-base text-gray-500">All features included</p>
                            </li>
                            <li class="flex items-start">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-check text-purple-600"></i>
                                </div>
                                <p class="ml-3 text-base text-gray-500">Priority support</p>
                            </li>
                            <li class="flex items-start">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-check text-purple-600"></i>
                                </div>
                                <p class="ml-3 text-base text-gray-500">Team collaboration</p>
                            </li>
                        </ul>
                        <div class="mt-8">
                            <a href="/auth/register.php" class="btn-primary block w-full text-center">
                                Start for Free
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Testimonials Section -->
    <div id="testimonials" class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h2 class="text-base text-primary font-semibold tracking-wide uppercase">Testimonials</h2>
                <p class="mt-2 text-3xl leading-8 font-extrabold tracking-tight text-gray-900 sm:text-4xl">
                    Real people, real results
                </p>
            </div>

            <div class="mt-20">
                <div class="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-3">
                    <!-- Testimonial 1 -->
                    <div class="bg-white rounded-2xl p-8">
                        <div class="flex items-center mb-4">
                            <img class="h-12 w-12 rounded-full" src="https://ui-avatars.com/api/?name=John+Doe&background=random" alt="John Doe">
                            <div class="ml-4">
                                <h4 class="text-lg font-bold">John Doe</h4>
                                <p class="text-gray-600">CEO, TechCorp</p>
                            </div>
                        </div>
                        <p class="text-gray-600">"I used to spend 45 minutes every morning checking different apps. Now? 5 minutes with my coffee and I'm good to go."</p>
                    </div>

                    <!-- Testimonial 2 -->
                    <div class="bg-white rounded-2xl p-8">
                        <div class="flex items-center mb-4">
                            <img class="h-12 w-12 rounded-full" src="https://ui-avatars.com/api/?name=Jane+Smith&background=random" alt="Jane Smith">
                            <div class="ml-4">
                                <h4 class="text-lg font-bold">Jane Smith</h4>
                                <p class="text-gray-600">Product Manager</p>
                            </div>
                        </div>
                        <p class="text-gray-600">"My business metrics, crypto portfolio, and important messages all in one place? This is exactly what I needed."</p>
                    </div>

                    <!-- Testimonial 3 -->
                    <div class="bg-white rounded-2xl p-8">
                        <div class="flex items-center mb-4">
                            <img class="h-12 w-12 rounded-full" src="https://ui-avatars.com/api/?name=Mike+Johnson&background=random" alt="Mike Johnson">
                            <div class="ml-4">
                                <h4 class="text-lg font-bold">Mike Johnson</h4>
                                <p class="text-gray-600">Crypto Trader</p>
                            </div>
                        </div>
                        <p class="text-gray-600">"Caught the Bitcoin pump early thanks to my morning brief. This thing pays for itself."</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- FAQ Section -->
    <div class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h2 class="text-base text-primary font-semibold tracking-wide uppercase">FAQ</h2>
                <p class="mt-2 text-3xl leading-8 font-extrabold tracking-tight text-gray-900 sm:text-4xl">
                    Frequently Asked Questions
                </p>
            </div>

            <div class="mt-16 max-w-3xl mx-auto">
                <div class="space-y-4">
                    <div class="bg-gray-50 rounded-lg">
                        <button class="w-full px-6 py-4 text-left flex justify-between items-center hover:bg-gray-100 transition-colors duration-200 rounded-lg focus:outline-none focus:ring-2 focus-ring-primary" onclick="toggleFAQ(this)">
                            <span class="text-lg font-medium text-gray-900">What time is the newsletter delivered?</span>
                            <i class="fas fa-chevron-down text-gray-500 transition-transform duration-200"></i>
                        </button>
                        <div class="faq-answer px-6 pt-2 pb-4 text-gray-600" style="display: none;">
                            The newsletter is delivered to your inbox every morning at 6 AM in your local timezone.
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 rounded-lg">
                        <button class="w-full px-6 py-4 text-left flex justify-between items-center hover:bg-gray-100 transition-colors duration-200 rounded-lg focus:outline-none focus:ring-2 focus-ring-primary" onclick="toggleFAQ(this)">
                            <span class="text-lg font-medium text-gray-900">Can I customize what information I receive?</span>
                            <i class="fas fa-chevron-down text-gray-500 transition-transform duration-200"></i>
                        </button>
                        <div class="faq-answer px-6 pt-2 pb-4 text-gray-600" style="display: none;">
                            Yes! You can customize your preferences in your dashboard to receive exactly the information that matters to you.
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 rounded-lg">
                        <button class="w-full px-6 py-4 text-left flex justify-between items-center hover:bg-gray-100 transition-colors duration-200 rounded-lg focus:outline-none focus:ring-2 focus-ring-primary" onclick="toggleFAQ(this)">
                            <span class="text-lg font-medium text-gray-900">How do you handle my data?</span>
                            <i class="fas fa-chevron-down text-gray-500 transition-transform duration-200"></i>
                        </button>
                        <div class="faq-answer px-6 pt-2 pb-4 text-gray-600" style="display: none;">
                            We take data security seriously. All your data is encrypted and we never share it with third parties. Read our privacy policy for more details.
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 rounded-lg">
                        <button class="w-full px-6 py-4 text-left flex justify-between items-center hover:bg-gray-100 transition-colors duration-200 rounded-lg focus:outline-none focus:ring-2 focus-ring-primary" onclick="toggleFAQ(this)">
                            <span class="text-lg font-medium text-gray-900">Can I cancel my subscription anytime?</span>
                            <i class="fas fa-chevron-down text-gray-500 transition-transform duration-200"></i>
                        </button>
                        <div class="faq-answer px-6 pt-2 pb-4 text-gray-600" style="display: none;">
                            Yes, you can cancel your subscription at any time. There are no long-term commitments required.
                        </div>
                    </div>
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