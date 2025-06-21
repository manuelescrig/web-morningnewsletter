<?php
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Scheduler.php';

$auth = Auth::getInstance();
$auth->requireAuth();

$user = $auth->getCurrentUser();
$scheduler = new Scheduler();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    
    if (!$auth->validateCSRFToken($csrfToken)) {
        $error = 'Invalid request. Please try again.';
    } else {
        $timezone = $_POST['timezone'] ?? '';
        $sendTime = $_POST['send_time'] ?? '';
        
        if (empty($timezone) || empty($sendTime)) {
            $error = 'Please select both timezone and send time.';
        } else {
            // Validate timezone
            try {
                new DateTimeZone($timezone);
            } catch (Exception $e) {
                $error = 'Invalid timezone selected.';
            }
            
            // Validate time format (HH:MM)
            if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $sendTime)) {
                $error = 'Invalid time format.';
            }
            
            if (!$error) {
                $updateData = [
                    'timezone' => $timezone,
                    'send_time' => $sendTime
                ];
                
                if ($user->updateProfile($updateData)) {
                    $success = 'Schedule updated successfully!';
                    // Refresh user data
                    $user = $auth->getCurrentUser();
                } else {
                    $error = 'Failed to update schedule. Please try again.';
                }
            }
        }
    }
}

$scheduleStatus = $scheduler->getScheduleStatus($user);
$csrfToken = $auth->generateCSRFToken();

