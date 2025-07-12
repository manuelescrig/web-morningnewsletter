<?php
/**
 * Individual Newsletter Management Page
 * 
 * Manage a specific newsletter's sources, settings, and schedule
 */

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Newsletter.php';
require_once __DIR__ . '/../core/Scheduler.php';
require_once __DIR__ . '/../core/SourceModule.php';
require_once __DIR__ . '/../config/database.php';

// Include all source modules
require_once __DIR__ . '/../modules/bitcoin.php';
require_once __DIR__ . '/../modules/sp500.php';
require_once __DIR__ . '/../modules/weather.php';
require_once __DIR__ . '/../modules/news.php';
require_once __DIR__ . '/../modules/appstore.php';
require_once __DIR__ . '/../modules/stripe.php';

$auth = Auth::getInstance();
$auth->requireAuth();

$user = $auth->getCurrentUser();
$newsletterId = (int)($_GET['id'] ?? 0);

if (!$newsletterId) {
    header('Location: /dashboard/');
    exit;
}

$newsletter = $user->getNewsletter($newsletterId);
if (!$newsletter) {
    header('Location: /dashboard/');
    exit;
}

$scheduler = new Scheduler();
$scheduleStatus = $scheduler->getScheduleStatus($newsletter);
$sources = $newsletter->getSources();
$error = '';
$success = '';

$currentPage = 'newsletter';

// Comprehensive timezone list with major cities
$timezones = [
    // UTC and GMT
    'UTC' => 'UTC (Coordinated Universal Time)',
    'GMT' => 'GMT (Greenwich Mean Time)',
    
    // North America
    'America/New_York' => 'New York (Eastern Time)',
    'America/Chicago' => 'Chicago (Central Time)',
    'America/Denver' => 'Denver (Mountain Time)',
    'America/Los_Angeles' => 'Los Angeles (Pacific Time)',
    'America/Phoenix' => 'Phoenix (Arizona Time)',
    'America/Anchorage' => 'Anchorage (Alaska Time)',
    'America/Honolulu' => 'Honolulu (Hawaii Time)',
    'America/Toronto' => 'Toronto (Eastern Time)',
    'America/Vancouver' => 'Vancouver (Pacific Time)',
    'America/Mexico_City' => 'Mexico City',
    
    // South America
    'America/Sao_Paulo' => 'São Paulo (Brazil Time)',
    'America/Argentina/Buenos_Aires' => 'Buenos Aires',
    'America/Lima' => 'Lima',
    'America/Bogota' => 'Bogotá',
    'America/Santiago' => 'Santiago',
    
    // Europe
    'Europe/London' => 'London (GMT/BST)',
    'Europe/Paris' => 'Paris (CET/CEST)',
    'Europe/Berlin' => 'Berlin (CET/CEST)',
    'Europe/Rome' => 'Rome (CET/CEST)',
    'Europe/Madrid' => 'Madrid (CET/CEST)',
    'Europe/Amsterdam' => 'Amsterdam (CET/CEST)',
    'Europe/Brussels' => 'Brussels (CET/CEST)',
    'Europe/Vienna' => 'Vienna (CET/CEST)',
    'Europe/Prague' => 'Prague (CET/CEST)',
    'Europe/Stockholm' => 'Stockholm (CET/CEST)',
    'Europe/Oslo' => 'Oslo (CET/CEST)',
    'Europe/Copenhagen' => 'Copenhagen (CET/CEST)',
    'Europe/Helsinki' => 'Helsinki (EET/EEST)',
    'Europe/Warsaw' => 'Warsaw (CET/CEST)',
    'Europe/Moscow' => 'Moscow (MSK)',
    'Europe/Istanbul' => 'Istanbul (TRT)',
    'Europe/Athens' => 'Athens (EET/EEST)',
    'Europe/Zurich' => 'Zurich (CET/CEST)',
    'Europe/Dublin' => 'Dublin (GMT/IST)',
    'Europe/Lisbon' => 'Lisbon (WET/WEST)',
    
    // Asia
    'Asia/Tokyo' => 'Tokyo (JST)',
    'Asia/Seoul' => 'Seoul (KST)',
    'Asia/Shanghai' => 'Shanghai (CST)',
    'Asia/Hong_Kong' => 'Hong Kong (HKT)',
    'Asia/Singapore' => 'Singapore (SGT)',
    'Asia/Bangkok' => 'Bangkok (ICT)',
    'Asia/Jakarta' => 'Jakarta (WIB)',
    'Asia/Manila' => 'Manila (PST)',
    'Asia/Kuala_Lumpur' => 'Kuala Lumpur (MYT)',
    'Asia/Kolkata' => 'Mumbai/Delhi (IST)',
    'Asia/Karachi' => 'Karachi (PKT)',
    'Asia/Dubai' => 'Dubai (GST)',
    'Asia/Tehran' => 'Tehran (IRST)',
    'Asia/Baghdad' => 'Baghdad (AST)',
    'Asia/Riyadh' => 'Riyadh (AST)',
    'Asia/Jerusalem' => 'Jerusalem (IST)',
    'Asia/Baku' => 'Baku (AZT)',
    'Asia/Yerevan' => 'Yerevan (AMT)',
    'Asia/Tbilisi' => 'Tbilisi (GET)',
    'Asia/Almaty' => 'Almaty (ALMT)',
    'Asia/Tashkent' => 'Tashkent (UZT)',
    'Asia/Novosibirsk' => 'Novosibirsk (NOVT)',
    'Asia/Vladivostok' => 'Vladivostok (VLAT)',
    
    // Africa
    'Africa/Cairo' => 'Cairo (EET)',
    'Africa/Johannesburg' => 'Johannesburg (SAST)',
    'Africa/Lagos' => 'Lagos (WAT)',
    'Africa/Nairobi' => 'Nairobi (EAT)',
    'Africa/Casablanca' => 'Casablanca (WET)',
    'Africa/Algiers' => 'Algiers (CET)',
    'Africa/Tunis' => 'Tunis (CET)',
    'Africa/Addis_Ababa' => 'Addis Ababa (EAT)',
    'Africa/Accra' => 'Accra (GMT)',
    'Africa/Kinshasa' => 'Kinshasa (WAT)',
    
    // Australia & Oceania
    'Australia/Sydney' => 'Sydney (AEST/AEDT)',
    'Australia/Melbourne' => 'Melbourne (AEST/AEDT)',
    'Australia/Brisbane' => 'Brisbane (AEST)',
    'Australia/Perth' => 'Perth (AWST)',
    'Australia/Adelaide' => 'Adelaide (ACST/ACDT)',
    'Australia/Darwin' => 'Darwin (ACST)',
    'Pacific/Auckland' => 'Auckland (NZST/NZDT)',
    'Pacific/Fiji' => 'Fiji (FJT)',
    'Pacific/Honolulu' => 'Honolulu (HST)',
    'Pacific/Guam' => 'Guam (ChST)',
    'Pacific/Tahiti' => 'Tahiti (TAHT)',
    
    // Other major cities
    'Indian/Maldives' => 'Maldives (MVT)',
    'Indian/Mauritius' => 'Mauritius (MUT)',
    'Atlantic/Reykjavik' => 'Reykjavik (GMT)',
    'Atlantic/Azores' => 'Azores (AZOT/AZOST)',
    'Atlantic/Cape_Verde' => 'Cape Verde (CVT)',
];

