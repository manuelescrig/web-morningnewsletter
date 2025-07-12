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
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <?php include __DIR__ . '/includes/navigation.php'; ?>

    <div class="max-w-4xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Success message when coming from email -->
        <?php if ($fromEmail): ?>
            <div class="mb-6 bg-blue-50 border border-blue-200 rounded-md p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-info-circle text-blue-400"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-blue-700">
                            You've been redirected from your email's "View in Browser" link. You're now viewing this newsletter in your dashboard history.
                        </p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Header -->
        <div class="mb-6">
            <nav class="flex mb-4" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                    <li class="inline-flex items-center">
                        <a href="/dashboard/history.php" class="text-gray-700 hover:text-blue-600 inline-flex items-center">
                            Newsletter History
                        </a>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                            <span class="text-gray-500">Issue #<?php echo $historyEntry['issue_number']; ?></span>
                        </div>
                    </li>
                </ol>
            </nav>
            
            <div class="flex justify-between items-start">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">
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
                            class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md font-medium transition-colors duration-200">
                        <i class="fas fa-print mr-2"></i>
                        Print
                    </button>
                    
                    <a href="/dashboard/history.php?newsletter_id=<?php echo $historyEntry['newsletter_id']; ?>" 
                       class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md font-medium transition-colors duration-200">
                        <i class="fas fa-list mr-2"></i>
                        All Issues
                    </a>
                </div>
            </div>
        </div>

        <!-- Newsletter Content -->
        <div class="bg-white shadow rounded-lg overflow-hidden">
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

        <!-- Source Data (if available) -->
        <?php if (!empty($historyEntry['sources_data'])): ?>
            <?php $sourcesData = json_decode($historyEntry['sources_data'], true); ?>
            <?php if ($sourcesData): ?>
                <div class="mt-6 bg-white shadow rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">Source Data</h2>
                        <p class="text-sm text-gray-600 mt-1">Raw data used to generate this newsletter</p>
                    </div>
                    
                    <div class="p-6">
                        <div class="space-y-4">
                            <?php foreach ($sourcesData as $source): ?>
                                <div class="border border-gray-200 rounded-lg p-4">
                                    <h3 class="font-medium text-gray-900 mb-2">
                                        <?php echo htmlspecialchars($source['title']); ?>
                                        <span class="text-sm text-gray-500 ml-2">(<?php echo htmlspecialchars($source['type']); ?>)</span>
                                    </h3>
                                    
                                    <?php if (!empty($source['data'])): ?>
                                        <div class="bg-gray-50 rounded p-3">
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm">
                                                <?php foreach ($source['data'] as $item): ?>
                                                    <?php if (isset($item['label']) && isset($item['value'])): ?>
                                                        <div class="flex justify-between">
                                                            <span class="text-gray-600"><?php echo htmlspecialchars($item['label']); ?>:</span>
                                                            <span class="font-medium text-gray-900">
                                                                <?php echo htmlspecialchars($item['value']); ?>
                                                                <?php if (isset($item['delta']) && $item['delta'] !== null): ?>
                                                                    <span class="ml-1 text-xs <?php echo (float)$item['delta'] >= 0 ? 'text-green-600' : 'text-red-600'; ?>">
                                                                        (<?php echo (float)$item['delta'] >= 0 ? '+' : ''; ?><?php echo $item['delta']; ?>)
                                                                    </span>
                                                                <?php endif; ?>
                                                            </span>
                                                        </div>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($source['last_updated'])): ?>
                                        <div class="mt-2 text-xs text-gray-500">
                                            Last updated: <?php echo $source['last_updated']; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <style>
        /* Newsletter content styling */
        .newsletter-preview {
            /* Ensure content is readable */
            max-width: 100%;
            overflow-wrap: break-word;
        }
        
        .newsletter-preview img {
            max-width: 100%;
            height: auto;
        }
        
        .newsletter-preview table {
            max-width: 100%;
            border-collapse: collapse;
        }
        
        .newsletter-preview td, .newsletter-preview th {
            padding: 8px;
            border: 1px solid #e5e7eb;
        }
        
        /* Print styles */
        @media print {
            .no-print {
                display: none !important;
            }
            
            body {
                background: white !important;
            }
            
            .bg-gray-50 {
                background: white !important;
            }
            
            .shadow {
                box-shadow: none !important;
            }
        }
    </style>

    <script>
        function printNewsletter() {
            // Hide navigation and other elements
            document.querySelector('nav').classList.add('no-print');
            document.querySelector('.max-w-4xl > .mb-6').classList.add('no-print');
            
            // Focus on the newsletter content
            window.print();
            
            // Restore navigation after printing
            setTimeout(() => {
                document.querySelector('nav').classList.remove('no-print');
                document.querySelector('.max-w-4xl > .mb-6').classList.remove('no-print');
            }, 1000);
        }
    </script>
</body>
</html>