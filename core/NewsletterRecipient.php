<?php
require_once __DIR__ . '/../config/database.php';

class NewsletterRecipient {
    private $id;
    private $newsletterId;
    private $email;
    private $isActive;
    private $unsubscribeToken;
    private $unsubscribedAt;
    private $createdAt;
    
    public function __construct($data = null) {
        if ($data) {
            $this->id = $data['id'] ?? null;
            $this->newsletterId = $data['newsletter_id'] ?? null;
            $this->email = $data['email'] ?? null;
            $this->isActive = $data['is_active'] ?? 1;
            $this->unsubscribeToken = $data['unsubscribe_token'] ?? null;
            $this->unsubscribedAt = $data['unsubscribed_at'] ?? null;
            $this->createdAt = $data['created_at'] ?? null;
        }
    }
    
    // Getters
    public function getId() { return $this->id; }
    public function getNewsletterId() { return $this->newsletterId; }
    public function getEmail() { return $this->email; }
    public function isActive() { return $this->isActive; }
    public function getUnsubscribeToken() { return $this->unsubscribeToken; }
    public function getUnsubscribedAt() { return $this->unsubscribedAt; }
    public function getCreatedAt() { return $this->createdAt; }
    
    // Setters
    public function setIsActive($isActive) { $this->isActive = $isActive; }
    
    public static function findById($id) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM newsletter_recipients WHERE id = ?");
        $stmt->execute([$id]);
        $data = $stmt->fetch();
        
        return $data ? new self($data) : null;
    }
    
    public static function findByUnsubscribeToken($token) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM newsletter_recipients WHERE unsubscribe_token = ?");
        $stmt->execute([$token]);
        $data = $stmt->fetch();
        
        return $data ? new self($data) : null;
    }
    
    public static function findByNewsletterIdAndEmail($newsletterId, $email) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT * FROM newsletter_recipients 
            WHERE newsletter_id = ? AND email = ?
        ");
        $stmt->execute([$newsletterId, $email]);
        $data = $stmt->fetch();
        
        return $data ? new self($data) : null;
    }
    
    public static function findByNewsletterIdActive($newsletterId) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT * FROM newsletter_recipients 
            WHERE newsletter_id = ? AND is_active = 1 
            ORDER BY created_at ASC
        ");
        $stmt->execute([$newsletterId]);
        $recipients = [];
        
        while ($data = $stmt->fetch()) {
            $recipients[] = new self($data);
        }
        
        return $recipients;
    }
    
    public static function findByEmail($email) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT nr.*, n.title as newsletter_title 
            FROM newsletter_recipients nr
            JOIN newsletters n ON nr.newsletter_id = n.id
            WHERE nr.email = ? 
            ORDER BY nr.created_at ASC
        ");
        $stmt->execute([$email]);
        $recipients = [];
        
        while ($data = $stmt->fetch()) {
            $recipients[] = new self($data);
        }
        
        return $recipients;
    }
    
    public function create($newsletterId, $email) {
        // Check if recipient already exists
        $existing = self::findByNewsletterIdAndEmail($newsletterId, $email);
        if ($existing) {
            if ($existing->isActive()) {
                throw new Exception("Email is already subscribed to this newsletter");
            } else {
                // Reactivate existing subscription
                return $existing->reactivate();
            }
        }
        
        $db = Database::getInstance()->getConnection();
        
        // Generate unique unsubscribe token
        $this->unsubscribeToken = bin2hex(random_bytes(32));
        
        $stmt = $db->prepare("
            INSERT INTO newsletter_recipients (newsletter_id, email, unsubscribe_token) 
            VALUES (?, ?, ?)
        ");
        
        $stmt->execute([$newsletterId, $email, $this->unsubscribeToken]);
        
        $this->id = $db->lastInsertId();
        $this->newsletterId = $newsletterId;
        $this->email = $email;
        $this->isActive = 1;
        
        return $this->id;
    }
    
    public function unsubscribe() {
        if (!$this->id) {
            throw new Exception("Cannot unsubscribe recipient without ID");
        }
        
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            UPDATE newsletter_recipients 
            SET is_active = 0, unsubscribed_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        
        $success = $stmt->execute([$this->id]);
        
        if ($success) {
            $this->isActive = 0;
            $this->unsubscribedAt = date('Y-m-d H:i:s');
        }
        
        return $success;
    }
    
    public function reactivate() {
        if (!$this->id) {
            throw new Exception("Cannot reactivate recipient without ID");
        }
        
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            UPDATE newsletter_recipients 
            SET is_active = 1, unsubscribed_at = NULL
            WHERE id = ?
        ");
        
        $success = $stmt->execute([$this->id]);
        
        if ($success) {
            $this->isActive = 1;
            $this->unsubscribedAt = null;
        }
        
        return $success;
    }
    
    public function delete() {
        if (!$this->id) {
            return false;
        }
        
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("DELETE FROM newsletter_recipients WHERE id = ?");
        return $stmt->execute([$this->id]);
    }
    
    public function regenerateUnsubscribeToken() {
        $this->unsubscribeToken = bin2hex(random_bytes(32));
        
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            UPDATE newsletter_recipients 
            SET unsubscribe_token = ?
            WHERE id = ?
        ");
        
        return $stmt->execute([$this->unsubscribeToken, $this->id]);
    }
    
    public function getNewsletter() {
        require_once __DIR__ . '/Newsletter.php';
        return Newsletter::findById($this->newsletterId);
    }
    
    public static function getActiveRecipientsForNewsletter($newsletterId) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT email, unsubscribe_token 
            FROM newsletter_recipients 
            WHERE newsletter_id = ? AND is_active = 1
        ");
        $stmt->execute([$newsletterId]);
        
        return $stmt->fetchAll();
    }
    
    public static function bulkUnsubscribeByEmail($email) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            UPDATE newsletter_recipients 
            SET is_active = 0, unsubscribed_at = CURRENT_TIMESTAMP
            WHERE email = ? AND is_active = 1
        ");
        
        return $stmt->execute([$email]);
    }
    
    public static function getRecipientStats($newsletterId) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT 
                COUNT(*) as total_recipients,
                SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_recipients,
                SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as unsubscribed_recipients
            FROM newsletter_recipients 
            WHERE newsletter_id = ?
        ");
        $stmt->execute([$newsletterId]);
        
        return $stmt->fetch();
    }
}