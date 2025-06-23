<?php
// Show errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set content type AFTER any potential errors
header('Content-Type: text/plain');

echo "=== DEBUG WITH ERRORS ===\n";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "ERROR: Not a POST request\n";
    exit;
}

echo "1. Method check: OK\n";

try {
    $rawInput = file_get_contents('php://input');
    echo "2. Raw input length: " . strlen($rawInput) . "\n";
    
    $input = json_decode($rawInput, true);
    echo "3. JSON decode: " . (is_array($input) ? 'OK' : 'FAILED') . "\n";
    
    if (isset($input['plan'])) {
        echo "4. Plan received: " . $input['plan'] . "\n";
    } else {
        echo "4. Plan missing\n";
        exit;
    }
    
    echo "5. Loading Auth...\n";
    require_once __DIR__ . '/../core/Auth.php';
    echo "6. Auth loaded OK\n";
    
    echo "7. Getting Auth instance...\n";
    $auth = Auth::getInstance();
    echo "8. Auth instance OK\n";
    
    echo "9. Checking login status...\n";
    $isLoggedIn = $auth->isLoggedIn();
    echo "10. Login status: " . ($isLoggedIn ? 'LOGGED IN' : 'NOT LOGGED IN') . "\n";
    
    if (!$isLoggedIn) {
        echo "ERROR: User not logged in\n";
        exit;
    }
    
    echo "11. Getting current user...\n";
    $user = $auth->getCurrentUser();
    echo "12. User ID: " . $user['id'] . "\n";
    
    echo "13. Loading Stripe config...\n";
    require_once __DIR__ . '/../config/stripe.php';
    echo "14. Stripe config loaded OK\n";
    
    echo "15. Getting price ID...\n";
    $priceId = StripeConfig::getPriceId($input['plan']);
    echo "16. Price ID: " . $priceId . "\n";
    
    echo "SUCCESS: All steps completed!\n";
    
} catch (Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
} catch (Error $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
?>