<?php

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Test file paths
$authPath = __DIR__ . '/../core/Auth.php';
$stripePath = __DIR__ . '/../config/stripe.php';

if (!file_exists($authPath)) {
    error_log('API: Auth.php not found at: ' . $authPath);
    http_response_code(500);
    echo json_encode(['error' => 'Auth.php file not found']);
    exit;
}

if (!file_exists($stripePath)) {
    error_log('API: stripe.php not found at: ' . $stripePath);
    http_response_code(500);
    echo json_encode(['error' => 'stripe.php file not found']);
    exit;
}

require_once $authPath;
require_once $stripePath;

try {
    error_log('API: Starting checkout session creation');
    
    // Check if user is authenticated
    $auth = Auth::getInstance();
    if (!$auth->isLoggedIn()) {
        error_log('API: User not logged in');
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        exit;
    }
    
    error_log('API: User authenticated');
    
    $user = $auth->getCurrentUser();
    $userId = $user['id'];
    
    // Get request data
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON input']);
        exit;
    }
    
    $plan = $input['plan'] ?? null;
    
    // Validate plan
    $validPlans = ['starter', 'pro', 'unlimited'];
    if (!$plan || !in_array($plan, $validPlans)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid plan specified']);
        exit;
    }
    
    // Check if user already has an active subscription
    require_once __DIR__ . '/../core/SubscriptionManager.php';
    $subscriptionManager = new SubscriptionManager();
    $currentPlan = $subscriptionManager->getUserPlanInfo($userId);
    
    if ($currentPlan['subscription_status'] === 'active') {
        http_response_code(400);
        echo json_encode(['error' => 'User already has an active subscription']);
        exit;
    }
    
    // Get base URL for redirects
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $baseUrl = "$protocol://$host";
    
    $successUrl = $baseUrl . '/payment-success.php?session_id={CHECKOUT_SESSION_ID}';
    $cancelUrl = $baseUrl . '/payment-cancel.php';
    
    // Create Stripe checkout session
    $stripeHelper = new StripeHelper();
    $session = $stripeHelper->createCheckoutSession(
        $userId,
        $plan,
        $successUrl,
        $cancelUrl
    );
    
    // Return the checkout URL
    echo json_encode([
        'checkout_url' => $session['url'],
        'session_id' => $session['id']
    ]);
    
} catch (Exception $e) {
    error_log('Checkout session creation error: ' . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to create checkout session',
        'message' => $e->getMessage()
    ]);
}