<?php
/**
 * Debug script for scheduler timezone issues
 * Run via web: https://yourdomain.com/debug-scheduler.php
 */

// Display output as plain text for easier reading
header('Content-Type: text/plain; charset=utf-8');

// Load required files
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/core/Newsletter.php';
require_once __DIR__ . '/core/User.php';
require_once __DIR__ . '/core/Scheduler.php';

echo "=== Newsletter Scheduler Debug ===\n\n";

// Get database connection
$db = Database::getInstance()->getConnection();

// 1. Show current time in different timezones
echo "1. Current Time Information:\n";
$utcTime = new DateTime('now', new DateTimeZone('UTC'));
echo "   UTC Time: " . $utcTime->format('Y-m-d H:i:s') . "\n";

$timezones = ['America/New_York', 'America/Chicago', 'America/Los_Angeles', 'Europe/London'];
foreach ($timezones as $tz) {
    $localTime = new DateTime('now', new DateTimeZone($tz));
    echo "   $tz: " . $localTime->format('Y-m-d H:i:s') . "\n";
}
echo "\n";

// 2. Check active newsletters
echo "2. Active Newsletters:\n";
try {
    $stmt = $db->query("
        SELECT n.*, u.email, u.email_verified 
        FROM newsletters n 
        JOIN users u ON n.user_id = u.id 
        WHERE n.is_active = 1 
        AND u.email_verified = 1
        ORDER BY n.id
    ");
    $newsletters = $stmt->fetchAll();
    
    if (empty($newsletters)) {
        echo "   No active newsletters found.\n";
    } else {
        foreach ($newsletters as $data) {
            $newsletter = new Newsletter($data);
            echo "\n   Newsletter ID: " . $newsletter->getId() . "\n";
            echo "   - Title: " . $newsletter->getTitle() . "\n";
            echo "   - User: " . $data['email'] . "\n";
            echo "   - Timezone: " . $newsletter->getTimezone() . "\n";
            echo "   - Send Time: " . $newsletter->getSendTime() . "\n";
            echo "   - Daily Times: " . json_encode($newsletter->getDailyTimes()) . "\n";
            echo "   - Active: " . ($newsletter->isActive() ? 'Yes' : 'No') . "\n";
            
            // Calculate next send time
            $scheduler = new Scheduler();
            $userTime = new DateTime('now', new DateTimeZone($newsletter->getTimezone()));
            echo "   - Current time in user TZ: " . $userTime->format('Y-m-d H:i:s') . "\n";
            
            // Check if should send now
            $newslettersToSend = $scheduler->getNewslettersToSend(15);
            $shouldSendNow = false;
            foreach ($newslettersToSend as $item) {
                if ($item['newsletter']->getId() == $newsletter->getId()) {
                    $shouldSendNow = true;
                    break;
                }
            }
            echo "   - Should send in current window: " . ($shouldSendNow ? 'YES' : 'NO') . "\n";
        }
    }
} catch (Exception $e) {
    echo "   Error: " . $e->getMessage() . "\n";
}
echo "\n";

// 3. Check recent newsletter history
echo "3. Recent Newsletter History (last 10 entries):\n";
try {
    $stmt = $db->query("
        SELECT nh.*, n.title, n.timezone
        FROM newsletter_history nh
        JOIN newsletters n ON nh.newsletter_id = n.id
        ORDER BY nh.sent_at DESC
        LIMIT 10
    ");
    $history = $stmt->fetchAll();
    
    if (empty($history)) {
        echo "   No newsletter history found.\n";
    } else {
        foreach ($history as $entry) {
            $sentUtc = new DateTime($entry['sent_at'], new DateTimeZone('UTC'));
            $sentLocal = clone $sentUtc;
            $sentLocal->setTimezone(new DateTimeZone($entry['timezone']));
            
            echo "\n   Newsletter: " . $entry['title'] . " (ID: " . $entry['newsletter_id'] . ")\n";
            echo "   - Sent UTC: " . $sentUtc->format('Y-m-d H:i:s') . "\n";
            echo "   - Sent Local: " . $sentLocal->format('Y-m-d H:i:s') . " (" . $entry['timezone'] . ")\n";
            echo "   - Status: " . ($entry['email_sent'] ? 'SENT' : 'FAILED') . "\n";
            if ($entry['error_message']) {
                echo "   - Error: " . $entry['error_message'] . "\n";
            }
        }
    }
} catch (Exception $e) {
    echo "   Newsletter history table might not exist: " . $e->getMessage() . "\n";
}
echo "\n";

// 4. Test the scheduling window
echo "4. Current 15-minute Scheduling Window:\n";
$scheduler = new Scheduler();
$newslettersToSend = $scheduler->getNewslettersToSend(15);
echo "   Newsletters to send: " . count($newslettersToSend) . "\n";

if (!empty($newslettersToSend)) {
    foreach ($newslettersToSend as $item) {
        $newsletter = $item['newsletter'];
        $user = $item['user'];
        echo "\n   - Newsletter ID " . $newsletter->getId() . " for " . $user->getEmail() . "\n";
        if (isset($item['send_time'])) {
            echo "     Send time: " . $item['send_time'] . "\n";
        }
    }
}

echo "\n=== Debug Complete ===\n";
?>