// Common timezones
$timezones = [
    'America/New_York' => 'Eastern Time (EST/EDT)',
    'America/Chicago' => 'Central Time (CST/CDT)',
    'America/Denver' => 'Mountain Time (MST/MDT)',
    'America/Los_Angeles' => 'Pacific Time (PST/PDT)',
    'America/Phoenix' => 'Arizona Time (MST)',
    'America/Anchorage' => 'Alaska Time (AKST/AKDT)',
    'Pacific/Honolulu' => 'Hawaii Time (HST)',
    'Europe/London' => 'London (GMT/BST)',
    'Europe/Paris' => 'Paris (CET/CEST)',
    'Europe/Berlin' => 'Berlin (CET/CEST)',
    'Europe/Rome' => 'Rome (CET/CEST)',
    'Europe/Madrid' => 'Madrid (CET/CEST)',
    'Europe/Amsterdam' => 'Amsterdam (CET/CEST)',
    'Asia/Tokyo' => 'Tokyo (JST)',
    'Asia/Shanghai' => 'Shanghai (CST)',
    'Asia/Hong_Kong' => 'Hong Kong (HKT)',
    'Asia/Singapore' => 'Singapore (SGT)',
    'Asia/Seoul' => 'Seoul (KST)',
    'Asia/Kolkata' => 'India (IST)',
    'Asia/Dubai' => 'Dubai (GST)',
    'Australia/Sydney' => 'Sydney (AEST/AEDT)',
    'Australia/Melbourne' => 'Melbourne (AEST/AEDT)',
    'Australia/Perth' => 'Perth (AWST)',
    'UTC' => 'UTC'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule - MorningNewsletter</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="/" class="text-2xl font-bold text-blue-600">MorningNewsletter</a>
                    <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                        <a href="/dashboard/" class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            Dashboard
                        </a>
                        <a href="/dashboard/sources.php" class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            Sources
                        </a>
                        <a href="/dashboard/schedule.php" class="border-blue-500 text-gray-900 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            Schedule
                        </a>
                        <a href="/dashboard/settings.php" class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            Settings
                        </a>
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

    <div class="max-w-4xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="px-4 py-6 sm:px-0">
            <h1 class="text-3xl font-bold text-gray-900">Newsletter Schedule</h1>
            <p class="mt-2 text-gray-600">Configure when you want to receive your morning newsletter</p>
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
            <!-- Current Schedule -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Current Schedule</h3>
                    
                    <div class="space-y-4">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-clock text-blue-600 text-xl"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-900">Send Time</p>
                                <p class="text-lg text-gray-600"><?php echo htmlspecialchars($user->getSendTime()); ?></p>
                            </div>
                        </div>
                        
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-globe text-blue-600 text-xl"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-900">Timezone</p>
                                <p class="text-lg text-gray-600"><?php echo htmlspecialchars($user->getTimezone()); ?></p>
                            </div>
                        </div>
                        
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-calendar text-blue-600 text-xl"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-900">Next Newsletter</p>
                                <p class="text-lg text-gray-600"><?php echo date('F j, Y g:i A T', strtotime($scheduleStatus['next_send'])); ?></p>
                            </div>
                        </div>
                        
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-envelope text-blue-600 text-xl"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-900">Today's Status</p>
                                <p class="text-lg">
                                    <?php if ($scheduleStatus['sent_today']): ?>
                                        <span class="text-green-600"><i class="fas fa-check-circle mr-1"></i>Sent</span>
                                    <?php else: ?>
                                        <span class="text-yellow-600"><i class="fas fa-clock mr-1"></i>Pending</span>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Update Schedule Form -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Update Schedule</h3>
                    
                    <form method="POST" class="space-y-6">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                        
                        <div>
                            <label for="timezone" class="block text-sm font-medium text-gray-700">Timezone</label>
                            <select id="timezone" name="timezone" required
                                    class="mt-1 block w-full px-3 py-2 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                <?php foreach ($timezones as $value => $label): ?>
                                    <option value="<?php echo htmlspecialchars($value); ?>" 
                                            <?php echo $value === $user->getTimezone() ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="mt-1 text-sm text-gray-500">Select your local timezone for accurate delivery</p>
                        </div>

                        <div>
                            <label for="send_time" class="block text-sm font-medium text-gray-700">Send Time</label>
                            <input type="time" id="send_time" name="send_time" required
                                   value="<?php echo htmlspecialchars($user->getSendTime()); ?>"
                                   class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            <p class="mt-1 text-sm text-gray-500">Choose what time you want to receive your newsletter</p>
                        </div>

                        <div class="flex items-center justify-between">
                            <button type="submit"
                                    class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <i class="fas fa-save mr-2"></i>
                                Update Schedule
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Schedule Information -->
        <div class="mt-6 bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">How It Works</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="text-center">
                        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-md bg-blue-500 text-white mb-4">
                            <i class="fas fa-clock text-xl"></i>
                        </div>
                        <h4 class="text-lg font-medium text-gray-900 mb-2">Daily Delivery</h4>
                        <p class="text-sm text-gray-600">Your newsletter is automatically generated and sent every day at your chosen time.</p>
                    </div>
                    
                    <div class="text-center">
                        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-md bg-green-500 text-white mb-4">
                            <i class="fas fa-globe text-xl"></i>
                        </div>
                        <h4 class="text-lg font-medium text-gray-900 mb-2">Timezone Aware</h4>
                        <p class="text-sm text-gray-600">Delivery time is calculated based on your local timezone, so you always get it when expected.</p>
                    </div>
                    
                    <div class="text-center">
                        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-md bg-purple-500 text-white mb-4">
                            <i class="fas fa-sync text-xl"></i>
                        </div>
                        <h4 class="text-lg font-medium text-gray-900 mb-2">Fresh Data</h4>
                        <p class="text-sm text-gray-600">All your data sources are refreshed just before sending to ensure you get the latest information.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Update next send time dynamically when timezone or time changes
        function updateNextSendTime() {
            const timezone = document.getElementById('timezone').value;
            const sendTime = document.getElementById('send_time').value;
            
            if (timezone && sendTime) {
                // This would ideally make an AJAX call to calculate the next send time
                // For now, we'll just update on form submission
            }
        }

        document.getElementById('timezone').addEventListener('change', updateNextSendTime);
        document.getElementById('send_time').addEventListener('change', updateNextSendTime);
    </script>
</body>
</html>