// Available source modules
$availableModules = [
    'bitcoin' => new BitcoinModule(),
    'weather' => new WeatherModule(),
    'news' => new NewsModule(),
    'sp500' => new SP500Module(),
    'stripe' => new StripeModule(),
    'appstore' => new AppStoreModule()
];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $csrfToken = $_POST['csrf_token'] ?? '';
    
    if (!$auth->validateCSRFToken($csrfToken)) {
        $error = 'Invalid request. Please try again.';
    } else {
        switch ($action) {
            case 'update_newsletter_settings':
                $updateData = [
                    'title' => trim($_POST['title'] ?? ''),
                    'timezone' => $_POST['timezone'] ?? 'UTC',
                    'send_time' => $_POST['send_time'] ?? '06:00',
                    'frequency' => $_POST['frequency'] ?? 'daily',
                    'is_paused' => isset($_POST['is_paused']) ? 1 : 0
                ];
                
                // Handle days_of_week for weekly frequency
                if ($updateData['frequency'] === 'weekly') {
                    $daysOfWeek = $_POST['days_of_week'] ?? [];
                    $updateData['days_of_week'] = !empty($daysOfWeek) ? json_encode(array_map('intval', $daysOfWeek)) : '';
                } else {
                    $updateData['days_of_week'] = '';
                }
                
                // Handle day_of_month for monthly/quarterly frequency
                if (in_array($updateData['frequency'], ['monthly', 'quarterly'])) {
                    $updateData['day_of_month'] = max(1, min(31, (int)($_POST['day_of_month'] ?? 1)));
                } else {
                    $updateData['day_of_month'] = 1;
                }
                
                // Handle months for quarterly frequency
                if ($updateData['frequency'] === 'quarterly') {
                    $months = $_POST['months'] ?? [];
                    $updateData['months'] = !empty($months) ? json_encode(array_map('intval', $months)) : '';
                } else {
                    $updateData['months'] = '';
                }
                
                if (empty($updateData['title'])) {
                    $error = 'Newsletter title is required.';
                } else {
                    if ($newsletter->update($updateData)) {
                        $success = 'Newsletter settings updated successfully!';
                        // Refresh newsletter data
                        $newsletter = $user->getNewsletter($newsletterId);
                        $scheduleStatus = $scheduler->getScheduleStatus($newsletter);
                    } else {
                        $error = 'Failed to update newsletter settings.';
                    }
                }
                break;
                
            case 'update_source_order':
                $sourceIds = $_POST['source_ids'] ?? [];
                if (is_array($sourceIds) && !empty($sourceIds)) {
                    if ($newsletter->updateSourceOrder($sourceIds)) {
                        $success = 'Source order updated successfully!';
                        // Refresh sources
                        $sources = $newsletter->getSources();
                    } else {
                        $error = 'Failed to update source order.';
                    }
                } else {
                    $error = 'Invalid source order data.';
                }
                break;
                
            case 'update_source':
                $sourceId = $_POST['source_id'] ?? '';
                $sourceName = $_POST['source_name'] ?? '';
                $config = [];
                
                // Get configuration fields from POST data
                foreach ($_POST as $key => $value) {
                    if (strpos($key, 'config_') === 0) {
                        $configKey = substr($key, 7); // Remove 'config_' prefix
                        $config[$configKey] = $value;
                    }
                }
                
                if ($newsletter->updateSource($sourceId, $config, $sourceName)) {
                    $success = 'Source updated successfully!';
                    // Refresh sources
                    $sources = $newsletter->getSources();
                } else {
                    $error = 'Failed to update source.';
                }
                break;
                
            case 'add_source':
                $sourceType = $_POST['source_type'] ?? '';
                $sourceName = $_POST['source_name'] ?? '';
                $config = [];
                
                // Get configuration fields from POST data
                foreach ($_POST as $key => $value) {
                    if (strpos($key, 'config_') === 0) {
                        $configKey = substr($key, 7); // Remove 'config_' prefix
                        $config[$configKey] = $value;
                    }
                }
                
                if ($user->canAddSource()) {
                    try {
                        // Get the source module class
                        $moduleClass = ucfirst($sourceType) . 'Module';
                        if (class_exists($moduleClass)) {
                            $module = new $moduleClass();
                            
                            // Validate configuration
                            if ($module->validateConfig($config)) {
                                if ($newsletter->addSource($sourceType, $config, $sourceName)) {
                                    $success = 'Source added successfully!';
                                    // Refresh sources
                                    $sources = $newsletter->getSources();
                                } else {
                                    $error = 'Failed to add source.';
                                }
                            } else {
                                $error = 'Invalid source configuration.';
                            }
                        } else {
                            $error = 'Invalid source type.';
                        }
                    } catch (Exception $e) {
                        $error = 'Error adding source: ' . $e->getMessage();
                    }
                } else {
                    $error = 'You have reached your source limit. Please upgrade your plan to add more sources.';
                }
                break;
                
            case 'delete_source':
                $sourceId = $_POST['source_id'] ?? '';
                if ($newsletter->removeSource($sourceId)) {
                    $success = 'Source deleted successfully!';
                    // Refresh sources
                    $sources = $newsletter->getSources();
                } else {
                    $error = 'Failed to delete source.';
                }
                break;
                
        }
    }
}

