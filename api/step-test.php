<?php
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Not POST']);
    exit;
}

try {
    $step = $_GET['step'] ?? '1';
    
    switch($step) {
        case '1':
            // Test input
            $rawInput = file_get_contents('php://input');
            $input = json_decode($rawInput, true);
            echo json_encode(['step' => 1, 'success' => true, 'input' => $input]);
            break;
            
        case '2':
            // Test Auth
            require_once __DIR__ . '/../core/Auth.php';
            $auth = Auth::getInstance();
            $user = $auth->getCurrentUser();
            echo json_encode(['step' => 2, 'success' => true, 'logged_in' => $auth->isLoggedIn(), 'user_id' => $user['id'] ?? null]);
            break;
            
        case '3':
            // Test Stripe config
            require_once __DIR__ . '/../config/stripe.php';
            $priceId = StripeConfig::getPriceId('starter');
            echo json_encode(['step' => 3, 'success' => true, 'price_id' => $priceId]);
            break;
            
        case '4':
            // Test SubscriptionManager
            require_once __DIR__ . '/../core/SubscriptionManager.php';
            $subscriptionManager = new SubscriptionManager();
            echo json_encode(['step' => 4, 'success' => true, 'class' => 'SubscriptionManager created']);
            break;
            
        case '5':
            // Test database query
            require_once __DIR__ . '/../core/Auth.php';
            require_once __DIR__ . '/../core/SubscriptionManager.php';
            $auth = Auth::getInstance();
            $user = $auth->getCurrentUser();
            $subscriptionManager = new SubscriptionManager();
            $currentPlan = $subscriptionManager->getUserPlanInfo($user['id']);
            echo json_encode(['step' => 5, 'success' => true, 'plan_info' => $currentPlan]);
            break;
            
        default:
            echo json_encode(['error' => 'Invalid step']);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'step' => $step ?? 'unknown',
        'error' => $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ]);
} catch (Error $e) {
    echo json_encode([
        'step' => $step ?? 'unknown', 
        'fatal_error' => $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ]);
}
?>