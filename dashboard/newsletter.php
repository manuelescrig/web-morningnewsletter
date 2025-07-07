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

// Common timezones for dropdown
$timezones = [
    'UTC' => 'UTC',
    'America/New_York' => 'Eastern Time',
    'America/Chicago' => 'Central Time', 
    'America/Denver' => 'Mountain Time',
    'America/Los_Angeles' => 'Pacific Time',
    'Europe/London' => 'London',
    'Europe/Paris' => 'Paris',
    'Asia/Tokyo' => 'Tokyo',
    'Australia/Sydney' => 'Sydney'
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
                    'send_time' => $_POST['send_time'] ?? '06:00'
                ];
                
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
                
                if ($newsletter->canAddSource($user->getPlan())) {
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
                if ($newsletter->deleteSource($sourceId)) {
                    $success = 'Source deleted successfully!';
                    // Refresh sources
                    $sources = $newsletter->getSources();
                } else {
                    $error = 'Failed to delete source.';
                }
                break;
                
            case 'toggle_source':
                $sourceId = $_POST['source_id'] ?? '';
                $isActive = $_POST['is_active'] === '1';
                if ($newsletter->toggleSource($sourceId, $isActive)) {
                    $success = 'Source ' . ($isActive ? 'enabled' : 'disabled') . ' successfully!';
                    // Refresh sources
                    $sources = $newsletter->getSources();
                } else {
                    $error = 'Failed to toggle source.';
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
                            <i class="fas fa-home mr-2"></i>
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
            <!-- Left Column: Newsletter Settings -->
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

            <!-- Right Column: Data Sources -->
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
                                                        <p class="text-sm text-gray-600"><?php echo ucfirst($source['type']); ?> Source</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="flex items-center space-x-2">
                                                <form method="POST" class="inline">
                                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($auth->generateCSRFToken()); ?>">
                                                    <input type="hidden" name="action" value="toggle_source">
                                                    <input type="hidden" name="source_id" value="<?php echo $source['id']; ?>">
                                                    <input type="hidden" name="is_active" value="<?php echo $source['is_active'] ? '0' : '1'; ?>">
                                                    <button type="submit" class="text-sm px-3 py-1 rounded-md font-medium transition-colors duration-200 <?php echo $source['is_active'] ? 'bg-green-100 text-green-800 hover:bg-green-200' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'; ?>">
                                                        <?php echo $source['is_active'] ? 'Enabled' : 'Disabled'; ?>
                                                    </button>
                                                </form>
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
                                        
                                        <?php if ($source['config']): ?>
                                            <div class="mt-3 pt-3 border-t border-gray-100">
                                                <div class="text-sm text-gray-600">
                                                    <?php
                                                    $config = json_decode($source['config'], true);
                                                    if ($config) {
                                                        $configDisplay = [];
                                                        foreach ($config as $key => $value) {
                                                            if (!empty($value)) {
                                                                $configDisplay[] = ucfirst(str_replace('_', ' ', $key)) . ': ' . 
                                                                                 (strlen($value) > 50 ? substr($value, 0, 50) . '...' : $value);
                                                            }
                                                        }
                                                        echo htmlspecialchars(implode(' • ', $configDisplay));
                                                    }
                                                    ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Source configuration data
        const sourceConfigs = <?php echo json_encode(array_map(function($type, $module) {
            return [
                'type' => $type,
                'title' => $module->getTitle(),
                'fields' => $module->getConfigFields()
            ];
        }, array_keys($availableModules), $availableModules)); ?>;

        // Current sources data for editing
        const currentSources = <?php echo json_encode(array_map(function($source) {
            return [
                'id' => $source['id'],
                'type' => $source['type'],
                'name' => $source['name'],
                'config' => json_decode($source['config'], true) ?? []
            ];
        }, $sources)); ?>;

        function showSourceConfig(sourceType) {
            const configDiv = document.getElementById('sourceConfig');
            const fieldsDiv = document.getElementById('configFields');
            
            if (!sourceType) {
                configDiv.classList.add('hidden');
                return;
            }
            
            const config = sourceConfigs.find(c => c.type === sourceType);
            if (!config || !config.fields || config.fields.length === 0) {
                configDiv.classList.add('hidden');
                return;
            }
            
            fieldsDiv.innerHTML = '';
            config.fields.forEach(field => {
                const fieldDiv = document.createElement('div');
                fieldDiv.innerHTML = `
                    <label for="config_${field.name}" class="block text-sm font-medium text-gray-700 mb-1">
                        ${field.label}${field.required ? ' *' : ''}
                    </label>
                    <input type="${field.type}" name="config_${field.name}" id="config_${field.name}"
                           ${field.required ? 'required' : ''} 
                           placeholder="${field.placeholder || ''}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    ${field.description ? `<p class="text-xs text-gray-500 mt-1">${field.description}</p>` : ''}
                `;
                fieldsDiv.appendChild(fieldDiv);
            });
            
            configDiv.classList.remove('hidden');
        }

        function editSource(sourceId) {
            const source = currentSources.find(s => s.id == sourceId);
            if (!source) return;
            
            // For now, just alert - you could implement a modal here
            alert(`Edit source: ${source.name || source.type}\n\nConfiguration:\n${JSON.stringify(source.config, null, 2)}`);
        }

        function deleteSource(sourceId) {
            const source = currentSources.find(s => s.id == sourceId);
            if (!source) return;
            
            if (confirm(`Are you sure you want to delete "${source.name || source.type}"?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($auth->generateCSRFToken()); ?>">
                    <input type="hidden" name="action" value="delete_source">
                    <input type="hidden" name="source_id" value="${sourceId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
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
    </script>
</body>
</html>