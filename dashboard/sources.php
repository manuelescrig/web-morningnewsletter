<?php
require_once __DIR__ . '/../core/Auth.php';
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
$sources = $user->getSources();
$error = '';
$success = '';

// Available source types
$availableModules = [
    'bitcoin' => ['class' => 'BitcoinModule', 'name' => 'Bitcoin Price', 'description' => 'Track Bitcoin price and changes'],
    'sp500' => ['class' => 'SP500Module', 'name' => 'S&P 500 Index', 'description' => 'Monitor S&P 500 performance'],
    'weather' => ['class' => 'WeatherModule', 'name' => 'Weather', 'description' => 'Daily weather updates for your city'],
    'news' => ['class' => 'NewsModule', 'name' => 'News Headlines', 'description' => 'Top news headlines from trusted sources'],
    'appstore' => ['class' => 'AppStoreModule', 'name' => 'App Store Sales', 'description' => 'App Store Connect revenue tracking'],
    'stripe' => ['class' => 'StripeModule', 'name' => 'Stripe Revenue', 'description' => 'Track your Stripe payments and revenue']
];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $csrfToken = $_POST['csrf_token'] ?? '';
    
    if (!$auth->validateCSRFToken($csrfToken)) {
        $error = 'Invalid request. Please try again.';
    } else {
        switch ($action) {
            case 'add_source':
                $type = $_POST['source_type'] ?? '';
                $config = $_POST['config'] ?? [];
                
                if (!isset($availableModules[$type])) {
                    $error = 'Invalid source type';
                } elseif (!$user->canAddSource()) {
                    $error = 'You have reached your source limit for the current plan';
                } else {
                    try {
                        $moduleClass = $availableModules[$type]['class'];
                        $module = new $moduleClass($config);
                        
                        if ($module->validateConfig($config)) {
                            $user->addSource($type, $config);
                            $success = 'Source added successfully';
                            // Refresh sources
                            $sources = $user->getSources();
                        } else {
                            $error = 'Invalid configuration for this source type';
                        }
                    } catch (Exception $e) {
                        $error = $e->getMessage();
                    }
                }
                break;
                
            case 'remove_source':
                $sourceId = $_POST['source_id'] ?? '';
                if ($user->removeSource($sourceId)) {
                    $success = 'Source removed successfully';
                    // Refresh sources
                    $sources = $user->getSources();
                } else {
                    $error = 'Failed to remove source';
                }
                break;
        }
    }
}

// Handle delete via GET (with confirmation)
if (isset($_GET['delete'])) {
    $sourceId = $_GET['delete'];
    // In a real app, you'd show a confirmation modal, but for simplicity:
    $user->removeSource($sourceId);
    header('Location: /dashboard/sources.php?removed=1');
    exit;
}

if (isset($_GET['removed'])) {
    $success = 'Source removed successfully';
}

