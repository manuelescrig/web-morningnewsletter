<?php
// Test script to check scheduler and database setup
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/core/Scheduler.php';
require_once __DIR__ . '/core/Newsletter.php';

echo "=== Newsletter Scheduler Test ===\n\n";

// Initialize database
echo "1. Initializing database...\n";
$db = Database::getInstance()->getConnection();
echo "   ✓ Database initialized\n\n";

// Check tables
echo "2. Checking database tables...\n";
$tables = $db->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll();
foreach ($tables as $table) {
    echo "   - " . $table['name'] . "\n";
}
echo "\n";

// Create test user and newsletter for testing
echo "3. Creating test data...\n";
try {
    // Check if test user exists
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute(['test@example.com']);
    $userId = $stmt->fetchColumn();
    
    if (!$userId) {
        // Create test user
        $stmt = $db->prepare("INSERT INTO users (email, name, password_hash, timezone, email_verified) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute(['test@example.com', 'Test User', password_hash('test123', PASSWORD_DEFAULT), 'America/New_York', 1]);
        $userId = $db->lastInsertId();
        echo "   ✓ Created test user (ID: $userId)\n";
    } else {
        echo "   ✓ Test user already exists (ID: $userId)\n";
    }
    
    // Check if newsletter exists
    $stmt = $db->prepare("SELECT id FROM newsletters WHERE user_id = ?");
    $stmt->execute([$userId]);
    $newsletterId = $stmt->fetchColumn();
    
    if (!$newsletterId) {
        // Create test newsletter with current time + 5 minutes
        $sendTime = (new DateTime('+5 minutes'))->format('H:i');
        $stmt = $db->prepare("INSERT INTO newsletters (user_id, title, timezone, send_time, is_active) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$userId, 'Test Newsletter', 'America/New_York', $sendTime, 1]);
        $newsletterId = $db->lastInsertId();
        echo "   ✓ Created test newsletter (ID: $newsletterId, send time: $sendTime)\n";
    } else {
        echo "   ✓ Test newsletter already exists (ID: $newsletterId)\n";
    }
    
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
}
echo "\n";

// Test scheduler
echo "4. Testing scheduler...\n";
$scheduler = new Scheduler();

// Current time info
$currentTime = new DateTime('now', new DateTimeZone('UTC'));
$localTime = new DateTime('now', new DateTimeZone('America/New_York'));
echo "   Current UTC time: " . $currentTime->format('Y-m-d H:i:s') . "\n";
echo "   Current EST time: " . $localTime->format('Y-m-d H:i:s') . "\n\n";

// Check newsletters to send
echo "5. Checking newsletters to send (15-minute window)...\n";
$newslettersToSend = $scheduler->getNewslettersToSend(15);
echo "   Found " . count($newslettersToSend) . " newsletter(s) to send\n";

foreach ($newslettersToSend as $item) {
    $newsletter = $item['newsletter'];
    $user = $item['user'];
    echo "\n   Newsletter Details:\n";
    echo "   - ID: " . $newsletter->getId() . "\n";
    echo "   - Title: " . $newsletter->getTitle() . "\n";
    echo "   - User: " . $user->getEmail() . "\n";
    echo "   - Send Time: " . $newsletter->getSendTime() . "\n";
    echo "   - Timezone: " . $newsletter->getTimezone() . "\n";
    echo "   - Active: " . ($newsletter->isActive() ? 'Yes' : 'No') . "\n";
}

// Check recent newsletter history
echo "\n6. Checking recent newsletter history...\n";
try {
    $stmt = $db->query("SELECT * FROM newsletter_history ORDER BY sent_at DESC LIMIT 5");
    $history = $stmt->fetchAll();
    
    if (empty($history)) {
        echo "   No newsletter history found\n";
    } else {
        foreach ($history as $entry) {
            echo "   - Newsletter " . $entry['newsletter_id'] . " sent at " . $entry['sent_at'] . " (status: " . $entry['status'] . ")\n";
        }
    }
} catch (Exception $e) {
    echo "   Newsletter history table might not exist yet\n";
}

echo "\n=== Test Complete ===\n";
?>