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
            
            if (!$user || $newsletter->isPaused()) continue;
            
            $newsletterTime = $this->getNewsletterCurrentTime($newsletter);
            
            // Always check daily_times array (even for single times)
            $dailyTimes = $newsletter->getDailyTimes();
            
            if (!empty($dailyTimes)) {
                // Handle all times from daily_times array
                foreach ($dailyTimes as $time) {
                    $newsletterSendTime = $this->getNewsletterSendDateTimeForTime($newsletter, $newsletterTime, $time);
                    
                    if ($this->isTimeInWindow($newsletterSendTime, $windowStart, $windowEnd)) {
                        if ($this->shouldSendNewsletterAtTime($newsletter, $newsletterTime, $time)) {
                            $newslettersToSend[] = [
                                'newsletter' => $newsletter, 
                                'user' => $user, 
                                'send_time' => $time
                            ];
                            break; // Only add once per window
                        }
                    }
                }
            } else {
                // Fallback to single time (for legacy newsletters)
                $newsletterSendTime = $this->getNewsletterSendDateTime($newsletter, $newsletterTime);
                
                if ($this->isTimeInWindow($newsletterSendTime, $windowStart, $windowEnd)) {
                    if ($this->shouldSendNewsletter($newsletter, $newsletterTime)) {
                        $newslettersToSend[] = ['newsletter' => $newsletter, 'user' => $user];
                    }
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
    
    private function getNewsletterSendDateTimeForTime(Newsletter $newsletter, DateTime $newsletterTime, $sendTime) {
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
            FROM newsletter_history 
            WHERE newsletter_id = ? 
            AND email_sent = 1 
            AND DATE(sent_at) = ?
        ");
        $stmt->execute([$newsletterId, $today]);
        $result = $stmt->fetch();
        
        return $result['count'] > 0;
    }

    /**
     * Get the last sent time for a newsletter
     */
    private function getLastSentTime($newsletterId, $timezone) {
        $stmt = $this->db->prepare("
            SELECT sent_at 
            FROM newsletter_history 
            WHERE newsletter_id = ? 
            AND email_sent = 1 
            ORDER BY sent_at DESC 
            LIMIT 1
        ");
        $stmt->execute([$newsletterId]);
        $result = $stmt->fetch();
        
        if ($result) {
            // Convert to user's timezone
            $lastSent = new DateTime($result['sent_at'], new DateTimeZone('UTC'));
            $lastSent->setTimezone(new DateTimeZone($timezone));
            return $lastSent;
        }
        
        return null;
    }
    
    private function wasNewsletterSentAtTimeToday($newsletterId, $sendTime) {
        $today = date('Y-m-d');
        
        // Create time window around the send time (30 minutes)
        $startTime = date('H:i:s', strtotime($sendTime . ' -15 minutes'));
        $endTime = date('H:i:s', strtotime($sendTime . ' +15 minutes'));
        
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM newsletter_history 
            WHERE newsletter_id = ? 
            AND email_sent = 1 
            AND DATE(sent_at) = ?
            AND TIME(sent_at) BETWEEN ? AND ?
        ");
        $stmt->execute([$newsletterId, $today, $startTime, $endTime]);
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
            $sendTime = $item['send_time'] ?? null; // For multiple daily sends
            
            try {
                $this->sendNewsletterToUser($newsletter, $user, $sendTime);
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
    
    private function sendNewsletterToUser(Newsletter $newsletter, User $user, $sendTime = null) {
        // Send newsletter with history and view-in-browser functionality
        $success = $this->emailSender->sendNewsletterWithHistory($user, $newsletter, $sendTime);
        
        if (!$success) {
            throw new Exception("Failed to send email to " . $user->getEmail());
        }
        
        return true;
    }
    
    public function getNextSendTime($object) {
        // Handle both Newsletter and User objects for backward compatibility
        if ($object instanceof Newsletter) {
            $newsletter = $object;
        } else if ($object instanceof User) {
            $user = $object;
            $newsletter = $user->getDefaultNewsletter();
            if (!$newsletter) {
                throw new Exception("User has no newsletters");
            }
        } else {
            throw new Exception("Invalid object type for getNextSendTime");
        }
        
        return $this->calculateNextSendTime($newsletter);
    }
    
    /**
     * Calculate the next send time based on newsletter frequency
     */
    private function calculateNextSendTime(Newsletter $newsletter) {
        $currentTime = $this->getNewsletterCurrentTime($newsletter);
        $dailyTimes = $newsletter->getDailyTimes();
        
        if (!empty($dailyTimes)) {
            // Always use daily_times array approach
            return $this->getNextMultipleTimesSendTime($newsletter, $currentTime);
        } else {
            // Fallback to single time for legacy newsletters
            $sendTime = $newsletter->getSendTime();
            list($hour, $minute) = explode(':', $sendTime);
            
            switch ($newsletter->getFrequency()) {
                case 'daily':
                    return $this->getNextDailySendTime($newsletter, $currentTime, $hour, $minute);
                case 'weekly':
                    return $this->getNextWeeklySendTime($newsletter, $currentTime, $hour, $minute);
                case 'monthly':
                    return $this->getNextMonthlySendTime($newsletter, $currentTime, $hour, $minute);
                case 'quarterly':
                    return $this->getNextQuarterlySendTime($newsletter, $currentTime, $hour, $minute);
                default:
                    return $this->getNextDailySendTime($newsletter, $currentTime, $hour, $minute);
            }
        }
    }
    
    private function getNextDailySendTime(Newsletter $newsletter, DateTime $currentTime, $hour, $minute) {
        $nextSend = clone $currentTime;
        $nextSend->setTime((int)$hour, (int)$minute, 0);
        
        // If send time has passed today, schedule for tomorrow
        if ($nextSend <= $currentTime) {
            $nextSend->add(new DateInterval('P1D'));
        }
        
        return $nextSend;
    }
    
    private function getNextMultipleTimesSendTime(Newsletter $newsletter, DateTime $currentTime) {
        $dailyTimes = $newsletter->getDailyTimes();
        if (empty($dailyTimes)) {
            // Fallback to regular logic if no times configured
            $sendTime = $newsletter->getSendTime();
            list($hour, $minute) = explode(':', $sendTime);
            return $this->getNextDailySendTime($newsletter, $currentTime, $hour, $minute);
        }
        
        // Sort times for easier processing
        sort($dailyTimes);
        
        $currentTimeStr = $currentTime->format('H:i');
        $currentTimeStamp = strtotime($currentTimeStr);
        
        // For daily frequency, check today first
        if ($newsletter->getFrequency() === 'daily') {
            // Find the next send time today
            foreach ($dailyTimes as $time) {
                $timeStamp = strtotime($time);
                if ($timeStamp > $currentTimeStamp) {
                    // Check if not already sent at this time
                    if (!$this->wasNewsletterSentAtTimeToday($newsletter->getId(), $time)) {
                        $nextSend = clone $currentTime;
                        list($hour, $minute) = explode(':', $time);
                        $nextSend->setTime((int)$hour, (int)$minute, 0);
                        return $nextSend;
                    }
                }
            }
            
            // No more sends today, get first time tomorrow
            $nextSend = clone $currentTime;
            $nextSend->add(new DateInterval('P1D'));
            list($hour, $minute) = explode(':', $dailyTimes[0]);
            $nextSend->setTime((int)$hour, (int)$minute, 0);
            return $nextSend;
        } else {
            // For weekly/monthly, find the next scheduled date, then earliest time
            $baseNextSend = $this->getBaseNextSendDate($newsletter, $currentTime);
            list($hour, $minute) = explode(':', $dailyTimes[0]); // Use earliest time
            $baseNextSend->setTime((int)$hour, (int)$minute, 0);
            return $baseNextSend;
        }
    }
    
    private function getBaseNextSendDate(Newsletter $newsletter, DateTime $currentTime) {
        $sendTime = $newsletter->getSendTime();
        list($hour, $minute) = explode(':', $sendTime);
        
        switch ($newsletter->getFrequency()) {
            case 'weekly':
                return $this->getNextWeeklySendTime($newsletter, $currentTime, $hour, $minute);
            case 'monthly':
                return $this->getNextMonthlySendTime($newsletter, $currentTime, $hour, $minute);
            case 'quarterly':
                return $this->getNextQuarterlySendTime($newsletter, $currentTime, $hour, $minute);
            default:
                return $this->getNextDailySendTime($newsletter, $currentTime, $hour, $minute);
        }
    }
    
    private function getNextWeeklySendTime(Newsletter $newsletter, DateTime $currentTime, $hour, $minute) {
        $daysOfWeek = $newsletter->getDaysOfWeek();
        if (empty($daysOfWeek)) {
            $daysOfWeek = [1]; // Default to Monday
        }
        
        $currentDayOfWeek = (int)$currentTime->format('N'); // 1 = Monday, 7 = Sunday
        $nextSend = clone $currentTime;
        $nextSend->setTime((int)$hour, (int)$minute, 0);
        
        // Check if today is a scheduled day and time hasn't passed
        if (in_array($currentDayOfWeek, $daysOfWeek) && $nextSend > $currentTime) {
            return $nextSend;
        }
        
        // Find the next scheduled day
        for ($i = 1; $i <= 7; $i++) {
            $testDate = clone $currentTime;
            $testDate->add(new DateInterval("P{$i}D"));
            $testDayOfWeek = (int)$testDate->format('N');
            
            if (in_array($testDayOfWeek, $daysOfWeek)) {
                $testDate->setTime((int)$hour, (int)$minute, 0);
                return $testDate;
            }
        }
        
        // Fallback - should never reach here
        return $this->getNextDailySendTime($newsletter, $currentTime, $hour, $minute);
    }
    
    private function getNextMonthlySendTime(Newsletter $newsletter, DateTime $currentTime, $hour, $minute) {
        $dayOfMonth = $newsletter->getDayOfMonth();
        $currentDay = (int)$currentTime->format('j');
        
        $nextSend = clone $currentTime;
        
        // Try this month first
        $lastDayOfMonth = (int)$currentTime->format('t');
        $targetDay = min($dayOfMonth, $lastDayOfMonth);
        
        if ($currentDay < $targetDay || ($currentDay == $targetDay && $currentTime->format('H:i') < $newsletter->getSendTime())) {
            // Can send this month
            $nextSend->setDate((int)$nextSend->format('Y'), (int)$nextSend->format('n'), $targetDay);
            $nextSend->setTime((int)$hour, (int)$minute, 0);
            return $nextSend;
        }
        
        // Move to next month
        $nextSend->add(new DateInterval('P1M'));
        $nextSend->setDate((int)$nextSend->format('Y'), (int)$nextSend->format('n'), 1); // First day of next month
        
        $lastDayOfNextMonth = (int)$nextSend->format('t');
        $targetDay = min($dayOfMonth, $lastDayOfNextMonth);
        
        $nextSend->setDate((int)$nextSend->format('Y'), (int)$nextSend->format('n'), $targetDay);
        $nextSend->setTime((int)$hour, (int)$minute, 0);
        
        return $nextSend;
    }
    
    private function getNextQuarterlySendTime(Newsletter $newsletter, DateTime $currentTime, $hour, $minute) {
        $months = $newsletter->getMonths();
        $dayOfMonth = $newsletter->getDayOfMonth();
        
        if (empty($months)) {
            $months = [1, 4, 7, 10]; // Default to first month of each quarter
        }
        
        $currentMonth = (int)$currentTime->format('n');
        $currentDay = (int)$currentTime->format('j');
        $currentYear = (int)$currentTime->format('Y');
        
        // Check if we can send this month
        if (in_array($currentMonth, $months)) {
            $lastDayOfMonth = (int)$currentTime->format('t');
            $targetDay = min($dayOfMonth, $lastDayOfMonth);
            
            if ($currentDay < $targetDay || ($currentDay == $targetDay && $currentTime->format('H:i') < $newsletter->getSendTime())) {
                $nextSend = clone $currentTime;
                $nextSend->setDate($currentYear, $currentMonth, $targetDay);
                $nextSend->setTime((int)$hour, (int)$minute, 0);
                return $nextSend;
            }
        }
        
        // Find next scheduled month
        $nextMonth = null;
        foreach ($months as $month) {
            if ($month > $currentMonth) {
                $nextMonth = $month;
                $nextYear = $currentYear;
                break;
            }
        }
        
        // If no month found this year, use first month of next year
        if ($nextMonth === null) {
            $nextMonth = $months[0];
            $nextYear = $currentYear + 1;
        } else {
            $nextYear = $currentYear;
        }
        
        $nextSend = new DateTime();
        $nextSend->setTimezone($currentTime->getTimezone());
        $nextSend->setDate($nextYear, $nextMonth, 1);
        
        $lastDayOfMonth = (int)$nextSend->format('t');
        $targetDay = min($dayOfMonth, $lastDayOfMonth);
        
        $nextSend->setDate($nextYear, $nextMonth, $targetDay);
        $nextSend->setTime((int)$hour, (int)$minute, 0);
        
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
            $lastSentTime = $this->getLastSentTime($newsletter->getId(), $timezone);
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
            $lastSentTime = $this->getLastSentTime($newsletter->getId(), $timezone);
        } else {
            throw new Exception("Invalid object type for getScheduleStatus");
        }
        
        return [
            'next_send' => $nextSend->format('Y-m-d H:i:s T'),
            'next_send_formatted' => $nextSend->format('F j, Y g:i A T'),
            'next_send_object' => $nextSend,
            'sent_today' => $wasEmailSentToday,
            'last_sent' => $lastSentTime,
            'timezone' => $timezone,
            'send_time' => $sendTime
        ];
    }
    
    /**
     * Check if a newsletter should be sent based on its frequency and schedule
     */
    private function shouldSendNewsletter(Newsletter $newsletter, DateTime $currentTime) {
        switch ($newsletter->getFrequency()) {
            case 'daily':
                return $this->shouldSendDaily($newsletter);
            case 'weekly':
                return $this->shouldSendWeekly($newsletter, $currentTime);
            case 'monthly':
                return $this->shouldSendMonthly($newsletter, $currentTime);
            case 'quarterly':
                return $this->shouldSendQuarterly($newsletter, $currentTime);
            default:
                return $this->shouldSendDaily($newsletter); // Default to daily
        }
    }
    
    /**
     * Check if newsletter should be sent at a specific time (for any frequency with multiple times)
     */
    private function shouldSendNewsletterAtTime(Newsletter $newsletter, DateTime $currentTime, $sendTime) {
        // First check if already sent at this time today
        if ($this->wasNewsletterSentAtTimeToday($newsletter->getId(), $sendTime)) {
            return false;
        }
        
        // Then check frequency-specific rules
        switch ($newsletter->getFrequency()) {
            case 'daily':
                return true; // Daily can send every day
                
            case 'weekly':
                return $this->shouldSendWeekly($newsletter, $currentTime);
                
            case 'monthly':
                return $this->shouldSendMonthly($newsletter, $currentTime);
                
            case 'quarterly':
                return $this->shouldSendQuarterly($newsletter, $currentTime);
                
            default:
                return true; // Default to daily behavior
        }
    }
    
    /**
     * Check if daily newsletter should be sent (original logic)
     */
    private function shouldSendDaily(Newsletter $newsletter) {
        return !$this->wasNewsletterSentToday($newsletter->getId());
    }
    
    
    /**
     * Check if two times are within a tolerance window
     */
    private function isTimeNear($time1, $time2, $toleranceMinutes = 15) {
        $timestamp1 = strtotime($time1);
        $timestamp2 = strtotime($time2);
        $diff = abs($timestamp1 - $timestamp2);
        
        return $diff <= ($toleranceMinutes * 60);
    }
    
    /**
     * Check if weekly newsletter should be sent
     */
    private function shouldSendWeekly(Newsletter $newsletter, DateTime $currentTime) {
        $daysOfWeek = $newsletter->getDaysOfWeek();
        
        if (empty($daysOfWeek)) {
            // Default to Monday if no days specified
            $daysOfWeek = [1];
        }
        
        $currentDayOfWeek = (int)$currentTime->format('N'); // 1 = Monday, 7 = Sunday
        
        if (!in_array($currentDayOfWeek, $daysOfWeek)) {
            return false; // Not a scheduled day
        }
        
        // Check if already sent today
        return !$this->wasNewsletterSentToday($newsletter->getId());
    }
    
    /**
     * Check if monthly newsletter should be sent
     */
    private function shouldSendMonthly(Newsletter $newsletter, DateTime $currentTime) {
        $dayOfMonth = $newsletter->getDayOfMonth();
        $currentDay = (int)$currentTime->format('j');
        
        // Handle end-of-month scenarios (e.g., day 31 in February)
        $lastDayOfMonth = (int)$currentTime->format('t');
        $targetDay = min($dayOfMonth, $lastDayOfMonth);
        
        if ($currentDay !== $targetDay) {
            return false; // Not the scheduled day of month
        }
        
        // Check if already sent this month
        return !$this->wasNewsletterSentThisMonth($newsletter->getId());
    }
    
    /**
     * Check if quarterly newsletter should be sent
     */
    private function shouldSendQuarterly(Newsletter $newsletter, DateTime $currentTime) {
        $months = $newsletter->getMonths();
        $dayOfMonth = $newsletter->getDayOfMonth();
        
        if (empty($months)) {
            // Default to first month of each quarter
            $months = [1, 4, 7, 10];
        }
        
        $currentMonth = (int)$currentTime->format('n');
        $currentDay = (int)$currentTime->format('j');
        
        if (!in_array($currentMonth, $months)) {
            return false; // Not a scheduled month
        }
        
        // Handle end-of-month scenarios
        $lastDayOfMonth = (int)$currentTime->format('t');
        $targetDay = min($dayOfMonth, $lastDayOfMonth);
        
        if ($currentDay !== $targetDay) {
            return false; // Not the scheduled day of month
        }
        
        // Check if already sent this quarter
        return !$this->wasNewsletterSentThisQuarter($newsletter->getId());
    }
    
    /**
     * Check if newsletter was sent this month
     */
    private function wasNewsletterSentThisMonth($newsletterId) {
        $firstDayOfMonth = date('Y-m-01');
        $lastDayOfMonth = date('Y-m-t');
        
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM newsletter_history 
            WHERE newsletter_id = ? 
            AND email_sent = 1 
            AND DATE(sent_at) BETWEEN ? AND ?
        ");
        $stmt->execute([$newsletterId, $firstDayOfMonth, $lastDayOfMonth]);
        $result = $stmt->fetch();
        
        return $result['count'] > 0;
    }
    
    /**
     * Check if newsletter was sent this quarter
     */
    private function wasNewsletterSentThisQuarter($newsletterId) {
        $currentMonth = (int)date('n');
        $currentYear = date('Y');
        
        // Determine current quarter
        $quarter = ceil($currentMonth / 3);
        $quarterStartMonth = (($quarter - 1) * 3) + 1;
        $quarterEndMonth = $quarter * 3;
        
        $quarterStart = sprintf('%s-%02d-01', $currentYear, $quarterStartMonth);
        $quarterEnd = date('Y-m-t', mktime(0, 0, 0, $quarterEndMonth, 1, $currentYear));
        
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM newsletter_history 
            WHERE newsletter_id = ? 
            AND email_sent = 1 
            AND DATE(sent_at) BETWEEN ? AND ?
        ");
        $stmt->execute([$newsletterId, $quarterStart, $quarterEnd]);
        $result = $stmt->fetch();
        
        return $result['count'] > 0;
    }
}