$csrfToken = $auth->generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sources - MorningNewsletter</title>
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
                        <a href="/dashboard/sources.php" class="border-blue-500 text-gray-900 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            Sources
                        </a>
                        <a href="/dashboard/schedule.php" class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            Schedule
                        </a>
                        <a href="/dashboard/settings.php" class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            Settings
                        </a>
                        <?php if ($user->isAdmin()): ?>
                        <a href="/dashboard/cron_status.php" class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
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

    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="px-4 py-6 sm:px-0">
            <h1 class="text-3xl font-bold text-gray-900">Data Sources</h1>
            <p class="mt-2 text-gray-600">
                Manage your newsletter data sources 
                (<?php echo $user->getSourceCount(); ?>/<?php echo $user->getSourceLimit() === PHP_INT_MAX ? '∞' : $user->getSourceLimit(); ?> used)
            </p>
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

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Current Sources -->
            <div class="lg:col-span-2">
                <div class="bg-white shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Your Active Sources</h3>
                        
                        <?php if (empty($sources)): ?>
                            <div class="text-center py-8">
                                <i class="fas fa-database text-gray-300 text-4xl mb-4"></i>
                                <p class="text-gray-500">No sources configured yet. Add your first source to get started.</p>
                            </div>
                        <?php else: ?>
                            <div class="space-y-4">
                                <?php foreach ($sources as $source): ?>
                                    <div class="border border-gray-200 rounded-lg p-4">
                                        <div class="flex items-center justify-between">
                                            <div class="flex-1">
                                                <h4 class="text-lg font-medium text-gray-900 capitalize">
                                                    <?php echo htmlspecialchars($availableModules[$source['type']]['name'] ?? $source['type']); ?>
                                                </h4>
                                                <p class="text-sm text-gray-500 mt-1">
                                                    <?php echo htmlspecialchars($availableModules[$source['type']]['description'] ?? ''); ?>
                                                </p>
                                                <p class="text-xs text-gray-400 mt-2">
                                                    Last updated: <?php echo $source['last_updated'] ? date('M j, Y g:i A', strtotime($source['last_updated'])) : 'Never'; ?>
                                                </p>
                                            </div>
                                            <div class="flex items-center space-x-2 ml-4">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    <i class="fas fa-check mr-1"></i>
                                                    Active
                                                </span>
                                                <form method="POST" class="inline">
                                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                                    <input type="hidden" name="action" value="remove_source">
                                                    <input type="hidden" name="source_id" value="<?php echo $source['id']; ?>">
                                                    <button type="submit" 
                                                            onclick="return confirm('Are you sure you want to remove this source?')"
                                                            class="text-red-600 hover:text-red-500">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Add New Source -->
            <div class="lg:col-span-1">
                <div class="bg-white shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Add New Source</h3>
                        
                        <?php if (!$user->canAddSource()): ?>
                            <div class="text-center py-8">
                                <i class="fas fa-lock text-gray-300 text-4xl mb-4"></i>
                                <h4 class="text-lg font-medium text-gray-900 mb-2">Source Limit Reached</h4>
                                <p class="text-gray-500 mb-4">Upgrade your plan to add more sources.</p>
                                <a href="/upgrade" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                                    <i class="fas fa-crown mr-2"></i>
                                    Upgrade Plan
                                </a>
                            </div>
                        <?php else: ?>
                            <form method="POST" id="add-source-form">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                <input type="hidden" name="action" value="add_source">
                                
                                <div class="mb-4">
                                    <label for="source_type" class="block text-sm font-medium text-gray-700 mb-2">Source Type</label>
                                    <select id="source_type" name="source_type" required onchange="showConfigFields(this.value)"
                                            class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                        <option value="">Select a source...</option>
                                        <?php foreach ($availableModules as $type => $info): ?>
                                            <option value="<?php echo $type; ?>"><?php echo htmlspecialchars($info['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- Dynamic config fields will be inserted here -->
                                <div id="config-fields"></div>

                                <button type="submit" 
                                        class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    <i class="fas fa-plus mr-2"></i>
                                    Add Source
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Available Sources Info -->
                <div class="mt-6 bg-white shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Available Sources</h3>
                        <div class="space-y-3">
                            <?php foreach ($availableModules as $type => $info): ?>
                                <div class="flex items-start">
                                    <div class="flex-1">
                                        <h4 class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($info['name']); ?></h4>
                                        <p class="text-xs text-gray-500"><?php echo htmlspecialchars($info['description']); ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const moduleConfigs = <?php echo json_encode(array_map(function($type, $info) use ($availableModules) {
            $moduleClass = $info['class'];
            $module = new $moduleClass();
            return $module->getConfigFields();
        }, array_keys($availableModules), $availableModules)); ?>;

        function showConfigFields(sourceType) {
            const configContainer = document.getElementById('config-fields');
            configContainer.innerHTML = '';

            if (!sourceType || !moduleConfigs[sourceType]) {
                return;
            }

            const fields = moduleConfigs[sourceType];
            
            fields.forEach(field => {
                const fieldDiv = document.createElement('div');
                fieldDiv.className = 'mb-4';
                
                let fieldHtml = `
                    <label for="config_${field.name}" class="block text-sm font-medium text-gray-700 mb-2">
                        ${field.label}${field.required ? ' *' : ''}
                    </label>
                `;

                if (field.type === 'select') {
                    fieldHtml += `<select id="config_${field.name}" name="config[${field.name}]" ${field.required ? 'required' : ''} 
                                    class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">`;
                    Object.entries(field.options).forEach(([value, label]) => {
                        const selected = field.default === value ? 'selected' : '';
                        fieldHtml += `<option value="${value}" ${selected}>${label}</option>`;
                    });
                    fieldHtml += '</select>';
                } else if (field.type === 'textarea') {
                    fieldHtml += `<textarea id="config_${field.name}" name="config[${field.name}]" ${field.required ? 'required' : ''} 
                                    rows="3" placeholder="${field.description || ''}"
                                    class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">${field.default || ''}</textarea>`;
                } else {
                    const inputType = field.type === 'password' ? 'password' : (field.type === 'number' ? 'number' : 'text');
                    fieldHtml += `<input type="${inputType}" id="config_${field.name}" name="config[${field.name}]" ${field.required ? 'required' : ''} 
                                    value="${field.default || ''}" placeholder="${field.description || ''}"
                                    ${field.min ? `min="${field.min}"` : ''} ${field.max ? `max="${field.max}"` : ''}
                                    class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">`;
                }

                if (field.description) {
                    fieldHtml += `<p class="mt-1 text-xs text-gray-500">${field.description}</p>`;
                }

                fieldDiv.innerHTML = fieldHtml;
                configContainer.appendChild(fieldDiv);
            });
        }
    </script>
</body>
</html>