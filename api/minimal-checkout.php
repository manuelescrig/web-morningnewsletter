<?php
header('Content-Type: application/json');

// Disable error display to prevent HTML output
ini_set('display_errors', 0);
ini_set('log_errors', 1);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    // Get input
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);
    
    if (!$input || !isset($input['plan'])) {
        throw new Exception('Invalid input');
    }
    
    $plan = $input['plan'];
    
    // Load auth
    require_once __DIR__ . '/../core/Auth.php';
    $auth = Auth::getInstance();
    
    if (!$auth->isLoggedIn()) {
        throw new Exception('Not authenticated');
    }
    
    $user = $auth->getCurrentUser();
    
    // Load Stripe config
    require_once __DIR__ . '/../config/stripe.php';
    $priceId = StripeConfig::getPriceId($plan);
    
    if (!$priceId) {
        throw new Exception('Invalid plan');
    }
    
    // For now, just return test data without actually calling Stripe API
    echo json_encode([
        'success' => true,
        'plan' => $plan,
        'price_id' => $priceId,
        'user_id' => $user['id'],
        'user_email' => $user['email'],
        'test_mode' => true
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ]);
} catch (Error $e) {
    http_response_code(500);
    echo json_encode([
        'fatal_error' => $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ]);
}
?>