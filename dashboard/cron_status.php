<?php
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Scheduler.php';

$auth = Auth::getInstance();
$auth->requireAuth();

$user = $auth->getCurrentUser();

// Restrict access to admin users only
if (!$user->isAdmin()) {
    header('Location: /dashboard/');
    exit();
}

$currentPage = 'cron_status';

$scheduler = new Scheduler();
$logFile = __DIR__ . '/../logs/cron.log';
$error = '';
$success = '';

// Handle manual actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $csrfToken = $_POST['csrf_token'] ?? '';
    
    if (!$auth->validateCSRFToken($csrfToken)) {
        $error = 'Invalid request. Please try again.';
    } else {
        switch ($action) {
            case 'test_cron':
                try {
                    $results = $scheduler->sendNewsletters();
                    $success = "Manual cron test completed. Sent: {$results['sent']}, Failed: {$results['failed']}, Total: {$results['total']}";
                } catch (Exception $e) {
                    $error = 'Cron test failed: ' . $e->getMessage();
                }
                break;
        }
    }
}

// Get recent email logs
$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("
    SELECT el.*, u.email 
    FROM email_logs el 
    JOIN users u ON el.user_id = u.id 
    ORDER BY el.sent_at DESC 
    LIMIT 20
");
$stmt->execute();
$recentLogs = $stmt->fetchAll();

// Get cron log tail
$cronLogTail = '';
if (file_exists($logFile)) {
    $cronLogTail = shell_exec("tail -n 20 " . escapeshellarg($logFile));
}

$csrfToken = $auth->generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cron Status - MorningNewsletter</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <?php include __DIR__ . '/includes/lucide-head.php'; ?>
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="/assets/css/dashboard.css">
    <link rel="stylesheet" href="/assets/css/custom.css">    <meta http-equiv="refresh" content="60"> <!-- Auto refresh every minute -->
</head>
<body class="bg-gray-50">
    <?php include __DIR__ . '/includes/navigation.php'; ?>

    <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Page Header -->
        <div class="mb-8">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dashboard-title">Cron Job Status</h1>
                    <p class="mt-2 text-gray-600">Monitor automated newsletter sending</p>
                </div>
                <div class="text-sm text-gray-500">
                    <i class="fas fa-sync-alt"></i> Auto-refresh every 60 seconds
                </div>
            </div>
        </div>

        <?php if ($error): ?>
        <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <?php if ($success): ?>
        <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded">
            <i class="fas fa-check-circle mr-2"></i>
            <?php echo htmlspecialchars($success); ?>
        </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- System Status -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">System Status</h3>
                    
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-500">Cron Script</span>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo file_exists(__DIR__ . '/../cron/send_emails.php') ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                <?php echo file_exists(__DIR__ . '/../cron/send_emails.php') ? 'Available' : 'Missing'; ?>
                            </span>
                        </div>
                        
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-500">Logs Directory</span>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo is_writable(__DIR__ . '/../logs') ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                <?php echo is_writable(__DIR__ . '/../logs') ? 'Writable' : 'Not Writable'; ?>
                            </span>
                        </div>
                        
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-500">Database</span>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                Connected
                            </span>
                        </div>
                        
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-500">Last Cron Log</span>
                            <span class="text-sm text-gray-900">
                                <?php 
                                if (file_exists($logFile)) {
                                    echo date('M j, Y g:i A', filemtime($logFile));
                                } else {
                                    echo 'No logs yet';
                                }
                                ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Manual Actions -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Manual Actions</h3>
                    
                    <div class="space-y-4">
                        <form method="POST" class="inline">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                            <input type="hidden" name="action" value="test_cron">
                            <button type="submit" class="btn-pill inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium text-white bg-primary hover-bg-primary-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus-ring-primary">
                                <i class="fas fa-play mr-2"></i>
                                Run Cron Job Now
                            </button>
                        </form>
                        
                        <div class="text-sm text-gray-600">
                            <p class="mb-2"><strong>Cron Job URL:</strong></p>
                            <code class="bg-gray-100 px-2 py-1 rounded text-xs block break-all">
                                <?php echo 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/../cron/send_emails.php'; ?>
                            </code>
                            <p class="mt-2 text-xs">Use this URL in your hosting provider's cron job settings. Set to run every 15 minutes.</p>
                        </div>
                        
                        <div class="text-sm text-gray-600">
                            <p class="mb-2"><strong>Cron Schedule:</strong></p>
                            <code class="bg-gray-100 px-2 py-1 rounded text-xs block">
                                */15 * * * * (Every 15 minutes)
                            </code>
                            <p class="mt-2 text-xs">This will check for users whose send time falls within each 15-minute window.</p>
                        </div>
                        
                        <div class="text-sm text-gray-600">
                            <p class="mb-2"><strong>Test Links:</strong></p>
                            <div class="space-y-1">
                                <a href="<?php echo dirname($_SERVER['REQUEST_URI']) . '/../cron/send_emails.php'; ?>?mode=health-check" target="_blank" class="text-primary hover:text-primary text-xs">
                                    <i class="fas fa-heart mr-1"></i>Health Check
                                </a><br>
                                <a href="<?php echo dirname($_SERVER['REQUEST_URI']) . '/../cron/send_emails.php'; ?>?mode=dry-run" target="_blank" class="text-primary hover:text-primary text-xs">
                                    <i class="fas fa-eye mr-1"></i>Dry Run (Preview)
                                </a><br>
                                <a href="<?php echo dirname($_SERVER['REQUEST_URI']) . '/../cron/send_emails.php'; ?>" target="_blank" class="text-primary hover:text-primary text-xs">
                                    <i class="fas fa-play mr-1"></i>Manual Run
                                </a>
                            </div>
                            <p class="mt-2 text-xs text-gray-500">Click these links to test the cron script manually.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Email Logs -->
        <div class="mt-6 bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Recent Email Deliveries</h3>
                
                <?php if (empty($recentLogs)): ?>
                    <p class="text-gray-500">No email deliveries recorded yet.</p>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Error</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($recentLogs as $log): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo date('M j, g:i A', strtotime($log['sent_at'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($log['email']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $log['status'] === 'sent' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                            <?php echo htmlspecialchars($log['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        <?php echo $log['error_message'] ? htmlspecialchars($log['error_message']) : '-'; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Cron Log Output -->
        <?php if ($cronLogTail): ?>
        <div class="mt-6 bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Recent Cron Log Output</h3>
                <pre class="bg-gray-100 p-4 rounded text-xs overflow-x-auto"><?php echo htmlspecialchars($cronLogTail); ?></pre>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script src="/assets/js/main.js"></script>
    <script src="/assets/js/dashboard.js"></script>
    <?php include __DIR__ . '/includes/lucide-init.php'; ?>
</body>
</html>