<?php
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/NewsletterHistory.php';

$auth = Auth::getInstance();
$auth->requireAuth();

$user = $auth->getCurrentUser();
$historyId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$fromEmail = isset($_GET['from']) && $_GET['from'] === 'email';

if (!$historyId) {
    header('Location: /dashboard/history.php');
    exit;
}

$historyManager = new NewsletterHistory();
$historyEntry = $historyManager->getHistoryEntry($historyId, $user->getId());

if (!$historyEntry) {
    header('Location: /dashboard/history.php');
    exit;
}

$currentPage = 'history';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($historyEntry['title']); ?> - Newsletter History</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <?php include __DIR__ . '/includes/lucide-head.php'; ?>
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="/assets/css/dashboard.css">
</head>
<body class="bg-gray-50">
    <?php include __DIR__ . '/includes/navigation.php'; ?>
    
    <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Header -->
        <div class="mb-8">
            <?php if ($fromEmail): ?>
            <div class="mb-6 bg-primary-lightest border border-primary-light rounded-md p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-info-circle text-primary-light"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-primary-dark">
                            You've been redirected from your email's "View in Browser" link. You're now viewing this newsletter in your dashboard history.
                        </p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            <nav class="flex mb-6" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-0.5">
                    <li class="inline-flex items-center">
                        <a href="/dashboard/history.php" class="breadcrumb-link inline-flex items-center text-sm font-medium text-gray-600 hover:text-primary">
                            History
                        </a>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <i class="fas fa-angle-right text-gray-400 mx-2 text-base"></i>
                            <span class="text-sm font-medium text-gray-500">Issue #<?php echo $historyEntry['issue_number']; ?></span>
                        </div>
                    </li>
                </ol>
            </nav>
            
            <div class="flex justify-between items-start">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dashboard-title">
                        <?php echo htmlspecialchars($historyEntry['newsletter_title']); ?>
                        <span class="ml-2 text-lg text-gray-500">
                            #<?php echo $historyEntry['issue_number']; ?>
                        </span>
                    </h1>
                    
                    <div class="mt-2 flex items-center text-sm text-gray-600">
                        <i class="fas fa-calendar mr-2"></i>
                        <span><?php echo date('F j, Y g:i A', strtotime($historyEntry['sent_at'])); ?></span>
                        
                        <?php if ($historyEntry['email_status'] === 'sent' || $historyEntry['email_status'] === 'failed'): ?>
                        <span class="mx-3">•</span>
                        <div>
                            <?php if ($historyEntry['email_status'] === 'sent'): ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    <i class="fas fa-check-circle mr-1"></i>
                                    Sent
                                </span>
                            <?php elseif ($historyEntry['email_status'] === 'failed'): ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    <i class="fas fa-times-circle mr-1"></i>
                                    Failed
                                </span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($historyEntry['email_status'] === 'failed' && $historyEntry['error_message']): ?>
                        <div class="mt-3 p-3 bg-red-50 border border-red-200 rounded-md">
                            <div class="flex">
                                <i class="fas fa-exclamation-triangle text-red-500 mr-2 mt-0.5"></i>
                                <div>
                                    <p class="text-sm text-red-800">
                                        <strong>Email delivery failed:</strong> <?php echo htmlspecialchars($historyEntry['error_message']); ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="flex space-x-3">
                    <button onclick="printNewsletter()" 
                            class="btn-pill btn-secondary-dark px-4 py-2 font-medium">
                        <i class="fas fa-print mr-2"></i>
                        Print
                    </button>
                    
                    <a href="/dashboard/history.php?newsletter_id=<?php echo $historyEntry['newsletter_id']; ?>" 
                       class="btn-pill bg-primary hover-bg-primary-dark text-white px-4 py-2 font-medium transition-colors duration-200">
                        <i class="fas fa-list mr-2"></i>
                        All Issues
                    </a>
                </div>
            </div>
        </div>

        <!-- Newsletter Content -->
        <div class="bg-white shadow-lg rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h2 class="text-lg font-semibold text-gray-900">Newsletter Content</h2>
                <p class="text-sm text-gray-600 mt-1">This is exactly what was sent to your email</p>
            </div>
            
            <div id="newsletter-content" class="p-6">
                <!-- Newsletter HTML Content -->
                <div class="newsletter-preview">
                    <?php echo $historyEntry['content']; ?>
                </div>
            </div>
        </div>

    </div>

    <script src="/assets/js/main.js"></script>
    <script src="/assets/js/dashboard.js"></script>
    <?php include __DIR__ . '/includes/lucide-init.php'; ?>
    <script>
        function printNewsletter() {
            Dashboard.print.hideElementsAndPrint(['nav', '.max-w-7xl > .mb-6']);
        }
        
        // Override legacy font declarations in newsletter content
        document.addEventListener('DOMContentLoaded', function() {
            const newsletterPreview = document.querySelector('.newsletter-preview');
            if (newsletterPreview) {
                // Find and modify any style elements within the newsletter
                const styleElements = newsletterPreview.querySelectorAll('style');
                styleElements.forEach(function(style) {
                    if (style.textContent.includes('Segoe UI')) {
                        style.textContent = style.textContent.replace(
                            /font-family:\s*[^;]+;/g, 
                            'font-family: ui-sans-serif, system-ui, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";'
                        );
                    }
                });
                
                // Also override any inline styles
                const elementsWithFontFamily = newsletterPreview.querySelectorAll('[style*="font-family"]');
                elementsWithFontFamily.forEach(function(element) {
                    const currentStyle = element.getAttribute('style');
                    const newStyle = currentStyle.replace(
                        /font-family:\s*[^;]+;?/g, 
                        'font-family: ui-sans-serif, system-ui, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";'
                    );
                    element.setAttribute('style', newStyle);
                });
            }
        });
    </script>
</body>
</html>