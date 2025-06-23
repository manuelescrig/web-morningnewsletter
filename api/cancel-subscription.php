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
    $userId = $user['id'];
    
    // Get user's active subscription
    $subscriptionManager = new SubscriptionManager();
    $subscriptionInfo = $subscriptionManager->getUserPlanInfo($userId);
    
    if ($subscriptionInfo['subscription_status'] !== 'active') {
        http_response_code(400);
        echo json_encode(['error' => 'No active subscription found']);
        exit;
    }
    
    if (!$subscriptionInfo['stripe_subscription_id']) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid subscription data']);
        exit;
    }
    
    // Cancel subscription at period end via Stripe
    $stripeHelper = new StripeHelper();
    $result = $stripeHelper->cancelSubscriptionAtPeriodEnd($subscriptionInfo['stripe_subscription_id']);
    
    // Update local subscription record
    $subscriptionManager->updateSubscription($subscriptionInfo['stripe_subscription_id'], [
        'cancel_at_period_end' => 1
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Subscription will be cancelled at the end of the current period'
    ]);
    
} catch (Exception $e) {
    error_log('Subscription cancellation error: ' . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to cancel subscription',
        'message' => $e->getMessage()
    ]);
}