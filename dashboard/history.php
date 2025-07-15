<?php
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Newsletter.php';
require_once __DIR__ . '/../core/NewsletterHistory.php';

$auth = Auth::getInstance();
$auth->requireAuth();

$user = $auth->getCurrentUser();
$currentPage = 'history';

// Get parameters
$newsletterId = isset($_GET['newsletter_id']) ? (int)$_GET['newsletter_id'] : null;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

$historyManager = new NewsletterHistory();
$newsletters = Newsletter::findByUser($user->getId());

// Get history based on filters
if ($search) {
    $history = $historyManager->searchHistory($user->getId(), $search, $newsletterId, $perPage);
    $totalCount = count($history); // For search, we'll show all results on one page
} else if ($newsletterId) {
    $history = $historyManager->getHistory($newsletterId, $perPage, $offset);
    $totalCount = $historyManager->getHistoryCount($newsletterId);
} else {
    $history = $historyManager->getUserHistory($user->getId(), $perPage, $offset);
    // For user history, we'll estimate total count by checking if we got a full page
    $totalCount = ($page * $perPage) + (count($history) == $perPage ? 1 : 0);
}

$totalPages = ceil($totalCount / $perPage);

// Get selected newsletter for title
$selectedNewsletter = null;
if ($newsletterId) {
    foreach ($newsletters as $newsletter) {
        if ($newsletter->getId() == $newsletterId) {
            $selectedNewsletter = $newsletter;
            break;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>History - MorningNewsletter</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <?php include __DIR__ . '/includes/navigation.php'; ?>

    <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Page Header -->
        <div class="mb-8">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">History</h1>
                    <p class="mt-2 text-gray-600">
                        <?php if ($selectedNewsletter): ?>
                            View past issues of "<?php echo htmlspecialchars($selectedNewsletter->getTitle()); ?>"
                        <?php else: ?>
                            View all your past newsletter issues
                        <?php endif; ?>
                    </p>
                </div>
                
                <div class="flex space-x-3">
                    <?php if ($newsletterId): ?>
                        <a href="/dashboard/history.php" 
                           class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md font-medium transition-colors duration-200">
                            <i class="fas fa-list mr-2"></i>
                            All History
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Search and Filter -->
        <div class="mb-6 bg-white shadow rounded-lg">
            <div class="p-6">
                <form method="GET" class="flex flex-col md:flex-row gap-4">
                    <div class="flex-1">
                        <label for="search" class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                        <input type="text" 
                               name="search" 
                               id="search" 
                               value="<?php echo htmlspecialchars($search); ?>"
                               placeholder="Search newsletter content..."
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    
                    <div class="w-full md:w-64">
                        <label for="newsletter_id" class="block text-sm font-medium text-gray-700 mb-2">Newsletter</label>
                        <select name="newsletter_id" 
                                id="newsletter_id"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">All Newsletters</option>
                            <?php foreach ($newsletters as $newsletter): ?>
                                <option value="<?php echo $newsletter->getId(); ?>" 
                                        <?php echo $newsletterId == $newsletter->getId() ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($newsletter->getTitle()); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="flex items-end">
                        <button type="submit" 
                                class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md font-medium transition-colors duration-200">
                            <i class="fas fa-search mr-2"></i>
                            Search
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- History List -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">
                    Newsletter Issues
                    <?php if ($search): ?>
                        <span class="text-sm font-normal text-gray-600">- Search results for "<?php echo htmlspecialchars($search); ?>"</span>
                    <?php endif; ?>
                </h2>
            </div>
            
            <div class="divide-y divide-gray-200">
                <?php if (empty($history)): ?>
                    <div class="px-6 py-12 text-center">
                        <div class="text-gray-400 text-5xl mb-4">📰</div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No newsletter history yet</h3>
                        <p class="text-gray-600">
                            <?php if ($search): ?>
                                No newsletters found matching your search.
                            <?php else: ?>
                                Your newsletters will appear here once they start being sent.
                            <?php endif; ?>
                        </p>
                    </div>
                <?php else: ?>
                    <?php foreach ($history as $entry): ?>
                        <div class="px-6 py-4 hover:bg-gray-50 transition-colors duration-200 cursor-pointer" 
                             onclick="window.location.href='/dashboard/view-history.php?id=<?php echo $entry['id']; ?>'">
                            <div class="flex items-start">
                                <div class="flex-1">
                                    <div class="flex items-center">
                                        <h3 class="text-lg font-medium text-gray-900">
                                            <?php echo htmlspecialchars($entry['newsletter_title']); ?>
                                            <span class="ml-2 text-sm text-gray-500">
                                                #<?php echo $entry['issue_number']; ?>
                                            </span>
                                        </h3>
                                        
                                        <!-- Status Badge -->
                                        <?php if ($entry['email_status'] === 'sent' || $entry['email_status'] === 'failed'): ?>
                                        <div class="ml-3">
                                            <?php if ($entry['email_status'] === 'sent'): ?>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    <i class="fas fa-check-circle mr-1"></i>
                                                    Sent
                                                </span>
                                            <?php elseif ($entry['email_status'] === 'failed'): ?>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                    <i class="fas fa-times-circle mr-1"></i>
                                                    Failed
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="mt-2 flex items-center text-sm text-gray-600">
                                        <i class="fas fa-calendar mr-2"></i>
                                        <span><?php echo date('F j, Y g:i A', strtotime($entry['sent_at'])); ?></span>
                                        
                                        <?php if ($entry['email_status'] === 'failed' && $entry['error_message']): ?>
                                            <span class="mx-2">•</span>
                                            <span class="text-red-600">
                                                <i class="fas fa-exclamation-triangle mr-1"></i>
                                                <?php echo htmlspecialchars($entry['error_message']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1 && !$search): ?>
            <div class="mt-6 flex justify-center">
                <nav class="flex items-center space-x-2">
                    <?php if ($page > 1): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" 
                           class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors duration-200">
                            Previous
                        </a>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="px-3 py-2 text-sm font-medium text-blue-600 bg-blue-50 border border-blue-300 rounded-md">
                                <?php echo $i; ?>
                            </span>
                        <?php else: ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                               class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors duration-200">
                                <?php echo $i; ?>
                            </a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" 
                           class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors duration-200">
                            Next
                        </a>
                    <?php endif; ?>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>