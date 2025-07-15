<?php
/**
 * Web-based email sending cron job
 * 
 * Usage:
 * 1. Normal operation: https://domain.com/cron/send_emails.php
 * 2. Health check: https://domain.com/cron/send_emails.php?mode=health-check
 * 3. Dry run: https://domain.com/cron/send_emails.php?mode=dry-run
 * 4. Force send: https://domain.com/cron/send_emails.php?mode=force-send&user_id=123
 * 
 * It will find all users whose send time falls within the current 15-minute window
 * and send them their personalized newsletter.
 */

// Set headers for web output
header('Content-Type: text/plain');

// Basic security logging (but allow all access for web cron)
if (!empty($_SERVER['HTTP_USER_AGENT'])) {
    error_log("Web cron accessed from: " . $_SERVER['HTTP_USER_AGENT']);
}

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Change to project root directory to ensure relative paths work
chdir(__DIR__ . '/..');

try {
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../core/User.php';
    require_once __DIR__ . '/../core/NewsletterBuilder.php';
    require_once __DIR__ . '/../core/Scheduler.php';
    require_once __DIR__ . '/../core/EmailSender.php';
} catch (Exception $e) {
    echo "Error loading required files: " . $e->getMessage() . "\n";
    exit(1);
}

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
    
    // Output to browser (already set Content-Type to text/plain)
    echo $logMessage;
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
                $newsletterInfo = isset($error['newsletter_id']) ? " newsletter {$error['newsletter_id']} ('{$error['newsletter_title']}')": "";
                logMessage("Error sending{$newsletterInfo} to user {$error['user_id']} ({$error['email']}): {$error['error']}", 'ERROR');
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
if (isset($_GET['mode']) && $_GET['mode'] === 'health-check') {
    try {
        // Test database connection
        $db = Database::getInstance()->getConnection();
        $stmt = $db->query("SELECT COUNT(*) FROM users");
        $userCount = $stmt->fetchColumn();
        
        logMessage("Health check passed. Database connected. $userCount users in system.");
        
        // Test scheduler
        $scheduler = new Scheduler();
        $newsletters = $scheduler->getNewslettersToSend(15);
        logMessage("Health check: Found " . count($newsletters) . " newsletters scheduled for current window");
        
        echo "✓ Health check passed\n";
        exit(0);
        
    } catch (Exception $e) {
        logMessage("Health check failed: " . $e->getMessage(), 'ERROR');
        echo "✗ Health check failed: " . $e->getMessage() . "\n";
        exit(1);
    }
}

// Dry run mode - show what would be sent without actually sending
if (isset($_GET['mode']) && $_GET['mode'] === 'dry-run') {
    try {
        $scheduler = new Scheduler();
        $users = $scheduler->getUsersToSend(15);
        
        logMessage("DRY RUN: Would send newsletters to " . count($users) . " users:");
        
        foreach ($users as $user) {
            $newsletters = $user->getNewsletters();
            logMessage("DRY RUN: User {$user->getId()} ({$user->getEmail()}) - " . count($newsletters) . " newsletters:");
            foreach ($newsletters as $newsletter) {
                logMessage("  - Newsletter {$newsletter->getId()}: '{$newsletter->getTitle()}' ({$newsletter->getSourceCount()} sources)");
            }
        }
        
        echo "Dry run completed. Check logs for details.\n";
        exit(0);
        
    } catch (Exception $e) {
        logMessage("Dry run failed: " . $e->getMessage(), 'ERROR');
        echo "Dry run failed: " . $e->getMessage() . "\n";
        exit(1);
    }
}

// Force send mode - send to a specific user or newsletter (for testing)
if (isset($_GET['mode']) && $_GET['mode'] === 'force-send') {
    try {
        if (isset($_GET['newsletter_id'])) {
            // Send specific newsletter
            $newsletterId = (int)$_GET['newsletter_id'];
            $newsletter = Newsletter::findById($newsletterId);
            
            if (!$newsletter) {
                logMessage("Force send failed: Newsletter $newsletterId not found", 'ERROR');
                exit(1);
            }
            
            $user = User::findById($newsletter->getUserId());
            if (!$user) {
                logMessage("Force send failed: User for newsletter $newsletterId not found", 'ERROR');
                exit(1);
            }
            
            logMessage("FORCE SEND: Sending newsletter $newsletterId ('{$newsletter->getTitle()}') to user {$user->getId()} ({$user->getEmail()})");
            
            $emailSender = new EmailSender();
            $success = $emailSender->sendNewsletterWithHistory($user, $newsletter);
            
            if ($success) {
                logMessage("FORCE SEND: Successfully sent newsletter $newsletterId to user {$user->getId()}");
                echo "✓ Newsletter sent successfully\n";
            } else {
                logMessage("FORCE SEND: Failed to send newsletter $newsletterId to user {$user->getId()}", 'ERROR');
                echo "✗ Failed to send newsletter\n";
                exit(1);
            }
        } else if (isset($_GET['user_id'])) {
            // Send default newsletter for user (backward compatibility)
            $userId = (int)$_GET['user_id'];
            $user = User::findById($userId);
            
            if (!$user) {
                logMessage("Force send failed: User $userId not found", 'ERROR');
                exit(1);
            }
            
            $newsletter = $user->getDefaultNewsletter();
            if (!$newsletter) {
                logMessage("Force send failed: User $userId has no newsletters", 'ERROR');
                exit(1);
            }
            
            logMessage("FORCE SEND: Sending default newsletter to user $userId ({$user->getEmail()})");
            
            $emailSender = new EmailSender();
            $success = $emailSender->sendNewsletterWithHistory($user, $newsletter);
            
            if ($success) {
                logMessage("FORCE SEND: Successfully sent to user $userId");
                echo "✓ Newsletter sent successfully\n";
            } else {
                logMessage("FORCE SEND: Failed to send to user $userId", 'ERROR');
                echo "✗ Failed to send newsletter\n";
                exit(1);
            }
        } else {
            logMessage("Force send failed: Either user_id or newsletter_id parameter required", 'ERROR');
            echo "Force send failed: Either user_id or newsletter_id parameter required\n";
            exit(1);
        }
        
    } catch (Exception $e) {
        logMessage("Force send failed: " . $e->getMessage(), 'ERROR');
        echo "Force send failed: " . $e->getMessage() . "\n";
        exit(1);
    }
    
    exit(0);
}

// Show usage if invalid parameters for web requests
if (isset($_GET['mode']) && !in_array($_GET['mode'], ['health-check', 'dry-run', 'force-send'])) {
    echo "Invalid mode. Valid modes: health-check, dry-run, force-send\n";
    exit(1);
}

// Normal operation
main();
exit(0);
?>