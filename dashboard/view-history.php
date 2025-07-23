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

        <!-- Newsletter preview in device mockup -->
        <div class="bg-white rounded-xl shadow-2xl overflow-hidden">
            <!-- Email client mockup header -->
            <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="flex space-x-1">
                            <div class="w-3 h-3 bg-red-400 rounded-full"></div>
                            <div class="w-3 h-3 bg-yellow-400 rounded-full"></div>
                            <div class="w-3 h-3 bg-green-400 rounded-full"></div>
                        </div>
                        <span class="text-sm font-medium text-gray-600"><?php echo htmlspecialchars($historyEntry['newsletter_title']); ?></span>
                    </div>
                    <div class="flex items-center space-x-4 text-sm text-gray-500">
                        <span><i class="fas fa-envelope mr-1"></i> To: <?php echo htmlspecialchars($user->getEmail()); ?></span>
                        <span><i class="fas fa-calendar mr-1"></i> <?php echo date('F j, Y g:i A', strtotime($historyEntry['sent_at'])); ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Newsletter content with proper styling -->
            <div id="newsletter-content" class="newsletter-display" style="background-color: #f8f9fa; padding: 0;">
                <iframe 
                    srcdoc="<?php echo htmlspecialchars($historyEntry['content'], ENT_QUOTES); ?>" 
                    class="w-full border-0" 
                    style="min-height: 600px; background: white;"
                    onload="this.style.height = this.contentWindow.document.documentElement.scrollHeight + 'px';">
                </iframe>
            </div>
        </div>

    </div>

    <script src="/assets/js/main.js"></script>
    <script src="/assets/js/dashboard.js"></script>
    <?php include __DIR__ . '/includes/lucide-init.php'; ?>
    <script>
        function printNewsletter() {
            // Print the iframe content directly
            const iframe = document.querySelector('iframe');
            if (iframe && iframe.contentWindow) {
                iframe.contentWindow.print();
            }
        }
        
        // Auto-resize iframe based on content
        function resizeIframe() {
            const iframe = document.querySelector('iframe');
            if (iframe && iframe.contentWindow) {
                try {
                    const height = iframe.contentWindow.document.documentElement.scrollHeight;
                    iframe.style.height = height + 'px';
                } catch (e) {
                    // Fallback height if cross-origin issues
                    iframe.style.height = '800px';
                }
            }
        }
        
        // Resize on load
        window.addEventListener('load', resizeIframe);
        
        // Resize periodically in case content changes
        setInterval(resizeIframe, 1000);
    </script>
</body>
</html>