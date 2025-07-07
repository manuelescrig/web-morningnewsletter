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
    private $newsletter_title;
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
            $this->newsletter_title = $userData['newsletter_title'] ?? 'Your Morning Brief';
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
            $this->db->beginTransaction();
            
            $stmt->execute([$email, $passwordHash, $timezone, $verificationToken]);
            $this->id = $this->db->lastInsertId();
            $this->email = $email;
            $this->plan = 'free';
            $this->timezone = $timezone;
            $this->send_time = '06:00';
            $this->newsletter_title = 'Your Morning Brief';
            $this->email_verified = 0;
            $this->is_admin = 0;
            
            // Create default newsletter for the user
            require_once __DIR__ . '/Newsletter.php';
            require_once __DIR__ . '/NewsletterRecipient.php';
            
            $newsletter = new Newsletter();
            $newsletterId = $newsletter->create($this->id, 'Your Morning Brief', '06:00', $timezone);
            
            // Subscribe the user to their own newsletter
            $recipient = new NewsletterRecipient();
            $recipient->create($newsletterId, $email);
            
            $this->db->commit();
            return $verificationToken;
        } catch (PDOException $e) {
            $this->db->rollBack();
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
        try {
            error_log("User::updateProfile called with data: " . json_encode($data));
            
            $allowedFields = ['timezone', 'send_time', 'plan', 'name', 'email', 'newsletter_title'];
            $updates = [];
            $values = [];
            
            foreach ($data as $field => $value) {
                if (in_array($field, $allowedFields)) {
                    $updates[] = "$field = ?";
                    $values[] = $value;
                }
            }
            
            if (empty($updates)) {
                error_log("User::updateProfile - No valid fields to update");
                return false;
            }
            
            $values[] = $this->id;
            $sql = "UPDATE users SET " . implode(', ', $updates) . ", updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            error_log("User::updateProfile - SQL: " . $sql);
            error_log("User::updateProfile - Values: " . json_encode($values));
            
            $stmt = $this->db->prepare($sql);
            $success = $stmt->execute($values);
            
            error_log("User::updateProfile - Execute result: " . ($success ? 'true' : 'false'));
            
            if (!$success) {
                $errorInfo = $stmt->errorInfo();
                error_log("User::updateProfile - Database error: " . json_encode($errorInfo));
            }
            
            // Update local properties if successful
            if ($success) {
                foreach ($data as $field => $value) {
                    if (in_array($field, $allowedFields)) {
                        $this->$field = $value;
                        error_log("User::updateProfile - Updated local property $field to: " . $value);
                    }
                }
            }
            
            return $success;
            
        } catch (Exception $e) {
            error_log("User::updateProfile - Exception: " . $e->getMessage());
            error_log("User::updateProfile - Stack trace: " . $e->getTraceAsString());
            return false;
        }
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
        // Check if newsletter_id column exists in sources table
        try {
            $stmt = $this->db->query("PRAGMA table_info(sources)");
            $columns = $stmt->fetchAll();
            $hasNewsletterIdColumn = false;
            $hasUserIdColumn = false;
            
            foreach ($columns as $column) {
                if ($column['name'] === 'newsletter_id') {
                    $hasNewsletterIdColumn = true;
                }
                if ($column['name'] === 'user_id') {
                    $hasUserIdColumn = true;
                }
            }
            
            // If we have newsletter structure, use it
            if ($hasNewsletterIdColumn) {
                $newsletter = $this->getPrimaryNewsletter();
                if ($newsletter) {
                    return $newsletter->getSources();
                }
            }
            
            // Fallback to legacy direct sources (for backward compatibility)
            if ($hasUserIdColumn) {
                $stmt = $this->db->prepare("
                    SELECT * FROM sources 
                    WHERE user_id = ? AND is_active = 1 
                    ORDER BY sort_order, created_at
                ");
                $stmt->execute([$this->id]);
                return $stmt->fetchAll();
            }
            
        } catch (Exception $e) {
            error_log("Error in getSources: " . $e->getMessage());
        }
        
        return [];
    }
    
    public function getNewsletters() {
        require_once __DIR__ . '/Newsletter.php';
        return Newsletter::findByUserId($this->id);
    }
    
    public function getPrimaryNewsletter() {
        $newsletters = $this->getNewsletters();
        return !empty($newsletters) ? $newsletters[0] : null;
    }
    
    public function createNewsletter($title = null, $sendTime = null, $timezone = null) {
        require_once __DIR__ . '/Newsletter.php';
        require_once __DIR__ . '/NewsletterRecipient.php';
        
        $newsletter = new Newsletter();
        $newsletterId = $newsletter->create(
            $this->id,
            $title ?: 'Your Morning Brief',
            $sendTime ?: $this->send_time ?: '06:00',
            $timezone ?: $this->timezone ?: 'UTC'
        );
        
        // Subscribe the user to their new newsletter
        $recipient = new NewsletterRecipient();
        $recipient->create($newsletterId, $this->email);
        
        return Newsletter::findById($newsletterId);
    }
    
    public function getSourceCount() {
        try {
            // Check if newsletter_id column exists in sources table
            $stmt = $this->db->query("PRAGMA table_info(sources)");
            $columns = $stmt->fetchAll();
            $hasNewsletterIdColumn = false;
            $hasUserIdColumn = false;
            
            foreach ($columns as $column) {
                if ($column['name'] === 'newsletter_id') {
                    $hasNewsletterIdColumn = true;
                }
                if ($column['name'] === 'user_id') {
                    $hasUserIdColumn = true;
                }
            }
            
            // If we have newsletter structure, use it
            if ($hasNewsletterIdColumn) {
                $newsletter = $this->getPrimaryNewsletter();
                if ($newsletter) {
                    $stmt = $this->db->prepare("
                        SELECT COUNT(*) as count 
                        FROM sources 
                        WHERE newsletter_id = ? AND is_active = 1
                    ");
                    $stmt->execute([$newsletter->getId()]);
                    return $stmt->fetch()['count'];
                }
            }
            
            // Fallback to legacy count
            if ($hasUserIdColumn) {
                $stmt = $this->db->prepare("
                    SELECT COUNT(*) as count 
                    FROM sources 
                    WHERE user_id = ? AND is_active = 1
                ");
                $stmt->execute([$this->id]);
                return $stmt->fetch()['count'];
            }
            
        } catch (Exception $e) {
            error_log("Error in getSourceCount: " . $e->getMessage());
        }
        
        return 0;
    }
    
    public function getSourceLimit() {
        switch ($this->plan) {
            case 'free':
                return 1;
            case 'starter':
                return 5;
            case 'pro':
                return 15;
            case 'unlimited':
                return PHP_INT_MAX;
            default:
                return 1;
        }
    }
    
    public function canAddSource() {
        return $this->getSourceCount() < $this->getSourceLimit();
    }
    
    public function addSource($type, $config = [], $name = null) {
        if (!$this->canAddSource()) {
            throw new Exception("Source limit reached for current plan");
        }
        
        try {
            // Check if newsletter_id column exists in sources table
            $stmt = $this->db->query("PRAGMA table_info(sources)");
            $columns = $stmt->fetchAll();
            $hasNewsletterIdColumn = false;
            $hasUserIdColumn = false;
            
            foreach ($columns as $column) {
                if ($column['name'] === 'newsletter_id') {
                    $hasNewsletterIdColumn = true;
                }
                if ($column['name'] === 'user_id') {
                    $hasUserIdColumn = true;
                }
            }
            
            // If we have newsletter structure, use it
            if ($hasNewsletterIdColumn) {
                $newsletter = $this->getPrimaryNewsletter();
                if (!$newsletter) {
                    throw new Exception("No newsletter found for user");
                }
                
                // Get the next sort order
                $stmt = $this->db->prepare("
                    SELECT COALESCE(MAX(sort_order), 0) + 1 as next_order 
                    FROM sources 
                    WHERE newsletter_id = ? AND is_active = 1
                ");
                $stmt->execute([$newsletter->getId()]);
                $nextOrder = $stmt->fetch()['next_order'];
                
                $stmt = $this->db->prepare("
                    INSERT INTO sources (newsletter_id, type, name, config, sort_order) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                
                return $stmt->execute([
                    $newsletter->getId(), 
                    $type, 
                    $name,
                    json_encode($config),
                    $nextOrder
                ]);
            }
            
            // Fallback to legacy structure
            if ($hasUserIdColumn) {
                // Get the next sort order
                $stmt = $this->db->prepare("
                    SELECT COALESCE(MAX(sort_order), 0) + 1 as next_order 
                    FROM sources 
                    WHERE user_id = ? AND is_active = 1
                ");
                $stmt->execute([$this->id]);
                $nextOrder = $stmt->fetch()['next_order'];
                
                $stmt = $this->db->prepare("
                    INSERT INTO sources (user_id, type, name, config, sort_order) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                
                return $stmt->execute([
                    $this->id, 
                    $type, 
                    $name,
                    json_encode($config),
                    $nextOrder
                ]);
            }
            
        } catch (Exception $e) {
            error_log("Error in addSource: " . $e->getMessage());
            throw $e;
        }
        
        throw new Exception("Unable to add source - database structure issue");
    }
    
    public function removeSource($sourceId) {
        try {
            // Check if newsletter_id column exists in sources table
            $stmt = $this->db->query("PRAGMA table_info(sources)");
            $columns = $stmt->fetchAll();
            $hasNewsletterIdColumn = false;
            $hasUserIdColumn = false;
            
            foreach ($columns as $column) {
                if ($column['name'] === 'newsletter_id') {
                    $hasNewsletterIdColumn = true;
                }
                if ($column['name'] === 'user_id') {
                    $hasUserIdColumn = true;
                }
            }
            
            // If we have newsletter structure, use it
            if ($hasNewsletterIdColumn) {
                $newsletter = $this->getPrimaryNewsletter();
                if (!$newsletter) {
                    return false;
                }
                
                $stmt = $this->db->prepare("
                    UPDATE sources 
                    SET is_active = 0 
                    WHERE id = ? AND newsletter_id = ?
                ");
                
                return $stmt->execute([$sourceId, $newsletter->getId()]);
            }
            
            // Fallback to legacy structure
            if ($hasUserIdColumn) {
                $stmt = $this->db->prepare("
                    UPDATE sources 
                    SET is_active = 0 
                    WHERE id = ? AND user_id = ?
                ");
                
                return $stmt->execute([$sourceId, $this->id]);
            }
            
        } catch (Exception $e) {
            error_log("Error in removeSource: " . $e->getMessage());
        }
        
        return false;
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
    
    public function requestEmailChange($newEmail) {
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
    
    public function promotePlan() {
        $planHierarchy = ['free', 'starter', 'pro', 'unlimited'];
        $currentIndex = array_search($this->plan, $planHierarchy);
        
        if ($currentIndex === false || $currentIndex >= count($planHierarchy) - 1) {
            return false; // Already at highest plan or invalid plan
        }
        
        $newPlan = $planHierarchy[$currentIndex + 1];
        
        $stmt = $this->db->prepare("
            UPDATE users 
            SET plan = ?, updated_at = CURRENT_TIMESTAMP 
            WHERE id = ?
        ");
        
        $success = $stmt->execute([$newPlan, $this->id]);
        if ($success) {
            $this->plan = $newPlan;
        }
        return $success;
    }
    
    public function demotePlan() {
        $planHierarchy = ['free', 'starter', 'pro', 'unlimited'];
        $currentIndex = array_search($this->plan, $planHierarchy);
        
        if ($currentIndex === false || $currentIndex <= 0) {
            return false; // Already at lowest plan or invalid plan
        }
        
        $newPlan = $planHierarchy[$currentIndex - 1];
        
        $stmt = $this->db->prepare("
            UPDATE users 
            SET plan = ?, updated_at = CURRENT_TIMESTAMP 
            WHERE id = ?
        ");
        
        $success = $stmt->execute([$newPlan, $this->id]);
        if ($success) {
            $this->plan = $newPlan;
        }
        return $success;
    }
    
    public function changePlan($newPlan) {
        $validPlans = ['free', 'starter', 'pro', 'unlimited'];
        
        if (!in_array($newPlan, $validPlans)) {
            return false;
        }
        
        $stmt = $this->db->prepare("
            UPDATE users 
            SET plan = ?, updated_at = CURRENT_TIMESTAMP 
            WHERE id = ?
        ");
        
        $success = $stmt->execute([$newPlan, $this->id]);
        if ($success) {
            $this->plan = $newPlan;
        }
        return $success;
    }
    
    public function getNextPlan() {
        $planHierarchy = ['free', 'starter', 'pro', 'unlimited'];
        $currentIndex = array_search($this->plan, $planHierarchy);
        
        if ($currentIndex === false || $currentIndex >= count($planHierarchy) - 1) {
            return null;
        }
        
        return $planHierarchy[$currentIndex + 1];
    }
    
    public function getPreviousPlan() {
        $planHierarchy = ['free', 'starter', 'pro', 'unlimited'];
        $currentIndex = array_search($this->plan, $planHierarchy);
        
        if ($currentIndex === false || $currentIndex <= 0) {
            return null;
        }
        
        return $planHierarchy[$currentIndex - 1];
    }
    
    public function updateSourceOrder($sourceIds) {
        $newsletter = $this->getPrimaryNewsletter();
        if (!$newsletter) {
            return false;
        }
        
        try {
            $this->db->beginTransaction();
            
            $stmt = $this->db->prepare("
                UPDATE sources 
                SET sort_order = ?
                WHERE id = ? AND newsletter_id = ?
            ");
            
            foreach ($sourceIds as $order => $sourceId) {
                $stmt->execute([$order + 1, $sourceId, $newsletter->getId()]);
            }
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error updating source order: " . $e->getMessage());
            return false;
        }
    }
    
    public function updateSource($sourceId, $config = null, $name = null) {
        $newsletter = $this->getPrimaryNewsletter();
        if (!$newsletter) {
            return false;
        }
        
        $updates = [];
        $values = [];
        
        if ($config !== null) {
            $updates[] = "config = ?";
            $values[] = json_encode($config);
        }
        
        if ($name !== null) {
            $updates[] = "name = ?";
            $values[] = $name;
        }
        
        if (empty($updates)) {
            return false;
        }
        
        $values[] = $sourceId;
        $values[] = $newsletter->getId();
        
        $stmt = $this->db->prepare("
            UPDATE sources 
            SET " . implode(', ', $updates) . ", last_updated = CURRENT_TIMESTAMP 
            WHERE id = ? AND newsletter_id = ?
        ");
        
        return $stmt->execute($values);
    }
    
    // Getters
    public function getId() { return $this->id; }
    public function getEmail() { return $this->email; }
    public function getName() { return $this->name; }
    public function getPlan() { return $this->plan; }
    public function getTimezone() { return $this->timezone; }
    public function getSendTime() { return $this->send_time; }
    public function getNewsletterTitle() { return $this->newsletter_title ?? 'Your Morning Brief'; }
    public function isEmailVerified() { return (bool)$this->email_verified; }
    public function isAdmin() { return (bool)$this->is_admin; }
}