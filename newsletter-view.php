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
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/User.php';
require_once __DIR__ . '/core/NewsletterHistory.php';
require_once __DIR__ . '/config/database.php';

// Get parameters
$historyId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$token = isset($_GET['token']) ? $_GET['token'] : '';

if (!$historyId || !$token) {
    http_response_code(404);
    die('Newsletter not found');
}

// Get newsletter history entry
$historyManager = new NewsletterHistory();
$historyEntry = $historyManager->getHistoryEntry($historyId);

if (!$historyEntry) {
    http_response_code(404);
    die('Newsletter not found');
}

// Verify token - simple token based on history ID and user ID
$secretKey = 'newsletter_view_secret_2025'; // In production, use env variable
$expectedToken = hash('sha256', $historyId . $historyEntry['user_id'] . $secretKey);

if (!hash_equals($expectedToken, $token)) {
    http_response_code(403);
    die('Invalid access token');
}

// Check if user is logged in
$auth = new Auth();
$currentUser = null;

if ($auth->isLoggedIn()) {
    $currentUser = $auth->getUser();
}

// If user is logged in
if ($currentUser) {
    // Check if this is their newsletter
    if ($currentUser->getId() == $historyEntry['user_id']) {
        // Redirect to history page with this specific newsletter highlighted
        $redirectUrl = '/dashboard/view-history.php?id=' . $historyId . '&from=email';
        header('Location: ' . $redirectUrl);
        exit;
    } else {
        // This is not their newsletter - show error
        http_response_code(403);
        die('You do not have permission to view this newsletter');
    }
} else {
    // User is not logged in - redirect to login with return URL
    $returnUrl = urlencode($_SERVER['REQUEST_URI']);
    $loginUrl = '/auth/login.php?return=' . $returnUrl;
    header('Location: ' . $loginUrl);
    exit;
}
// If we reach here, something went wrong
http_response_code(500);
die('An error occurred while processing your request');
?>