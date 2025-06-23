<?php
// START SESSION FIRST - before any output!
session_start();

// Then set headers
header('Content-Type: application/json');

// Only allow POST
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
    
    // Create Stripe helper and checkout session
    $stripeHelper = new StripeHelper();
    
    // Get URLs for redirect
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $baseUrl = "$protocol://$host";
    
    $successUrl = $baseUrl . '/payment-success.php?session_id={CHECKOUT_SESSION_ID}';
    
    // Use the referer (previous page) as cancel URL, fallback to upgrade page
    $cancelUrl = $_SERVER['HTTP_REFERER'] ?? $baseUrl . '/upgrade';
    
    // Create the actual Stripe checkout session
    $session = $stripeHelper->createCheckoutSession(
        $user->getId(),
        $plan,
        $successUrl,
        $cancelUrl
    );
    
    // Return success with checkout URL
    echo json_encode([
        'success' => true,
        'checkout_url' => $session['url'],
        'session_id' => $session['id']
    ]);
    
} catch (Exception $e) {
    error_log('Checkout error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}
?>