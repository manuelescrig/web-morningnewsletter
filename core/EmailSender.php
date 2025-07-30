<?php
require_once __DIR__ . '/User.php';
require_once __DIR__ . '/NewsletterHistory.php';
require_once __DIR__ . '/../config/database.php';

class EmailSender {
    private $provider;
    private $apiKey;
    private $fromEmail;
    private $fromName;
    
    public function __construct() {
        // Load email configuration
        $config = require __DIR__ . '/../config/email.php';
        
        $this->provider = $_ENV['EMAIL_PROVIDER'] ?? $config['provider'];
        
        // Get provider-specific configuration
        $providerConfig = $config[$this->provider] ?? $config['plunk'];
        
        $this->apiKey = $_ENV['EMAIL_API_KEY'] ?? $providerConfig['api_key'];
        $this->fromEmail = $_ENV['FROM_EMAIL'] ?? $providerConfig['from_email'];
        $this->fromName = $_ENV['FROM_NAME'] ?? $providerConfig['from_name'];
    }
    
    public function sendNewsletter(User $user, $htmlContent, $subject = null, $newsletterId = null, $sourcesData = null) {
        if (!$subject) {
            $subject = "Your Morning Brief - " . date('F j, Y');
        }
        
        $historyId = null;
        
        try {
            // Save newsletter to history before sending to get the history ID
            if ($newsletterId) {
                $newsletterHistory = new NewsletterHistory();
                $historyId = $newsletterHistory->saveToHistory(
                    $newsletterId,
                    $user->getId(),
                    $subject,
                    $htmlContent, // We'll update this with the final content later
                    $sourcesData
                );
                
                if (!$historyId) {
                    error_log("Warning: Failed to save newsletter to history before sending");
                }
            }
            
            $success = $this->sendEmail(
                $user->getEmail(),
                $subject,
                $htmlContent
            );
            
            $this->logEmail($user->getId(), $success ? 'sent' : 'failed', null, $newsletterId, $historyId);
            return $success;
            
        } catch (Exception $e) {
            $this->logEmail($user->getId(), 'failed', $e->getMessage(), $newsletterId, $historyId);
            throw $e;
        }
    }
    
    public function sendNewsletterWithHistory(User $user, Newsletter $newsletter, $scheduledSendTime = null) {
        $subject = $newsletter->getTitle() . " - " . date('F j, Y');
        $historyTitle = date('F j, Y'); // Only date for history
        $historyId = null;
        
        try {
            // First build the newsletter to get the sources data
            require_once __DIR__ . '/NewsletterBuilder.php';
            $builder = new NewsletterBuilder($newsletter, $user);
            $initialResult = $builder->buildWithSourceData();
            
            // Save to history to get the ID (use date-only title for history)
            $newsletterHistory = new NewsletterHistory();
            $historyId = $newsletterHistory->saveToHistory(
                $newsletter->getId(),
                $user->getId(),
                $historyTitle,
                '', // Placeholder content - will be updated below
                $initialResult['sources_data'],
                $scheduledSendTime
            );
            
            if (!$historyId) {
                throw new Exception("Failed to create newsletter history entry");
            }
            
            // Now rebuild the newsletter with the history ID for the "View in Browser" link
            $finalResult = $builder->buildWithSourceDataAndHistoryId($historyId);
            
            // Update the history entry with the final content that includes the view link
            $this->updateHistoryContent($historyId, $finalResult['content']);
            
            $success = $this->sendEmail(
                $user->getEmail(),
                $subject,
                $finalResult['content']
            );
            
            $this->logEmail($user->getId(), $success ? 'sent' : 'failed', null, $newsletter->getId(), $historyId);
            return $success;
            
        } catch (Exception $e) {
            $this->logEmail($user->getId(), 'failed', $e->getMessage(), $newsletter->getId(), $historyId);
            throw $e;
        }
    }
    
