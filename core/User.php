<?php
require_once __DIR__ . '/../config/database.php';

class User {
    private $db;
    private $id;
    private $email;
    private $plan;
    private $timezone;
    private $send_time;
    private $email_verified;
    private $is_admin;
    
    public function __construct($userData = null) {
        $this->db = Database::getInstance()->getConnection();
        
        if ($userData) {
            $this->id = $userData['id'];
            $this->email = $userData['email'];
            $this->plan = $userData['plan'];
            $this->timezone = $userData['timezone'];
            $this->send_time = $userData['send_time'];
            $this->email_verified = $userData['email_verified'];
            $this->is_admin = $userData['is_admin'] ?? 0;
        }
    }
    
    public function create($email, $password, $timezone = 'UTC') {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $verificationToken = bin2hex(random_bytes(32));
        
        $stmt = $this->db->prepare("
            INSERT INTO users (email, password_hash, timezone, verification_token) 
            VALUES (?, ?, ?, ?)
        ");
        
        try {
            $stmt->execute([$email, $passwordHash, $timezone, $verificationToken]);
            $this->id = $this->db->lastInsertId();
            $this->email = $email;
            $this->plan = 'free';
            $this->timezone = $timezone;
            $this->send_time = '06:00';
            $this->email_verified = 0;
            $this->is_admin = 0;
            
            return $verificationToken;
        } catch (PDOException $e) {
            throw new Exception("Failed to create user: " . $e->getMessage());
        }
    }
    
    public static function findByEmail($email) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $userData = $stmt->fetch();
        
        if ($userData) {
            return new self($userData);
        }
        return null;
    }
    
    public static function findById($id) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $userData = $stmt->fetch();
        
        if ($userData) {
            return new self($userData);
        }
        return null;
    }
    
    public function verifyEmail($token) {
        $stmt = $this->db->prepare("
            UPDATE users 
            SET email_verified = 1, verification_token = NULL 
            WHERE id = ? AND verification_token = ?
        ");
        
        $success = $stmt->execute([$this->id, $token]);
        if ($success && $stmt->rowCount() > 0) {
            $this->email_verified = 1;
            return true;
        }
        return false;
    }
    
    public function updateProfile($data) {
        $allowedFields = ['timezone', 'send_time', 'plan'];
        $updates = [];
        $values = [];
        
        foreach ($data as $field => $value) {
            if (in_array($field, $allowedFields)) {
                $updates[] = "$field = ?";
                $values[] = $value;
            }
        }
        
        if (empty($updates)) {
            return false;
        }
        
        $values[] = $this->id;
        $stmt = $this->db->prepare("
            UPDATE users 
            SET " . implode(', ', $updates) . ", updated_at = CURRENT_TIMESTAMP 
            WHERE id = ?
        ");
        
        return $stmt->execute($values);
    }
    
    public function getSources() {
        $stmt = $this->db->prepare("
            SELECT * FROM sources 
            WHERE user_id = ? AND is_active = 1 
            ORDER BY created_at
        ");
        $stmt->execute([$this->id]);
        return $stmt->fetchAll();
    }
    
    public function getSourceCount() {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM sources 
            WHERE user_id = ? AND is_active = 1
        ");
        $stmt->execute([$this->id]);
        return $stmt->fetch()['count'];
    }
    
    public function getSourceLimit() {
        switch ($this->plan) {
            case 'free':
                return 1;
            case 'medium':
                return 5;
            case 'premium':
                return PHP_INT_MAX;
            default:
                return 1;
        }
    }
    
    public function canAddSource() {
        return $this->getSourceCount() < $this->getSourceLimit();
    }
    
    public function addSource($type, $config = []) {
        if (!$this->canAddSource()) {
            throw new Exception("Source limit reached for current plan");
        }
        
        $stmt = $this->db->prepare("
            INSERT INTO sources (user_id, type, config) 
            VALUES (?, ?, ?)
        ");
        
        return $stmt->execute([
            $this->id, 
            $type, 
            json_encode($config)
        ]);
    }
    
    public function removeSource($sourceId) {
        $stmt = $this->db->prepare("
            UPDATE sources 
            SET is_active = 0 
            WHERE id = ? AND user_id = ?
        ");
        
        return $stmt->execute([$sourceId, $this->id]);
    }
    
    public function promoteToAdmin() {
        $stmt = $this->db->prepare("
            UPDATE users 
            SET is_admin = 1, updated_at = CURRENT_TIMESTAMP 
            WHERE id = ?
        ");
        
        $success = $stmt->execute([$this->id]);
        if ($success) {
            $this->is_admin = 1;
        }
        return $success;
    }
    
    public function demoteFromAdmin() {
        $stmt = $this->db->prepare("
            UPDATE users 
            SET is_admin = 0, updated_at = CURRENT_TIMESTAMP 
            WHERE id = ?
        ");
        
        $success = $stmt->execute([$this->id]);
        if ($success) {
            $this->is_admin = 0;
        }
        return $success;
    }
    
    // Getters
    public function getId() { return $this->id; }
    public function getEmail() { return $this->email; }
    public function getPlan() { return $this->plan; }
    public function getTimezone() { return $this->timezone; }
    public function getSendTime() { return $this->send_time; }
    public function isEmailVerified() { return (bool)$this->email_verified; }
    public function isAdmin() { return (bool)$this->is_admin; }
}