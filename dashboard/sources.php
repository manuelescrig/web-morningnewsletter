<?php
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/SourceModule.php';

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

// Restrict access to admin users only
if (!$user->isAdmin()) {
    header('Location: /dashboard/');
    exit();
}

$error = '';
$success = '';

$currentPage = 'sources';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $csrfToken = $_POST['csrf_token'] ?? '';
    
    if (!$auth->validateCSRFToken($csrfToken)) {
        $error = 'Invalid request. Please try again.';
    } else {
        $db = Database::getInstance()->getConnection();
        
        switch ($action) {
            case 'update_source':
                $sourceId = $_POST['source_id'] ?? '';
                $name = $_POST['name'] ?? '';
                $description = $_POST['description'] ?? '';
                $category = $_POST['category'] ?? '';
                $isEnabled = isset($_POST['is_enabled']) ? 1 : 0;
                
                try {
                    $stmt = $db->prepare("
                        UPDATE source_configs 
                        SET name = ?, description = ?, category = ?, is_enabled = ?, updated_at = CURRENT_TIMESTAMP 
                        WHERE id = ?
                    ");
                    
                    if ($stmt->execute([$name, $description, $category, $isEnabled, $sourceId])) {
                        $success = 'Source configuration updated successfully';
                    } else {
                        $error = 'Failed to update source configuration';
                    }
                } catch (Exception $e) {
                    $error = 'Error updating source: ' . $e->getMessage();
                }
                break;
        }
    }
}

// Get all source configurations grouped by category
$db = Database::getInstance()->getConnection();
$stmt = $db->query("SELECT * FROM source_configs ORDER BY category, type");
$sourceConfigs = $stmt->fetchAll();

// Group sources by category
$sourcesByCategory = [];
foreach ($sourceConfigs as $config) {
    $category = $config['category'] ?? 'general';
    if (!isset($sourcesByCategory[$category])) {
        $sourcesByCategory[$category] = [];
    }
    $sourcesByCategory[$category][] = $config;
}

