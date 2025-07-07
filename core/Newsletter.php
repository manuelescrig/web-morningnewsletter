<?php
require_once __DIR__ . '/../config/database.php';

class Newsletter {
    private $id;
    private $userId;
    private $title;
    private $sendTime;
    private $timezone;
    private $isActive;
    private $unsubscribeToken;
    private $createdAt;
    private $updatedAt;
    
    public function __construct($data = null) {
        if ($data) {
            $this->id = $data['id'] ?? null;
            $this->userId = $data['user_id'] ?? null;
            $this->title = $data['title'] ?? 'Your Morning Brief';
            $this->sendTime = $data['send_time'] ?? '06:00';
            $this->timezone = $data['timezone'] ?? 'UTC';
            $this->isActive = $data['is_active'] ?? 1;
            $this->unsubscribeToken = $data['unsubscribe_token'] ?? null;
            $this->createdAt = $data['created_at'] ?? null;
            $this->updatedAt = $data['updated_at'] ?? null;
        }
    }
    
    // Getters
    public function getId() { return $this->id; }
    public function getUserId() { return $this->userId; }
    public function getTitle() { return $this->title; }
    public function getSendTime() { return $this->sendTime; }
    public function getTimezone() { return $this->timezone; }
    public function isActive() { return $this->isActive; }
    public function getUnsubscribeToken() { return $this->unsubscribeToken; }
    public function getCreatedAt() { return $this->createdAt; }
    public function getUpdatedAt() { return $this->updatedAt; }
    
    // Setters
    public function setTitle($title) { $this->title = $title; }
    public function setSendTime($sendTime) { $this->sendTime = $sendTime; }
    public function setTimezone($timezone) { $this->timezone = $timezone; }
    public function setIsActive($isActive) { $this->isActive = $isActive; }
    
    public static function findById($id) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM newsletters WHERE id = ?");
        $stmt->execute([$id]);
        $data = $stmt->fetch();
        
        return $data ? new self($data) : null;
    }
    
    public static function findByUserId($userId) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM newsletters WHERE user_id = ? ORDER BY created_at ASC");
        $stmt->execute([$userId]);
        $newsletters = [];
        
        while ($data = $stmt->fetch()) {
            $newsletters[] = new self($data);
        }
        
        return $newsletters;
    }
    
    public static function findByUnsubscribeToken($token) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM newsletters WHERE unsubscribe_token = ?");
        $stmt->execute([$token]);
        $data = $stmt->fetch();
        
        return $data ? new self($data) : null;
    }
    
    public function create($userId, $title = 'Your Morning Brief', $sendTime = '06:00', $timezone = 'UTC') {
        $db = Database::getInstance()->getConnection();
        
        // Generate unique unsubscribe token
        $this->unsubscribeToken = bin2hex(random_bytes(32));
        
        $stmt = $db->prepare("
            INSERT INTO newsletters (user_id, title, send_time, timezone, unsubscribe_token) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([$userId, $title, $sendTime, $timezone, $this->unsubscribeToken]);
        
        $this->id = $db->lastInsertId();
        $this->userId = $userId;
        $this->title = $title;
        $this->sendTime = $sendTime;
        $this->timezone = $timezone;
        $this->isActive = 1;
        
        return $this->id;
    }
    
    public function save() {
        if (!$this->id) {
            throw new Exception("Cannot save newsletter without ID. Use create() method.");
        }
        
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            UPDATE newsletters 
            SET title = ?, send_time = ?, timezone = ?, is_active = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        
        return $stmt->execute([
            $this->title,
            $this->sendTime,
            $this->timezone,
            $this->isActive,
            $this->id
        ]);
    }
    
    public function delete() {
        if (!$this->id) {
            return false;
        }
        
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("DELETE FROM newsletters WHERE id = ?");
        return $stmt->execute([$this->id]);
    }
    
    public function getSources() {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT * FROM sources 
            WHERE newsletter_id = ? AND is_active = 1 
            ORDER BY sort_order ASC, created_at ASC
        ");
        $stmt->execute([$this->id]);
        
        return $stmt->fetchAll();
    }
    
    public function getRecipients() {
        require_once __DIR__ . '/NewsletterRecipient.php';
        return NewsletterRecipient::findByNewsletterIdActive($this->id);
    }
    
    public function getActiveRecipientCount() {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT COUNT(*) as count 
            FROM newsletter_recipients 
            WHERE newsletter_id = ? AND is_active = 1
        ");
        $stmt->execute([$this->id]);
        $result = $stmt->fetch();
        
        return $result['count'] ?? 0;
    }
    
    public function regenerateUnsubscribeToken() {
        $this->unsubscribeToken = bin2hex(random_bytes(32));
        
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            UPDATE newsletters 
            SET unsubscribe_token = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        
        return $stmt->execute([$this->unsubscribeToken, $this->id]);
    }
    
    public static function getNewslettersForScheduledSending($currentServerTime, $windowMinutes = 15) {
        $db = Database::getInstance()->getConnection();
        
        // Find newsletters that should be sent within the time window
        $stmt = $db->prepare("
            SELECT n.*, u.email as owner_email 
            FROM newsletters n
            JOIN users u ON n.user_id = u.id
            WHERE n.is_active = 1 
            AND u.email_verified = 1
            AND (
                -- Convert user's send time to server time and check if within window
                datetime('now', '+' || ? || ' minutes') >= 
                datetime(date('now') || ' ' || n.send_time, n.timezone, 'utc')
                AND
                datetime('now', '-' || ? || ' minutes') <= 
                datetime(date('now') || ' ' || n.send_time, n.timezone, 'utc')
            )
        ");
        
        $stmt->execute([$windowMinutes, $windowMinutes]);
        $newsletters = [];
        
        while ($data = $stmt->fetch()) {
            $newsletters[] = new self($data);
        }
        
        return $newsletters;
    }
    
    public function shouldSendToday() {
        $db = Database::getInstance()->getConnection();
        
        // Check if we already sent a newsletter today
        $stmt = $db->prepare("
            SELECT COUNT(*) as count 
            FROM email_logs 
            WHERE newsletter_id = ? 
            AND DATE(sent_at) = DATE('now')
            AND status = 'sent'
        ");
        $stmt->execute([$this->id]);
        $result = $stmt->fetch();
        
        return ($result['count'] ?? 0) == 0;
    }
}