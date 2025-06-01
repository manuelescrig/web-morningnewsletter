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
            background: linear-gradient(135deg, #4F46E5 0%, #7C3AED 100%);
        }
        .trusted-by-logos {
            filter: grayscale(100%);
            opacity: 0.5;
        }
        .nav-scrolled {
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }
    </style>
</head>
<body class="bg-white">
    <!-- Navigation -->
    <nav id="main-nav" class="bg-white/80 backdrop-blur-md fixed w-full z-50 transition-all duration-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <div class="flex-shrink-0">
                    <h1 class="text-2xl font-bold text-indigo-600">MorningNewsletter</h1>
                </div>
                <div class="hidden md:block flex-1">
                    <div class="flex justify-center">
                        <div class="flex space-x-8">
                            <a href="#features" class="text-gray-700 hover:text-indigo-600 px-3 py-2">Features</a>
                            <a href="#pricing" class="text-gray-700 hover:text-indigo-600 px-3 py-2">Pricing</a>
                        </div>
                    </div>
                </div>
                <div class="hidden md:block">
                    <div class="flex items-center space-x-4">
                        <a href="/signin.php" class="px-4 py-2 rounded-md hover:bg-indigo-700">Sign In</a>
                        <a href="/signup.php" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">Try for Free</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <script>
        window.addEventListener('scroll', function() {
            const nav = document.getElementById('main-nav');
            if (window.scrollY > 0) {
                nav.classList.add('nav-scrolled');
            } else {
                nav.classList.remove('nav-scrolled');
            }
        });
    </script>

    <!-- Hero Section -->
    <div class="gradient-bg pt-32 pb-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h1 class="text-4xl tracking-tight font-extrabold text-white sm:text-5xl md:text-6xl">
                    <span class="block">Your Personalized</span>
                    <span class="block">Morning Brief,</span>
                    <span class="block">Delivered Daily.</span>
                </h1>
                <p class="mt-6 text-xl text-indigo-100 max-w-3xl mx-auto">
                    One email. Everything you care about—KPI trends, finance, crypto, weather, news, and messages—in one place.
                </p>
                <div class="mt-10">
                    <a href="/signup.php" class="inline-flex items-center px-8 py-4 border border-transparent text-base font-medium rounded-md text-indigo-600 bg-white hover:bg-indigo-50 md:py-4 md:text-lg md:px-10">
                        Start Free Trial
                        <i class="fas fa-arrow-right ml-2"></i>
                    </a>
                </div>
            </div>
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
                    <div class="flex items-center justify-center h-12 w-12 rounded-md bg-red-100 text-red-600 mb-4">
                        <i class="fas fa-clock text-xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Wasting Precious Time</h3>
                    <p class="text-gray-600">Spending 30+ minutes every morning checking multiple apps and platforms for updates.</p>
                </div>
                <div class="bg-gray-50 rounded-2xl p-8">
                    <div class="flex items-center justify-center h-12 w-12 rounded-md bg-red-100 text-red-600 mb-4">
                        <i class="fas fa-exclamation-triangle text-xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Missing Important Updates</h3>
                    <p class="text-gray-600">Critical information gets lost in the noise of countless notifications and messages.</p>
                </div>
                <div class="bg-gray-50 rounded-2xl p-8">
                    <div class="flex items-center justify-center h-12 w-12 rounded-md bg-red-100 text-red-600 mb-4">
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
            <p class="text-center text-gray-500 mb-8">Trusted by 9000+ professionals worldwide</p>
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
                <h2 class="text-base text-indigo-600 font-semibold tracking-wide uppercase">Features</h2>
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
                                <div class="flex items-center justify-center h-12 w-12 rounded-md bg-indigo-500 text-white">
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
                                <div class="flex items-center justify-center h-12 w-12 rounded-md bg-indigo-500 text-white">
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
                                <div class="flex items-center justify-center h-12 w-12 rounded-md bg-indigo-500 text-white">
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
                                <div class="flex items-center justify-center h-12 w-12 rounded-md bg-indigo-500 text-white">
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
                <h2 class="text-base text-indigo-600 font-semibold tracking-wide uppercase">Pricing</h2>
                <p class="mt-2 text-3xl leading-8 font-extrabold tracking-tight text-gray-900 sm:text-4xl">
                    Simple, transparent pricing
                </p>
                <p class="mt-4 text-xl text-gray-500">
                    Choose the plan that best fits your needs
                </p>
            </div>

            <div class="mt-16 grid grid-cols-1 gap-8 md:grid-cols-3">
                <!-- Free Tier -->
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                    <div class="px-6 py-8">
                        <h3 class="text-2xl font-bold text-gray-900">Free</h3>
                        <p class="mt-4 text-gray-500">Perfect for trying out MorningNewsletter</p>
                        <p class="mt-8">
                            <span class="text-4xl font-extrabold text-gray-900">$0</span>
                            <span class="text-base font-medium text-gray-500">/month</span>
                        </p>
                        <ul class="mt-6 space-y-4">
                            <li class="flex items-start">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-check text-green-500"></i>
                                </div>
                                <p class="ml-3 text-base text-gray-500">Basic KPI tracking</p>
                            </li>
                            <li class="flex items-start">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-check text-green-500"></i>
                                </div>
                                <p class="ml-3 text-base text-gray-500">Weather updates</p>
                            </li>
                            <li class="flex items-start">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-check text-green-500"></i>
                                </div>
                                <p class="ml-3 text-base text-gray-500">Basic news feed</p>
                            </li>
                        </ul>
                        <div class="mt-8">
                            <a href="/signup.php" class="block w-full bg-gray-50 border border-gray-300 rounded-md py-2 text-sm font-semibold text-gray-700 text-center hover:bg-gray-100">
                                Get Started
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Pro Tier -->
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden border-2 border-indigo-500 relative">
                    <div class="absolute top-0 right-0 -mt-4 -mr-4">
                        <span class="inline-flex rounded-full bg-indigo-500 px-4 py-1 text-sm font-semibold text-white">
                            Popular
                        </span>
                    </div>
                    <div class="px-6 py-8">
                        <h3 class="text-2xl font-bold text-gray-900">Pro</h3>
                        <p class="mt-4 text-gray-500">For professionals who need more</p>
                        <p class="mt-8">
                            <span class="text-4xl font-extrabold text-gray-900">$29</span>
                            <span class="text-base font-medium text-gray-500">/month</span>
                        </p>
                        <ul class="mt-6 space-y-4">
                            <li class="flex items-start">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-check text-green-500"></i>
                                </div>
                                <p class="ml-3 text-base text-gray-500">Everything in Free</p>
                            </li>
                            <li class="flex items-start">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-check text-green-500"></i>
                                </div>
                                <p class="ml-3 text-base text-gray-500">Advanced KPI tracking</p>
                            </li>
                            <li class="flex items-start">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-check text-green-500"></i>
                                </div>
                                <p class="ml-3 text-base text-gray-500">Crypto & finance updates</p>
                            </li>
                            <li class="flex items-start">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-check text-green-500"></i>
                                </div>
                                <p class="ml-3 text-base text-gray-500">Social media integration</p>
                            </li>
                            <li class="flex items-start">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-check text-green-500"></i>
                                </div>
                                <p class="ml-3 text-base text-gray-500">Priority support</p>
                            </li>
                        </ul>
                        <div class="mt-8">
                            <a href="/signup.php" class="block w-full bg-indigo-600 border border-transparent rounded-md py-2 text-sm font-semibold text-white text-center hover:bg-indigo-700">
                                Start Free Trial
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Enterprise Tier -->
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                    <div class="px-6 py-8">
                        <h3 class="text-2xl font-bold text-gray-900">Enterprise</h3>
                        <p class="mt-4 text-gray-500">For teams and organizations</p>
                        <p class="mt-8">
                            <span class="text-4xl font-extrabold text-gray-900">$99</span>
                            <span class="text-base font-medium text-gray-500">/month</span>
                        </p>
                        <ul class="mt-6 space-y-4">
                            <li class="flex items-start">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-check text-green-500"></i>
                                </div>
                                <p class="ml-3 text-base text-gray-500">Everything in Pro</p>
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
                                <p class="ml-3 text-base text-gray-500">Custom integrations</p>
                            </li>
                            <li class="flex items-start">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-check text-green-500"></i>
                                </div>
                                <p class="ml-3 text-base text-gray-500">Dedicated support</p>
                            </li>
                            <li class="flex items-start">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-check text-green-500"></i>
                                </div>
                                <p class="ml-3 text-base text-gray-500">SLA guarantee</p>
                            </li>
                        </ul>
                        <div class="mt-8">
                            <a href="/signup.php" class="block w-full bg-gray-50 border border-gray-300 rounded-md py-2 text-sm font-semibold text-gray-700 text-center hover:bg-gray-100">
                                Contact Sales
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
                <h2 class="text-base text-indigo-600 font-semibold tracking-wide uppercase">Testimonials</h2>
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
                <h2 class="text-base text-indigo-600 font-semibold tracking-wide uppercase">FAQ</h2>
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
            <h2 class="text-3xl font-extrabold text-white sm:text-4xl">
                Ready to transform your morning routine?
            </h2>
            <p class="mt-4 text-xl text-indigo-100">
                Join thousands of professionals who start their day with MorningNewsletter.
            </p>
            <div class="mt-8">
                <a href="/signup.php" class="inline-flex items-center px-8 py-4 border border-transparent text-base font-medium rounded-md text-indigo-600 bg-white hover:bg-indigo-50 md:py-4 md:text-lg md:px-10">
                    Start Free Trial
                    <i class="fas fa-arrow-right ml-2"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-50">
        <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
            <div class="border-t border-gray-200 pt-8">
                <div class="flex flex-col md:flex-row justify-between items-center">
                    <div class="flex space-x-6 mb-4 md:mb-0">
                        <a href="#" class="text-gray-400 hover:text-gray-500">Privacy</a>
                        <a href="#" class="text-gray-400 hover:text-gray-500">Terms</a>
                        <a href="#" class="text-gray-400 hover:text-gray-500">Support</a>
                    </div>
                    <p class="text-gray-400">&copy; 2024 MorningNewsletter.com. All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>
</body>
</html> 