// Get usage statistics for each source type
$usageStats = [];
foreach ($sourceConfigs as $config) {
    $stmt = $db->prepare("
        SELECT COUNT(*) as user_count,
               COUNT(DISTINCT user_id) as unique_users
        FROM sources 
        WHERE type = ? AND is_active = 1
    ");
    $stmt->execute([$config['type']]);
    $usageStats[$config['type']] = $stmt->fetch();
}

$csrfToken = $auth->generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Source Configuration - MorningNewsletter</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <?php include __DIR__ . '/includes/lucide-head.php'; ?>
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="/assets/css/dashboard.css">
<body class="bg-gray-50">
    <?php include __DIR__ . '/includes/navigation.php'; ?>

    <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Page Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dashboard-title">Source Configuration</h1>
            <p class="mt-2 text-gray-600">
                Manage global settings for all available data sources. Configure which sources are available to users and their default settings.
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

        <!-- Source Configuration by Category -->
        <?php 
        $categoryIcons = [
            'crypto' => 'coins',
            'finance' => 'chart-line',
            'lifestyle' => 'home',
            'news' => 'newspaper',
            'business' => 'briefcase',
            'general' => 'cog'
        ];
        
        $categoryNames = [
            'crypto' => 'Crypto',
            'finance' => 'Finance',
            'lifestyle' => 'Lifestyle',
            'news' => 'News',
            'business' => 'Business',
            'general' => 'General'
        ];
        ?>
        
        <?php foreach ($sourcesByCategory as $category => $configs): ?>
            <div class="mb-8">
                <!-- Category Header -->
                <div class="flex items-center mb-4">
                    <div class="flex items-center space-x-2">
                        <div class="w-8 h-8 bg-gray-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-<?php echo $categoryIcons[$category] ?? 'cog'; ?> text-gray-600"></i>
                        </div>
                        <h2 class="text-xl font-semibold text-gray-900">
                            <?php echo htmlspecialchars($categoryNames[$category] ?? ucfirst($category)); ?>
                        </h2>
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700">
                            <?php echo count($configs); ?> source<?php echo count($configs) === 1 ? '' : 's'; ?>
                        </span>
                    </div>
                </div>
                
                <!-- Sources in Category -->
                <div class="grid grid-cols-1 gap-6">
                    <?php foreach ($configs as $config): ?>
                        <div class="bg-white shadow rounded-lg">
                            <div class="px-4 py-5 sm:p-6">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-3 mb-4">
                                            <div class="flex-shrink-0">
                                                <div class="w-10 h-10 bg-primary-lightest rounded-lg flex items-center justify-center">
                                                    <i class="fas fa-<?php 
                                                        $icons = [
                                                            'bitcoin' => 'bitcoin',
                                                            'ethereum' => 'ethereum',
                                                            'xrp' => 'coins',
                                                            'binancecoin' => 'coins',
                                                            'sp500' => 'chart-line',
                                                            'weather' => 'cloud-sun',
                                                            'news' => 'newspaper',
                                                            'appstore' => 'mobile-alt',
                                                            'stripe' => 'credit-card'
                                                        ];
                                                        echo $icons[$config['type']] ?? 'plug';
                                                    ?> text-primary"></i>
                                                </div>
                                            </div>
                                            <div class="flex-1">
                                                <h3 class="text-lg font-medium text-gray-900">
                                                    <?php echo htmlspecialchars($config['name']); ?>
                                                </h3>
                                                <p class="text-sm text-gray-500">
                                                    Type: <?php echo htmlspecialchars($config['type']); ?>
                                                    <?php if ($config['api_required']): ?>
                                                        <span class="inline-flex items-center ml-2 px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">
                                                            <i class="fas fa-key mr-1"></i>
                                                            API Required
                                                        </span>
                                                    <?php endif; ?>
                                                </p>
                                            </div>
                                            <div class="flex items-center space-x-4">
                                                <!-- Usage Statistics -->
                                                <div class="text-center">
                                                    <div class="text-lg font-semibold text-gray-900">
                                                        <?php echo $usageStats[$config['type']]['user_count'] ?? 0; ?>
                                                    </div>
                                                    <div class="text-xs text-gray-500">Total Uses</div>
                                                </div>
                                                <div class="text-center">
                                                    <div class="text-lg font-semibold text-gray-900">
                                                        <?php echo $usageStats[$config['type']]['unique_users'] ?? 0; ?>
                                                    </div>
                                                    <div class="text-xs text-gray-500">Users</div>
                                                </div>
                                                <!-- Status Toggle -->
                                                <div class="flex items-center">
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $config['is_enabled'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                                        <i class="fas fa-<?php echo $config['is_enabled'] ? 'check' : 'times'; ?> mr-1"></i>
                                                        <?php echo $config['is_enabled'] ? 'Enabled' : 'Disabled'; ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Configuration Form -->
                                        <form method="POST" class="space-y-4">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                            <input type="hidden" name="action" value="update_source">
                                            <input type="hidden" name="source_id" value="<?php echo $config['id']; ?>">
                                            
                                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                                <div>
                                                    <label for="name_<?php echo $config['id']; ?>" class="block text-sm font-medium text-gray-700 mb-1">
                                                        Display Name
                                                    </label>
                                                    <input type="text" 
                                                           id="name_<?php echo $config['id']; ?>" 
                                                           name="name" 
                                                           value="<?php echo htmlspecialchars($config['name']); ?>"
                                                           class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus-ring-primary sm:text-sm">
                                                </div>
                                                
                                                <div>
                                                    <label for="category_<?php echo $config['id']; ?>" class="block text-sm font-medium text-gray-700 mb-1">
                                                        Category
                                                    </label>
                                                    <select id="category_<?php echo $config['id']; ?>" 
                                                            name="category"
                                                            class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus-ring-primary sm:text-sm">
                                                        <option value="crypto" <?php echo ($config['category'] === 'crypto') ? 'selected' : ''; ?>>Crypto</option>
                                                        <option value="finance" <?php echo ($config['category'] === 'finance') ? 'selected' : ''; ?>>Finance</option>
                                                        <option value="lifestyle" <?php echo ($config['category'] === 'lifestyle') ? 'selected' : ''; ?>>Lifestyle</option>
                                                        <option value="news" <?php echo ($config['category'] === 'news') ? 'selected' : ''; ?>>News</option>
                                                        <option value="business" <?php echo ($config['category'] === 'business') ? 'selected' : ''; ?>>Business</option>
                                                        <option value="general" <?php echo ($config['category'] === 'general') ? 'selected' : ''; ?>>General</option>
                                                    </select>
                                                </div>
                                                
                                                <div class="flex items-end">
                                                    <label class="flex items-center">
                                                        <input type="checkbox" 
                                                               name="is_enabled" 
                                                               <?php echo $config['is_enabled'] ? 'checked' : ''; ?>
                                                               class="rounded border-gray-300 text-primary shadow-sm focus:border-primary-light focus:ring focus:ring-primary-lightest focus:ring-opacity-50">
                                                        <span class="ml-2 text-sm font-medium text-gray-700">
                                                            Available to users
                                                        </span>
                                                    </label>
                                                </div>
                                            </div>
                                            
                                            <div>
                                                <label for="description_<?php echo $config['id']; ?>" class="block text-sm font-medium text-gray-700 mb-1">
                                                    Description
                                                </label>
                                                <textarea id="description_<?php echo $config['id']; ?>" 
                                                          name="description" 
                                                          rows="2"
                                                          class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus-ring-primary sm:text-sm"
                                                          placeholder="Brief description of what this source provides"><?php echo htmlspecialchars($config['description']); ?></textarea>
                                            </div>
                                            
                                            <div class="flex justify-end">
                                                <button type="submit" 
                                                        class="btn-pill inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium text-white bg-primary hover-bg-primary-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus-ring-primary">
                                                    <i class="fas fa-save mr-2"></i>
                                                    Save Changes
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- Summary Statistics -->
        <div class="mt-8 bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Summary Statistics</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-primary">
                            <?php echo count($sourceConfigs); ?>
                        </div>
                        <p class="text-sm text-gray-600">Total Source Types</p>
                    </div>
                    
                    <div class="text-center">
                        <div class="text-2xl font-bold text-green-600">
                            <?php echo count(array_filter($sourceConfigs, function($s) { return $s['is_enabled']; })); ?>
                        </div>
                        <p class="text-sm text-gray-600">Enabled Sources</p>
                    </div>
                    
                    <div class="text-center">
                        <div class="text-2xl font-bold text-purple-600">
                            <?php 
                            $totalUses = array_sum(array_map(function($stats) { 
                                return $stats['user_count'] ?? 0; 
                            }, $usageStats));
                            echo $totalUses;
                            ?>
                        </div>
                        <p class="text-sm text-gray-600">Total Source Uses</p>
                    </div>
                    
                    <div class="text-center">
                        <div class="text-2xl font-bold text-orange-600">
                            <?php echo count(array_filter($sourceConfigs, function($s) { return $s['api_required']; })); ?>
                        </div>
                        <p class="text-sm text-gray-600">API-Based Sources</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="/assets/js/main.js"></script>
    <script src="/assets/js/dashboard.js"></script>
    <?php include __DIR__ . '/includes/lucide-init.php'; ?>
</body>
</html>