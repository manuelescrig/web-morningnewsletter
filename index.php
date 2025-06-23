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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MorningNewsletter.com - Your Personalized Morning Brief</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #ffffff 0%, #f3f4f6 100%);
        }
        .trusted-by-logos {
            filter: grayscale(100%);
            opacity: 0.5;
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
        .mesh-bg {
            background: radial-gradient(circle at 25% 25%, #e0e7ff 0%, transparent 50%),
                        radial-gradient(circle at 75% 25%, #f3e8ff 0%, transparent 50%),
                        radial-gradient(circle at 25% 75%, #dbeafe 0%, transparent 50%),
                        radial-gradient(circle at 75% 75%, #fce7f3 0%, transparent 50%),
                        radial-gradient(circle at 50% 50%, #f8fafc 0%, transparent 50%);
        }
    </style>
</head>
<body class="bg-white">
    <?php include __DIR__ . '/includes/navigation.php'; ?>

    <!-- Hero Section -->
    <div class="relative mesh-bg py-24 sm:py-32 lg:py-40 overflow-hidden">
        <div class="mx-auto max-w-7xl px-6 lg:px-8 relative z-10">
            <div class="mx-auto max-w-4xl text-center">
                <div class="mb-8">
                    <span class="inline-flex items-center rounded-full bg-white/60 backdrop-blur-sm px-3 py-1 text-sm font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10">
                        <i class="fas fa-sparkles mr-2"></i>
                        AI-Powered Content Curation
                    </span>
                </div>
                <h1 class="text-4xl font-bold tracking-tight text-gray-900 sm:text-6xl lg:text-7xl">
                    Your Personalized 
                    <span class="gradient-text">
                        Morning Brief
                    </span>
                </h1>
                <p class="mt-6 text-lg leading-8 text-gray-700 sm:text-xl max-w-3xl mx-auto">
                    Start your day with clarity. Get everything that matters—KPIs, market updates, weather, news, and messages—delivered in one beautiful email.
                </p>
                <div class="mt-10 flex items-center justify-center gap-x-6">
                    <a href="#pricing" class="group relative inline-flex items-center justify-center px-8 py-4 text-lg font-semibold text-white bg-gradient-to-r from-blue-600 to-purple-600 rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 hover:scale-105">
                        See Pricing
                        <i class="fas fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform duration-200"></i>
                    </a>
                    <a href="#features" class="inline-flex items-center text-lg font-semibold text-gray-900 hover:text-blue-600 transition-colors duration-200">
                        See how it works
                        <i class="fas fa-play-circle ml-2"></i>
                    </a>
                </div>
                <div class="mt-12 flex items-center justify-center gap-x-8 text-sm text-gray-600">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle text-green-500 mr-2"></i>
                        Plans starting at $5/mo
                    </div>
                    <div class="flex items-center">
                        <i class="fas fa-check-circle text-green-500 mr-2"></i>
                        Cancel anytime
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Mesh overlay elements -->
        <div class="absolute inset-0 -z-10">
            <div class="absolute top-20 left-20 w-96 h-96 bg-gradient-to-br from-blue-200/40 to-purple-200/40 rounded-full blur-3xl"></div>
            <div class="absolute bottom-20 right-20 w-96 h-96 bg-gradient-to-br from-purple-200/40 to-pink-200/40 rounded-full blur-3xl"></div>
            <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-[800px] h-[600px] bg-gradient-to-br from-blue-100/30 to-purple-100/30 rounded-full blur-3xl"></div>
        </div>
    </div>

    <!-- Problem Agitation Section -->
    <div class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h2 class="text-3xl font-extrabold text-gray-900 sm:text-4xl">
                    Tired of Information Overload?
                </h2>
                <p class="mt-4 text-xl text-gray-500">
                    Every morning, you're drowning in a sea of notifications, emails, and updates.
                </p>
            </div>
            <div class="mt-16 grid grid-cols-1 gap-8 md:grid-cols-3">
                <div class="bg-gray-50 rounded-2xl p-8">
                    <div class="flex items-center justify-center h-12 w-12 rounded-md bg-purple-100 text-purple-600 mb-4">
                        <i class="fas fa-clock text-xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Wasting Precious Time</h3>
                    <p class="text-gray-600">Spending 30+ minutes every morning checking multiple apps and platforms for updates.</p>
                </div>
                <div class="bg-gray-50 rounded-2xl p-8">
                    <div class="flex items-center justify-center h-12 w-12 rounded-md bg-purple-100 text-purple-600 mb-4">
                        <i class="fas fa-exclamation-triangle text-xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Missing Important Updates</h3>
                    <p class="text-gray-600">Critical information gets lost in the noise of countless notifications and messages.</p>
                </div>
                <div class="bg-gray-50 rounded-2xl p-8">
                    <div class="flex items-center justify-center h-12 w-12 rounded-md bg-purple-100 text-purple-600 mb-4">
                        <i class="fas fa-bolt text-xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Starting Your Day Stressed</h3>
                    <p class="text-gray-600">Feeling overwhelmed before your day even begins, affecting your productivity.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Transformation Section -->
    <div class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h2 class="text-3xl font-extrabold text-gray-900 sm:text-4xl">
                    Transform Your Morning Routine
                </h2>
                <p class="mt-4 text-xl text-gray-500">
                    Start your day with clarity and confidence
                </p>
            </div>
            <div class="mt-16 grid grid-cols-1 gap-8 md:grid-cols-2">
                <div class="bg-white rounded-2xl p-8 shadow-lg">
                    <h3 class="text-2xl font-bold text-gray-900 mb-4">Before MorningNewsletter</h3>
                    <ul class="space-y-4">
                        <li class="flex items-start">
                            <i class="fas fa-times text-red-500 mt-1 mr-3"></i>
                            <p class="text-gray-600">Scattered information across multiple platforms</p>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-times text-red-500 mt-1 mr-3"></i>
                            <p class="text-gray-600">Missed important updates and messages</p>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-times text-red-500 mt-1 mr-3"></i>
                            <p class="text-gray-600">Wasted time checking different apps</p>
                        </li>
                    </ul>
                </div>
                <div class="bg-white rounded-2xl p-8 shadow-lg">
                    <h3 class="text-2xl font-bold text-gray-900 mb-4">After MorningNewsletter</h3>
                    <ul class="space-y-4">
                        <li class="flex items-start">
                            <i class="fas fa-check text-green-500 mt-1 mr-3"></i>
                            <p class="text-gray-600">One concise email with everything you need</p>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check text-green-500 mt-1 mr-3"></i>
                            <p class="text-gray-600">Never miss important updates</p>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check text-green-500 mt-1 mr-3"></i>
                            <p class="text-gray-600">Start your day focused and productive</p>
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
                <p class="text-gray-500 mb-2">Trusted by <?php echo $displayCount; ?> professionals worldwide</p>
                <?php if ($todayCount > 0): ?>
                    <p class="text-sm text-blue-600 font-medium">
                        <i class="fas fa-user-plus mr-1"></i>
                        <?php echo $todayCount; ?> <?php echo $todayCount === 1 ? 'professional joined' : 'professionals joined'; ?> today
                    </p>
                <?php endif; ?>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 items-center justify-items-center">
                <img src="https://via.placeholder.com/150x50?text=Company+1" alt="Company 1" class="trusted-by-logos h-8">
                <img src="https://via.placeholder.com/150x50?text=Company+2" alt="Company 2" class="trusted-by-logos h-8">
                <img src="https://via.placeholder.com/150x50?text=Company+3" alt="Company 3" class="trusted-by-logos h-8">
                <img src="https://via.placeholder.com/150x50?text=Company+4" alt="Company 4" class="trusted-by-logos h-8">
            </div>
        </div>
    </div>

    <!-- Features Section -->
    <div id="features" class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h2 class="text-base text-blue-600 font-semibold tracking-wide uppercase">Features</h2>
                <p class="mt-2 text-3xl leading-8 font-extrabold tracking-tight text-gray-900 sm:text-4xl">
                    Everything you need to start your day
                </p>
            </div>

            <div class="mt-20">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
                    <!-- Feature 1 -->
                    <div class="bg-white rounded-2xl p-8 shadow-lg hover:shadow-xl transition-shadow">
                        <div class="flex items-center mb-4">
                            <div class="flex-shrink-0">
                                <div class="flex items-center justify-center h-12 w-12 rounded-md bg-blue-500 text-white">
                                    <i class="fas fa-chart-line text-xl"></i>
                                </div>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-xl font-bold text-gray-900">Custom KPIs</h3>
                            </div>
                        </div>
                        <p class="mt-4 text-gray-600">
                            Track your business metrics and KPIs with customizable dashboards and alerts. Get instant insights into your business performance.
                        </p>
                    </div>

                    <!-- Feature 2 -->
                    <div class="bg-white rounded-2xl p-8 shadow-lg hover:shadow-xl transition-shadow">
                        <div class="flex items-center mb-4">
                            <div class="flex-shrink-0">
                                <div class="flex items-center justify-center h-12 w-12 rounded-md bg-blue-500 text-white">
                                    <i class="fas fa-coins text-xl"></i>
                                </div>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-xl font-bold text-gray-900">Finance & Crypto Markets</h3>
                            </div>
                        </div>
                        <p class="mt-4 text-gray-600">
                            Stay updated with market movements, crypto prices, and financial news. Make informed decisions with real-time market data.
                        </p>
                    </div>

                    <!-- Feature 3 -->
                    <div class="bg-white rounded-2xl p-8 shadow-lg hover:shadow-xl transition-shadow">
                        <div class="flex items-center mb-4">
                            <div class="flex-shrink-0">
                                <div class="flex items-center justify-center h-12 w-12 rounded-md bg-blue-500 text-white">
                                    <i class="fas fa-cloud-sun text-xl"></i>
                                </div>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-xl font-bold text-gray-900">Weather & Local News</h3>
                            </div>
                        </div>
                        <p class="mt-4 text-gray-600">
                            Get your local weather forecast and breaking news updates. Stay informed about what matters in your area.
                        </p>
                    </div>

                    <!-- Feature 4 -->
                    <div class="bg-white rounded-2xl p-8 shadow-lg hover:shadow-xl transition-shadow">
                        <div class="flex items-center mb-4">
                            <div class="flex-shrink-0">
                                <div class="flex items-center justify-center h-12 w-12 rounded-md bg-blue-500 text-white">
                                    <i class="fas fa-comments text-xl"></i>
                                </div>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-xl font-bold text-gray-900">Social Media DMs</h3>
                            </div>
                        </div>
                        <p class="mt-4 text-gray-600">
                            Never miss important messages from Twitter, Slack, and Discord. Stay connected with your team and community.
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
                <h2 class="text-base text-blue-600 font-semibold tracking-wide uppercase">Pricing</h2>
                <p class="mt-2 text-3xl leading-8 font-extrabold tracking-tight text-gray-900 sm:text-4xl">
                    Simple, transparent pricing
                </p>
                <p class="mt-4 text-xl text-gray-500">
                    Choose the plan that best fits your needs
                </p>
            </div>

            <div class="mt-16 grid grid-cols-1 gap-8 md:grid-cols-3">
                <!-- Starter Tier -->
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden hover:shadow-2xl hover:scale-105 transition-all duration-300 h-full flex flex-col">
                    <div class="px-6 py-8 flex-1 flex flex-col">
                        <h3 class="text-2xl font-bold text-gray-900 mb-4">Starter</h3>
                        <p class="mt-4 text-gray-500">Ideal for personal use or trying out the service</p>
                        <p class="mt-8">
                            <span class="text-4xl font-extrabold text-gray-900">$5</span>
                            <span class="text-base font-medium text-gray-500">/month</span>
                        </p>
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
                            <a href="https://buy.stripe.com/9B6aEX00yg9lgwEfiG0Ba00" class="block w-full bg-gradient-to-r from-purple-600 to-blue-600 hover:from-purple-700 hover:to-blue-700 text-white font-medium rounded-lg text-sm px-5 py-2.5 text-center transition-all duration-300">
                                Subscribe Now
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Pro Tier -->
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden hover:shadow-2xl hover:scale-105 transition-all duration-300 border-2 border-purple-400 relative h-full flex flex-col">
                    <div class="absolute -top-4 left-1/2 transform -translate-x-1/2 z-10">
                        <span class="inline-flex rounded-full bg-gradient-to-r from-purple-600 to-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-lg">
                            Popular
                        </span>
                    </div>
                    <div class="px-6 py-8 pt-12 flex-1 flex flex-col">
                        <h3 class="text-2xl font-bold text-gray-900 mb-4">Pro</h3>
                        <p class="mt-4 text-gray-500">Great for professionals who want more control</p>
                        <p class="mt-8">
                            <span class="text-4xl font-extrabold text-gray-900">$15</span>
                            <span class="text-base font-medium text-gray-500">/month</span>
                        </p>
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
                            <a href="https://buy.stripe.com/bJe7sLdRoaP1gwE0nM0Ba01" class="block w-full bg-gradient-to-r from-purple-600 to-blue-600 hover:from-purple-700 hover:to-blue-700 text-white font-medium rounded-lg text-sm px-5 py-2.5 text-center transition-all duration-300">
                                Subscribe Now
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Unlimited Tier -->
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden hover:shadow-2xl hover:scale-105 transition-all duration-300 h-full flex flex-col">
                    <div class="px-6 py-8 flex-1 flex flex-col">
                        <h3 class="text-2xl font-bold text-gray-900 mb-4">Unlimited</h3>
                        <p class="mt-4 text-gray-500">Perfect for power users, teams, or content curators</p>
                        <p class="mt-8">
                            <span class="text-4xl font-extrabold text-gray-900">$19</span>
                            <span class="text-base font-medium text-gray-500">/month</span>
                        </p>
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
                            <a href="https://buy.stripe.com/aFa00jfZw6yLdks9Ym0Ba02" class="block w-full bg-gradient-to-r from-purple-600 to-blue-600 hover:from-purple-700 hover:to-blue-700 text-white font-medium rounded-lg text-sm px-5 py-2.5 text-center transition-all duration-300">
                                Subscribe Now
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
                <h2 class="text-base text-blue-600 font-semibold tracking-wide uppercase">Testimonials</h2>
                <p class="mt-2 text-3xl leading-8 font-extrabold tracking-tight text-gray-900 sm:text-4xl">
                    Loved by professionals worldwide
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
                        <p class="text-gray-600">"MorningNewsletter has transformed how I start my day. Having all my important updates in one place saves me countless hours."</p>
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
                        <p class="text-gray-600">"The KPI tracking feature is a game-changer. I can now monitor all my business metrics without logging into multiple platforms."</p>
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
                        <p class="text-gray-600">"As a crypto trader, having market updates and news in my morning brief helps me make better trading decisions."</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- FAQ Section -->
    <div class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h2 class="text-base text-blue-600 font-semibold tracking-wide uppercase">FAQ</h2>
                <p class="mt-2 text-3xl leading-8 font-extrabold tracking-tight text-gray-900 sm:text-4xl">
                    Frequently Asked Questions
                </p>
            </div>

            <div class="mt-16 max-w-3xl mx-auto">
                <dl class="space-y-8">
                    <div>
                        <dt class="text-lg font-medium text-gray-900">What time is the newsletter delivered?</dt>
                        <dd class="mt-2 text-gray-600">The newsletter is delivered to your inbox every morning at 6 AM in your local timezone.</dd>
                    </div>
                    <div>
                        <dt class="text-lg font-medium text-gray-900">Can I customize what information I receive?</dt>
                        <dd class="mt-2 text-gray-600">Yes! You can customize your preferences in your dashboard to receive exactly the information that matters to you.</dd>
                    </div>
                    <div>
                        <dt class="text-lg font-medium text-gray-900">How do you handle my data?</dt>
                        <dd class="mt-2 text-gray-600">We take data security seriously. All your data is encrypted and we never share it with third parties. Read our privacy policy for more details.</dd>
                    </div>
                    <div>
                        <dt class="text-lg font-medium text-gray-900">Can I cancel my subscription anytime?</dt>
                        <dd class="mt-2 text-gray-600">Yes, you can cancel your subscription at any time. There are no long-term commitments required.</dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>

    <!-- Final CTA Section -->
    <div class="gradient-bg py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl font-extrabold text-gray-900 sm:text-4xl">
                Ready to transform your morning routine?
            </h2>
            <p class="mt-4 text-xl text-gray-600">
                Join thousands of professionals who start their day with MorningNewsletter.
            </p>
            <div class="mt-8">
                <a href="#pricing" class="inline-flex items-center text-white bg-gradient-to-br from-purple-600 to-blue-500 hover:bg-gradient-to-bl focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg px-8 py-4 text-center md:py-4 md:text-lg md:px-10">
                    Choose Your Plan<i class="fas fa-arrow-up ml-2"></i>
                </a>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html> 