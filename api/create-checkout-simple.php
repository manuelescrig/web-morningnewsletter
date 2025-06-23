<?php
// Headers first, before any output
header('Content-Type: application/json');

// Turn off error display to prevent HTML output
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Check request method first
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Only POST method allowed, got: ' . $_SERVER['REQUEST_METHOD']]);
    exit;
}

try {
    // Step 1: Get POST data properly
    $rawInput = file_get_contents('php://input');
    
    if (empty($rawInput)) {
        throw new Exception('No raw input data received');
    }
    
    $input = json_decode($rawInput, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON: ' . json_last_error_msg());
    }
    
    if (empty($input)) {
        throw new Exception('Empty input data after JSON decode');
    }
    
    // Step 2: Test Auth
    require_once __DIR__ . '/../core/Auth.php';
    $auth = Auth::getInstance();
    
    if (!$auth->isLoggedIn()) {
        throw new Exception('Not logged in');
    }
    
    $user = $auth->getCurrentUser();
    
    // Step 3: Test plan validation
    $plan = $input['plan'] ?? null;
    if (!in_array($plan, ['starter', 'pro', 'unlimited'])) {
        throw new Exception('Invalid plan: ' . $plan);
    }
    
    // Step 4: Test Stripe config
    require_once __DIR__ . '/../config/stripe.php';
    $priceId = StripeConfig::getPriceId($plan);
    
    if (!$priceId) {
        throw new Exception('No price ID found for plan: ' . $plan);
    }
    
    // For now, just return success without actually calling Stripe
    echo json_encode([
        'success' => true,
        'plan' => $plan,
        'price_id' => $priceId,
        'user_id' => $user['id'],
        'user_email' => $user['email']
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>