<?php
require_once __DIR__ . '/../core/Auth.php';
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
$scheduler = new Scheduler();
$scheduleStatus = $scheduler->getScheduleStatus($user);
$sources = $user->getSources();
$error = '';
$success = '';

$currentPage = 'dashboard';

// Handle source management actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $csrfToken = $_POST['csrf_token'] ?? '';
    
    if (!$auth->validateCSRFToken($csrfToken)) {
        $error = 'Invalid request. Please try again.';
    } else {
        switch ($action) {
            case 'update_source_order':
                $sourceIds = $_POST['source_ids'] ?? [];
                if (is_array($sourceIds) && !empty($sourceIds)) {
                    if ($user->updateSourceOrder($sourceIds)) {
                        $success = 'Source order updated successfully!';
                        // Refresh sources
                        $sources = $user->getSources();
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
                
                if ($user->updateSource($sourceId, $config, $sourceName)) {
                    $success = 'Source updated successfully!';
                    // Refresh sources
                    $sources = $user->getSources();
                } else {
                    $error = 'Failed to update source.';
                }
                break;
                
            case 'update_newsletter_title':
                $newTitle = trim($_POST['newsletter_title'] ?? '');
                if (empty($newTitle)) {
                    $newTitle = 'Your Morning Brief'; // Default fallback
                }
                
                if ($user->updateProfile(['newsletter_title' => $newTitle])) {
                    $success = 'Newsletter title updated successfully!';
                } else {
                    $error = 'Failed to update newsletter title.';
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
                                if ($user->addSource($sourceType, $config, $sourceName)) {
                                    $success = 'Source added successfully!';
                                    // Refresh sources
                                    $sources = $user->getSources();
                                } else {
                                    $error = 'Failed to add source. Please try again.';
                                }
                            } else {
                                $error = 'Invalid configuration for this source type.';
                            }
                        } else {
                            $error = 'Unknown source type.';
                        }
                    } catch (Exception $e) {
                        $error = 'Error: ' . $e->getMessage();
                    }
                } else {
                    $error = 'You have reached the source limit for your plan. Please upgrade to add more sources.';
                }
                break;
                
            case 'remove_source':
                $sourceId = $_POST['source_id'] ?? '';
                if ($user->removeSource($sourceId)) {
                    $success = 'Source removed successfully!';
                    // Refresh sources
                    $sources = $user->getSources();
                } else {
                    $error = 'Failed to remove source. Please try again.';
                }
                break;
        }
    }
}

// Get available source modules from database (only enabled ones)
$db = Database::getInstance()->getConnection();
$stmt = $db->query("SELECT * FROM source_configs WHERE is_enabled = 1 ORDER BY type");
$enabledSources = $stmt->fetchAll();

$availableModules = [];
foreach ($enabledSources as $sourceConfig) {
    $moduleClass = ucfirst($sourceConfig['type']) . 'Module';
    if (class_exists($moduleClass)) {
        $availableModules[$sourceConfig['type']] = new $moduleClass();
    }
}

