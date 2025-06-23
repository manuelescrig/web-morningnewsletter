<?php
// Navigation component for public pages
// Include this file after checking auth status and setting $isLoggedIn and $user variables

if (!isset($isLoggedIn)) {
    require_once __DIR__ . '/../core/Auth.php';
    $auth = Auth::getInstance();
    $isLoggedIn = $auth->isLoggedIn();
    $user = $isLoggedIn ? $auth->getCurrentUser() : null;
}

require_once __DIR__ . '/logo.php';
?>

<!-- Navigation -->
<nav id="main-nav" class="bg-white/80 backdrop-blur-md fixed w-full z-50 transition-all duration-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16 items-center">
            <div class="flex-shrink-0">
                <?php renderLogo('md'); ?>
            </div>
            <div class="hidden md:block flex-1">
                <div class="flex justify-center">
                    <div class="flex space-x-8">
                        <a href="/#features" class="text-gray-700 hover:text-blue-600 px-3 py-2">Features</a>
                        <a href="/#pricing" class="text-gray-700 hover:text-blue-600 px-3 py-2">Pricing</a>
                    </div>
                </div>
            </div>
            <div class="hidden md:block">
                <div class="flex items-center space-x-4">
                    <?php if ($isLoggedIn): ?>
                        <a href="/dashboard/" class="text-white bg-gradient-to-br from-purple-600 to-blue-500 hover:bg-gradient-to-bl focus:ring-4 focus:outline-none focus:ring-blue-300 dark:focus:ring-blue-800 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                            Go to Dashboard
                        </a>
                    <?php else: ?>
                        <a href="/auth/login.php" class="px-4 py-2 rounded-md hover:bg-blue-700">Log In</a>
                        <a href="#pricing" class="text-white bg-gradient-to-br from-purple-600 to-blue-500 hover:bg-gradient-to-bl focus:ring-4 focus:outline-none focus:ring-blue-300 dark:focus:ring-blue-800 font-medium rounded-lg text-sm px-5 py-2.5 text-center">Get Started</a>
                    <?php endif; ?>
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