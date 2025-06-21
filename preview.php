<?php
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/NewsletterBuilder.php';

$auth = Auth::getInstance();
$auth->requireAuth();

$user = $auth->getCurrentUser();

// Add debug mode
$debug = isset($_GET['debug']) && $_GET['debug'] === '1';

try {
    if ($debug) {
        echo "<h2>Debug Information:</h2>";
        echo "<p><strong>User:</strong> " . htmlspecialchars($user->getEmail()) . "</p>";
        echo "<p><strong>Sources:</strong> " . $user->getSourceCount() . "</p>";
        
        $sources = $user->getSources();
        echo "<h3>Source Details:</h3>";
        foreach ($sources as $source) {
            echo "<div style='margin: 10px 0; padding: 10px; border: 1px solid #ccc;'>";
            echo "<strong>Type:</strong> " . htmlspecialchars($source['type']) . "<br>";
            echo "<strong>Config:</strong> " . htmlspecialchars($source['config']) . "<br>";
            echo "<strong>Active:</strong> " . ($source['is_active'] ? 'Yes' : 'No') . "<br>";
            echo "<strong>Last Updated:</strong> " . ($source['last_updated'] ?: 'Never') . "<br>";
            echo "</div>";
        }
        echo "<hr style='margin: 20px 0;'>";
    }
    
    $builder = new NewsletterBuilder($user);
    $newsletterHtml = $builder->build();
    
    // Display the newsletter
    echo $newsletterHtml;
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