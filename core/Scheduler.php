<?php
require_once __DIR__ . '/User.php';
require_once __DIR__ . '/Newsletter.php';
require_once __DIR__ . '/NewsletterBuilder.php';
require_once __DIR__ . '/EmailSender.php';

class Scheduler {
    private $db;
    private $emailSender;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->emailSender = new EmailSender();
    }
    
    public function getNewslettersToSend($timeWindow = 15) {
        $currentTime = new DateTime('now', new DateTimeZone('UTC'));
        $windowStart = clone $currentTime;
        $windowEnd = clone $currentTime;
        $windowEnd->add(new DateInterval("PT{$timeWindow}M"));
        
        $newslettersToSend = [];
        
        // Get all newsletters from verified, active users
        $stmt = $this->db->prepare("
            SELECT n.*, u.email, u.email_verified, u.unsubscribed 
            FROM newsletters n
            JOIN users u ON n.user_id = u.id
            WHERE n.is_active = 1 
            AND u.email_verified = 1 
            AND (u.unsubscribed IS NULL OR u.unsubscribed = 0)
            AND n.send_time IS NOT NULL
        ");
        $stmt->execute();
        $allNewsletters = $stmt->fetchAll();
        
        foreach ($allNewsletters as $newsletterData) {
            $newsletter = new Newsletter($newsletterData);
            $user = User::findById($newsletter->getUserId());
            
            if (!$user) continue;
            
            $newsletterTime = $this->getNewsletterCurrentTime($newsletter);
            $newsletterSendTime = $this->getNewsletterSendDateTime($newsletter, $newsletterTime);
            
            // Check if newsletter's send time falls within our window
            if ($this->isTimeInWindow($newsletterSendTime, $windowStart, $windowEnd)) {
                // Check if we already sent this newsletter today
                if (!$this->wasNewsletterSentToday($newsletter->getId())) {
                    $newslettersToSend[] = ['newsletter' => $newsletter, 'user' => $user];
                }
            }
        }
        
        return $newslettersToSend;
    }
    
    // Backward compatibility method
    public function getUsersToSend($timeWindow = 15) {
        $newsletters = $this->getNewslettersToSend($timeWindow);
        $users = [];
        
        foreach ($newsletters as $item) {
            $users[] = $item['user'];
        }
        
        return $users;
    }
    
    private function getNewsletterCurrentTime(Newsletter $newsletter) {
        try {
            $timezone = new DateTimeZone($newsletter->getTimezone());
            return new DateTime('now', $timezone);
        } catch (Exception $e) {
            // Fallback to UTC if timezone is invalid
            return new DateTime('now', new DateTimeZone('UTC'));
        }
    }
    
    private function getNewsletterSendDateTime(Newsletter $newsletter, DateTime $newsletterTime) {
        $sendTime = $newsletter->getSendTime(); // Format: "06:00"
        list($hour, $minute) = explode(':', $sendTime);
        
        $sendDateTime = clone $newsletterTime;
        $sendDateTime->setTime((int)$hour, (int)$minute, 0);
        
        // Convert to UTC for comparison
        $sendDateTime->setTimezone(new DateTimeZone('UTC'));
        
        return $sendDateTime;
    }
    
    // Backward compatibility methods
    private function getUserCurrentTime(User $user) {
        try {
            $timezone = new DateTimeZone($user->getTimezone());
            return new DateTime('now', $timezone);
        } catch (Exception $e) {
            return new DateTime('now', new DateTimeZone('UTC'));
        }
    }
    
    private function getUserSendDateTime(User $user, DateTime $userTime) {
        $sendTime = $user->getSendTime();
        list($hour, $minute) = explode(':', $sendTime);
        
        $sendDateTime = clone $userTime;
        $sendDateTime->setTime((int)$hour, (int)$minute, 0);
        $sendDateTime->setTimezone(new DateTimeZone('UTC'));
        
        return $sendDateTime;
    }
    
    private function isTimeInWindow(DateTime $sendTime, DateTime $windowStart, DateTime $windowEnd) {
        return $sendTime >= $windowStart && $sendTime <= $windowEnd;
    }
    
    private function wasNewsletterSentToday($newsletterId) {
        $today = date('Y-m-d');
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM email_logs 
            WHERE newsletter_id = ? 
            AND status = 'sent' 
            AND DATE(sent_at) = ?
        ");
        $stmt->execute([$newsletterId, $today]);
        $result = $stmt->fetch();
        
        return $result['count'] > 0;
    }
    
    // Backward compatibility method
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
        $newslettersToSend = $this->getNewslettersToSend();
        $results = [
            'total' => count($newslettersToSend),
            'sent' => 0,
            'failed' => 0,
            'errors' => []
        ];
        
        foreach ($newslettersToSend as $item) {
            $newsletter = $item['newsletter'];
            $user = $item['user'];
            
            try {
                $this->sendNewsletterToUser($newsletter, $user);
                $results['sent']++;
            } catch (Exception $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'user_id' => $user->getId(),
                    'newsletter_id' => $newsletter->getId(),
                    'email' => $user->getEmail(),
                    'newsletter_title' => $newsletter->getTitle(),
                    'error' => $e->getMessage()
                ];
                error_log("Failed to send newsletter {$newsletter->getId()} to user {$user->getId()}: " . $e->getMessage());
            }
        }
        
        return $results;
    }
    
    private function sendNewsletterToUser(Newsletter $newsletter, User $user) {
        // Build newsletter content
        $builder = new NewsletterBuilder($newsletter, $user);
        $content = $builder->build();
        
        // Send email
        $subject = $newsletter->getTitle() . " - " . date('F j, Y');
        $success = $this->emailSender->sendNewsletter($user, $content, $subject, $newsletter->getId());
        
        if (!$success) {
            throw new Exception("Failed to send email to " . $user->getEmail());
        }
        
        return true;
    }
    
    public function getNextSendTime($object) {
        // Handle both Newsletter and User objects for backward compatibility
        if ($object instanceof Newsletter) {
            $newsletter = $object;
            $newsletterTime = $this->getNewsletterCurrentTime($newsletter);
            $sendTime = $newsletter->getSendTime();
        } else if ($object instanceof User) {
            $user = $object;
            $newsletter = $user->getDefaultNewsletter();
            if (!$newsletter) {
                throw new Exception("User has no newsletters");
            }
            $newsletterTime = $this->getNewsletterCurrentTime($newsletter);
            $sendTime = $newsletter->getSendTime();
        } else {
            throw new Exception("Invalid object type for getNextSendTime");
        }
        
        list($hour, $minute) = explode(':', $sendTime);
        
        $nextSend = clone $newsletterTime;
        $nextSend->setTime((int)$hour, (int)$minute, 0);
        
        // If send time has passed today, schedule for tomorrow
        if ($nextSend <= $newsletterTime) {
            $nextSend->add(new DateInterval('P1D'));
        }
        
        return $nextSend;
    }
    
    public function getScheduleStatus($object) {
        // Handle both Newsletter and User objects for backward compatibility
        if ($object instanceof Newsletter) {
            $newsletter = $object;
            $nextSend = $this->getNextSendTime($newsletter);
            $wasEmailSentToday = $this->wasNewsletterSentToday($newsletter->getId());
            $timezone = $newsletter->getTimezone();
            $sendTime = $newsletter->getSendTime();
        } else if ($object instanceof User) {
            $user = $object;
            $newsletter = $user->getDefaultNewsletter();
            if (!$newsletter) {
                throw new Exception("User has no newsletters");
            }
            $nextSend = $this->getNextSendTime($newsletter);
            $wasEmailSentToday = $this->wasNewsletterSentToday($newsletter->getId());
            $timezone = $newsletter->getTimezone();
            $sendTime = $newsletter->getSendTime();
        } else {
            throw new Exception("Invalid object type for getScheduleStatus");
        }
        
        return [
            'next_send' => $nextSend->format('Y-m-d H:i:s T'),
            'next_send_formatted' => $nextSend->format('F j, Y g:i A T'),
            'next_send_object' => $nextSend,
            'sent_today' => $wasEmailSentToday,
            'timezone' => $timezone,
            'send_time' => $sendTime
        ];
    }
}