<?php
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Scheduler.php';

$auth = Auth::getInstance();
$auth->requireAuth();

$user = $auth->getCurrentUser();
$scheduler = new Scheduler();
$scheduleStatus = $scheduler->getScheduleStatus($user);
$sources = $user->getSources();

$currentPage = 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - MorningNewsletter</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <?php include __DIR__ . '/includes/navigation.php'; ?>

    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="px-4 py-6 sm:px-0">
            <h1 class="text-3xl font-bold text-gray-900">Dashboard</h1>
            <p class="mt-2 text-gray-600">Manage your morning newsletter preferences and sources</p>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-8">
            <!-- Active Sources -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-database text-blue-600 text-2xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Active Sources</dt>
                                <dd class="text-lg font-medium text-gray-900">
                                    <?php echo $user->getSourceCount(); ?> / <?php echo $user->getSourceLimit() === PHP_INT_MAX ? '∞' : $user->getSourceLimit(); ?>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Current Plan -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-crown text-yellow-600 text-2xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Current Plan</dt>
                                <dd class="text-lg font-medium text-gray-900 capitalize"><?php echo htmlspecialchars($user->getPlan()); ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Next Newsletter -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-clock text-green-600 text-2xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Next Newsletter</dt>
                                <dd class="text-lg font-medium text-gray-900">
                                    <?php echo date('g:i A', strtotime($scheduleStatus['next_send'])); ?>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Email Status -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-envelope text-purple-600 text-2xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Today's Email</dt>
                                <dd class="text-lg font-medium text-gray-900">
                                    <?php echo $scheduleStatus['sent_today'] ? 'Sent' : 'Pending'; ?>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white shadow rounded-lg mb-8">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Quick Actions</h3>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <a href="/dashboard/sources.php" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <i class="fas fa-plus mr-2"></i>
                        Add Source
                    </a>
                    <a href="/dashboard/schedule.php" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <i class="fas fa-clock mr-2"></i>
                        Update Schedule
                    </a>
                    <a href="/dashboard/settings.php" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <i class="fas fa-cog mr-2"></i>
                        Settings
                    </a>
                    <a href="/preview.php" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <i class="fas fa-eye mr-2"></i>
                        Preview Newsletter
                    </a>
                    <a href="/dashboard/cron_status.php" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <i class="fas fa-clock mr-2"></i>
                        Cron Status
                    </a>
                </div>
            </div>
        </div>

        <!-- Active Sources -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Your Active Sources</h3>
                
                <?php if (empty($sources)): ?>
                    <div class="text-center py-12">
                        <i class="fas fa-database text-gray-300 text-6xl mb-4"></i>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No sources configured</h3>
                        <p class="text-gray-500 mb-6">Add your first data source to start receiving personalized morning updates.</p>
                        <a href="/dashboard/sources.php" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <i class="fas fa-plus mr-2"></i>
                            Add Your First Source
                        </a>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <?php foreach ($sources as $source): ?>
                            <div class="border border-gray-200 rounded-lg p-4">
                                <div class="flex items-center justify-between mb-2">
                                    <h4 class="text-sm font-medium text-gray-900 capitalize"><?php echo htmlspecialchars($source['type']); ?></h4>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Active
                                    </span>
                                </div>
                                <p class="text-sm text-gray-500 mb-3">
                                    Last updated: <?php echo $source['last_updated'] ? date('M j, g:i A', strtotime($source['last_updated'])) : 'Never'; ?>
                                </p>
                                <div class="flex space-x-2">
                                    <a href="/dashboard/sources.php?edit=<?php echo $source['id']; ?>" class="text-xs text-blue-600 hover:text-blue-500">
                                        <i class="fas fa-edit mr-1"></i>Edit
                                    </a>
                                    <a href="/dashboard/sources.php?delete=<?php echo $source['id']; ?>" class="text-xs text-red-600 hover:text-red-500">
                                        <i class="fas fa-trash mr-1"></i>Remove
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>