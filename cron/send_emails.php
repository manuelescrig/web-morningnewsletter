<?php
#!/usr/bin/env php
<?php
/**
 * Email sending cron job
 * 
 * This script should be run every 15 minutes via cron:
 * */15 * * * * php /path/to/morningnewsletter/cron/send_emails.php
 * 
 * It will find all users whose send time falls within the current 15-minute window
 * and send them their personalized newsletter.
 */

require_once __DIR__ . '/../core/Scheduler.php';
require_once __DIR__ . '/../core/EmailSender.php';

// Set up error logging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/cron.log');

// Ensure logs directory exists
$logDir = __DIR__ . '/../logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

function logMessage($message, $level = 'INFO') {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] [$level] $message" . PHP_EOL;
    
    // Log to file
    file_put_contents(__DIR__ . '/../logs/cron.log', $logMessage, FILE_APPEND | LOCK_EX);
    
    // Also output to console if running in CLI
    if (php_sapi_name() === 'cli') {
        echo $logMessage;
    }
}

function main() {
    logMessage("Starting email sending cron job");
    
    try {
        $scheduler = new Scheduler();
        $results = $scheduler->sendNewsletters();
        
        logMessage("Email sending completed. Results: " . json_encode($results));
        
        // Log individual errors if any
        if (!empty($results['errors'])) {
            foreach ($results['errors'] as $error) {
                logMessage("Error sending to user {$error['user_id']} ({$error['email']}): {$error['error']}", 'ERROR');
            }
        }
        
        // Success summary
        if ($results['total'] > 0) {
            $successRate = round(($results['sent'] / $results['total']) * 100, 1);
            logMessage("Successfully sent {$results['sent']}/{$results['total']} emails ({$successRate}% success rate)");
        } else {
            logMessage("No emails scheduled for this time window");
        }
        
    } catch (Exception $e) {
        logMessage("Fatal error in cron job: " . $e->getMessage(), 'ERROR');
        logMessage("Stack trace: " . $e->getTraceAsString(), 'ERROR');
        exit(1);
    }
    
    logMessage("Email sending cron job completed");
}

// Health check mode - just verify the system is working
if (isset($argv[1]) && $argv[1] === '--health-check') {
    try {
        // Test database connection
        $db = Database::getInstance()->getConnection();
        $stmt = $db->query("SELECT COUNT(*) FROM users");
        $userCount = $stmt->fetchColumn();
        
        logMessage("Health check passed. Database connected. $userCount users in system.");
        
        // Test scheduler
        $scheduler = new Scheduler();
        $users = $scheduler->getUsersToSend(15);
        logMessage("Health check: Found " . count($users) . " users scheduled for current window");
        
        echo "✓ Health check passed\n";
        exit(0);
        
    } catch (Exception $e) {
        logMessage("Health check failed: " . $e->getMessage(), 'ERROR');
        echo "✗ Health check failed: " . $e->getMessage() . "\n";
        exit(1);
    }
}

// Dry run mode - show what would be sent without actually sending
if (isset($argv[1]) && $argv[1] === '--dry-run') {
    try {
        $scheduler = new Scheduler();
        $users = $scheduler->getUsersToSend(15);
        
        logMessage("DRY RUN: Would send newsletters to " . count($users) . " users:");
        
        foreach ($users as $user) {
            $sources = $user->getSources();
            logMessage("DRY RUN: User {$user->getId()} ({$user->getEmail()}) - {$user->getSourceCount()} sources configured");
        }
        
        echo "Dry run completed. Check logs for details.\n";
        exit(0);
        
    } catch (Exception $e) {
        logMessage("Dry run failed: " . $e->getMessage(), 'ERROR');
        echo "Dry run failed: " . $e->getMessage() . "\n";
        exit(1);
    }
}

// Force send mode - send to a specific user (for testing)
if (isset($argv[1]) && $argv[1] === '--force-send' && isset($argv[2])) {
    try {
        $userId = (int)$argv[2];
        $user = User::findById($userId);
        
        if (!$user) {
            logMessage("Force send failed: User $userId not found", 'ERROR');
            exit(1);
        }
        
        logMessage("FORCE SEND: Sending newsletter to user $userId ({$user->getEmail()})");
        
        $builder = new NewsletterBuilder($user);
        $content = $builder->build();
        
        $emailSender = new EmailSender();
        $success = $emailSender->sendNewsletter($user, $content, "Test Newsletter - " . date('F j, Y'));
        
        if ($success) {
            logMessage("FORCE SEND: Successfully sent to user $userId");
            echo "✓ Newsletter sent successfully\n";
        } else {
            logMessage("FORCE SEND: Failed to send to user $userId", 'ERROR');
            echo "✗ Failed to send newsletter\n";
            exit(1);
        }
        
    } catch (Exception $e) {
        logMessage("Force send failed: " . $e->getMessage(), 'ERROR');
        echo "Force send failed: " . $e->getMessage() . "\n";
        exit(1);
    }
    
    exit(0);
}

// Show usage if invalid arguments
if (isset($argv[1])) {
    echo "Usage: php send_emails.php [--health-check|--dry-run|--force-send USER_ID]\n";
    echo "  --health-check    Check if the system is working properly\n";
    echo "  --dry-run        Show what would be sent without sending\n";
    echo "  --force-send ID  Force send newsletter to specific user ID\n";
    echo "  (no args)        Normal operation - send scheduled newsletters\n";
    exit(1);
}

// Normal operation
main();
exit(0);
?>