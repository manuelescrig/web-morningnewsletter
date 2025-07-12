<?php
/**
 * Test Newsletter Generation
 * 
 * This page will generate a test newsletter to verify the "View in Browser" link
 * DELETE THIS FILE after testing
 */

require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/User.php';
require_once __DIR__ . '/core/Newsletter.php';
require_once __DIR__ . '/core/NewsletterBuilder.php';
require_once __DIR__ . '/core/NewsletterHistory.php';
require_once __DIR__ . '/config/database.php';

$auth = Auth::getInstance();
$auth->requireAuth();

$user = $auth->getCurrentUser();
$newsletters = Newsletter::findByUser($user->getId());

if (empty($newsletters)) {
    die('No newsletters found. Please create a newsletter first.');
}

$newsletter = $newsletters[0]; // Use first newsletter

echo "<h1>Testing Newsletter Generation</h1>";
echo "<p>User: " . htmlspecialchars($user->getEmail()) . "</p>";
echo "<p>Newsletter: " . htmlspecialchars($newsletter->getTitle()) . "</p>";

// Create a test history entry
$historyManager = new NewsletterHistory();
$historyId = $historyManager->saveToHistory(
    $newsletter->getId(),
    $user->getId(),
    "Test Newsletter - " . date('F j, Y'),
    'placeholder content',
    []
);

echo "<p>Created history ID: $historyId</p>";

// Generate newsletter with history ID
$builder = new NewsletterBuilder($newsletter, $user);
$result = $builder->buildWithSourceDataAndHistoryId($historyId);

echo "<h2>Generated Newsletter Content:</h2>";
echo "<div style='border: 1px solid #ccc; padding: 20px; margin: 20px 0;'>";
echo $result['content'];
echo "</div>";

// Check if this newsletter content contains the "View in Browser" link
if (strpos($result['content'], 'View in browser') !== false) {
    echo "<p style='color: green;'><strong>✓ View in Browser link found in content!</strong></p>";
} else {
    echo "<p style='color: red;'><strong>✗ View in Browser link NOT found in content!</strong></p>";
}

// Update the history with the final content
$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("UPDATE newsletter_history SET content = ? WHERE id = ?");
$stmt->execute([$result['content'], $historyId]);

echo "<h3>Test Email Send</h3>";
echo "<form method='post'>";
echo "<p>Send this newsletter as a test email to: " . htmlspecialchars($user->getEmail()) . "</p>";
echo "<button type='submit' name='send_test' style='background: #0041EC; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>Send Test Email</button>";
echo "</form>";

// Handle email sending
if (isset($_POST['send_test'])) {
    require_once __DIR__ . '/core/EmailSender.php';
    
    $emailSender = new EmailSender();
    $success = $emailSender->sendNewsletterWithHistory($user, $newsletter);
    
    if ($success) {
        echo "<p style='color: green;'><strong>✓ Test email sent successfully!</strong></p>";
        echo "<p>Check your email and see if the 'View in Browser' link appears.</p>";
    } else {
        echo "<p style='color: red;'><strong>✗ Failed to send test email!</strong></p>";
    }
}

echo "<p><a href='/dashboard/history.php'>View in History</a></p>";
?>