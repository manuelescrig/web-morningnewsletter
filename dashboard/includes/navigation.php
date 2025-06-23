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
                    <a href="/dashboard/billing.php" class="<?php echo getNavClass('billing', $currentPage); ?>">
                        Billing
                    </a>
                    <a href="/dashboard/settings.php" class="<?php echo getNavClass('settings', $currentPage); ?>">
                        Settings
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
            <div class="flex items-center space-x-4">
                <span class="text-sm text-gray-700">Welcome, <?php echo htmlspecialchars($user->getEmail()); ?></span>
                <a href="/auth/logout.php" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>
    </div>
</nav>