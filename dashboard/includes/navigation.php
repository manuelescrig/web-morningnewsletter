<?php
/**
 * Shared Navigation Component for Dashboard Pages
 * 
 * Usage: 
 * $currentPage = 'dashboard'; // or 'sources', 'schedule', 'settings', 'cron_status'
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
    if ($page === $currentPage) {
        return 'border-blue-500 text-gray-900 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium';
    }
    return 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium';
}
?>
<!-- Navigation -->
<nav class="bg-white shadow-sm border-b">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex items-center">
<?php renderLogo('md'); ?>
                <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                    <a href="/dashboard/" class="<?php echo getNavClass('dashboard', $currentPage); ?>">
                        Dashboard
                    </a>
                    <a href="/dashboard/sources.php" class="<?php echo getNavClass('sources', $currentPage); ?>">
                        Sources
                    </a>
                    <a href="/dashboard/schedule.php" class="<?php echo getNavClass('schedule', $currentPage); ?>">
                        Schedule
                    </a>
                    <?php if ($user->isAdmin()): ?>
                    <a href="/dashboard/users.php" class="<?php echo getNavClass('users', $currentPage); ?>">
                        Users
                    </a>
                    <a href="/dashboard/cron_status.php" class="<?php echo getNavClass('cron_status', $currentPage); ?>">
                        Cron Status
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="relative">
                <!-- Profile dropdown -->
                <div class="relative ml-3">
                    <div>
                        <button type="button" onclick="toggleDropdown()" class="relative flex items-center max-w-xs rounded-full bg-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 lg:px-2 lg:py-1 lg:rounded-md lg:hover:bg-gray-50 transition-colors" id="user-menu-button" aria-expanded="false" aria-haspopup="true">
                            <div class="flex items-center">
                                <!-- Avatar circle with initial -->
                                <div class="flex-shrink-0 h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center">
                                    <span class="text-sm font-medium text-blue-800">
                                        <?php echo strtoupper(substr($user->getEmail(), 0, 1)); ?>
                                    </span>
                                </div>
                                <!-- User info (hidden on mobile, shown on desktop) -->
                                <div class="hidden lg:ml-3 lg:block">
                                    <div class="text-base font-medium text-gray-900">
                                        <?php echo $user->getEmail(); ?>
                                    </div>
                                    <div class="text-sm text-gray-500 capitalize"><?php echo $user->getPlan(); ?> Plan</div>
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
                            <div class="text-base font-medium text-gray-900">
                                <?php 
                                $emailParts = explode('@', $user->getEmail());
                                echo ucfirst($emailParts[0]);
                                ?>
                            </div>
                            <div class="text-sm text-gray-500 capitalize"><?php echo $user->getPlan(); ?> Plan</div>
                        </div>
                        
                        <!-- Billing/Upgrade option -->
                        <?php if ($user->getPlan() === 'free'): ?>
                        <a href="/upgrade" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem" tabindex="-1">
                            <i class="fas fa-arrow-up mr-2 text-blue-500"></i>
                            Upgrade Plan
                        </a>
                        <?php else: ?>
                        <a href="/billing" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem" tabindex="-1">
                            <i class="fas fa-credit-card mr-2 text-blue-500"></i>
                            Billing
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

<script>
function toggleDropdown() {
    const dropdown = document.getElementById('dropdown-menu');
    const button = document.getElementById('user-menu-button');
    
    if (dropdown.classList.contains('hidden')) {
        dropdown.classList.remove('hidden');
        button.setAttribute('aria-expanded', 'true');
    } else {
        dropdown.classList.add('hidden');
        button.setAttribute('aria-expanded', 'false');
    }
}

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    const dropdown = document.getElementById('dropdown-menu');
    const button = document.getElementById('user-menu-button');
    
    if (!button.contains(event.target) && !dropdown.contains(event.target)) {
        dropdown.classList.add('hidden');
        button.setAttribute('aria-expanded', 'false');
    }
});

// Close dropdown when pressing escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        const dropdown = document.getElementById('dropdown-menu');
        const button = document.getElementById('user-menu-button');
        
        dropdown.classList.add('hidden');
        button.setAttribute('aria-expanded', 'false');
    }
});
</script>