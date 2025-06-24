<?php
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/NewsletterBuilder.php';

$auth = Auth::getInstance();
$auth->requireAuth();

$user = $auth->getCurrentUser();

// Add debug mode
$debug = isset($_GET['debug']) && $_GET['debug'] === '1';

try {
    $builder = new NewsletterBuilder($user);
    $newsletterHtml = $builder->build();
    
    // Extract the body content from the newsletter HTML
    preg_match('/<body[^>]*>(.*?)<\/body>/is', $newsletterHtml, $matches);
    $newsletterContent = $matches[1] ?? $newsletterHtml;
    
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Newsletter Preview - MorningNewsletter</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        <style>
            /* Custom styles for the newsletter content */
            .newsletter-preview {
                font-family: Arial, sans-serif;
                line-height: 1.6;
                color: #333;
                max-width: 600px;
                margin: 0 auto;
                padding: 20px;
                background: white;
                border-radius: 8px;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            }
        </style>
    </head>
    <body class="bg-gray-50 min-h-screen py-8">
        <!-- Header with back button -->
        <div class="max-w-4xl mx-auto px-4 mb-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <a href="/dashboard/" class="inline-flex items-center text-gray-600 hover:text-gray-900 mr-4">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Back to Dashboard
                    </a>
                    <h1 class="text-2xl font-bold text-gray-900">Newsletter Preview</h1>
                </div>
                <div class="text-sm text-gray-500">
                    <?php echo date('F j, Y g:i A'); ?>
                </div>
            </div>
        </div>

        <!-- Centered newsletter preview -->
        <div class="max-w-4xl mx-auto px-4">
            <div class="bg-white rounded-lg shadow-lg p-8">
                <div class="newsletter-preview">
                    <?php 
                    if ($debug) {
                        echo "<div style='background: #f0f8ff; padding: 15px; margin-bottom: 20px; border-radius: 5px; border-left: 4px solid #2563eb;'>";
                        echo "<h3 style='margin-top: 0; color: #2563eb;'>Debug Information:</h3>";
                        echo "<p><strong>User:</strong> " . htmlspecialchars($user->getEmail()) . "</p>";
                        echo "<p><strong>Sources:</strong> " . $user->getSourceCount() . "</p>";
                        
                        $sources = $user->getSources();
                        if (!empty($sources)) {
                            echo "<h4>Source Details:</h4>";
                            foreach ($sources as $source) {
                                echo "<div style='margin: 10px 0; padding: 10px; background: white; border-radius: 3px;'>";
                                echo "<strong>Type:</strong> " . htmlspecialchars($source['type']) . "<br>";
                                echo "<strong>Active:</strong> " . ($source['is_active'] ? 'Yes' : 'No') . "<br>";
                                echo "<strong>Last Updated:</strong> " . ($source['last_updated'] ?: 'Never') . "<br>";
                                echo "</div>";
                            }
                        }
                        echo "</div>";
                    }
                    
                    echo $newsletterContent;
                    ?>
                </div>
            </div>
        </div>

        <!-- Footer with debug link -->
        <div class="max-w-4xl mx-auto px-4 mt-6 text-center">
            <div class="text-sm text-gray-500">
                <?php if (!$debug): ?>
                    <a href="?debug=1" class="text-blue-600 hover:text-blue-500">
                        <i class="fas fa-bug mr-1"></i>
                        Enable Debug Mode
                    </a>
                <?php else: ?>
                    <a href="?" class="text-blue-600 hover:text-blue-500">
                        <i class="fas fa-eye mr-1"></i>
                        Disable Debug Mode
                    </a>
                <?php endif; ?>
            </div>
        </div>
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