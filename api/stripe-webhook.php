<?php

header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/../config/stripe.php';
require_once __DIR__ . '/../core/SubscriptionManager.php';

try {
    // Get raw POST data
    $payload = file_get_contents('php://input');
    $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
    
    if (empty($payload) || empty($sig_header)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing payload or signature']);
        exit;
    }
    
    // Verify webhook signature
    $stripeHelper = new StripeHelper();
    if (!$stripeHelper->verifyWebhookSignature($payload, $sig_header)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid signature']);
        exit;
    }
    
    // Parse the event
    $event = json_decode($payload, true);
    if (!$event) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON']);
        exit;
    }
    
    $subscriptionManager = new SubscriptionManager();
    
    // Log the event for debugging
    error_log("Stripe webhook received: " . $event['type']);
    
    // Handle different event types
    switch ($event['type']) {
        case 'customer.subscription.created':
            $subscription = $event['data']['object'];
            $result = $subscriptionManager->handleSubscriptionCreated($subscription);
            
            if ($result) {
                error_log("Successfully processed subscription.created for subscription: " . $subscription['id']);
            } else {
                error_log("Failed to process subscription.created for subscription: " . $subscription['id']);
            }
            break;
            
        case 'customer.subscription.updated':
            $subscription = $event['data']['object'];
            $result = $subscriptionManager->handleSubscriptionUpdated($subscription);
            
            if ($result) {
                error_log("Successfully processed subscription.updated for subscription: " . $subscription['id']);
            } else {
                error_log("Failed to process subscription.updated for subscription: " . $subscription['id']);
            }
            break;
            
        case 'customer.subscription.deleted':
            $subscription = $event['data']['object'];
            $result = $subscriptionManager->handleSubscriptionDeleted($subscription);
            
            if ($result) {
                error_log("Successfully processed subscription.deleted for subscription: " . $subscription['id']);
                
                // Schedule follow-up emails for subscription cancellation
                try {
                    $dbSubscription = $subscriptionManager->getSubscriptionByStripeId($subscription['id']);
                    if ($dbSubscription && $dbSubscription['user_id']) {
                        require_once __DIR__ . '/../core/TransactionalEmailManager.php';
                        $transactionalManager = new TransactionalEmailManager();
                        
                        // Get the user to check their plan
                        require_once __DIR__ . '/../core/User.php';
                        $user = User::findById($dbSubscription['user_id']);
                        
                        if ($user) {
                            $variables = [
                                'subscription_plan' => $user->getPlan(),
                                'reactivation_url' => 'https://' . ($_SERVER['HTTP_HOST'] ?? 'morningnewsletter.com') . '/upgrade'
                            ];
                            
                            $transactionalManager->scheduleEmailsForEvent('subscription_cancelled', $dbSubscription['user_id'], $variables);
                            error_log("Scheduled follow-up emails for cancelled subscription: " . $subscription['id']);
                        }
                    }
                } catch (Exception $e) {
                    error_log("Failed to schedule follow-up emails for cancelled subscription: " . $e->getMessage());
                }
            } else {
                error_log("Failed to process subscription.deleted for subscription: " . $subscription['id']);
            }
            break;
            
        case 'invoice.payment_succeeded':
            $invoice = $event['data']['object'];
            
            // Record the payment
            if ($invoice['subscription'] && $invoice['customer']) {
                // Get subscription to find user_id
                $subscription = $subscriptionManager->getSubscriptionByStripeId($invoice['subscription']);
                
                if ($subscription) {
                    $paymentData = [
                        'user_id' => $subscription['user_id'],
                        'stripe_payment_intent_id' => $invoice['payment_intent'] ?? $invoice['id'],
                        'subscription_id' => $subscription['id'],
                        'amount' => $invoice['amount_paid'],
                        'currency' => $invoice['currency'],
                        'status' => 'succeeded',
                        'description' => 'Subscription payment: ' . ($subscription['plan'] ?? 'Unknown plan')
                    ];
                    
                    $subscriptionManager->recordPayment($paymentData);
                    error_log("Successfully recorded payment for invoice: " . $invoice['id']);
                }
            }
            break;
            
        case 'invoice.payment_failed':
            $invoice = $event['data']['object'];
            
            // Record the failed payment
            if ($invoice['subscription'] && $invoice['customer']) {
                $subscription = $subscriptionManager->getSubscriptionByStripeId($invoice['subscription']);
                
                if ($subscription) {
                    $paymentData = [
                        'user_id' => $subscription['user_id'],
                        'stripe_payment_intent_id' => $invoice['payment_intent'] ?? $invoice['id'],
                        'subscription_id' => $subscription['id'],
                        'amount' => $invoice['amount_due'],
                        'currency' => $invoice['currency'],
                        'status' => 'failed',
                        'description' => 'Failed subscription payment: ' . ($subscription['plan'] ?? 'Unknown plan')
                    ];
                    
                    $subscriptionManager->recordPayment($paymentData);
                    error_log("Successfully recorded failed payment for invoice: " . $invoice['id']);
                }
            }
            break;
            
        case 'checkout.session.completed':
            $session = $event['data']['object'];
            
            // This event is triggered when a user completes checkout
            // The subscription.created event will handle the actual subscription creation
            error_log("Checkout session completed: " . $session['id']);
            
            // You could send a welcome email or perform other actions here
            if (isset($session['metadata']['user_id'])) {
                $userId = $session['metadata']['user_id'];
                // Optional: Send welcome email to user
                error_log("User $userId successfully completed checkout");
            }
            break;
            
        default:
            error_log("Unhandled Stripe webhook event type: " . $event['type']);
            break;
    }
    
    // Return 200 to acknowledge receipt of the event
    http_response_code(200);
    echo json_encode(['status' => 'success']);
    
} catch (Exception $e) {
    error_log('Stripe webhook error: ' . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'error' => 'Webhook processing failed',
        'message' => $e->getMessage()
    ]);
}