<?php

// Simple version to test step by step
header('Content-Type: application/json');

try {
    // Step 1: Test basic response
    if (!isset($_POST) && !isset($GLOBALS['HTTP_RAW_POST_DATA'])) {
        $input = json_decode(file_get_contents('php://input'), true);
    } else {
        $input = $_POST;
    }
    
    if (empty($input)) {
        throw new Exception('No input data received');
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