<?php
/**
 * Newsletter View in Browser Handler
 * 
 * Handles "View in Browser" links from emails
 * URL: /newsletter-view.php?id=HISTORY_ID&token=TOKEN
 * 
 * Flow:
 * 1. Verify the token for the newsletter
 * 2. Check if user is logged in
 * 3. If logged in and it's their newsletter: redirect to history page
 * 4. If not logged in: redirect to login with return URL
 * 5. If wrong user: show error
 */

session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

try {
    require_once __DIR__ . '/core/Auth.php';
    require_once __DIR__ . '/core/User.php';
    require_once __DIR__ . '/core/NewsletterHistory.php';
    require_once __DIR__ . '/config/database.php';
} catch (Exception $e) {
    error_log("Newsletter view error loading files: " . $e->getMessage());
    http_response_code(500);
    die('Configuration error');
}

try {
    // Get parameters
    $historyId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $token = isset($_GET['token']) ? $_GET['token'] : '';

    error_log("Newsletter view: Processing request for history ID: $historyId");

    if (!$historyId || !$token) {
        error_log("Newsletter view: Missing parameters - historyId: $historyId, token length: " . strlen($token));
        http_response_code(404);
        die('Newsletter not found');
    }

    // Get newsletter history entry
    $historyManager = new NewsletterHistory();
    $historyEntry = $historyManager->getHistoryEntry($historyId);

    if (!$historyEntry) {
        error_log("Newsletter view: No history entry found for ID: $historyId");
        http_response_code(404);
        die('Newsletter not found');
    }

    error_log("Newsletter view: Found history entry for user: " . $historyEntry['user_id']);

    // Verify token - simple token based on history ID and user ID
    $secretKey = 'newsletter_view_secret_2025'; // In production, use env variable
    $expectedToken = hash('sha256', $historyId . $historyEntry['user_id'] . $secretKey);

    if (!hash_equals($expectedToken, $token)) {
        error_log("Newsletter view: Token mismatch for history ID: $historyId");
        http_response_code(403);
        die('Invalid access token');
    }

    error_log("Newsletter view: Token verified successfully");

    // Check if user is logged in
    $auth = Auth::getInstance();
    $currentUser = null;

    if ($auth->isLoggedIn()) {
        $currentUser = $auth->getCurrentUser();
        error_log("Newsletter view: User is logged in as: " . $currentUser->getId());
    } else {
        error_log("Newsletter view: User is not logged in");
    }

    // If user is logged in
    if ($currentUser) {
        // Check if this is their newsletter
        if ($currentUser->getId() == $historyEntry['user_id']) {
            // Redirect to history page with this specific newsletter highlighted
            $redirectUrl = '/dashboard/view-history.php?id=' . $historyId . '&from=email';
            error_log("Newsletter view: Redirecting to: $redirectUrl");
            header('Location: ' . $redirectUrl);
            exit;
        } else {
            // This is not their newsletter - show error
            error_log("Newsletter view: User " . $currentUser->getId() . " trying to access newsletter of user " . $historyEntry['user_id']);
            http_response_code(403);
            die('You do not have permission to view this newsletter');
        }
    } else {
        // User is not logged in - redirect to login with return URL
        $returnUrl = urlencode($_SERVER['REQUEST_URI']);
        $loginUrl = '/auth/login.php?return=' . $returnUrl;
        error_log("Newsletter view: Redirecting to login: $loginUrl");
        header('Location: ' . $loginUrl);
        exit;
    }
} catch (Exception $e) {
    error_log("Newsletter view fatal error: " . $e->getMessage());
    error_log("Newsletter view stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    die('An error occurred while processing your request: ' . $e->getMessage());
}
?>