    private function updateHistoryContent($historyId, $content) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("UPDATE newsletter_history SET content = ? WHERE id = ?");
        return $stmt->execute([$content, $historyId]);
    }
    
    private function sendEmail($to, $subject, $htmlBody) {
        switch ($this->provider) {
            case 'plunk':
                return $this->sendWithPlunk($to, $subject, $htmlBody);
            case 'resend':
                return $this->sendWithResend($to, $subject, $htmlBody);
            case 'smtp':
                return $this->sendWithSMTP($to, $subject, $htmlBody);
            default:
                error_log("Unknown email provider: {$this->provider}");
                return false;
        }
    }
    
    private function sendWithPlunk($to, $subject, $htmlBody) {
        // Plunk uses JSON format
        $data = [
            'to' => $to,
            'subject' => $subject,
            'body' => $htmlBody,
            'subscribed' => true,
            'from' => $this->fromEmail,
            'name' => $this->fromName
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.useplunk.com/v1/send');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            error_log("Plunk API cURL error: " . $error);
            return false;
        }
        
        // Log the full response for debugging
        error_log("Plunk API response (HTTP $httpCode): " . $response);
        
        if ($httpCode >= 200 && $httpCode < 300) {
            $responseData = json_decode($response, true);
            if ($responseData && isset($responseData['success']) && $responseData['success']) {
                error_log("Email sent successfully via Plunk");
                return true;
            } else {
                error_log("Email sent successfully via Plunk (HTTP 2xx)");
                return true;
            }
        }
        
        error_log("Plunk API error (HTTP $httpCode): " . $response);
        return false;
    }
    
    private function sendWithResend($to, $subject, $htmlBody) {
        $data = [
            'from' => $this->fromEmail,
            'to' => [$to],
            'subject' => $subject,
            'html' => $htmlBody
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.resend.com/emails');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            error_log("Resend API cURL error: " . $error);
            return false;
        }
        
        if ($httpCode >= 200 && $httpCode < 300) {
            $responseData = json_decode($response, true);
            if ($responseData && isset($responseData['id'])) {
                error_log("Email sent successfully via Resend. ID: " . $responseData['id']);
                return true;
            }
        }
        
        error_log("Resend API error (HTTP $httpCode): " . $response);
        return false;
    }
    
    private function sendWithSMTP($to, $subject, $htmlBody) {
        // Fallback to basic PHP mail() function
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
    
    private function logEmail($userId, $status, $errorMessage = null, $newsletterId = null, $historyId = null) {
        $db = Database::getInstance()->getConnection();
        
        // Try to insert with all new columns (newsletter_id and history_id)
        try {
            $stmt = $db->prepare("
                INSERT INTO email_logs (user_id, status, error_message, newsletter_id, history_id) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$userId, $status, $errorMessage, $newsletterId, $historyId]);
            
        } catch (Exception $e) {
            // Fall back to try with just newsletter_id if history_id column doesn't exist yet
            try {
                $stmt = $db->prepare("
                    INSERT INTO email_logs (user_id, status, error_message, newsletter_id) 
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$userId, $status, $errorMessage, $newsletterId]);
                
            } catch (Exception $e2) {
                // Final fallback to old schema if newsletter_id column doesn't exist
                $stmt = $db->prepare("
                    INSERT INTO email_logs (user_id, status, error_message) 
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$userId, $status, $errorMessage]);
            }
        }
    }
    
    public function sendVerificationEmail($email, $token) {
        // Try to use the new transactional email system
        try {
            require_once __DIR__ . '/TransactionalEmailManager.php';
            $transactionalManager = new TransactionalEmailManager();
            
            // Find user by email to get ID
            $user = User::findByEmail($email);
            if ($user) {
                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $verificationUrl = $protocol . "://" . $_SERVER['HTTP_HOST'] . "/auth/verify_email.php?token=" . $token;
                
                return $transactionalManager->sendTransactionalEmail('email_verification', $user->getId(), [
                    'verification_url' => $verificationUrl
                ]);
            }
        } catch (Exception $e) {
            error_log("Failed to use transactional email system, falling back to hardcoded: " . $e->getMessage());
        }
        
        // Fallback to hardcoded email
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $verificationUrl = $protocol . "://" . $_SERVER['HTTP_HOST'] . "/auth/verify_email.php?token=" . $token;
        $subject = "Verify your MorningNewsletter account";
        
        $htmlBody = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Verify Your Account</title>
        </head>
        <body style='font-family: ui-sans-serif, system-ui, sans-serif, \"Apple Color Emoji\", \"Segoe UI Emoji\", \"Segoe UI Symbol\", \"Noto Color Emoji\"; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;'>
            <div style='text-align: center; margin-bottom: 30px;'>
                <h1 style='color: #468BE6;'>Welcome to MorningNewsletter!</h1>
            </div>
            
            <div style='background-color: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;'>
                <p>Thank you for signing up! Please verify your email address to complete your registration.</p>
                
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='$verificationUrl' 
                       style='color: #468BE6; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block;'>
                        Verify Email Address
                    </a>
                </div>
                
                <p style='font-size: 14px; color: #666;'>
                    If the button doesn't work, copy and paste this link into your browser:
                </p>
                <div style='background-color: #e5e7eb; padding: 10px; border-radius: 4px; margin: 10px 0; word-break: break-all;'>
                    <a href='$verificationUrl' style='color: #468BE6; text-decoration: none; font-size: 12px;'>$verificationUrl</a>
                </div>
            </div>
            
            <div style='font-size: 12px; color: #666; text-align: center;'>
                <p>If you didn't create an account with me, please ignore this email.</p>
            </div>
        </body>
        </html>";
        
        return $this->sendEmail($email, $subject, $htmlBody);
    }
    
    public function sendWelcomeEmail($email, $name = '') {
        // Try to use the new transactional email system
        try {
            require_once __DIR__ . '/TransactionalEmailManager.php';
            $transactionalManager = new TransactionalEmailManager();
            
            // Find user by email to get ID
            $user = User::findByEmail($email);
            if ($user) {
                return $transactionalManager->sendTransactionalEmail('welcome_email', $user->getId());
            }
        } catch (Exception $e) {
            error_log("Failed to use transactional email system for welcome email, falling back to hardcoded: " . $e->getMessage());
        }
        
        // Fallback to hardcoded email
        $subject = "Welcome to MorningNewsletter!";
        
        // Extract first name from full name or email
        $firstName = $name;
        if (empty($firstName)) {
            // Try to extract from email (e.g., john.doe@email.com -> John)
            $emailParts = explode('@', $email);
            $namePart = $emailParts[0];
            $firstName = ucfirst(preg_replace('/[._-].*/', '', $namePart));
        } else {
            // Extract first name from full name
            $nameParts = explode(' ', $name);
            $firstName = $nameParts[0];
        }
        
        $htmlBody = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Welcome to MorningNewsletter</title>
        </head>
        <body style='font-family: ui-sans-serif, system-ui, sans-serif, \"Apple Color Emoji\", \"Segoe UI Emoji\", \"Segoe UI Symbol\", \"Noto Color Emoji\"; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;'>
            <div style='background-color: #f8f9fa; padding: 30px; border-radius: 8px;'>
                <p style='font-size: 16px; margin-bottom: 20px;'>Hi $firstName,</p>
                
                <p style='font-size: 16px; margin-bottom: 20px;'>
                    My name is Manuel, and I'm the founder of MorningNewsletter. Thanks so much for signing up!
                </p>
                
                <p style='font-size: 16px; margin-bottom: 20px;'>
                    If you ever have any questions or just want to share your thoughts, just reply to this email, I read every response.
                </p>
                
                <p style='font-size: 16px; margin-bottom: 20px;'>
                    Manuel
                </p>
                
                <div style='margin-top: 30px; padding-top: 20px; border-top: 1px solid #dee2e6;'>
                    <p style='font-size: 14px; color: #666; text-align: center;'>
                        You're receiving this because you signed up for MorningNewsletter.<br>
                        <a href='https://morningnewsletter.com' style='color: #468BE6; text-decoration: none;'>morningnewsletter.com</a>
                    </p>
                </div>
            </div>
        </body>
        </html>";
        
        // Also prepare plain text version
        $plainBody = "Hi $firstName,\n\n";
        $plainBody .= "My name is Manuel, and I'm the founder of MorningNewsletter. Thanks so much for signing up!\n\n";
        $plainBody .= "If you ever have any questions or just want to share your thoughts, just reply to this email, I read every response.\n\n";
        $plainBody .= "Manuel\n\n";
        $plainBody .= "---\n";
        $plainBody .= "You're receiving this because you signed up for MorningNewsletter.\n";
        $plainBody .= "morningnewsletter.com";
        
        return $this->sendEmail($email, $subject, $htmlBody);
    }
    
    public function sendPasswordResetEmail($email, $token) {
        // Try to use the new transactional email system
        try {
            require_once __DIR__ . '/TransactionalEmailManager.php';
            $transactionalManager = new TransactionalEmailManager();
            
            // Find user by email to get ID
            $user = User::findByEmail($email);
            if ($user) {
                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'] ?? 'morningnewsletter.com';
                $resetUrl = $protocol . "://" . $host . "/auth/reset_password.php?token=" . $token;
                
                return $transactionalManager->sendTransactionalEmail('password_reset', $user->getId(), [
                    'reset_url' => $resetUrl
                ]);
            }
        } catch (Exception $e) {
            error_log("Failed to use transactional email system for password reset, falling back to hardcoded: " . $e->getMessage());
        }
        
        // Fallback to hardcoded email
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
        <body style='font-family: ui-sans-serif, system-ui, sans-serif, \"Apple Color Emoji\", \"Segoe UI Emoji\", \"Segoe UI Symbol\", \"Noto Color Emoji\"; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;'>
            <div style='text-align: center; margin-bottom: 30px;'>
                <h1 style='color: #468BE6;'>Password Reset Request</h1>
            </div>
            
            <div style='background-color: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;'>
                <p>You have requested to reset your password for your MorningNewsletter account.</p>
                
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='$resetUrl' 
                       style='color: #dc2626; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block;'>
                        Reset Password
                    </a>
                </div>
                
                <p style='font-size: 14px; color: #666;'>
                    If the button doesn't work, copy and paste this link into your browser:
                </p>
                <div style='background-color: #e5e7eb; padding: 10px; border-radius: 4px; margin: 10px 0; word-break: break-all;'>
                    <a href='$resetUrl' style='color: #468BE6; text-decoration: none; font-size: 12px;'>$resetUrl</a>
                </div>
                
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
    
    public function sendEmailChangeVerification($newEmail, $token, $currentEmail) {
        // Try to use the new transactional email system
        try {
            require_once __DIR__ . '/TransactionalEmailManager.php';
            $transactionalManager = new TransactionalEmailManager();
            
            // Find user by current email to get ID
            $user = User::findByEmail($currentEmail);
            if ($user) {
                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'] ?? 'morningnewsletter.com';
                $verificationUrl = $protocol . "://" . $host . "/auth/verify_email.php?token=" . $token . "&type=email_change";
                
                return $transactionalManager->sendTransactionalEmail('email_change_verification', $user->getId(), [
                    'current_email' => $currentEmail,
                    'new_email' => $newEmail,
                    'verification_url' => $verificationUrl
                ]);
            }
        } catch (Exception $e) {
            error_log("Failed to use transactional email system for email change verification, falling back to hardcoded: " . $e->getMessage());
        }
        
        // Fallback to hardcoded email
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'morningnewsletter.com';
        $verificationUrl = $protocol . "://" . $host . "/auth/verify_email.php?token=" . $token . "&type=email_change";
        $subject = "Verify your new email address - MorningNewsletter";
        
        $htmlBody = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Verify Your New Email Address</title>
        </head>
        <body style='font-family: ui-sans-serif, system-ui, sans-serif, \"Apple Color Emoji\", \"Segoe UI Emoji\", \"Segoe UI Symbol\", \"Noto Color Emoji\"; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;'>
            <div style='text-align: center; margin-bottom: 30px;'>
                <h1 style='color: #468BE6;'>Email Address Change Request</h1>
            </div>
            
            <div style='background-color: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;'>
                <p>You have requested to change your email address from <strong>" . htmlspecialchars($currentEmail) . "</strong> to <strong>" . htmlspecialchars($newEmail) . "</strong>.</p>
                
                <p>To complete this change, please click the button below to verify your new email address:</p>
                
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='$verificationUrl' 
                       style='color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block;'>
                        Verify New Email Address
                    </a>
                </div>
                
                <p style='font-size: 14px; color: #666;'>
                    If the button doesn't work, copy and paste this link into your browser:
                </p>
                <div style='background-color: #e5e7eb; padding: 10px; border-radius: 4px; margin: 10px 0; word-break: break-all;'>
                    <a href='$verificationUrl' style='color: #468BE6; text-decoration: none; font-size: 12px;'>$verificationUrl</a>
                </div>
                
                <p style='font-size: 14px; color: #666; margin-top: 20px;'>
                    <strong>This verification link will expire in 1 hour.</strong>
                </p>
            </div>
            
            <div style='font-size: 12px; color: #666; text-align: center;'>
                <p>If you didn't request this email change, please ignore this email or contact support if you're concerned about your account security.</p>
            </div>
        </body>
        </html>
        ";
        
        return $this->sendEmail($newEmail, $subject, $htmlBody);
    }
    
    public function sendPreviewEmail($email, $subject, $htmlContent) {
        try {
            $success = $this->sendEmail($email, $subject, $htmlContent);
            
            if ($success) {
                error_log("Preview email sent successfully to: $email");
            } else {
                error_log("Failed to send preview email to: $email");
            }
            
            return $success;
        } catch (Exception $e) {
            error_log("Preview email error: " . $e->getMessage());
            throw $e;
        }
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