// Get source limits based on plan
$sourceLimits = [
    'free' => 1,
    'starter' => 5,
    'pro' => 15,
    'unlimited' => 999
];
$maxSources = $sourceLimits[$user->getPlan()] ?? 1;
$canAddSource = count($sources) < $maxSources;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($newsletter->getTitle()); ?> - MorningNewsletter</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="/assets/css/dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
</head>
<body class="bg-gray-50">
    <?php include __DIR__ . '/includes/navigation.php'; ?>
    
    <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Header -->
        <div class="mb-8">
            <nav class="flex mb-4" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                    <li class="inline-flex items-center">
                        <a href="/dashboard/" class="text-gray-700 hover:text-blue-600 inline-flex items-center">
                            My Newsletters
                        </a>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                            <span class="text-gray-500"><?php echo htmlspecialchars($newsletter->getTitle()); ?></span>
                        </div>
                    </li>
                </ol>
            </nav>
            
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900"><?php echo htmlspecialchars($newsletter->getTitle()); ?></h1>
                    <p class="text-gray-600 mt-2">Configure your newsletter sources and settings</p>
                </div>
                <div class="flex space-x-3">
                    <a href="/preview.php?newsletter_id=<?php echo $newsletter->getId(); ?>" 
                       class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md font-medium transition-colors duration-200"
                       target="_blank">
                        <i class="fas fa-eye mr-2"></i>
                        Preview Newsletter
                    </a>
                </div>
            </div>
        </div>

        <!-- Messages -->
        <?php if ($error): ?>
            <div class="mb-6 p-4 rounded-md bg-red-50 text-red-800 border border-red-200">
                <div class="flex">
                    <i class="fas fa-exclamation-triangle mr-2 mt-0.5"></i>
                    <div><?php echo htmlspecialchars($error); ?></div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="mb-6 p-4 rounded-md bg-green-50 text-green-800 border border-green-200">
                <div class="flex">
                    <i class="fas fa-check-circle mr-2 mt-0.5"></i>
                    <div><?php echo htmlspecialchars($success); ?></div>
                </div>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Column: Data Sources -->
            <div class="lg:col-span-2">
                <!-- Add New Source -->
                <?php if ($canAddSource): ?>
                    <div class="bg-white rounded-lg shadow mb-6">
                        <div class="p-6 border-b border-gray-200">
                            <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                                <i class="fas fa-plus-circle text-blue-600 mr-2"></i>
                                Add Data Source
                            </h2>
                            <p class="text-sm text-gray-600 mt-1">
                                You can add <?php echo $maxSources - count($sources); ?> more source<?php echo ($maxSources - count($sources)) !== 1 ? 's' : ''; ?> 
                                (<?php echo count($sources); ?>/<?php echo $maxSources; ?> used)
                            </p>
                        </div>
                        <div class="p-6">
                            <form method="POST" id="addSourceForm" class="space-y-4">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($auth->generateCSRFToken()); ?>">
                                <input type="hidden" name="action" value="add_source">
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="source_type" class="block text-sm font-medium text-gray-700 mb-2">
                                            Source Type
                                        </label>
                                        <select name="source_type" id="source_type" required onchange="showSourceConfig(this.value)"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                            <option value="">Select a source...</option>
                                            <?php foreach ($availableModules as $type => $module): ?>
                                                <option value="<?php echo $type; ?>"><?php echo $module->getTitle(); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div>
                                        <label for="source_name" class="block text-sm font-medium text-gray-700 mb-2">
                                            Display Name (Optional)
                                        </label>
                                        <input type="text" name="source_name" id="source_name" 
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                               placeholder="Custom name for this source">
                                    </div>
                                </div>
                                
                                <!-- Dynamic configuration fields -->
                                <div id="sourceConfig" class="hidden">
                                    <div class="border-t pt-4">
                                        <h3 class="text-md font-medium text-gray-900 mb-3">Source Configuration</h3>
                                        <div id="configFields" class="space-y-3"></div>
                                    </div>
                                </div>
                                
                                <div class="flex justify-end">
                                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md font-medium transition-colors duration-200">
                                        <i class="fas fa-plus mr-2"></i>
                                        Add Source
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                        <div class="flex">
                            <i class="fas fa-exclamation-triangle text-yellow-600 mr-2 mt-0.5"></i>
                            <div>
                                <p class="text-yellow-800">
                                    You've reached your source limit (<?php echo count($sources); ?>/<?php echo $maxSources; ?>). 
                                    <a href="/upgrade" class="underline hover:text-yellow-900">Upgrade your plan</a> to add more sources.
                                </p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Current Sources -->
                <div class="bg-white rounded-lg shadow">
                    <div class="p-6 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                            <i class="fas fa-list text-blue-600 mr-2"></i>
                            Current Sources (<?php echo count($sources); ?>)
                        </h2>
                        <?php if (count($sources) > 1): ?>
                            <p class="text-sm text-gray-600 mt-1">Drag and drop to reorder sources</p>
                        <?php endif; ?>
                    </div>
                    <div class="p-6">
                        <?php if (empty($sources)): ?>
                            <div class="text-center py-8">
                                <div class="text-gray-400 text-5xl mb-4">📊</div>
                                <h3 class="text-lg font-medium text-gray-900 mb-2">No sources configured</h3>
                                <p class="text-gray-600">Add your first data source to get started with your personalized newsletter.</p>
                            </div>
                        <?php else: ?>
                            <div id="sourcesList" class="space-y-4">
                                <?php foreach ($sources as $source): ?>
                                    <div class="border border-gray-200 rounded-lg p-4 source-item" data-source-id="<?php echo $source['id']; ?>">
                                        <div class="flex justify-between items-start">
                                            <div class="flex-1">
                                                <div class="flex items-center">
                                                    <div class="cursor-move mr-3 text-gray-400 hover:text-gray-600">
                                                        <i class="fas fa-grip-vertical"></i>
                                                    </div>
                                                    <div>
                                                        <h3 class="font-medium text-gray-900">
                                                            <?php echo htmlspecialchars($source['name'] ?: ucfirst($source['type'])); ?>
                                                        </h3>
                                                        <p class="text-sm text-gray-600"><?php echo ucfirst($source['type']); ?></p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="flex items-center space-x-2">
                                                <button onclick="editSource(<?php echo $source['id']; ?>)" 
                                                        class="text-gray-400 hover:text-blue-600 p-1">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button onclick="deleteSource(<?php echo $source['id']; ?>)" 
                                                        class="text-gray-400 hover:text-red-600 p-1">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                        
                                        <?php 
                                        // Only show config section if there's actual content to display
                                        if ($source['config']) {
                                            $config = json_decode($source['config'], true);
                                            $configDisplay = [];
                                            $hiddenFields = ['latitude', 'longitude']; // Fields to hide from display
                                            
                                            if ($config) {
                                                foreach ($config as $key => $value) {
                                                    if (!empty($value) && !in_array($key, $hiddenFields)) {
                                                        $configDisplay[] = ucfirst(str_replace('_', ' ', $key)) . ': ' . 
                                                                         (strlen($value) > 50 ? substr($value, 0, 50) . '...' : $value);
                                                    }
                                                }
                                            }
                                            
                                            // Only show the divider and content if there's something to display
                                            if (!empty($configDisplay)):
                                        ?>
                                            <div class="mt-3 pt-3 border-t border-gray-100">
                                                <div class="text-sm text-gray-600">
                                                    <?php echo htmlspecialchars(implode(' • ', $configDisplay)); ?>
                                                </div>
                                            </div>
                                        <?php endif; } ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Right Column: Newsletter Settings -->
            <div class="lg:col-span-1">
                <!-- Newsletter Settings -->
                <div class="bg-white rounded-lg shadow mb-6">
                    <div class="p-6 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                            <i class="fas fa-cog text-blue-600 mr-2"></i>
                            Newsletter Settings
                        </h2>
                    </div>
                    <div class="p-6">
                        <form method="POST" class="space-y-4">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($auth->generateCSRFToken()); ?>">
                            <input type="hidden" name="action" value="update_newsletter_settings">
                            
                            <div>
                                <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
                                    Newsletter Title
                                </label>
                                <input type="text" name="title" id="title" required 
                                       value="<?php echo htmlspecialchars($newsletter->getTitle()); ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            
                            <div>
                                <label for="timezone" class="block text-sm font-medium text-gray-700 mb-2">
                                    Timezone
                                </label>
                                <select name="timezone" id="timezone" 
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <?php foreach ($timezones as $value => $label): ?>
                                        <option value="<?php echo $value; ?>" <?php echo $value === $newsletter->getTimezone() ? 'selected' : ''; ?>>
                                            <?php echo $label; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div>
                                <label for="send_time" class="block text-sm font-medium text-gray-700 mb-2">
                                    Send Time
                                </label>
                                <input type="time" name="send_time" id="send_time" required
                                       value="<?php echo $newsletter->getSendTime(); ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            
                            <div>
                                <label for="frequency" class="block text-sm font-medium text-gray-700 mb-2">
                                    Frequency
                                </label>
                                <select name="frequency" id="frequency" onchange="updateScheduleOptions()"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="daily" <?php echo $newsletter->getFrequency() === 'daily' ? 'selected' : ''; ?>>Daily</option>
                                    <option value="weekly" <?php echo $newsletter->getFrequency() === 'weekly' ? 'selected' : ''; ?>>Weekly</option>
                                    <option value="monthly" <?php echo $newsletter->getFrequency() === 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                                    <option value="quarterly" <?php echo $newsletter->getFrequency() === 'quarterly' ? 'selected' : ''; ?>>Quarterly</option>
                                </select>
                            </div>
                            
                            <!-- Weekly Schedule Options -->
                            <div id="weekly-options" class="<?php echo $newsletter->getFrequency() !== 'weekly' ? 'hidden' : ''; ?>">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Days of Week
                                </label>
                                <div class="grid grid-cols-7 gap-2">
                                    <?php 
                                    $daysOfWeek = $newsletter->getDaysOfWeek();
                                    $dayNames = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
                                    for ($i = 1; $i <= 7; $i++): 
                                    ?>
                                        <label class="flex items-center justify-center p-2 border rounded cursor-pointer hover:bg-gray-50">
                                            <input type="checkbox" name="days_of_week[]" value="<?php echo $i; ?>" 
                                                   <?php echo in_array($i, $daysOfWeek) ? 'checked' : ''; ?>
                                                   class="sr-only">
                                            <span class="text-sm font-medium"><?php echo $dayNames[$i-1]; ?></span>
                                        </label>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            
                            <!-- Monthly/Quarterly Day Options -->
                            <div id="monthly-options" class="<?php echo !in_array($newsletter->getFrequency(), ['monthly', 'quarterly']) ? 'hidden' : ''; ?>">
                                <label for="day_of_month" class="block text-sm font-medium text-gray-700 mb-2">
                                    Day of Month
                                </label>
                                <select name="day_of_month" id="day_of_month"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <?php for ($day = 1; $day <= 31; $day++): ?>
                                        <option value="<?php echo $day; ?>" <?php echo $newsletter->getDayOfMonth() == $day ? 'selected' : ''; ?>>
                                            <?php echo $day; ?><?php echo $day == 1 ? 'st' : ($day == 2 ? 'nd' : ($day == 3 ? 'rd' : 'th')); ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                                <p class="text-xs text-gray-500 mt-1">For months with fewer days, will send on the last day of the month</p>
                            </div>
                            
                            <!-- Quarterly Month Options -->
                            <div id="quarterly-options" class="<?php echo $newsletter->getFrequency() !== 'quarterly' ? 'hidden' : ''; ?>">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Months
                                </label>
                                <div class="grid grid-cols-3 gap-2">
                                    <?php 
                                    $selectedMonths = $newsletter->getMonths();
                                    $monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                                    for ($i = 1; $i <= 12; $i++): 
                                    ?>
                                        <label class="flex items-center justify-center p-2 border rounded cursor-pointer hover:bg-gray-50">
                                            <input type="checkbox" name="months[]" value="<?php echo $i; ?>" 
                                                   <?php echo in_array($i, $selectedMonths) ? 'checked' : ''; ?>
                                                   class="sr-only">
                                            <span class="text-sm font-medium"><?php echo $monthNames[$i-1]; ?></span>
                                        </label>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            
                            <!-- Pause Option -->
                            <div class="flex items-center justify-between">
                                <div class="flex flex-col">
                                    <label class="text-sm font-medium text-gray-900">
                                        Pause newsletter sending
                                    </label>
                                    <p class="text-xs text-gray-500">
                                        Temporarily stop sending this newsletter
                                    </p>
                                </div>
                                
                                <div class="relative">
                                    <input type="checkbox" name="is_paused" id="is_paused" value="1" 
                                           <?php echo $newsletter->isPaused() ? 'checked' : ''; ?>
                                           class="sr-only toggle-checkbox">
                                    <label for="is_paused" class="toggle-label cursor-pointer">
                                        <div class="toggle-switch">
                                            <div class="toggle-slider"></div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                            
                            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md font-medium transition-colors duration-200">
                                <i class="fas fa-save mr-2"></i>
                                Update Settings
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Schedule Status -->
                <div class="bg-white rounded-lg shadow">
                    <div class="p-6 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                            <i class="fas fa-clock text-green-600 mr-2"></i>
                            Schedule Status
                        </h2>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Current Time:</span>
                                <span class="font-medium"><?php echo date('M j, g:i A'); ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Next Send:</span>
                                <span class="font-medium"><?php echo date('M j, g:i A', strtotime($scheduleStatus['next_send'])); ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Sent Today:</span>
                                <span class="font-medium">
                                    <?php if ($scheduleStatus['sent_today']): ?>
                                        <span class="text-green-600"><i class="fas fa-check-circle mr-1"></i>Yes</span>
                                    <?php else: ?>
                                        <span class="text-gray-500"><i class="fas fa-times-circle mr-1"></i>No</span>
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Initialize newsletter editor with PHP data
        window.sourceConfigs = <?php echo json_encode(array_map(function($type, $module) {
            return [
                'type' => $type,
                'title' => $module->getTitle(),
                'fields' => $module->getConfigFields()
            ];
        }, array_keys($availableModules), $availableModules)); ?>;

        window.currentSources = <?php echo json_encode(array_map(function($source) {
            return [
                'id' => $source['id'],
                'type' => $source['type'],
                'name' => $source['name'],
                'config' => json_decode($source['config'], true) ?? []
            ];
        }, $sources)); ?>;

        window.csrfToken = '<?php echo htmlspecialchars($auth->generateCSRFToken()); ?>';

        // Delegate to external newsletter editor
        function showSourceConfig(sourceType) {
            NewsletterEditor.sources.showConfigFields(sourceType);
        }

        // Delegate to external newsletter editor
        function editSource(sourceId) {
            NewsletterEditor.sources.editSource(sourceId);
        }
        
        function populateEditConfigFields(source) {
            const configDiv = document.getElementById('editConfigFields');
            const sourceConfig = sourceConfigs.find(c => c.type === source.type);
            
            if (!sourceConfig || !sourceConfig.fields || sourceConfig.fields.length === 0) {
                configDiv.innerHTML = '<p class="text-sm text-gray-500">No configuration options available.</p>';
                return;
            }
            
            configDiv.innerHTML = '';
            sourceConfig.fields.forEach(field => {
                const fieldDiv = document.createElement('div');
                const currentValue = source.config[field.name] || field.default || '';
                
                if (field.type === 'location_search') {
                    fieldDiv.innerHTML = `
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            ${field.label}${field.required ? ' *' : ''}
                        </label>
                        <div class="relative">
                            <input type="text" id="edit_config_${field.name}" 
                                   value="${currentValue}"
                                   placeholder="Search for a city or location..."
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <div id="edit_location_results" class="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg hidden max-h-60 overflow-y-auto"></div>
                        </div>
                    `;
                } else if (field.type === 'hidden') {
                    fieldDiv.innerHTML = `
                        <input type="hidden" name="config_${field.name}" id="edit_config_${field.name}" value="${currentValue}">
                    `;
                } else {
                    fieldDiv.innerHTML = `
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            ${field.label}${field.required ? ' *' : ''}
                        </label>
                        <input type="${field.type}" name="config_${field.name}" id="edit_config_${field.name}"
                               ${field.required ? 'required' : ''} 
                               value="${currentValue}"
                               placeholder="${field.placeholder || ''}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        ${field.description ? `<p class="text-xs text-gray-500 mt-1">${field.description}</p>` : ''}
                    `;
                }
                configDiv.appendChild(fieldDiv);
            });
            
            // Setup location search for weather sources
            if (source.type === 'weather') {
                setupEditLocationSearch();
            }
        }
        
        function setupEditLocationSearch() {
            const searchInput = document.getElementById('edit_config_location_search');
            const resultsDiv = document.getElementById('edit_location_results');
            
            if (!searchInput || !resultsDiv) return;
            
            let searchTimeout;
            
            searchInput.addEventListener('input', function() {
                const query = this.value.trim();
                
                if (query.length < 2) {
                    resultsDiv.classList.add('hidden');
                    return;
                }
                
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    searchEditLocations(query);
                }, 300);
            });
        }
        
        function searchEditLocations(query) {
            const resultsDiv = document.getElementById('edit_location_results');
            
            resultsDiv.innerHTML = '<div class="p-3 text-gray-500">Searching...</div>';
            resultsDiv.classList.remove('hidden');
            
            const apiUrl = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=5&addressdetails=1`;
            
            fetch(apiUrl, {
                headers: {
                    'User-Agent': 'MorningNewsletter/1.0'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.length === 0) {
                    resultsDiv.innerHTML = '<div class="p-3 text-gray-500">No locations found</div>';
                    return;
                }
                
                resultsDiv.innerHTML = '';
                data.forEach(location => {
                    const locationDiv = document.createElement('div');
                    locationDiv.className = 'p-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-b-0';
                    
                    locationDiv.innerHTML = `
                        <div class="font-medium text-gray-900">${location.name}</div>
                        <div class="text-sm text-gray-600">${location.display_name}</div>
                    `;
                    
                    locationDiv.addEventListener('click', function() {
                        selectEditLocation(location.name, location.lat, location.lon);
                    });
                    
                    resultsDiv.appendChild(locationDiv);
                });
            })
            .catch(error => {
                resultsDiv.innerHTML = '<div class="p-3 text-red-500">Error searching locations</div>';
            });
        }
        
        function selectEditLocation(name, lat, lon) {
            document.getElementById('edit_config_location_search').value = name;
            document.getElementById('edit_config_location').value = name;
            document.getElementById('edit_config_latitude').value = lat;
            document.getElementById('edit_config_longitude').value = lon;
            document.getElementById('edit_location_results').classList.add('hidden');
        }
        
        function closeEditModal() {
            const modal = document.querySelector('.fixed.inset-0');
            if (modal) {
                modal.remove();
            }
        }

        // Delegate to external newsletter editor
        function deleteSource(sourceId) {
            NewsletterEditor.sources.deleteSource(sourceId);
        }

        function setupLocationSearch() {
            const searchInput = document.getElementById('config_location_search');
            const resultsDiv = document.getElementById('location_results');
            
            if (!searchInput || !resultsDiv) return;
            
            let searchTimeout;
            
            searchInput.addEventListener('input', function() {
                const query = this.value.trim();
                
                if (query.length < 2) {
                    resultsDiv.classList.add('hidden');
                    return;
                }
                
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    searchLocations(query);
                }, 300);
            });
            
            // Hide results when clicking outside
            document.addEventListener('click', function(e) {
                if (!searchInput.contains(e.target) && !resultsDiv.contains(e.target)) {
                    resultsDiv.classList.add('hidden');
                }
            });
        }
        
        function searchLocations(query) {
            const resultsDiv = document.getElementById('location_results');
            
            // Show loading state
            resultsDiv.innerHTML = '<div class="p-3 text-gray-500">Searching...</div>';
            resultsDiv.classList.remove('hidden');
            
            // Use OpenStreetMap Nominatim API for geocoding (free, no API key required)
            const apiUrl = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=5&addressdetails=1`;
            
            fetch(apiUrl, {
                headers: {
                    'User-Agent': 'MorningNewsletter/1.0'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.length === 0) {
                    resultsDiv.innerHTML = '<div class="p-3 text-gray-500">No locations found</div>';
                    return;
                }
                
                resultsDiv.innerHTML = '';
                data.forEach(location => {
                    const locationDiv = document.createElement('div');
                    locationDiv.className = 'p-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-b-0';
                    
                    const displayName = location.display_name;
                    const shortName = location.name;
                    const country = location.address?.country || '';
                    const state = location.address?.state || location.address?.region || '';
                    
                    locationDiv.innerHTML = `
                        <div class="font-medium text-gray-900">${shortName}</div>
                        <div class="text-sm text-gray-600">${displayName}</div>
                    `;
                    
                    locationDiv.addEventListener('click', function() {
                        selectLocation(shortName, location.lat, location.lon, displayName);
                    });
                    
                    resultsDiv.appendChild(locationDiv);
                });
            })
            .catch(error => {
                console.error('Location search error:', error);
                resultsDiv.innerHTML = '<div class="p-3 text-red-500">Error searching locations</div>';
            });
        }
        
        function selectLocation(name, lat, lon, displayName) {
            // Update the search input
            document.getElementById('config_location_search').value = name;
            
            // Update the hidden fields
            document.getElementById('config_location').value = name;
            document.getElementById('config_latitude').value = lat;
            document.getElementById('config_longitude').value = lon;
            
            // Hide results
            document.getElementById('location_results').classList.add('hidden');
            
            console.log('Selected location:', { name, lat, lon, displayName });
        }


        // Initialize drag and drop for source ordering
        <?php if (count($sources) > 1): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const sourcesList = document.getElementById('sourcesList');
            if (sourcesList) {
                Sortable.create(sourcesList, {
                    handle: '.fa-grip-vertical',
                    animation: 150,
                    onEnd: function(evt) {
                        const sourceIds = Array.from(sourcesList.children).map(el => el.dataset.sourceId);
                        
                        // Send update request
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.innerHTML = `
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($auth->generateCSRFToken()); ?>">
                            <input type="hidden" name="action" value="update_source_order">
                            ${sourceIds.map(id => `<input type="hidden" name="source_ids[]" value="${id}">`).join('')}
                        `;
                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            }
        });
        <?php endif; ?>
        
        // Schedule options management
        function updateScheduleOptions() {
            const frequency = document.getElementById('frequency').value;
            const weeklyOptions = document.getElementById('weekly-options');
            const monthlyOptions = document.getElementById('monthly-options');
            const quarterlyOptions = document.getElementById('quarterly-options');
            
            // Hide all options first
            weeklyOptions.classList.add('hidden');
            monthlyOptions.classList.add('hidden');
            quarterlyOptions.classList.add('hidden');
            
            // Show relevant options based on frequency
            switch (frequency) {
                case 'weekly':
                    weeklyOptions.classList.remove('hidden');
                    break;
                case 'monthly':
                    monthlyOptions.classList.remove('hidden');
                    break;
                case 'quarterly':
                    monthlyOptions.classList.remove('hidden');
                    quarterlyOptions.classList.remove('hidden');
                    break;
            }
        }
    </script>

    <script src="/assets/js/main.js"></script>
    <script src="/assets/js/dashboard.js"></script>
    <script src="/assets/js/newsletter-editor.js"></script>
</body>
</html>