<?php
/**
 * Shared Navigation Component for Dashboard Pages
 * 
 * Usage: 
 * $currentPage = 'dashboard'; // or 'sources', 'schedule', 'settings', 'history', 'cron_status'
 * include __DIR__ . '/includes/navigation.php';
 */

if (!isset($user)) {
    throw new Exception('$user variable must be set before including navigation');
}

if (!isset($currentPage)) {
    $currentPage = 'dashboard';
}

require_once __DIR__ . '/../../includes/logo.php';

function getNavClass($page, $currentPage) {
    // Treat 'newsletter', 'newsletters', and 'dashboard' as the same for "My Newsletters" nav item
    if ($page === 'dashboard' && in_array($currentPage, ['newsletter', 'newsletters', 'dashboard'])) {
        return 'nav-tab-active inline-flex items-center px-6 py-4 text-sm transition-all duration-200';
    }
    
    if ($page === $currentPage) {
        return 'nav-tab-active inline-flex items-center px-6 py-4 text-sm transition-all duration-200';
    }
    return 'nav-tab-inactive inline-flex items-center px-6 py-4 text-sm transition-all duration-200';
}
?>
<!-- Navigation -->
<nav class="bg-white shadow-sm border-b">
    <div class="max-w-7xl mx-auto px-4">
        <div class="flex justify-between items-center h-16">
            <!-- Logo -->
            <div class="flex items-center">
<?php renderLogo('md'); ?>
            </div>
            
            <!-- Centered Navigation Pills (Desktop) -->
            <div class="absolute left-1/2 transform -translate-x-1/2 hidden sm:flex space-x-3">
                <a href="/dashboard/" class="<?php echo getNavClass('dashboard', $currentPage); ?>">
                    <i class="lucide lucide-newspaper mr-2 w-4 h-4"></i>My Newsletters
                </a>
                <a href="/dashboard/history.php" class="<?php echo getNavClass('history', $currentPage); ?>">
                    <i class="lucide lucide-archive mr-2 w-4 h-4"></i>History
                </a>
            </div>
            
            <!-- Mobile Navigation Pills -->
            <div class="flex sm:hidden space-x-2">
                <a href="/dashboard/" class="<?php echo getNavClass('dashboard', $currentPage); ?>">
                    <i class="lucide lucide-newspaper w-4 h-4"></i>
                    <span class="sr-only">My Newsletters</span>
                </a>
                <a href="/dashboard/history.php" class="<?php echo getNavClass('history', $currentPage); ?>">
                    <i class="lucide lucide-archive w-4 h-4"></i>
                    <span class="sr-only">History</span>
                </a>
            </div>
            
            <!-- Profile Section -->
            <div class="relative flex items-center">
                <!-- Profile dropdown -->
                <div class="relative ml-3">
                    <div>
                        <button type="button" class="relative flex items-center max-w-xs rounded-full bg-white text-sm focus:outline-none focus:ring-2 focus-ring-primary focus:ring-offset-2 lg:px-2 lg:py-1 lg:rounded-md lg:hover:bg-gray-50 transition-colors" id="user-menu-button" aria-expanded="false" aria-haspopup="true">
                            <div class="flex items-center">
                                <!-- Avatar circle with initial -->
                                <div class="flex-shrink-0 h-8 w-8 rounded-full bg-primary-lightest flex items-center justify-center">
                                    <span class="text-sm font-medium text-primary-darker">
                                        <?php echo strtoupper(substr($user->getEmail(), 0, 1)); ?>
                                    </span>
                                </div>
                                <!-- User info (hidden on mobile, shown on desktop) -->
                                <div class="hidden lg:ml-3 lg:block text-left">
                                    <div class="text-sm font-medium text-gray-900 text-left">
                                        <?php echo htmlspecialchars($user->getName() ?: $user->getEmail()); ?>
                                    </div>
                                    <div class="text-xs text-gray-500 capitalize text-left"><?php echo $user->getPlan(); ?> Plan</div>
                                </div>
                                <!-- Dropdown arrow -->
                                <div class="hidden lg:ml-2 lg:flex-shrink-0 lg:block">
                                    <i class="fas fa-chevron-down text-gray-400 text-xs"></i>
                                </div>
                            </div>
                        </button>
                    </div>

                    <!-- Dropdown menu -->
                    <div id="dropdown-menu" class="hidden absolute right-0 z-10 mt-2 w-48 origin-top-right rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none" role="menu" aria-orientation="vertical" aria-labelledby="user-menu-button" tabindex="-1">
                        <!-- User info (shown on mobile) -->
                        <div class="block lg:hidden px-4 py-2 border-b border-gray-200">
                            <div class="text-sm font-medium text-gray-900">
                                <?php echo htmlspecialchars($user->getName() ?: $user->getEmail()); ?>
                            </div>
                            <div class="text-xs text-gray-500 capitalize"><?php echo $user->getPlan(); ?> Plan</div>
                        </div>
                        
                        <!-- Account option -->
                        <a href="/account" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem" tabindex="-1">
                            <i class="fas fa-user mr-2 text-primary"></i>
                            Account
                        </a>
                        
                        <!-- Billing/Upgrade option -->
                        <?php if ($user->getPlan() === 'free'): ?>
                        <a href="/upgrade" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem" tabindex="-1">
                            <i class="fas fa-crown mr-2 text-primary"></i>
                            Upgrade Plan
                        </a>
                        <?php else: ?>
                        <a href="/billing" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem" tabindex="-1">
                            <i class="fas fa-credit-card mr-2 text-primary"></i>
                            Billing
                        </a>
                        <?php endif; ?>
                        
                        <!-- Admin options -->
                        <?php if ($user->isAdmin()): ?>
                        <!-- Divider for admin section -->
                        <div class="border-t border-gray-200 my-1"></div>
                        
                        <a href="/dashboard/users.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem" tabindex="-1">
                            <i class="fas fa-users mr-2 text-purple-500"></i>
                            Users
                        </a>
                        <a href="/dashboard/sources.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem" tabindex="-1">
                            <i class="fas fa-plug mr-2 text-purple-500"></i>
                            Source Config
                        </a>
                        <a href="/dashboard/cron_status.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem" tabindex="-1">
                            <i class="fas fa-server mr-2 text-purple-500"></i>
                            Cron Status
                        </a>
                        <?php endif; ?>
                        
                        <!-- Divider -->
                        <div class="border-t border-gray-200 my-1"></div>
                        
                        <!-- Logout option -->
                        <a href="/auth/logout.php" class="block px-4 py-2 text-sm text-red-700 hover:bg-red-50 hover:text-red-900" role="menuitem" tabindex="-1">
                            <i class="fas fa-sign-out-alt mr-2 text-red-500"></i>
                            Sign out
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>

