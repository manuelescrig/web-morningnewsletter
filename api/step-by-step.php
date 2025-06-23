<?php
header('Content-Type: application/json');

$step = $_GET['step'] ?? '1';

try {
    switch($step) {
        case '1':
            echo json_encode(['success' => true, 'step' => 1, 'message' => 'Basic PHP working']);
            break;
            
        case '2':
            // Test Auth loading
            require_once __DIR__ . '/../core/Auth.php';
            echo json_encode(['success' => true, 'step' => 2, 'message' => 'Auth.php loaded']);
            break;
            
        case '3':
            // Test Auth instance
            require_once __DIR__ . '/../core/Auth.php';
            $auth = Auth::getInstance();
            echo json_encode(['success' => true, 'step' => 3, 'message' => 'Auth instance created']);
            break;
            
        case '4':
            // Test Auth check
            require_once __DIR__ . '/../core/Auth.php';
            $auth = Auth::getInstance();
            $loggedIn = $auth->isLoggedIn();
            echo json_encode(['success' => true, 'step' => 4, 'message' => 'Auth check complete', 'logged_in' => $loggedIn]);
            break;
            
        case '5':
            // Test Stripe config
            require_once __DIR__ . '/../config/stripe.php';
            $priceId = StripeConfig::getPriceId('starter');
            echo json_encode(['success' => true, 'step' => 5, 'message' => 'Stripe config loaded', 'price_id' => $priceId]);
            break;
            
        default:
            echo json_encode(['error' => 'Invalid step']);
    }
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine(), 'step' => $step]);
} catch (Error $e) {
    echo json_encode(['fatal_error' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine(), 'step' => $step]);
}
?>