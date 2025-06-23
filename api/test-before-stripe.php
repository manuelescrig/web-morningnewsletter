<?php
// Test everything except the actual Stripe call
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    // Get POST data
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);
    
    if (!$input || !isset($input['plan'])) {
        throw new Exception('Invalid input data');
    }
    
    $plan = $input['plan'];
    if (!in_array($plan, ['starter', 'pro', 'unlimited'])) {
        throw new Exception('Invalid plan: ' . $plan);
    }
    
    // Load Auth (session already started)
    require_once __DIR__ . '/../core/Auth.php';
    $auth = Auth::getInstance();
    
    if (!$auth->isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        exit;
    }
    
    $user = $auth->getCurrentUser();
    
    // Load Stripe config
    require_once __DIR__ . '/../config/stripe.php';
    $priceId = StripeConfig::getPriceId($plan);
    
    if (!$priceId) {
        throw new Exception('No price ID found for plan: ' . $plan);
    }
    
    // Test subscription manager
    require_once __DIR__ . '/../core/SubscriptionManager.php';
    $subscriptionManager = new SubscriptionManager();
    $currentPlan = $subscriptionManager->getUserPlanInfo($user['id']);
    
    // Return test data (don't create actual Stripe session yet)
    echo json_encode([
        'success' => true,
        'plan' => $plan,
        'price_id' => $priceId,
        'user_id' => $user['id'],
        'user_email' => $user['email'],
        'current_subscription' => $currentPlan,
        'test_mode' => true,
        'message' => 'Ready to create Stripe session'
    ]);
    
} catch (Exception $e) {
    error_log('Test error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ]);
}
?>