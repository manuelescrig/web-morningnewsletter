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
<nav id="main-nav" class="nav-container fixed w-full z-50 transition-all duration-300">
    <div class="nav-inner mx-auto">
        <div class="flex justify-between h-16 items-center px-6">
            <!-- Logo -->
            <div class="flex-shrink-0">
                <?php renderLogo('md'); ?>
            </div>
            
            <!-- Navigation Links -->
            <div class="hidden md:block flex-1">
                <div class="flex justify-center">
                    <div class="flex space-x-4">
                        <a href="/#features" 
                           class="text-gray-700 hover:text-primary px-3 py-2 transition-all duration-200">
                            Features
                        </a>
                        <a href="/#pricing" 
                           class="text-gray-700 hover:text-primary px-3 py-2 transition-all duration-200">
                            Pricing
                        </a>
                        <a href="/blog" 
                           class="text-gray-700 hover:text-primary px-3 py-2 transition-all duration-200">
                            Blog
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="hidden md:block">
                <div class="flex items-center space-x-4">
                    <?php if ($isLoggedIn): ?>
                        <a href="/dashboard/" 
                           class="btn-primary">
                            Go to Dashboard
                        </a>
                    <?php else: ?>
                        <a href="/auth/login.php" 
                           class="text-gray-700 hover:text-primary px-3 py-2 transition-all duration-200">
                            Log In
                        </a>
                        <a href="#pricing" 
                           class="btn-primary">
                            Get Started
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</nav>