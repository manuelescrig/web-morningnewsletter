<?php
require_once __DIR__ . '/User.php';
require_once __DIR__ . '/NewsletterBuilder.php';
require_once __DIR__ . '/EmailSender.php';

class Scheduler {
    private $db;
    private $emailSender;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->emailSender = new EmailSender();
    }
    
    public function getUsersToSend($timeWindow = 15) {
        $currentTime = new DateTime('now', new DateTimeZone('UTC'));
        $windowStart = clone $currentTime;
        $windowEnd = clone $currentTime;
        $windowEnd->add(new DateInterval("PT{$timeWindow}M"));
        
        $users = [];
        
        // Get all users with verified emails who haven't unsubscribed
        $stmt = $this->db->prepare("
            SELECT * FROM users 
            WHERE email_verified = 1 
            AND (unsubscribed IS NULL OR unsubscribed = 0)
            AND send_time IS NOT NULL
        ");
        $stmt->execute();
        $allUsers = $stmt->fetchAll();
        
        foreach ($allUsers as $userData) {
            $user = new User($userData);
            $userTime = $this->getUserCurrentTime($user);
            $userSendTime = $this->getUserSendDateTime($user, $userTime);
            
            // Check if user's send time falls within our window
            if ($this->isTimeInWindow($userSendTime, $windowStart, $windowEnd)) {
                // Check if we already sent today
                if (!$this->wasEmailSentToday($user->getId())) {
                    $users[] = $user;
                }
            }
        }
        
        return $users;
    }
    
    private function getUserCurrentTime(User $user) {
        try {
            $timezone = new DateTimeZone($user->getTimezone());
            return new DateTime('now', $timezone);
        } catch (Exception $e) {
            // Fallback to UTC if timezone is invalid
            return new DateTime('now', new DateTimeZone('UTC'));
        }
    }
    
    private function getUserSendDateTime(User $user, DateTime $userTime) {
        $sendTime = $user->getSendTime(); // Format: "06:00"
        list($hour, $minute) = explode(':', $sendTime);
        
        $sendDateTime = clone $userTime;
        $sendDateTime->setTime((int)$hour, (int)$minute, 0);
        
        // Convert to UTC for comparison
        $sendDateTime->setTimezone(new DateTimeZone('UTC'));
        
        return $sendDateTime;
    }
    
    private function isTimeInWindow(DateTime $sendTime, DateTime $windowStart, DateTime $windowEnd) {
        return $sendTime >= $windowStart && $sendTime <= $windowEnd;
    }
    
    private function wasEmailSentToday($userId) {
        $today = date('Y-m-d');
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM email_logs 
            WHERE user_id = ? 
            AND status = 'sent' 
            AND DATE(sent_at) = ?
        ");
        $stmt->execute([$userId, $today]);
        $result = $stmt->fetch();
        
        return $result['count'] > 0;
    }
    
    public function sendNewsletters() {
        $users = $this->getUsersToSend();
        $results = [
            'total' => count($users),
            'sent' => 0,
            'failed' => 0,
            'errors' => []
        ];
        
        foreach ($users as $user) {
            try {
                $this->sendNewsletterToUser($user);
                $results['sent']++;
            } catch (Exception $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'user_id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'error' => $e->getMessage()
                ];
                error_log("Failed to send newsletter to user {$user->getId()}: " . $e->getMessage());
            }
        }
        
        return $results;
    }
    
    private function sendNewsletterToUser(User $user) {
        // Build newsletter content
        $builder = new NewsletterBuilder($user);
        $content = $builder->build();
        
        // Send email
        $subject = "Your Morning Brief - " . date('F j, Y');
        $success = $this->emailSender->sendNewsletter($user, $content, $subject);
        
        if (!$success) {
            throw new Exception("Failed to send email to " . $user->getEmail());
        }
        
        return true;
    }
    
    public function getNextSendTime(User $user) {
        $userTime = $this->getUserCurrentTime($user);
        $sendTime = $user->getSendTime();
        list($hour, $minute) = explode(':', $sendTime);
        
        $nextSend = clone $userTime;
        $nextSend->setTime((int)$hour, (int)$minute, 0);
        
        // If send time has passed today, schedule for tomorrow
        if ($nextSend <= $userTime) {
            $nextSend->add(new DateInterval('P1D'));
        }
        
        return $nextSend;
    }
    
    public function getScheduleStatus(User $user) {
        $nextSend = $this->getNextSendTime($user);
        $wasEmailSentToday = $this->wasEmailSentToday($user->getId());
        
        return [
            'next_send' => $nextSend->format('Y-m-d H:i:s T'),
            'next_send_formatted' => $nextSend->format('F j, Y g:i A T'),
            'next_send_object' => $nextSend,
            'sent_today' => $wasEmailSentToday,
            'timezone' => $user->getTimezone(),
            'send_time' => $user->getSendTime()
        ];
    }
}