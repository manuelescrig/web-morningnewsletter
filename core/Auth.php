<?php
require_once __DIR__ . '/User.php';

class Auth {
    private static $instance = null;
    
    private function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function login($email, $password) {
        $user = User::findByEmail($email);
        
        if (!$user) {
            return ['success' => false, 'message' => 'Invalid email or password'];
        }
        
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT password_hash FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $userData = $stmt->fetch();
        
        if (!password_verify($password, $userData['password_hash'])) {
            return ['success' => false, 'message' => 'Invalid email or password'];
        }
        
        if (!$user->isEmailVerified()) {
            return ['success' => false, 'message' => 'Please verify your email before logging in'];
        }
        
        $_SESSION['user_id'] = $user->getId();
        $_SESSION['user_email'] = $user->getEmail();
        
        return ['success' => true, 'user' => $user];
    }
    
    public function register($email, $password, $confirmPassword, $timezone = 'UTC') {
        // Validation
        if (empty($email) || empty($password)) {
            return ['success' => false, 'message' => 'Email and password are required'];
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Please enter a valid email address'];
        }
        
        if (strlen($password) < 8) {
            return ['success' => false, 'message' => 'Password must be at least 8 characters long'];
        }
        
        if ($password !== $confirmPassword) {
            return ['success' => false, 'message' => 'Passwords do not match'];
        }
        
        // Check if user already exists
        if (User::findByEmail($email)) {
            return ['success' => false, 'message' => 'An account with this email already exists'];
        }
        
        try {
            $user = new User();
            $verificationToken = $user->create($email, $password, $timezone);
            
            // Send verification email
            $emailSent = $this->sendVerificationEmail($email, $verificationToken);
            
            $message = 'Account created successfully. ';
            if ($emailSent) {
                $message .= 'Please check your email to verify your account.';
            } else {
                $message .= 'However, we couldn\'t send the verification email. Please contact support or try the manual verification link.';
            }
            
            return [
                'success' => true, 
                'message' => $message,
                'user_id' => $user->getId(),
                'email_sent' => $emailSent,
                'verification_token' => $verificationToken // For manual verification if needed
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function logout() {
        $_SESSION = [];
        session_destroy();
        return true;
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        return User::findById($_SESSION['user_id']);
    }
    
    public function requireAuth() {
        if (!$this->isLoggedIn()) {
            header('Location: /auth/login.php');
            exit;
        }
    }
    
    public function verifyEmail($token) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT * FROM users 
            WHERE verification_token = ? AND email_verified = 0
        ");
        $stmt->execute([$token]);
        $userData = $stmt->fetch();
        
        if (!$userData) {
            return ['success' => false, 'message' => 'Invalid or expired verification token'];
        }
        
        $user = new User($userData);
        if ($user->verifyEmail($token)) {
            return ['success' => true, 'message' => 'Email verified successfully. You can now log in.'];
        }
        
        return ['success' => false, 'message' => 'Failed to verify email'];
    }
    
    private function sendVerificationEmail($email, $token) {
        require_once __DIR__ . '/EmailSender.php';
        
        $emailSender = new EmailSender();
        
        try {
            return $emailSender->sendVerificationEmail($email, $token);
        } catch (Exception $e) {
            error_log("Failed to send verification email to $email: " . $e->getMessage());
            return false;
        }
    }
    
    public function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    public function validateCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}