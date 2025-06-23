<?php

// START SESSION FIRST
session_start();

header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../config/stripe.php';
require_once __DIR__ . '/../core/SubscriptionManager.php';

try {
    // Check if user is authenticated
    $auth = Auth::getInstance();
    if (!$auth->isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        exit;
    }
    
    $user = $auth->getCurrentUser();
    $userId = $user->getId();
    
    // Get user's subscription info
    $subscriptionManager = new SubscriptionManager();
    $subscriptionInfo = $subscriptionManager->getUserPlanInfo($userId);
    
    if (!$subscriptionInfo['stripe_customer_id']) {
        http_response_code(400);
        echo json_encode(['error' => 'No customer record found. You need to have an active subscription to access billing portal.']);
        exit;
    }
    
    // Get base URL for return
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $returnUrl = "$protocol://$host/dashboard/billing.php";
    
    // Create billing portal session
    $stripeHelper = new StripeHelper();
    $session = $stripeHelper->createCustomerPortalSession(
        $subscriptionInfo['stripe_customer_id'],
        $returnUrl
    );
    
    echo json_encode([
        'success' => true,
        'portal_url' => $session['url']
    ]);
    
} catch (Exception $e) {
    error_log('Billing portal error: ' . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to access billing portal',
        'message' => $e->getMessage()
    ]);
}