<?php
// Simple test to check if API endpoint is accessible
echo "Testing API endpoint...\n";

// Test if the file exists
$apiFile = __DIR__ . '/api/create-checkout-session.php';
if (!file_exists($apiFile)) {
    echo "❌ API file does not exist at: $apiFile\n";
    exit;
}

echo "✅ API file exists\n";

// Test if we can include the file
try {
    // Don't actually include it as it would run, just check syntax
    $content = file_get_contents($apiFile);
    if (strpos($content, '<?php') === false) {
        echo "❌ API file doesn't contain PHP code\n";
        exit;
    }
    echo "✅ API file contains PHP code\n";
} catch (Exception $e) {
    echo "❌ Error reading API file: " . $e->getMessage() . "\n";
    exit;
}

// Test database connection
try {
    require_once __DIR__ . '/config/database.php';
    $db = Database::getInstance();
    echo "✅ Database connection works\n";
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}

// Test Stripe config
try {
    require_once __DIR__ . '/config/stripe.php';
    $publishableKey = StripeConfig::getPublishableKey();
    $secretKey = StripeConfig::getSecretKey();
    
    if (empty($publishableKey) || empty($secretKey)) {
        echo "❌ Stripe keys are empty\n";
    } else {
        echo "✅ Stripe configuration loaded\n";
        echo "   Publishable key: " . substr($publishableKey, 0, 12) . "...\n";
        echo "   Secret key: " . substr($secretKey, 0, 12) . "...\n";
    }
} catch (Exception $e) {
    echo "❌ Stripe config error: " . $e->getMessage() . "\n";
}

echo "\nTest complete!\n";
?>