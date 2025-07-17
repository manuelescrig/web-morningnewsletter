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
require_once __DIR__ . '/../modules/ethereum.php';
require_once __DIR__ . '/../modules/xrp.php';
require_once __DIR__ . '/../modules/binancecoin.php';
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
// Get enabled modules from database
$db = Database::getInstance()->getConnection();
$stmt = $db->query("SELECT * FROM source_configs WHERE is_enabled = 1 ORDER BY category, name");
$enabledSourceConfigs = $stmt->fetchAll();

// Create available modules array based on enabled sources
$availableModules = [];
$moduleClasses = [
    'bitcoin' => 'BitcoinModule',
    'ethereum' => 'EthereumModule', 
    'xrp' => 'XrpModule',
    'binancecoin' => 'BinancecoinModule',
    'weather' => 'WeatherModule',
    'news' => 'NewsModule',
    'sp500' => 'SP500Module',
    'stripe' => 'StripeModule',
    'appstore' => 'AppStoreModule'
];

foreach ($enabledSourceConfigs as $config) {
    $type = $config['type'];
    if (isset($moduleClasses[$type]) && class_exists($moduleClasses[$type])) {
        $availableModules[$type] = new $moduleClasses[$type]();
    }
}

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
                    'frequency' => $_POST['frequency'] ?? 'daily',
                    'is_paused' => isset($_POST['is_paused']) ? 1 : 0
                ];
                
                // Handle send times - always use daily_times array
                $dailyTimes = $_POST['daily_times'] ?? [];
                $dailyTimes = array_filter($dailyTimes); // Remove empty values
                
                if (empty($dailyTimes)) {
                    // Fallback to legacy send_time or default
                    $sendTime = $_POST['send_time'] ?? '06:00';
                    $dailyTimes = [$sendTime];
                } else {
                    $sendTime = $dailyTimes[0]; // Use first time as primary
                }
                
                $updateData['send_time'] = $sendTime;
                $updateData['daily_times'] = json_encode($dailyTimes);
                
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
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
                       class="btn-pill bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 font-medium transition-colors duration-200"
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
                <!-- Add New Source Button -->
                <?php if ($canAddSource): ?>
                    <div class="bg-white rounded-lg shadow mb-6">
                        <div class="p-6">
                            <div class="text-center">
                                <h2 class="text-lg font-semibold text-gray-900 mb-2">Add Data Source</h2>
                                <p class="text-sm text-gray-600 mb-4">
                                    You can add <?php echo $maxSources - count($sources); ?> more source<?php echo ($maxSources - count($sources)) !== 1 ? 's' : ''; ?> 
                                    (<?php echo count($sources); ?>/<?php echo $maxSources; ?> used)
                                </p>
                                <button onclick="openAddSourceModal()" class="btn-pill bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 font-medium transition-colors duration-200 shadow-lg hover:shadow-xl">
                                    <i class="fas fa-plus mr-2"></i>
                                    Browse & Add Sources
                                </button>
                            </div>
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
                                <label for="frequency" class="block text-sm font-medium text-gray-700 mb-2">
                                    Frequency
                                </label>
                                <select name="frequency" id="frequency" onchange="updateScheduleOptions()"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="daily" <?php echo $newsletter->getFrequency() === 'daily' ? 'selected' : ''; ?>>Daily</option>
                                    <option value="weekly" <?php echo $newsletter->getFrequency() === 'weekly' ? 'selected' : ''; ?>>Weekly</option>
                                    <option value="monthly" <?php echo $newsletter->getFrequency() === 'monthly' ? 'selected' : ''; ?>>Monthly</option>
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
                                        <label class="flex items-center justify-center p-2 border rounded cursor-pointer hover:bg-gray-50 day-checkbox">
                                            <input type="checkbox" name="days_of_week[]" value="<?php echo $i; ?>" 
                                                   <?php echo in_array($i, $daysOfWeek) ? 'checked' : ''; ?>
                                                   class="sr-only" onchange="toggleDaySelection(this)">
                                            <span class="text-sm font-medium"><?php echo $dayNames[$i-1]; ?></span>
                                        </label>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            
                            <!-- Monthly Day Options -->
                            <div id="monthly-options" class="<?php echo $newsletter->getFrequency() !== 'monthly' ? 'hidden' : ''; ?>">
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
                            
                            <!-- Send Times (always visible, 15-minute intervals) -->
                            <div id="send-times-section">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Send Times (15-minute intervals only)
                                </label>
                                <div id="daily-times-container" class="space-y-2">
                                    <?php 
                                    $dailyTimes = $newsletter->getDailyTimes();
                                    if (empty($dailyTimes)) {
                                        $dailyTimes = [$newsletter->getSendTime()]; // Default to current send time
                                    }
                                    foreach ($dailyTimes as $index => $time): 
                                    ?>
                                        <div class="flex items-center gap-2">
                                            <select name="daily_times[]" 
                                                    class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                                <?php for ($h = 0; $h < 24; $h++): ?>
                                                    <?php for ($m = 0; $m < 60; $m += 15): ?>
                                                        <?php 
                                                        $timeValue = sprintf('%02d:%02d', $h, $m);
                                                        $timeDisplay = date('g:i A', strtotime($timeValue));
                                                        $selected = ($timeValue === $time) ? 'selected' : '';
                                                        ?>
                                                        <option value="<?php echo $timeValue; ?>" <?php echo $selected; ?>>
                                                            <?php echo $timeDisplay; ?>
                                                        </option>
                                                    <?php endfor; ?>
                                                <?php endfor; ?>
                                            </select>
                                            <?php if (count($dailyTimes) > 1): ?>
                                                <button type="button" onclick="removeDailyTime(this)" class="text-red-600 hover:text-red-800 px-2">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            <?php else: ?>
                                                <div class="px-2 w-8"></div> <!-- Spacer for alignment -->
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <button type="button" onclick="addDailyTime()" class="mt-2 text-blue-600 hover:text-blue-800 text-sm">
                                    <i class="fas fa-plus mr-1"></i> Add another time
                                </button>
                                <p class="text-xs text-gray-500 mt-1">Add multiple send times for each scheduled day. Times are restricted to 15-minute intervals to match the cron schedule.</p>
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
                            
                            <button type="submit" class="btn-pill w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 font-medium transition-colors duration-200">
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
                                <span class="font-medium"><?php 
                                    $userTimezone = new DateTimeZone($newsletter->getTimezone());
                                    $currentTime = new DateTime('now', $userTimezone);
                                    echo $currentTime->format('M j, g:i A'); 
                                ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Next Send:</span>
                                <span class="font-medium"><?php echo $scheduleStatus['next_send_object']->format('M j, g:i A'); ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Last Sent:</span>
                                <span class="font-medium">
                                    <?php if ($scheduleStatus['last_sent']): ?>
                                        <span class="text-blue-600"><?php echo $scheduleStatus['last_sent']->format('M j, g:i A'); ?></span>
                                    <?php else: ?>
                                        <span class="text-gray-500">Never</span>
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
            // Find the source data from the sources array
            const sources = <?php echo json_encode($sources); ?>;
            const source = sources.find(s => s.id == sourceId);
            
            if (source) {
                const config = source.config ? JSON.parse(source.config) : {};
                
                // Populate the form
                document.getElementById('editSourceId').value = sourceId;
                document.getElementById('editSourceType').value = source.type;
                document.getElementById('editSourceName').value = source.name || '';
                
                // Hide all config sections and disable ALL their fields
                document.querySelectorAll('[id^="editConfig"]').forEach(section => {
                    section.classList.add('hidden');
                    // Disable ALL fields in hidden sections so they don't get submitted
                    section.querySelectorAll('input, select, textarea').forEach(field => {
                        field.disabled = true;
                        if (field.hasAttribute('required')) {
                            field.removeAttribute('required');
                            field.setAttribute('data-was-required', 'true');
                        }
                    });
                });
                
                // Show relevant config section and populate fields
                const configSection = document.getElementById(`editConfig${source.type.charAt(0).toUpperCase() + source.type.slice(1)}`);
                if (configSection) {
                    configSection.classList.remove('hidden');
                    
                    // Re-enable ALL fields for the visible section
                    configSection.querySelectorAll('input, select, textarea').forEach(field => {
                        field.disabled = false;
                        if (field.hasAttribute('data-was-required')) {
                            field.setAttribute('required', 'required');
                        }
                    });
                    
                    // Populate config fields based on source type
                    if (source.type === 'weather') {
                        document.getElementById('edit_weather_location_search').value = config.location || '';
                        document.getElementById('edit_weather_location').value = config.location || '';
                        document.getElementById('edit_weather_latitude').value = config.latitude || '';
                        document.getElementById('edit_weather_longitude').value = config.longitude || '';
                        
                        // Set up location search
                        setupEditLocationSearch();
                    } else if (source.type === 'news') {
                        document.getElementById('edit_news_api_key').value = config.api_key || '';
                        document.getElementById('edit_news_country').value = config.country || 'us';
                        document.getElementById('edit_news_category').value = config.category || 'general';
                        document.getElementById('edit_news_limit').value = config.limit || '5';
                    } else if (source.type === 'stripe') {
                        document.getElementById('edit_stripe_api_key').value = config.api_key || '';
                    } else if (source.type === 'sp500') {
                        document.getElementById('edit_sp500_api_key').value = config.api_key || '';
                    }
                }
                
                // Open the modal
                Dashboard.modal.open('editSourceModal');
            }
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
            const searchInput = document.getElementById('edit_weather_location_search');
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
            document.getElementById('edit_weather_location_search').value = name;
            document.getElementById('edit_weather_location').value = name;
            document.getElementById('edit_weather_latitude').value = lat;
            document.getElementById('edit_weather_longitude').value = lon;
            document.getElementById('edit_location_results').classList.add('hidden');
        }
        
        function closeEditModal() {
            const modal = document.getElementById('editSourceModal');
            if (modal) {
                modal.remove();
            }
        }

        // Delegate to external newsletter editor
        function deleteSource(sourceId) {
            // Find the source data from the sources array
            const sources = <?php echo json_encode($sources); ?>;
            const source = sources.find(s => s.id == sourceId);
            
            if (source) {
                const sourceName = source.name || source.type;
                const message = `Are you sure you want to delete "${sourceName}"? This action cannot be undone.`;
                
                if (confirm(message)) {
                    // Create form and submit
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = `
                        <input type="hidden" name="action" value="delete_source">
                        <input type="hidden" name="source_id" value="${sourceId}">
                        <input type="hidden" name="csrf_token" value="${window.csrfToken}">
                    `;
                    document.body.appendChild(form);
                    form.submit();
                }
            }
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
            const multipleDailyOptions = document.getElementById('multiple-daily-options');
            const weeklyOptions = document.getElementById('weekly-options');
            const monthlyOptions = document.getElementById('monthly-options');
            const quarterlyOptions = document.getElementById('quarterly-options');
            
            // Hide all options first
            multipleDailyOptions.classList.add('hidden');
            weeklyOptions.classList.add('hidden');
            monthlyOptions.classList.add('hidden');
            quarterlyOptions.classList.add('hidden');
            
            // Show relevant options based on frequency
            switch (frequency) {
                case 'multiple_daily':
                    multipleDailyOptions.classList.remove('hidden');
                    break;
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
        
        // Add daily time slot
        function addDailyTime() {
            const container = document.getElementById('daily-times-container');
            const newTimeDiv = document.createElement('div');
            newTimeDiv.className = 'flex items-center gap-2';
            
            // Generate time options for 15-minute intervals
            let timeOptions = '';
            for (let h = 0; h < 24; h++) {
                for (let m = 0; m < 60; m += 15) {
                    const timeValue = String(h).padStart(2, '0') + ':' + String(m).padStart(2, '0');
                    const timeObj = new Date('2000-01-01 ' + timeValue);
                    const timeDisplay = timeObj.toLocaleTimeString('en-US', {hour: 'numeric', minute: '2-digit', hour12: true});
                    const selected = (timeValue === '12:00') ? 'selected' : '';
                    timeOptions += `<option value="${timeValue}" ${selected}>${timeDisplay}</option>`;
                }
            }
            
            newTimeDiv.innerHTML = `
                <select name="daily_times[]" 
                        class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    ${timeOptions}
                </select>
                <button type="button" onclick="removeDailyTime(this)" class="text-red-600 hover:text-red-800 px-2">
                    <i class="fas fa-times"></i>
                </button>
            `;
            container.appendChild(newTimeDiv);
        }
        
        // Remove daily time slot
        function removeDailyTime(button) {
            const container = document.getElementById('daily-times-container');
            const timeDiv = button.parentElement;
            
            // Don't allow removing the last time slot
            if (container.children.length > 1) {
                timeDiv.remove();
            }
        }
        
        // Add Source Modal Functions
        function openAddSourceModal() {
            Dashboard.modal.open('addSourceModal');
            // Reset search and filters
            document.getElementById('sourceSearch').value = '';
            filterByCategory('all');
            hideSelectedSourceForm();
        }
        
        function filterSources(searchTerm) {
            const cards = document.querySelectorAll('.source-card');
            const term = searchTerm.toLowerCase();
            
            cards.forEach(card => {
                const title = card.dataset.title;
                const type = card.dataset.type;
                const visible = title.includes(term) || type.includes(term);
                card.style.display = visible ? 'block' : 'none';
            });
        }
        
        function filterByCategory(category) {
            // Update button states
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.classList.remove('active', 'bg-blue-600', 'text-white');
                btn.classList.add('bg-gray-200', 'text-gray-700');
            });
            event.target.classList.add('active', 'bg-blue-600', 'text-white');
            event.target.classList.remove('bg-gray-200', 'text-gray-700');
            
            // Filter source cards
            const cards = document.querySelectorAll('.source-card');
            cards.forEach(card => {
                const cardCategory = card.dataset.category;
                const visible = category === 'all' || cardCategory === category;
                card.style.display = visible ? 'block' : 'none';
            });
        }
        
        function selectSource(sourceType, sourceTitle) {
            // Update form
            document.getElementById('selectedSourceType').value = sourceType;
            document.getElementById('selectedSourceName').placeholder = `e.g., My ${sourceTitle}`;
            
            // Generate configuration fields
            generateSourceConfigFields(sourceType);
            
            // Show form
            showSelectedSourceForm();
        }
        
        function showSelectedSourceForm() {
            document.getElementById('selectedSourceForm').classList.remove('hidden');
            // Scroll to form
            document.getElementById('selectedSourceForm').scrollIntoView({ behavior: 'smooth' });
        }
        
        function hideSelectedSourceForm() {
            document.getElementById('selectedSourceForm').classList.add('hidden');
        }
        
        function generateSourceConfigFields(sourceType) {
            const configDiv = document.getElementById('selectedSourceConfig');
            let fieldsHtml = '';
            
            switch(sourceType) {
                case 'weather':
                    fieldsHtml = `
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Location *</label>
                            <div class="relative">
                                <input type="text" id="config_location_search" 
                                       placeholder="Search for a city or location..."
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <div id="location_results" class="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg hidden max-h-60 overflow-y-auto"></div>
                            </div>
                            <input type="hidden" id="config_location" name="config_location">
                            <input type="hidden" id="config_latitude" name="config_latitude">
                            <input type="hidden" id="config_longitude" name="config_longitude">
                            <p class="text-xs text-gray-500 mt-1">Search and select your city for accurate weather data</p>
                        </div>
                    `;
                    break;
                case 'news':
                    fieldsHtml = `
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">API Key *</label>
                            <input type="text" name="config[api_key]" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="Your NewsAPI key">
                            <p class="text-xs text-gray-500 mt-1">Get your free API key from <a href="https://newsapi.org" target="_blank" class="text-blue-600">NewsAPI</a></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Country</label>
                            <select name="config[country]" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="us">United States</option>
                                <option value="gb">United Kingdom</option>
                                <option value="ca">Canada</option>
                                <option value="au">Australia</option>
                                <option value="de">Germany</option>
                                <option value="fr">France</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                            <select name="config[category]" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="general">General</option>
                                <option value="business">Business</option>
                                <option value="technology">Technology</option>
                                <option value="sports">Sports</option>
                                <option value="health">Health</option>
                                <option value="science">Science</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Article Limit</label>
                            <input type="number" name="config[limit]" min="1" max="20" value="5"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    `;
                    break;
                case 'stripe':
                    fieldsHtml = `
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Secret API Key *</label>
                            <input type="text" name="config[api_key]" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="sk_live_... or sk_test_...">
                            <p class="text-xs text-gray-500 mt-1">Found in your Stripe Dashboard under Developers > API keys</p>
                        </div>
                    `;
                    break;
                case 'sp500':
                    fieldsHtml = `
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Alpha Vantage API Key *</label>
                            <input type="text" name="config[api_key]" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="Your Alpha Vantage API key">
                            <p class="text-xs text-gray-500 mt-1">Get your free API key from <a href="https://www.alphavantage.co/support/#api-key" target="_blank" class="text-blue-600">Alpha Vantage</a></p>
                        </div>
                    `;
                    break;
                case 'bitcoin':
                case 'ethereum':
                case 'xrp':
                case 'binancecoin':
                    fieldsHtml = `
                        <div class="bg-green-50 border border-green-200 rounded-md p-3">
                            <div class="flex">
                                <i class="fas fa-check-circle text-green-500 mr-2 mt-0.5"></i>
                                <p class="text-sm text-green-800">No configuration required! Cryptocurrency price data is provided free by Binance API with 24-hour comparison.</p>
                            </div>
                        </div>
                    `;
                    break;
                case 'appstore':
                    fieldsHtml = `
                        <div class="bg-yellow-50 border border-yellow-200 rounded-md p-3">
                            <div class="flex">
                                <i class="fas fa-info-circle text-yellow-500 mr-2 mt-0.5"></i>
                                <p class="text-sm text-yellow-800">App Store analytics integration is coming soon. This source is currently in development.</p>
                            </div>
                        </div>
                    `;
                    break;
                default:
                    fieldsHtml = '<p class="text-sm text-gray-500">No configuration required for this source.</p>';
            }
            
            configDiv.innerHTML = fieldsHtml;
            
            // Set up location search for weather sources
            if (sourceType === 'weather') {
                setupAddLocationSearch();
            }
        }
        
        function setupAddLocationSearch() {
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
                    searchAddLocations(query);
                }, 300);
            });
        }
        
        function searchAddLocations(query) {
            const resultsDiv = document.getElementById('location_results');
            
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
                        selectAddLocation(location.name, location.lat, location.lon);
                    });
                    
                    resultsDiv.appendChild(locationDiv);
                });
            })
            .catch(error => {
                resultsDiv.innerHTML = '<div class="p-3 text-red-500">Error searching locations</div>';
            });
        }
        
        function selectAddLocation(name, lat, lon) {
            document.getElementById('config_location_search').value = name;
            document.getElementById('config_location').value = name;
            document.getElementById('config_latitude').value = lat;
            document.getElementById('config_longitude').value = lon;
            document.getElementById('location_results').classList.add('hidden');
        }
    </script>

    <!-- Add Source Modal -->
    <div id="addSourceModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-10 mx-auto p-5 border w-full max-w-4xl shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-medium text-gray-900">Add Data Source</h3>
                    <button onclick="Dashboard.modal.close('addSourceModal')" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-lg"></i>
                    </button>
                </div>
                
                
                
                <!-- Sources by Category -->
                <div id="sourcesGrid" class="space-y-6">
                    <?php 
                    // Group available modules by category
                    $modulesByCategory = [];
                    foreach ($availableModules as $type => $module) {
                        $sourceConfig = null;
                        foreach ($enabledSourceConfigs as $config) {
                            if ($config['type'] === $type) {
                                $sourceConfig = $config;
                                break;
                            }
                        }
                        $category = $sourceConfig ? $sourceConfig['category'] : 'general';
                        if (!isset($modulesByCategory[$category])) {
                            $modulesByCategory[$category] = [];
                        }
                        $modulesByCategory[$category][$type] = $module;
                    }
                    
                    $categoryNames = [
                        'crypto' => 'Crypto',
                        'finance' => 'Finance', 
                        'lifestyle' => 'Lifestyle',
                        'news' => 'News',
                        'business' => 'Business',
                        'general' => 'General'
                    ];
                    
                    $categoryIcons = [
                        'crypto' => 'fas fa-coins',
                        'finance' => 'fas fa-chart-line',
                        'lifestyle' => 'fas fa-home',
                        'news' => 'fas fa-newspaper',
                        'business' => 'fas fa-briefcase',
                        'general' => 'fas fa-cog'
                    ];
                    ?>
                    
                    <?php foreach ($modulesByCategory as $category => $modules): ?>
                        <div class="source-category" data-category="<?php echo $category; ?>">
                            <!-- Category Header -->
                            <div class="flex items-center mb-3">
                                <div class="w-6 h-6 bg-gray-100 rounded-lg flex items-center justify-center mr-2">
                                    <i class="<?php echo $categoryIcons[$category] ?? 'fas fa-cog'; ?> text-gray-600 text-sm"></i>
                                </div>
                                <h3 class="text-lg font-medium text-gray-900">
                                    <?php echo $categoryNames[$category] ?? ucfirst($category); ?>
                                </h3>
                                <span class="ml-2 inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700">
                                    <?php echo count($modules); ?>
                                </span>
                            </div>
                            
                            <!-- Category Sources Grid -->
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <?php foreach ($modules as $type => $module): ?>
                        <?php
                        // Get source config from database
                        $sourceConfig = null;
                        foreach ($enabledSourceConfigs as $config) {
                            if ($config['type'] === $type) {
                                $sourceConfig = $config;
                                break;
                            }
                        }
                        
                        // Default icons for each source type
                        $iconMap = [
                            'bitcoin' => 'fab fa-bitcoin',
                            'ethereum' => 'fab fa-ethereum',
                            'xrp' => 'fas fa-coins',
                            'binancecoin' => 'fas fa-coins',
                            'weather' => 'fas fa-cloud-sun',
                            'news' => 'fas fa-newspaper',
                            'sp500' => 'fas fa-chart-line',
                            'stripe' => 'fab fa-stripe',
                            'appstore' => 'fab fa-app-store'
                        ];
                        
                        $info = [
                            'icon' => $iconMap[$type] ?? 'fas fa-cube',
                            'category' => $sourceConfig ? $sourceConfig['category'] : 'general',
                            'description' => $sourceConfig ? $sourceConfig['description'] : 'Data source'
                        ];
                        ?>
                        <div class="source-card border border-gray-200 rounded-lg p-4 hover:border-blue-300 hover:shadow-md transition-all duration-200 cursor-pointer" 
                             data-type="<?php echo $type; ?>" 
                             data-category="<?php echo $info['category']; ?>"
                             data-title="<?php echo strtolower($module->getTitle()); ?>"
                             onclick="selectSource('<?php echo $type; ?>', '<?php echo htmlspecialchars($module->getTitle()); ?>')">
                            <div class="flex items-start space-x-3">
                                <div class="flex-shrink-0">
                                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                        <i class="<?php echo $info['icon']; ?> text-blue-600 text-xl"></i>
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h4 class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($module->getTitle()); ?></h4>
                                    <p class="text-xs text-gray-500 mt-1"><?php echo $info['description']; ?></p>
                                    <div class="mt-2">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                            <?php echo ucfirst($info['category']); ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="flex-shrink-0">
                                    <i class="fas fa-plus text-gray-400"></i>
                                </div>
                            </div>
                        </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Selected Source Form -->
                <div id="selectedSourceForm" class="hidden mt-6 border-t pt-6">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($auth->generateCSRFToken()); ?>">
                        <input type="hidden" name="action" value="add_source">
                        <input type="hidden" id="selectedSourceType" name="source_type">
                        
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Display Name (Optional)</label>
                                <input type="text" name="source_name" id="selectedSourceName"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                       placeholder="Custom name for this source">
                            </div>
                            
                            <!-- Dynamic configuration fields will be inserted here -->
                            <div id="selectedSourceConfig"></div>
                            
                            <div class="flex justify-end space-x-3 pt-4">
                                <button type="button" onclick="Dashboard.modal.close('addSourceModal')" 
                                        class="btn-pill bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 transition-colors duration-200">
                                    Cancel
                                </button>
                                <button type="submit" 
                                        class="btn-pill bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 transition-colors duration-200">
                                    <i class="fas fa-plus mr-2"></i>
                                    Add Source
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Source Modal -->
    <div id="editSourceModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Edit Source</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="update_source">
                    <input type="hidden" id="editSourceId" name="source_id">
                    <input type="hidden" id="editSourceType" name="source_type">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($auth->generateCSRFToken()); ?>">
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Name</label>
                            <input type="text" id="editSourceName" name="source_name" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <!-- Weather Config -->
                        <div id="editConfigWeather" class="hidden">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Location *</label>
                                <div class="relative">
                                    <input type="text" id="edit_weather_location_search" name="config_location_search" 
                                           placeholder="Search for a city or location..."
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <div id="edit_location_results" class="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg hidden max-h-60 overflow-y-auto"></div>
                                </div>
                                <input type="hidden" id="edit_weather_location" name="config_location">
                                <input type="hidden" id="edit_weather_latitude" name="config_latitude">
                                <input type="hidden" id="edit_weather_longitude" name="config_longitude">
                            </div>
                        </div>
                        
                        <!-- News Config -->
                        <div id="editConfigNews" class="hidden">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">API Key *</label>
                                <input type="text" id="edit_news_api_key" name="config_api_key" required
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Country</label>
                                <select id="edit_news_country" name="config_country" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="us">United States</option>
                                    <option value="gb">United Kingdom</option>
                                    <option value="ca">Canada</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                                <select id="edit_news_category" name="config_category" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="general">General</option>
                                    <option value="business">Business</option>
                                    <option value="technology">Technology</option>
                                    <option value="sports">Sports</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Article Limit</label>
                                <input type="number" id="edit_news_limit" name="config_limit" min="1" max="20" value="5"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>
                        
                        <!-- Stripe Config -->
                        <div id="editConfigStripe" class="hidden">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">API Key *</label>
                                <input type="text" id="edit_stripe_api_key" name="config_api_key" required
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>
                        
                        <!-- SP500 Config -->
                        <div id="editConfigSp500" class="hidden">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">API Key *</label>
                                <input type="text" id="edit_sp500_api_key" name="config_api_key" required
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>
                        
                        <!-- Bitcoin Config (no config needed) -->
                        <div id="editConfigBitcoin" class="hidden">
                            <p class="text-sm text-gray-500">No configuration required for Bitcoin price data.</p>
                        </div>
                        
                        <!-- Ethereum Config (no config needed) -->
                        <div id="editConfigEthereum" class="hidden">
                            <p class="text-sm text-gray-500">No configuration required for Ethereum price data.</p>
                        </div>
                        
                        <!-- Tether Config (no config needed) -->
                        <div id="editConfigTether" class="hidden">
                            <p class="text-sm text-gray-500">No configuration required for Tether price data.</p>
                        </div>
                        
                        <!-- XRP Config (no config needed) -->
                        <div id="editConfigXrp" class="hidden">
                            <p class="text-sm text-gray-500">No configuration required for XRP price data.</p>
                        </div>
                        
                        <!-- Binance Coin Config (no config needed) -->
                        <div id="editConfigBinancecoin" class="hidden">
                            <p class="text-sm text-gray-500">No configuration required for Binance Coin price data.</p>
                        </div>
                        
                        <!-- AppStore Config -->
                        <div id="editConfigAppstore" class="hidden">
                            <p class="text-sm text-gray-500">App Store configuration options will be available soon.</p>
                        </div>
                    </div>
                    
                    <div class="mt-6 flex justify-end space-x-2">
                        <button type="button" onclick="Dashboard.modal.close('editSourceModal')" 
                                class="btn-pill bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 transition-colors duration-200">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="btn-pill bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 transition-colors duration-200">
                            Update Source
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="/assets/js/main.js"></script>
    <script src="/assets/js/dashboard.js"></script>
    <script src="/assets/js/newsletter-editor.js"></script>
</body>
</html>