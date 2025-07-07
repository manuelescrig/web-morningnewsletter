<?php
/**
 * Test Script for Multi-Newsletter System
 * 
 * This script tests the new multi-newsletter functionality without affecting production data.
 * Run this after migration to ensure everything works correctly.
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/core/User.php';
require_once __DIR__ . '/core/Newsletter.php';
require_once __DIR__ . '/core/NewsletterBuilder.php';
require_once __DIR__ . '/core/Scheduler.php';

echo "🧪 Testing Multi-Newsletter System\n";
echo "===================================\n\n";

function testResult($test, $result, $details = '') {
    $status = $result ? '✅ PASS' : '❌ FAIL';
    echo "$status: $test";
    if ($details) {
        echo " - $details";
    }
    echo "\n";
    return $result;
}

try {
    // Test 1: Database Schema
    echo "📋 Testing Database Schema...\n";
    $db = Database::getInstance()->getConnection();
    
    // Check newsletters table exists
    $stmt = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='newsletters'");
    $newslettersTableExists = $stmt->fetch() !== false;
    testResult("Newsletters table exists", $newslettersTableExists);
    
    // Check if sources table has newsletter_id column
    $stmt = $db->query("PRAGMA table_info(sources)");
    $columns = $stmt->fetchAll();
    $hasNewsletterIdColumn = false;
    foreach ($columns as $column) {
        if ($column['name'] === 'newsletter_id') {
            $hasNewsletterIdColumn = true;
            break;
        }
    }
    testResult("Sources table has newsletter_id column", $hasNewsletterIdColumn);
    
    // Test 2: Newsletter Class
    echo "\n📰 Testing Newsletter Class...\n";
    
    // Get first user for testing
    $stmt = $db->query("SELECT * FROM users WHERE email_verified = 1 LIMIT 1");
    $userData = $stmt->fetch();
    
    if ($userData) {
        $user = new User($userData);
        $newsletters = $user->getNewsletters();
        testResult("User can retrieve newsletters", is_array($newsletters), count($newsletters) . " newsletters found");
        
        if (!empty($newsletters)) {
            $newsletter = $newsletters[0];
            testResult("Newsletter has valid ID", is_numeric($newsletter->getId()));
            testResult("Newsletter has title", !empty($newsletter->getTitle()));
            testResult("Newsletter has timezone", !empty($newsletter->getTimezone()));
            testResult("Newsletter has send time", !empty($newsletter->getSendTime()));
            
            // Test newsletter sources
            $sources = $newsletter->getSources();
            testResult("Newsletter can retrieve sources", is_array($sources), count($sources) . " sources found");
        }
    } else {
        echo "⚠️  No verified users found for testing\n";
    }
    
    // Test 3: NewsletterBuilder
    echo "\n🏗️ Testing NewsletterBuilder...\n";
    
    if (!empty($newsletters)) {
        $newsletter = $newsletters[0];
        try {
            $builder = new NewsletterBuilder($newsletter, $user);
            testResult("NewsletterBuilder can be instantiated with Newsletter", true);
            
            $content = $builder->buildForPreview();
            testResult("NewsletterBuilder can generate content", !empty($content));
            testResult("Generated content contains newsletter title", 
                strpos($content, $newsletter->getTitle()) !== false);
        } catch (Exception $e) {
            testResult("NewsletterBuilder functionality", false, $e->getMessage());
        }
    }
    
    // Test 4: Backward Compatibility
    echo "\n🔄 Testing Backward Compatibility...\n";
    
    if ($userData) {
        try {
            // Test old-style User methods
            $sources = $user->getSources();
            testResult("User->getSources() still works", is_array($sources));
            
            $sourceCount = $user->getSourceCount();
            testResult("User->getSourceCount() still works", is_numeric($sourceCount));
            
            $timezone = $user->getTimezone();
            testResult("User->getTimezone() still works", !empty($timezone));
            
            $sendTime = $user->getSendTime();
            testResult("User->getSendTime() still works", !empty($sendTime));
            
            $title = $user->getNewsletterTitle();
            testResult("User->getNewsletterTitle() still works", !empty($title));
            
            // Test old-style NewsletterBuilder
            $builder = NewsletterBuilder::fromUser($user);
            testResult("NewsletterBuilder::fromUser() works", $builder instanceof NewsletterBuilder);
            
        } catch (Exception $e) {
            testResult("Backward compatibility", false, $e->getMessage());
        }
    }
    
    // Test 5: Scheduler
    echo "\n⏰ Testing Scheduler...\n";
    
    try {
        $scheduler = new Scheduler();
        
        // Test new newsletter-based methods
        $newslettersToSend = $scheduler->getNewslettersToSend(60); // 1 hour window for testing
        testResult("Scheduler->getNewslettersToSend() works", is_array($newslettersToSend));
        
        // Test backward compatibility
        $usersToSend = $scheduler->getUsersToSend(60);
        testResult("Scheduler->getUsersToSend() still works", is_array($usersToSend));
        
        if (!empty($newsletters)) {
            $newsletter = $newsletters[0];
            $scheduleStatus = $scheduler->getScheduleStatus($newsletter);
            testResult("Scheduler->getScheduleStatus() works with Newsletter", 
                is_array($scheduleStatus) && isset($scheduleStatus['next_send']));
            
            $scheduleStatusUser = $scheduler->getScheduleStatus($user);
            testResult("Scheduler->getScheduleStatus() still works with User", 
                is_array($scheduleStatusUser) && isset($scheduleStatusUser['next_send']));
        }
        
    } catch (Exception $e) {
        testResult("Scheduler functionality", false, $e->getMessage());
    }
    
    // Test 6: Data Integrity
    echo "\n🔍 Testing Data Integrity...\n";
    
    // Check that all newsletters have valid users
    $stmt = $db->query("
        SELECT COUNT(*) as count 
        FROM newsletters n 
        LEFT JOIN users u ON n.user_id = u.id 
        WHERE u.id IS NULL
    ");
    $orphanedNewsletters = $stmt->fetch()['count'];
    testResult("No orphaned newsletters", $orphanedNewsletters == 0, "$orphanedNewsletters orphaned newsletters");
    
    // Check that all sources with newsletter_id have valid newsletters
    $stmt = $db->query("
        SELECT COUNT(*) as count 
        FROM sources s 
        LEFT JOIN newsletters n ON s.newsletter_id = n.id 
        WHERE s.newsletter_id IS NOT NULL AND n.id IS NULL
    ");
    $orphanedSources = $stmt->fetch()['count'];
    testResult("No orphaned sources", $orphanedSources == 0, "$orphanedSources orphaned sources");
    
    // Summary
    echo "\n📊 Test Summary\n";
    echo "================\n";
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM users WHERE email_verified = 1");
    $userCount = $stmt->fetch()['count'];
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM newsletters WHERE is_active = 1");
    $newsletterCount = $stmt->fetch()['count'];
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM sources WHERE is_active = 1");
    $sourceCount = $stmt->fetch()['count'];
    
    echo "👥 Verified Users: $userCount\n";
    echo "📰 Active Newsletters: $newsletterCount\n";
    echo "📊 Active Sources: $sourceCount\n";
    echo "\n";
    
    if ($newsletterCount >= $userCount) {
        echo "✅ Migration appears successful! Each user has at least one newsletter.\n";
    } else {
        echo "⚠️  Warning: Fewer newsletters than users. Some users may be missing newsletters.\n";
    }
    
    echo "\n🎉 Testing completed!\n";
    echo "\nNext steps:\n";
    echo "1. Test the dashboard UI updates\n";
    echo "2. Test the cron job with: php cron/send_emails.php?mode=dry-run\n";
    echo "3. Test force send with: php cron/send_emails.php?mode=force-send&newsletter_id=1\n";
    
} catch (Exception $e) {
    echo "❌ Fatal error during testing: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}