<?php
require_once __DIR__ . '/../config/database.php';

class User {
    private $db;
    private $id;
    private $email;
    private $name;
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
            $this->name = $userData['name'];
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
        $allowedFields = ['timezone', 'send_time', 'plan', 'name', 'email'];
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
        
        $success = $stmt->execute($values);
        
        // Update local properties if successful
        if ($success) {
            foreach ($data as $field => $value) {
                if (in_array($field, $allowedFields)) {
                    $this->$field = $value;
                }
            }
        }
        
        return $success;
    }
    
    public function changePassword($currentPassword, $newPassword) {
        // First verify current password
        $stmt = $this->db->prepare("SELECT password_hash FROM users WHERE id = ?");
        $stmt->execute([$this->id]);
        $user = $stmt->fetch();
        
        if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
            return false;
        }
        
        // Update to new password
        $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("
            UPDATE users 
            SET password_hash = ?, updated_at = CURRENT_TIMESTAMP 
            WHERE id = ?
        ");
        
        return $stmt->execute([$newPasswordHash, $this->id]);
    }
    
    public function deleteAccount($password) {
        // First verify password
        $stmt = $this->db->prepare("SELECT password_hash FROM users WHERE id = ?");
        $stmt->execute([$this->id]);
        $user = $stmt->fetch();
        
        if (!$user || !password_verify($password, $user['password_hash'])) {
            return false;
        }
        
        try {
            $this->db->beginTransaction();
            
            // Delete user's sources
            $stmt = $this->db->prepare("DELETE FROM sources WHERE user_id = ?");
            $stmt->execute([$this->id]);
            
            // Delete user's subscriptions  
            $stmt = $this->db->prepare("DELETE FROM subscriptions WHERE user_id = ?");
            $stmt->execute([$this->id]);
            
            // Delete user's payments
            $stmt = $this->db->prepare("DELETE FROM payments WHERE user_id = ?");
            $stmt->execute([$this->id]);
            
            // Delete the user
            $stmt = $this->db->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$this->id]);
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
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
    
    public function delete() {
        try {
            // Start transaction for data consistency
            $this->db->beginTransaction();
            
            // Delete related data first (sources and email_logs have CASCADE DELETE in schema)
            // But we'll explicitly delete them to be safe
            $stmt = $this->db->prepare("DELETE FROM sources WHERE user_id = ?");
            $stmt->execute([$this->id]);
            
            $stmt = $this->db->prepare("DELETE FROM email_logs WHERE user_id = ?");
            $stmt->execute([$this->id]);
            
            // Delete the user
            $stmt = $this->db->prepare("DELETE FROM users WHERE id = ?");
            $success = $stmt->execute([$this->id]);
            
            if ($success) {
                $this->db->commit();
                return true;
            } else {
                $this->db->rollback();
                return false;
            }
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw new Exception("Failed to delete user: " . $e->getMessage());
        }
    }
    
    public function resendVerificationEmail() {
        // Only resend if user is not already verified
        if ($this->email_verified) {
            return ['success' => false, 'message' => 'User is already verified'];
        }
        
        try {
            // Generate new verification token
            $verificationToken = bin2hex(random_bytes(32));
            
            // Update the verification token in database
            $stmt = $this->db->prepare("
                UPDATE users 
                SET verification_token = ?, updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?
            ");
            
            $success = $stmt->execute([$verificationToken, $this->id]);
            
            if (!$success) {
                return ['success' => false, 'message' => 'Failed to update verification token'];
            }
            
            // Send verification email using the public method
            require_once __DIR__ . '/EmailSender.php';
            
            $emailSender = new EmailSender();
            $emailResult = $emailSender->sendVerificationEmail($this->email, $verificationToken);
            
            if ($emailResult) {
                return ['success' => true, 'message' => 'Verification email sent successfully'];
            } else {
                // Email failed but token was updated - log for manual verification if needed
                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'] ?? 'morningnewsletter.com';
                $verificationUrl = $protocol . "://" . $host . "/auth/verify_email.php?token=" . $verificationToken;
                error_log("Email sending failed for user {$this->id} but token updated. Manual verification URL: {$verificationUrl}");
                return ['success' => false, 'message' => 'Failed to send email, but verification token was updated'];
            }
            
        } catch (Exception $e) {
            error_log("Resend verification error for user {$this->id}: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    public function sendPasswordResetEmail() {
        try {
            // Generate password reset token
            $resetToken = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour')); // Token expires in 1 hour
            
            // Update the reset token in database
            $stmt = $this->db->prepare("
                UPDATE users 
                SET verification_token = ?, updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?
            ");
            
            $success = $stmt->execute([$resetToken, $this->id]);
            
            if (!$success) {
                return ['success' => false, 'message' => 'Failed to update reset token'];
            }
            
            // Send password reset email
            require_once __DIR__ . '/EmailSender.php';
            
            $emailSender = new EmailSender();
            $emailResult = $emailSender->sendPasswordResetEmail($this->email, $resetToken);
            
            if ($emailResult) {
                return ['success' => true, 'message' => 'Password reset email sent successfully'];
            } else {
                // Email failed but token was updated - log for manual reset if needed
                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'] ?? 'morningnewsletter.com';
                $resetUrl = $protocol . "://" . $host . "/auth/reset_password.php?token=" . $resetToken;
                error_log("Password reset email failed for user {$this->id} but token updated. Manual reset URL: {$resetUrl}");
                return ['success' => false, 'message' => 'Failed to send email, but reset token was updated'];
            }
            
        } catch (Exception $e) {
            error_log("Password reset error for user {$this->id}: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    public function resetPassword($token, $newPassword) {
        try {
            // Verify token and get user
            $stmt = $this->db->prepare("
                SELECT id FROM users 
                WHERE verification_token = ? AND id = ?
            ");
            $stmt->execute([$token, $this->id]);
            
            if (!$stmt->fetch()) {
                return ['success' => false, 'message' => 'Invalid or expired reset token'];
            }
            
            // Hash new password
            $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            
            // Update password, clear reset token, and mark as verified
            $stmt = $this->db->prepare("
                UPDATE users 
                SET password_hash = ?, verification_token = NULL, email_verified = 1, updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?
            ");
            
            $success = $stmt->execute([$passwordHash, $this->id]);
            
            if ($success) {
                return ['success' => true, 'message' => 'Password reset successfully'];
            } else {
                return ['success' => false, 'message' => 'Failed to update password'];
            }
            
        } catch (Exception $e) {
            error_log("Password reset error for user {$this->id}: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    public static function findByResetToken($token) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE verification_token = ?");
        $stmt->execute([$token]);
        $userData = $stmt->fetch();
        
        if ($userData) {
            return new self($userData);
        }
        return null;
    }
    
    public function requestEmailChange($newEmail, $password) {
        // Verify password first
        $stmt = $this->db->prepare("SELECT password_hash FROM users WHERE id = ?");
        $stmt->execute([$this->id]);
        $userData = $stmt->fetch();
        
        if (!$userData || !password_verify($password, $userData['password_hash'])) {
            return ['success' => false, 'message' => 'Incorrect password'];
        }
        
        // Check if new email is already in use
        if (User::findByEmail($newEmail)) {
            return ['success' => false, 'message' => 'This email address is already in use'];
        }
        
        try {
            // Generate verification token
            $verificationToken = bin2hex(random_bytes(32));
            
            // Store the new email temporarily in verification_token field as JSON
            $tokenData = json_encode([
                'type' => 'email_change',
                'new_email' => $newEmail,
                'token' => $verificationToken,
                'expires_at' => date('Y-m-d H:i:s', strtotime('+1 hour'))
            ]);
            
            // Update the verification token in database
            $stmt = $this->db->prepare("
                UPDATE users 
                SET verification_token = ?, updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?
            ");
            
            $success = $stmt->execute([$tokenData, $this->id]);
            
            if (!$success) {
                return ['success' => false, 'message' => 'Failed to update verification token'];
            }
            
            // Send verification email to the NEW email address
            require_once __DIR__ . '/EmailSender.php';
            
            $emailSender = new EmailSender();
            $emailResult = $emailSender->sendEmailChangeVerification($newEmail, $verificationToken, $this->email);
            
            if ($emailResult) {
                return ['success' => true, 'message' => 'Verification email sent to your new email address. Please check your inbox to confirm the change.'];
            } else {
                return ['success' => false, 'message' => 'Failed to send verification email. Please try again.'];
            }
            
        } catch (Exception $e) {
            error_log("Email change request error for user {$this->id}: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    public function verifyEmailChange($token) {
        try {
            // Get the current verification token data
            $stmt = $this->db->prepare("SELECT verification_token FROM users WHERE id = ?");
            $stmt->execute([$this->id]);
            $result = $stmt->fetch();
            
            if (!$result || !$result['verification_token']) {
                return ['success' => false, 'message' => 'No pending email change request found'];
            }
            
            $tokenData = json_decode($result['verification_token'], true);
            
            if (!$tokenData || $tokenData['type'] !== 'email_change' || $tokenData['token'] !== $token) {
                return ['success' => false, 'message' => 'Invalid verification token'];
            }
            
            // Check if token has expired
            if (strtotime($tokenData['expires_at']) < time()) {
                return ['success' => false, 'message' => 'Verification token has expired. Please request a new email change.'];
            }
            
            $newEmail = $tokenData['new_email'];
            
            // Double-check that the new email is still available
            if (User::findByEmail($newEmail)) {
                return ['success' => false, 'message' => 'This email address is already in use'];
            }
            
            // Update the email address and clear the verification token
            $stmt = $this->db->prepare("
                UPDATE users 
                SET email = ?, verification_token = NULL, updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?
            ");
            
            $success = $stmt->execute([$newEmail, $this->id]);
            
            if ($success) {
                $this->email = $newEmail;
                return ['success' => true, 'message' => 'Email address updated successfully!'];
            } else {
                return ['success' => false, 'message' => 'Failed to update email address'];
            }
            
        } catch (Exception $e) {
            error_log("Email change verification error for user {$this->id}: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    public static function findByEmailChangeToken($token) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE verification_token IS NOT NULL");
        $stmt->execute();
        $users = $stmt->fetchAll();
        
        foreach ($users as $userData) {
            $tokenData = json_decode($userData['verification_token'], true);
            if ($tokenData && 
                $tokenData['type'] === 'email_change' && 
                $tokenData['token'] === $token) {
                return new self($userData);
            }
        }
        
        return null;
    }
    
    public function getPendingEmailChange() {
        try {
            $stmt = $this->db->prepare("SELECT verification_token FROM users WHERE id = ?");
            $stmt->execute([$this->id]);
            $result = $stmt->fetch();
            
            if (!$result || !$result['verification_token']) {
                return null;
            }
            
            $tokenData = json_decode($result['verification_token'], true);
            
            if (!$tokenData || $tokenData['type'] !== 'email_change') {
                return null;
            }
            
            // Check if token has expired
            if (strtotime($tokenData['expires_at']) < time()) {
                return null;
            }
            
            return [
                'new_email' => $tokenData['new_email'],
                'expires_at' => $tokenData['expires_at']
            ];
            
        } catch (Exception $e) {
            return null;
        }
    }
    
    public function cancelEmailChange() {
        try {
            $stmt = $this->db->prepare("SELECT verification_token FROM users WHERE id = ?");
            $stmt->execute([$this->id]);
            $result = $stmt->fetch();
            
            if (!$result || !$result['verification_token']) {
                return false;
            }
            
            $tokenData = json_decode($result['verification_token'], true);
            
            if (!$tokenData || $tokenData['type'] !== 'email_change') {
                return false;
            }
            
            // Clear the verification token
            $stmt = $this->db->prepare("
                UPDATE users 
                SET verification_token = NULL, updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?
            ");
            
            return $stmt->execute([$this->id]);
            
        } catch (Exception $e) {
            error_log("Cancel email change error for user {$this->id}: " . $e->getMessage());
            return false;
        }
    }
    
    // Getters
    public function getId() { return $this->id; }
    public function getEmail() { return $this->email; }
    public function getName() { return $this->name; }
    public function getPlan() { return $this->plan; }
    public function getTimezone() { return $this->timezone; }
    public function getSendTime() { return $this->send_time; }
    public function isEmailVerified() { return (bool)$this->email_verified; }
    public function isAdmin() { return (bool)$this->is_admin; }
}