<?php
require_once __DIR__ . '/User.php';

class EmailSender {
    private $smtpHost;
    private $smtpPort;
    private $smtpUsername;
    private $smtpPassword;
    private $fromEmail;
    private $fromName;
    
    public function __construct() {
        // TODO: Move to config file
        $this->smtpHost = $_ENV['SMTP_HOST'] ?? 'localhost';
        $this->smtpPort = $_ENV['SMTP_PORT'] ?? 587;
        $this->smtpUsername = $_ENV['SMTP_USERNAME'] ?? '';
        $this->smtpPassword = $_ENV['SMTP_PASSWORD'] ?? '';
        $this->fromEmail = $_ENV['FROM_EMAIL'] ?? 'noreply@morningnewsletter.com';
        $this->fromName = $_ENV['FROM_NAME'] ?? 'MorningNewsletter';
    }
    
    public function sendNewsletter(User $user, $htmlContent, $subject = null) {
        if (!$subject) {
            $subject = "Your Morning Brief - " . date('F j, Y');
        }
        
        try {
            $success = $this->sendEmail(
                $user->getEmail(),
                $subject,
                $htmlContent
            );
            
            $this->logEmail($user->getId(), $success ? 'sent' : 'failed');
            return $success;
            
        } catch (Exception $e) {
            $this->logEmail($user->getId(), 'failed', $e->getMessage());
            throw $e;
        }
    }
    
    private function sendEmail($to, $subject, $htmlBody) {
        // For MVP, use basic PHP mail() function
        // TODO: Implement PHPMailer for production
        
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: ' . $this->fromName . ' <' . $this->fromEmail . '>',
            'Reply-To: ' . $this->fromEmail,
            'X-Mailer: PHP/' . phpversion()
        ];
        
        $success = mail($to, $subject, $htmlBody, implode("\r\n", $headers));
        
        if (!$success) {
            error_log("Failed to send email to: $to");
        }
        
        return $success;
    }
    
    private function logEmail($userId, $status, $errorMessage = null) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            INSERT INTO email_logs (user_id, status, error_message) 
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$userId, $status, $errorMessage]);
    }
    
    public function sendVerificationEmail($email, $token) {
        $verificationUrl = "http://" . $_SERVER['HTTP_HOST'] . "/auth/verify_email.php?token=" . $token;
        $subject = "Verify your MorningNewsletter account";
        
        $htmlBody = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Verify Your Account</title>
        </head>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;'>
            <div style='text-align: center; margin-bottom: 30px;'>
                <h1 style='color: #2563eb;'>Welcome to MorningNewsletter!</h1>
            </div>
            
            <div style='background-color: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;'>
                <p>Thank you for signing up! Please verify your email address to complete your registration.</p>
                
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='$verificationUrl' 
                       style='background-color: #2563eb; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block;'>
                        Verify Email Address
                    </a>
                </div>
                
                <p style='font-size: 14px; color: #666;'>
                    If the button doesn't work, copy and paste this link into your browser:<br>
                    <a href='$verificationUrl'>$verificationUrl</a>
                </p>
            </div>
            
            <div style='font-size: 12px; color: #666; text-align: center;'>
                <p>If you didn't create an account with us, please ignore this email.</p>
            </div>
        </body>
        </html>";
        
        return $this->sendEmail($email, $subject, $htmlBody);
    }
    
    public function sendPasswordResetEmail($email, $token) {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'morningnewsletter.com';
        $resetUrl = $protocol . "://" . $host . "/auth/reset_password.php?token=" . $token;
        $subject = "Reset your MorningNewsletter password";
        
        $htmlBody = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Reset Your Password</title>
        </head>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;'>
            <div style='text-align: center; margin-bottom: 30px;'>
                <h1 style='color: #2563eb;'>Password Reset Request</h1>
            </div>
            
            <div style='background-color: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;'>
                <p>You have requested to reset your password for your MorningNewsletter account.</p>
                
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='$resetUrl' 
                       style='background-color: #dc2626; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block;'>
                        Reset Password
                    </a>
                </div>
                
                <p style='font-size: 14px; color: #666;'>
                    If the button doesn't work, copy and paste this link into your browser:<br>
                    <a href='$resetUrl'>$resetUrl</a>
                </p>
                
                <p style='font-size: 14px; color: #666; margin-top: 20px;'>
                    <strong>This link will expire in 1 hour.</strong>
                </p>
            </div>
            
            <div style='font-size: 12px; color: #666; text-align: center;'>
                <p>If you didn't request a password reset, please ignore this email. Your password will remain unchanged.</p>
            </div>
        </body>
        </html>
        ";
        
        return $this->sendEmail($email, $subject, $htmlBody);
    }
    
    public function getEmailStats($userId, $days = 30) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT 
                status,
                COUNT(*) as count
            FROM email_logs 
            WHERE user_id = ? 
            AND sent_at >= datetime('now', '-$days days')
            GROUP BY status
        ");
        $stmt->execute([$userId]);
        
        $stats = ['sent' => 0, 'failed' => 0];
        while ($row = $stmt->fetch()) {
            $stats[$row['status']] = $row['count'];
        }
        
        return $stats;
    }
}