$csrfToken = $auth->generateCSRFToken();
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
                                    <?php echo htmlspecialchars($scheduleStatus['next_send_object']->format('g:i A')); ?>
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

        <!-- Newsletter Section -->
        <div class="bg-white shadow rounded-lg mb-8">
            <div class="px-4 py-5 sm:p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center space-x-3">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Your Newsletter</h3>
                        <div class="flex items-center space-x-2">
                            <span class="text-sm text-gray-500">•</span>
                            <button id="newsletter-title-display" onclick="editNewsletterTitle()" class="text-sm text-blue-600 hover:text-blue-800 hover:underline cursor-pointer font-medium" title="Click to edit newsletter title">
                                "<?php echo htmlspecialchars($user->getNewsletterTitle()); ?>"
                            </button>
                            <i class="fas fa-edit text-xs text-gray-400"></i>
                        </div>
                    </div>
                    <a href="/preview.php" target="_blank" class="inline-flex items-center px-4 py-2 border border-blue-300 shadow-sm text-sm font-medium rounded-md text-blue-700 bg-blue-50 hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <i class="fas fa-eye mr-2"></i>
                        Preview Newsletter
                    </a>
                </div>
                
                <!-- Newsletter Title Edit Form (Hidden by default) -->
                <div id="newsletter-title-edit" class="hidden mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                    <form method="POST" class="flex items-center space-x-3">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                        <input type="hidden" name="action" value="update_newsletter_title">
                        <div class="flex-1">
                            <label for="newsletter_title_input" class="block text-sm font-medium text-gray-700 mb-1">Newsletter Title</label>
                            <input type="text" id="newsletter_title_input" name="newsletter_title" 
                                   value="<?php echo htmlspecialchars($user->getNewsletterTitle()); ?>"
                                   placeholder="Enter newsletter title"
                                   maxlength="100"
                                   class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        </div>
                        <div class="flex items-end space-x-2">
                            <button type="submit" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <i class="fas fa-check mr-1"></i>
                                Save
                            </button>
                            <button type="button" onclick="cancelNewsletterTitleEdit()" class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <i class="fas fa-times mr-1"></i>
                                Cancel
                            </button>
                        </div>
                    </form>
                    <p class="mt-2 text-xs text-gray-500">This title will appear at the top of your newsletter emails.</p>
                </div>
                
                <!-- Newsletter Drop Zone -->
                <div id="newsletter-drop-zone" class="border-2 border-dashed border-gray-300 rounded-lg p-6 min-h-[200px] transition-colors duration-200">
                    <?php if (empty($sources)): ?>
                        <div class="text-center">
                            <i class="fas fa-newspaper text-gray-300 text-4xl mb-4"></i>
                            <h4 class="text-lg font-medium text-gray-900 mb-2">Your Newsletter is Empty</h4>
                            <p class="text-gray-500 mb-4">Drag sources from below to add them to your newsletter</p>
                        </div>
                    <?php else: ?>
                        <div id="sortable-sources" class="grid grid-cols-1 gap-3">
                            <?php foreach ($sources as $source): ?>
                                <div class="source-item bg-gray-50 border border-gray-200 rounded-lg p-4 cursor-move" data-source-id="<?php echo $source['id']; ?>" data-source-type="<?php echo $source['type']; ?>" draggable="true">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center flex-1">
                                            <i class="fas fa-grip-vertical text-gray-400 mr-3"></i>
                                            <div class="flex-1">
                                                <h5 class="text-sm font-medium text-gray-900">
                                                    <?php 
                                                    if (!empty($source['name'])) {
                                                        echo htmlspecialchars($source['name']);
                                                    } else {
                                                        $moduleClass = ucfirst($source['type']) . 'Module';
                                                        if (class_exists($moduleClass)) {
                                                            $tempModule = new $moduleClass();
                                                            echo htmlspecialchars($tempModule->getTitle());
                                                        } else {
                                                            echo htmlspecialchars($source['type']);
                                                        }
                                                    }
                                                    ?>
                                                </h5>
                                                <?php if (!empty($source['name'])): ?>
                                                    <p class="text-xs text-gray-400">
                                                        <?php 
                                                        $moduleClass = ucfirst($source['type']) . 'Module';
                                                        if (class_exists($moduleClass)) {
                                                            $tempModule = new $moduleClass();
                                                            echo htmlspecialchars($tempModule->getTitle());
                                                        } else {
                                                            echo htmlspecialchars($source['type']);
                                                        }
                                                        ?>
                                                    </p>
                                                <?php endif; ?>
                                                <p class="text-xs text-gray-500">
                                                    Last updated: <?php echo $source['last_updated'] ? date('M j, g:i A', strtotime($source['last_updated'])) : 'Never'; ?>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                <i class="fas fa-check mr-1"></i>
                                                Active
                                            </span>
                                            <button onclick="editSource(<?php echo $source['id']; ?>, '<?php echo $source['type']; ?>', '<?php echo htmlspecialchars($source['name'] ?? '', ENT_QUOTES); ?>', <?php echo htmlspecialchars($source['config'] ?? '{}', ENT_QUOTES); ?>)" class="text-blue-600 hover:text-blue-500" title="Edit source">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button onclick="removeSource(<?php echo $source['id']; ?>)" class="text-red-600 hover:text-red-500" title="Remove source">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Available Sources -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Available Sources</h3>
                <p class="text-sm text-gray-600 mb-6">Drag sources to your newsletter above to add them. You can add multiple instances of the same source type with different configurations. You can use <?php echo $user->getSourceLimit() === PHP_INT_MAX ? 'unlimited' : $user->getSourceLimit(); ?> sources on your <?php echo ucfirst($user->getPlan()); ?> plan.</p>
                
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <?php 
                    $userSourceTypes = array_column($sources, 'type');
                    foreach ($availableModules as $type => $module): 
                        $sourceCount = count(array_filter($sources, function($source) use ($type) {
                            return $source['type'] === $type;
                        }));
                        $canAdd = $user->canAddSource();
                    ?>
                        <div class="available-source border border-gray-200 rounded-lg p-4 <?php echo $canAdd ? 'cursor-move hover:border-blue-300 hover:shadow-sm transition-all duration-200' : 'opacity-30'; ?>" 
                             data-source-type="<?php echo $type; ?>" 
                             <?php echo $canAdd ? 'draggable="true"' : ''; ?>>
                            <div class="flex items-center justify-between mb-2">
                                <h4 class="text-sm font-medium text-gray-900"><?php 
                                $sourceConfig = array_filter($enabledSources, function($s) use ($type) {
                                    return $s['type'] === $type;
                                });
                                $sourceConfig = reset($sourceConfig);
                                echo htmlspecialchars($sourceConfig['name'] ?? $module->getTitle());
                                ?></h4>
                                <?php if ($sourceCount > 0): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        <i class="fas fa-layers mr-1"></i>
                                        <?php echo $sourceCount; ?>
                                    </span>
                                <?php elseif (!$user->canAddSource()): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                        <i class="fas fa-lock mr-1"></i>
                                        Limit
                                    </span>
                                <?php else: ?>
                                    <i class="fas fa-grip-vertical text-gray-300"></i>
                                <?php endif; ?>
                            </div>
                            <p class="text-xs text-gray-500 mb-3">
                                <?php 
                                $sourceConfig = array_filter($enabledSources, function($s) use ($type) {
                                    return $s['type'] === $type;
                                });
                                $sourceConfig = reset($sourceConfig);
                                echo htmlspecialchars($sourceConfig['description'] ?? 'Data source module');
                                ?>
                            </p>
                            <?php if ($canAdd): ?>
                                <button onclick="showAddSourceModal('<?php echo $type; ?>')" class="w-full text-xs text-blue-600 hover:text-blue-500 border border-blue-200 rounded px-2 py-1 hover:bg-blue-50">
                                    <i class="fas fa-plus mr-1"></i>Configure & Add
                                </button>
                            <?php else: ?>
                                <div class="text-xs text-gray-400 text-center">
                                    <i class="fas fa-crown mr-1"></i>Upgrade plan to add more sources
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Source Modal -->
    <div id="add-source-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900" id="modal-title">Add Source</h3>
                    <button onclick="closeAddSourceModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <form id="add-source-form" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    <input type="hidden" name="action" value="add_source">
                    <input type="hidden" name="source_type" id="modal-source-type">
                    
                    <div class="mb-4">
                        <label for="source_name" class="block text-sm font-medium text-gray-700 mb-2">
                            Source Name (Optional)
                        </label>
                        <input type="text" id="source_name" name="source_name" 
                               placeholder="Give this source a custom name (e.g., 'New York Weather', 'Personal Stripe')"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <p class="mt-1 text-xs text-gray-500">Leave empty to use the default name</p>
                    </div>
                    
                    <div id="modal-config-fields"></div>
                    
                    <div class="flex justify-end space-x-3 mt-6">
                        <button type="button" onclick="closeAddSourceModal()" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            <i class="fas fa-plus mr-2"></i>
                            Add Source
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Source Modal -->
    <div id="edit-source-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900" id="edit-modal-title">Edit Source</h3>
                    <button onclick="closeEditSourceModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <form id="edit-source-form" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    <input type="hidden" name="action" value="update_source">
                    <input type="hidden" name="source_id" id="edit-source-id">
                    
                    <div class="mb-4">
                        <label for="edit_source_name" class="block text-sm font-medium text-gray-700 mb-2">
                            Source Name
                        </label>
                        <input type="text" id="edit_source_name" name="source_name" 
                               placeholder="Give this source a custom name"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <p class="mt-1 text-xs text-gray-500">Leave empty to use the default name</p>
                    </div>
                    
                    <div id="edit-modal-config-fields"></div>
                    
                    <div class="flex justify-end space-x-3 mt-6">
                        <button type="button" onclick="closeEditSourceModal()" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            <i class="fas fa-save mr-2"></i>
                            Update Source
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Module configurations for dynamic form generation
        const moduleConfigs = <?php 
        try {
            $configs = [];
            foreach ($availableModules as $type => $module) {
                $configs[$type] = $module->getConfigFields();
            }
            echo json_encode($configs);
        } catch (Exception $e) {
            error_log("Error generating module configs: " . $e->getMessage());
            echo '{}';
        }
        ?>;

        let draggedElement = null;

        // Newsletter title editing functionality
        function editNewsletterTitle() {
            const displayElement = document.getElementById('newsletter-title-display');
            const editElement = document.getElementById('newsletter-title-edit');
            const inputElement = document.getElementById('newsletter_title_input');
            
            // Hide display, show edit form
            displayElement.parentElement.parentElement.style.display = 'none';
            editElement.classList.remove('hidden');
            
            // Focus the input and select the text
            inputElement.focus();
            inputElement.select();
        }
        
        function cancelNewsletterTitleEdit() {
            const displayElement = document.getElementById('newsletter-title-display');
            const editElement = document.getElementById('newsletter-title-edit');
            const inputElement = document.getElementById('newsletter_title_input');
            
            // Reset input to original value
            inputElement.value = inputElement.defaultValue;
            
            // Show display, hide edit form
            displayElement.parentElement.parentElement.style.display = 'flex';
            editElement.classList.add('hidden');
        }
        
        // Handle escape key to cancel editing
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                const editElement = document.getElementById('newsletter-title-edit');
                if (!editElement.classList.contains('hidden')) {
                    cancelNewsletterTitleEdit();
                }
            }
        });

        // Drag and drop functionality
        document.addEventListener('DOMContentLoaded', function() {
            initializeDragAndDrop();
            initializeSortableSources();
        });

        function initializeDragAndDrop() {
            const dropZone = document.getElementById('newsletter-drop-zone');
            const availableSources = document.querySelectorAll('.available-source[draggable="true"]');
            
            // Add drag event listeners to available sources
            availableSources.forEach(source => {
                source.addEventListener('dragstart', function(e) {
                    draggedElement = this;
                    this.style.opacity = '0.5';
                    e.dataTransfer.effectAllowed = 'move';
                    e.dataTransfer.setData('text/html', this.outerHTML);
                });
                
                source.addEventListener('dragend', function(e) {
                    this.style.opacity = '1';
                    draggedElement = null;
                });
            });
            
            // Add drop zone event listeners
            dropZone.addEventListener('dragover', function(e) {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
                this.classList.add('border-blue-500', 'bg-blue-50');
            });
            
            dropZone.addEventListener('dragleave', function(e) {
                this.classList.remove('border-blue-500', 'bg-blue-50');
            });
            
            dropZone.addEventListener('drop', function(e) {
                e.preventDefault();
                this.classList.remove('border-blue-500', 'bg-blue-50');
                
                if (draggedElement) {
                    const sourceType = draggedElement.dataset.sourceType;
                    showAddSourceModal(sourceType);
                }
            });
        }

        function initializeSortableSources() {
            const sortableContainer = document.getElementById('sortable-sources');
            if (!sortableContainer) return;
            
            let draggedSourceElement = null;
            let dragOverElement = null;
            
            const sourceItems = sortableContainer.querySelectorAll('.source-item');
            
            sourceItems.forEach(item => {
                item.addEventListener('dragstart', function(e) {
                    draggedSourceElement = this;
                    this.style.opacity = '0.5';
                    e.dataTransfer.effectAllowed = 'move';
                });
                
                item.addEventListener('dragend', function(e) {
                    this.style.opacity = '1';
                    draggedSourceElement = null;
                    // Remove all drag-over indicators
                    sourceItems.forEach(item => {
                        item.classList.remove('border-blue-500', 'border-t-4');
                    });
                    
                    // Update the order
                    updateSourceOrder();
                });
                
                item.addEventListener('dragover', function(e) {
                    e.preventDefault();
                    if (draggedSourceElement && this !== draggedSourceElement) {
                        // Remove previous indicators
                        sourceItems.forEach(item => {
                            item.classList.remove('border-blue-500', 'border-t-4');
                        });
                        
                        // Add indicator to current item
                        this.classList.add('border-blue-500', 'border-t-4');
                        dragOverElement = this;
                    }
                });
                
                item.addEventListener('drop', function(e) {
                    e.preventDefault();
                    if (draggedSourceElement && this !== draggedSourceElement) {
                        // Determine if we should insert before or after
                        const rect = this.getBoundingClientRect();
                        const midpoint = rect.top + rect.height / 2;
                        const insertAfter = e.clientY > midpoint;
                        
                        if (insertAfter) {
                            this.parentNode.insertBefore(draggedSourceElement, this.nextSibling);
                        } else {
                            this.parentNode.insertBefore(draggedSourceElement, this);
                        }
                    }
                });
            });
        }
        
        function updateSourceOrder() {
            const sourceItems = document.querySelectorAll('#sortable-sources .source-item');
            const sourceIds = Array.from(sourceItems).map(item => item.dataset.sourceId);
            
            if (sourceIds.length === 0) return;
            
            // Send AJAX request to update order
            const formData = new FormData();
            formData.append('csrf_token', '<?php echo htmlspecialchars($csrfToken); ?>');
            formData.append('action', 'update_source_order');
            sourceIds.forEach((id, index) => {
                formData.append('source_ids[]', id);
            });
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            }).then(response => {
                if (response.ok) {
                    console.log('Source order updated successfully');
                } else {
                    console.error('Failed to update source order');
                }
            }).catch(error => {
                console.error('Error updating source order:', error);
            });
        }

        function showAddSourceModal(sourceType) {
            const modal = document.getElementById('add-source-modal');
            const title = document.getElementById('modal-title');
            const sourceTypeInput = document.getElementById('modal-source-type');
            const configFields = document.getElementById('modal-config-fields');
            
            // Set source type
            sourceTypeInput.value = sourceType;
            title.textContent = `Add ${getSourceName(sourceType)}`;
            
            // Clear source name field
            const sourceNameField = document.getElementById('source_name');
            if (sourceNameField) {
                sourceNameField.value = '';
            }
            
            // Clear and populate config fields
            configFields.innerHTML = '';
            
            if (moduleConfigs[sourceType]) {
                moduleConfigs[sourceType].forEach(field => {
                    createConfigField(field, configFields);
                });
            }
            
            // Show modal
            modal.classList.remove('hidden');
        }

        function closeAddSourceModal() {
            const modal = document.getElementById('add-source-modal');
            modal.classList.add('hidden');
            
            // Clear the source name field
            const sourceNameField = document.getElementById('source_name');
            if (sourceNameField) {
                sourceNameField.value = '';
            }
        }

        function createConfigField(field, container, prefix = '') {
            const fieldDiv = document.createElement('div');
            fieldDiv.className = 'mb-4';
            
            const fieldId = prefix + 'config_' + field.name;
            const fieldName = prefix ? prefix.replace('_', '') + 'config_' + field.name : 'config_' + field.name;
            
            let fieldHtml = `
                <label for="${fieldId}" class="block text-sm font-medium text-gray-700 mb-2">
                    ${field.label}${field.required ? ' *' : ''}
                </label>
            `;

            if (field.type === 'location_search') {
                fieldHtml += `
                    <div class="relative">
                        <input type="text" id="${fieldId}_search_input" placeholder="Type a city name (e.g., New York, London, Tokyo)..."
                               class="block w-full px-3 py-2 pl-10 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400"></i>
                        </div>
                        <div id="${fieldId}_search_results" class="hidden absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-y-auto"></div>
                        <div id="${fieldId}_search_loading" class="hidden absolute right-3 top-2">
                            <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-blue-500"></div>
                        </div>
                    </div>
                `;
            } else if (field.type === 'select') {
                fieldHtml += `<select id="${fieldId}" name="${fieldName}" ${field.required ? 'required' : ''} 
                                class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">`;
                Object.entries(field.options).forEach(([value, label]) => {
                    const selected = field.default === value ? 'selected' : '';
                    fieldHtml += `<option value="${value}" ${selected}>${label}</option>`;
                });
                fieldHtml += '</select>';
            } else if (field.type === 'textarea') {
                fieldHtml += `<textarea id="${fieldId}" name="${fieldName}" ${field.required ? 'required' : ''} 
                                rows="3" placeholder="${field.description || ''}"
                                class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">${field.default || ''}</textarea>`;
            } else if (field.type === 'hidden') {
                fieldHtml += `<input type="hidden" id="${fieldId}" name="${fieldName}" value="${field.default || ''}">`;
                // Don't show label or description for hidden fields
                fieldHtml = `<input type="hidden" id="${fieldId}" name="${fieldName}" value="${field.default || ''}">`;
            } else {
                const inputType = field.type === 'password' ? 'password' : (field.type === 'number' ? 'number' : 'text');
                fieldHtml += `<input type="${inputType}" id="${fieldId}" name="${fieldName}" ${field.required ? 'required' : ''} 
                                value="${field.default || ''}" placeholder="${field.description || ''}"
                                ${field.min ? `min="${field.min}"` : ''} ${field.max ? `max="${field.max}"` : ''}
                                class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">`;
            }

            if (field.description && field.type !== 'hidden') {
                fieldHtml += `<p class="mt-1 text-xs text-gray-500">${field.description}</p>`;
            }

            fieldDiv.innerHTML = fieldHtml;
            container.appendChild(fieldDiv);
            
            // Initialize location search if this is a location search field
            if (field.type === 'location_search') {
                initializeLocationSearch(fieldId);
            }
        }

        function getSourceName(sourceType) {
            const sourceConfigs = <?php echo json_encode($enabledSources); ?>;
            const sourceConfig = sourceConfigs.find(s => s.type === sourceType);
            return sourceConfig ? sourceConfig.name : sourceType;
        }

        function editSource(sourceId, sourceType, sourceName, configJson) {
            const modal = document.getElementById('edit-source-modal');
            const title = document.getElementById('edit-modal-title');
            const sourceIdInput = document.getElementById('edit-source-id');
            const sourceNameInput = document.getElementById('edit_source_name');
            const configFields = document.getElementById('edit-modal-config-fields');
            
            // Set basic info
            sourceIdInput.value = sourceId;
            sourceNameInput.value = sourceName;
            title.textContent = `Edit ${getSourceName(sourceType)}`;
            
            // Clear and populate config fields
            configFields.innerHTML = '';
            
            let config = {};
            try {
                config = JSON.parse(configJson);
            } catch (e) {
                console.warn('Could not parse config JSON:', configJson);
            }
            
            if (moduleConfigs[sourceType]) {
                moduleConfigs[sourceType].forEach(field => {
                    createConfigField(field, configFields, 'edit_');
                    
                    // Populate existing values
                    const fieldElement = document.getElementById('edit_config_' + field.name);
                    if (fieldElement && config[field.name] !== undefined) {
                        fieldElement.value = config[field.name];
                    }
                });
            }
            
            // Show modal
            modal.classList.remove('hidden');
        }

        function closeEditSourceModal() {
            const modal = document.getElementById('edit-source-modal');
            modal.classList.add('hidden');
        }

        function removeSource(sourceId) {
            if (confirm('Are you sure you want to remove this source from your newsletter?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    <input type="hidden" name="action" value="remove_source">
                    <input type="hidden" name="source_id" value="${sourceId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Close modal when clicking outside
        document.addEventListener('click', function(event) {
            const modal = document.getElementById('add-source-modal');
            if (event.target === modal) {
                closeAddSourceModal();
            }
        });

        // Location search functionality
        let searchTimeout;
        let lastSearchTime = 0;
        const MIN_SEARCH_INTERVAL = 1000; // 1 second minimum between searches
        
        function initializeLocationSearch(fieldId = 'config_location_search') {
            const searchInput = document.getElementById(fieldId + '_search_input');
            const resultsDiv = document.getElementById(fieldId + '_search_results');
            const loadingDiv = document.getElementById(fieldId + '_search_loading');
            
            if (!searchInput) return;
            
            searchInput.addEventListener('input', function() {
                const query = this.value.trim();
                
                clearTimeout(searchTimeout);
                
                if (query.length < 2) {
                    resultsDiv.classList.add('hidden');
                    return;
                }
                
                // Rate limiting: ensure at least 1 second between searches
                const now = Date.now();
                const timeSinceLastSearch = now - lastSearchTime;
                
                if (timeSinceLastSearch < MIN_SEARCH_INTERVAL) {
                    const delay = MIN_SEARCH_INTERVAL - timeSinceLastSearch;
                    searchTimeout = setTimeout(() => {
                        searchLocations(query, fieldId);
                    }, delay);
                } else {
                    searchTimeout = setTimeout(() => {
                        searchLocations(query, fieldId);
                    }, 300);
                }
            });
            
            // Hide results when clicking outside
            document.addEventListener('click', function(event) {
                if (!searchInput.contains(event.target) && !resultsDiv.contains(event.target)) {
                    resultsDiv.classList.add('hidden');
                }
            });
        }
        
        async function searchLocations(query, fieldId = 'config_location_search') {
            const resultsDiv = document.getElementById(fieldId + '_search_results');
            const loadingDiv = document.getElementById(fieldId + '_search_loading');
            
            // Update last search time
            lastSearchTime = Date.now();
            
            try {
                loadingDiv.classList.remove('hidden');
                resultsDiv.classList.add('hidden');
                
                // Use the geocoding API in the root directory (workaround for server config)
                const apiUrl = `/geocoding.php?q=${encodeURIComponent(query)}`;
                
                const response = await fetch(apiUrl);
                const data = await response.json();
                
                loadingDiv.classList.add('hidden');
                
                if (!response.ok) {
                    if (response.status === 429) {
                        throw new Error('Rate limit exceeded. Please wait a moment and try again.');
                    }
                    throw new Error(data.error || 'Failed to search locations');
                }
                
                if (data.error) {
                    throw new Error(data.error);
                }
                
                displayLocationResults(data.results || [], fieldId);
                
            } catch (error) {
                loadingDiv.classList.add('hidden');
                console.error('Location search error:', error);
                
                let errorMessage = 'Failed to search locations. Please try again.';
                if (error.message.includes('Rate limit')) {
                    errorMessage = 'Too many requests. Please wait a moment and try again.';
                } else if (error.message.includes('Network')) {
                    errorMessage = 'Network error. Please check your connection and try again.';
                }
                
                resultsDiv.innerHTML = `
                    <div class="p-3 text-sm text-red-600">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        ${errorMessage}
                    </div>
                `;
                resultsDiv.classList.remove('hidden');
            }
        }
        
        function displayLocationResults(results, fieldId = 'config_location_search') {
            const resultsDiv = document.getElementById(fieldId + '_search_results');
            
            if (results.length === 0) {
                resultsDiv.innerHTML = `
                    <div class="p-3 text-sm text-gray-500">
                        <i class="fas fa-search mr-2"></i>
                        No locations found. Try a different search term.
                    </div>
                `;
                resultsDiv.classList.remove('hidden');
                return;
            }
            
            let html = '';
            results.forEach((location, index) => {
                html += `
                    <div class="location-result p-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-b-0" 
                         onclick="selectLocation('${location.name.replace(/'/g, "\\'")}', ${location.latitude}, ${location.longitude}, '${fieldId}')">
                        <div class="flex items-center">
                            <i class="fas fa-map-marker-alt text-gray-400 mr-2"></i>
                            <div class="font-medium text-gray-900">${location.name}</div>
                        </div>
                    </div>
                `;
            });
            
            resultsDiv.innerHTML = html;
            resultsDiv.classList.remove('hidden');
        }
        
        function selectLocation(name, latitude, longitude, fieldId = 'config_location_search') {
            // Determine field names based on prefix
            const locationFieldId = fieldId.replace('location_search', 'location');
            const latFieldId = fieldId.replace('location_search', 'latitude');  
            const lonFieldId = fieldId.replace('location_search', 'longitude');
            
            // Fill in the form fields
            const locationField = document.getElementById(locationFieldId);
            const latField = document.getElementById(latFieldId);
            const lonField = document.getElementById(lonFieldId);
            const searchInput = document.getElementById(fieldId + '_search_input');
            const resultsDiv = document.getElementById(fieldId + '_search_results');
            
            if (locationField) locationField.value = name;
            if (latField) latField.value = latitude;
            if (lonField) lonField.value = longitude;
            if (searchInput) searchInput.value = name;
            
            // Hide results
            resultsDiv.classList.add('hidden');
            
            // Show success feedback
            if (searchInput) {
                searchInput.style.borderColor = '#10B981';
                setTimeout(() => {
                    searchInput.style.borderColor = '';
                }, 1000);
            }
        }
    </script>
</body>
</html>