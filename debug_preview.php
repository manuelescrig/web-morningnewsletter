<?php
/**
 * Debug Preview Script
 * 
 * This script helps debug the preview functionality.
 * Access: http://yourdomain.com/debug_preview.php
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Preview Debug</h1>";

try {
    echo "<h2>1. Loading Core Classes</h2>";
    require_once __DIR__ . '/core/User.php';
    echo "✅ User class loaded<br>";
    
    require_once __DIR__ . '/core/Newsletter.php';
    echo "✅ Newsletter class loaded<br>";
    
    require_once __DIR__ . '/core/NewsletterBuilder.php';
    echo "✅ NewsletterBuilder class loaded<br>";
    
    require_once __DIR__ . '/core/Auth.php';
    echo "✅ Auth class loaded<br>";
    
    echo "<h2>2. Testing Auth</h2>";
    $auth = Auth::getInstance();
    echo "✅ Auth instance created<br>";
    
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        session_start();
    }
    
    if (!isset($_SESSION['user_id'])) {
        echo "❌ No user logged in. Please log in first.<br>";
        echo "<a href='/auth/login.php'>Login here</a><br>";
        exit;
    }
    
    echo "✅ User session found: " . $_SESSION['user_id'] . "<br>";
    
    echo "<h2>3. Loading User</h2>";
    $user = User::findById($_SESSION['user_id']);
    if (!$user) {
        echo "❌ User not found<br>";
        exit;
    }
    echo "✅ User loaded: " . $user->getEmail() . "<br>";
    
    echo "<h2>4. Testing User Methods</h2>";
    echo "✅ Newsletter Title: " . $user->getNewsletterTitle() . "<br>";
    echo "✅ Timezone: " . $user->getTimezone() . "<br>";
    echo "✅ Send Time: " . $user->getSendTime() . "<br>";
    echo "✅ Source Count: " . $user->getSourceCount() . "<br>";
    
    echo "<h2>5. Testing Newsletter Access</h2>";
    $newsletters = $user->getNewsletters();
    echo "✅ User has " . count($newsletters) . " newsletters<br>";
    
    if (empty($newsletters)) {
        echo "❌ No newsletters found. Creating default newsletter...<br>";
        $newsletterId = $user->createNewsletter('My Morning Brief', 'UTC', '06:00');
        if ($newsletterId) {
            echo "✅ Default newsletter created with ID: $newsletterId<br>";
            $newsletters = $user->getNewsletters();
        } else {
            echo "❌ Failed to create default newsletter<br>";
            exit;
        }
    }
    
    $defaultNewsletter = $user->getDefaultNewsletter();
    if ($defaultNewsletter) {
        echo "✅ Default newsletter: " . $defaultNewsletter->getTitle() . " (ID: " . $defaultNewsletter->getId() . ")<br>";
    } else {
        echo "❌ No default newsletter available<br>";
        exit;
    }
    
    echo "<h2>6. Testing NewsletterBuilder</h2>";
    $builder = NewsletterBuilder::fromUser($user);
    echo "✅ NewsletterBuilder created from user<br>";
    
    echo "<h2>7. Building Preview</h2>";
    $newsletterHtml = $builder->buildForPreview();
    echo "✅ Newsletter HTML generated (" . strlen($newsletterHtml) . " characters)<br>";
    
    echo "<h2>8. Preview Test Complete</h2>";
    echo "✅ All tests passed! The preview should work now.<br>";
    echo "<a href='/preview.php'>Try Preview Page</a><br>";
    
    // Show a snippet of the generated HTML
    echo "<h3>Generated HTML Preview (first 500 chars):</h3>";
    echo "<pre>" . htmlspecialchars(substr($newsletterHtml, 0, 500)) . "...</pre>";
    
} catch (Exception $e) {
    echo "<h2>❌ Error Occurred</h2>";
    echo "<strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "<br>";
    echo "<strong>File:</strong> " . $e->getFile() . "<br>";
    echo "<strong>Line:</strong> " . $e->getLine() . "<br>";
    echo "<h3>Stack Trace:</h3>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "<br><br><strong>Debug completed at:</strong> " . date('Y-m-d H:i:s');
?>