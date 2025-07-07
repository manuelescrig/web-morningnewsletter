<?php
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/NewsletterBuilder.php';

$auth = Auth::getInstance();
$auth->requireAuth();

$user = $auth->getCurrentUser();

// Check if this is an admin user who can send preview emails
$isAdmin = $user->isAdmin();
$previewSent = false;
$previewError = '';

// Handle email preview sending (admin only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isAdmin) {
    $action = $_POST['action'] ?? '';
    $csrfToken = $_POST['csrf_token'] ?? '';
    
    if ($auth->validateCSRFToken($csrfToken) && $action === 'send_preview') {
        try {
            error_log("Preview email: Starting send process for user " . $user->getId());
            
            require_once __DIR__ . '/core/EmailSender.php';
            $builder = NewsletterBuilder::fromUser($user);
            $newsletterHtml = $builder->buildForPreview();
            
            error_log("Preview email: Newsletter HTML generated, length: " . strlen($newsletterHtml));
            
            $emailSender = new EmailSender();
            $success = $emailSender->sendPreviewEmail(
                $user->getEmail(), 
                'Newsletter Preview - ' . $user->getNewsletterTitle(),
                $newsletterHtml
            );
            
            error_log("Preview email: Send result: " . ($success ? 'success' : 'failed'));
            
            if ($success) {
                $previewSent = true;
            } else {
                $previewError = 'Failed to send preview email. Please check your email configuration.';
            }
        } catch (Exception $e) {
            error_log("Preview email exception: " . $e->getMessage());
            error_log("Preview email stack trace: " . $e->getTraceAsString());
            $previewError = 'Error sending preview: ' . $e->getMessage();
        }
    } else if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isAdmin) {
        error_log("Preview email: CSRF validation failed or wrong action. Action: $action, CSRF valid: " . ($auth->validateCSRFToken($csrfToken) ? 'yes' : 'no'));
        $previewError = 'Invalid request. Please try again.';
    }
}

try {
    $builder = NewsletterBuilder::fromUser($user);
    $newsletterHtml = $builder->buildForPreview();
    
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Newsletter Preview - MorningNewsletter</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    </head>
    <body class="bg-gray-100 min-h-screen">
        <!-- Header with controls -->
        <div class="bg-white border-b border-gray-200 sticky top-0 z-10">
            <div class="max-w-7xl mx-auto px-4 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <a href="/dashboard/" class="inline-flex items-center text-gray-600 hover:text-gray-900">
                            <i class="fas fa-arrow-left mr-2"></i>
                            Back to Dashboard
                        </a>
                        <div class="h-6 border-l border-gray-300"></div>
                        <h1 class="text-xl font-semibold text-gray-900">Newsletter Preview</h1>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            <?php echo htmlspecialchars($user->getNewsletterTitle()); ?>
                        </span>
                    </div>
                    
                    <div class="flex items-center space-x-3">
                        <?php if ($isAdmin): ?>
                            <form method="POST" class="inline-flex">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($auth->generateCSRFToken()); ?>">
                                <input type="hidden" name="action" value="send_preview">
                                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                    <i class="fas fa-paper-plane mr-2"></i>
                                    Send Preview Email
                                </button>
                            </form>
                        <?php endif; ?>
                        
                        <div class="text-sm text-gray-500">
                            <i class="fas fa-calendar mr-1"></i>
                            <?php echo date('F j, Y g:i A'); ?>
                        </div>
                    </div>
                </div>
                
                <?php if ($previewSent): ?>
                    <div class="mt-3 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-md">
                        <div class="flex">
                            <i class="fas fa-check-circle mr-2 mt-0.5"></i>
                            <div>
                                <strong>Preview email sent!</strong> Check your inbox at <?php echo htmlspecialchars($user->getEmail()); ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($previewError): ?>
                    <div class="mt-3 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-md">
                        <div class="flex">
                            <i class="fas fa-exclamation-triangle mr-2 mt-0.5"></i>
                            <div>
                                <strong>Error:</strong> <?php echo htmlspecialchars($previewError); ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Newsletter preview in device mockup -->
        <div class="max-w-4xl mx-auto px-4 py-8">
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
                            <span class="text-sm font-medium text-gray-600">Email Preview</span>
                        </div>
                        <div class="flex items-center space-x-4 text-sm text-gray-500">
                            <span><i class="fas fa-envelope mr-1"></i> To: <?php echo htmlspecialchars($user->getEmail()); ?></span>
                            <span><i class="fas fa-tag mr-1"></i> Newsletter</span>
                        </div>
                    </div>
                </div>
                
                <!-- Newsletter content with proper styling -->
                <div class="newsletter-display" style="background-color: #f8f9fa; padding: 0;">
                    <iframe 
                        srcdoc="<?php echo htmlspecialchars($newsletterHtml, ENT_QUOTES); ?>" 
                        class="w-full border-0" 
                        style="min-height: 600px; background: white;"
                        onload="this.style.height = this.contentWindow.document.documentElement.scrollHeight + 'px';">
                    </iframe>
                </div>
            </div>
            
            <!-- Additional info panel -->
            <div class="mt-6 bg-white rounded-lg shadow p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-blue-600"><?php echo count($user->getSources()); ?></div>
                        <div class="text-sm text-gray-600">Active Sources</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-green-600"><?php echo $user->getTimezone(); ?></div>
                        <div class="text-sm text-gray-600">Timezone</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-purple-600"><?php echo $user->getSendTime(); ?></div>
                        <div class="text-sm text-gray-600">Send Time</div>
                    </div>
                </div>
                
                <?php if (!$isAdmin): ?>
                    <div class="mt-4 p-4 bg-blue-50 rounded-lg">
                        <div class="flex items-start">
                            <i class="fas fa-info-circle text-blue-500 mr-2 mt-0.5"></i>
                            <div class="text-sm text-blue-700">
                                <strong>Preview Mode:</strong> This shows how your newsletter will look when delivered. 
                                The actual email will be sent automatically at your scheduled time (<?php echo $user->getSendTime(); ?> <?php echo $user->getTimezone(); ?>).
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <script>
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
    <?php
    
} catch (Exception $e) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Newsletter Preview - MorningNewsletter</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    </head>
    <body class="bg-gray-50 min-h-screen flex items-center justify-center">
        <div class="max-w-md w-full bg-white rounded-lg shadow-lg p-6">
            <div class="text-center">
                <i class="fas fa-exclamation-triangle text-red-500 text-4xl mb-4"></i>
                <h2 class="text-xl font-semibold text-gray-900 mb-2">Preview Error</h2>
                <p class="text-gray-600 mb-4"><?php echo htmlspecialchars($e->getMessage()); ?></p>
                <a href="/dashboard/" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back to Dashboard
                </a>
            </div>
        </div>
    </body>
    </html>
    <?php
}
?>