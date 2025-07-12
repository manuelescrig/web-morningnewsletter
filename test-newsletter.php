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

// Update the history with the final content
$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("UPDATE newsletter_history SET content = ? WHERE id = ?");
$stmt->execute([$result['content'], $historyId]);

echo "<p><a href='/dashboard/history.php'>View in History</a></p>";
?>