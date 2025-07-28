<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/EmailSender.php';
require_once __DIR__ . '/User.php';

class TransactionalEmailManager {
    private $db;
    private $emailSender;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->emailSender = new EmailSender();
    }
    
    /**
     * Get all email templates
     */
    public function getTemplates() {
        $stmt = $this->db->query("
            SELECT * FROM transactional_email_templates 
            ORDER BY name ASC
        ");
        return $stmt->fetchAll();
    }
    
    /**
     * Get a specific template by ID
     */
    public function getTemplate($id) {
        $stmt = $this->db->prepare("
            SELECT * FROM transactional_email_templates 
            WHERE id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    /**
     * Get a template by type
     */
    public function getTemplateByType($type) {
        $stmt = $this->db->prepare("
            SELECT * FROM transactional_email_templates 
            WHERE type = ? AND is_enabled = 1
        ");
        $stmt->execute([$type]);
        return $stmt->fetch();
    }
    
    /**
     * Update a template
     */
    public function updateTemplate($id, $data) {
        $allowedFields = ['name', 'description', 'subject', 'html_template', 'is_enabled', 'trigger_event', 'delay_hours', 'conditions'];
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
        
        $values[] = $id;
        $sql = "UPDATE transactional_email_templates SET " . implode(', ', $updates) . ", updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($values);
    }
    
    /**
     * Get all rules
     */
    public function getRules() {
        $stmt = $this->db->query("
            SELECT r.*, t.name as template_name, t.type as template_type
            FROM transactional_email_rules r
            JOIN transactional_email_templates t ON r.template_id = t.id
            ORDER BY r.trigger_event ASC, r.delay_hours ASC
        ");
        return $stmt->fetchAll();
    }
    
    /**
     * Get a specific rule by ID
     */
    public function getRule($id) {
        $stmt = $this->db->prepare("
            SELECT r.*, t.name as template_name, t.type as template_type
            FROM transactional_email_rules r
            JOIN transactional_email_templates t ON r.template_id = t.id
            WHERE r.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    /**
     * Create a new rule
     */
    public function createRule($data) {
        $stmt = $this->db->prepare("
            INSERT INTO transactional_email_rules 
            (name, trigger_event, delay_hours, template_id, is_enabled, conditions) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        return $stmt->execute([
            $data['name'],
            $data['trigger_event'],
            $data['delay_hours'] ?? 0,
            $data['template_id'],
            $data['is_enabled'] ?? 1,
            $data['conditions'] ?? null
        ]);
    }
    
    /**
     * Update a rule
     */
    public function updateRule($id, $data) {
        $allowedFields = ['name', 'trigger_event', 'delay_hours', 'template_id', 'is_enabled', 'conditions'];
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
        
        $values[] = $id;
        $sql = "UPDATE transactional_email_rules SET " . implode(', ', $updates) . ", updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($values);
    }
    
    /**
     * Delete a rule
     */
    public function deleteRule($id) {
        $stmt = $this->db->prepare("DELETE FROM transactional_email_rules WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    /**
     * Send a transactional email using the template system
     */
    public function sendTransactionalEmail($type, $userId, $variables = []) {
        // Get the template
        $template = $this->getTemplateByType($type);
        if (!$template || !$template['is_enabled']) {
            error_log("Template '$type' not found or disabled");
            return false;
        }
        
        // Get user
        $user = User::findById($userId);
        if (!$user) {
            error_log("User $userId not found");
            return false;
        }
        
        // Prepare variables
        $variables = array_merge($variables, [
            'email' => $user->getEmail(),
            'name' => $user->getName(),
            'first_name' => $this->getFirstName($user)
        ]);
        
        // Replace variables in template
        $subject = $this->replaceVariables($template['subject'], $variables);
        $htmlContent = $this->replaceVariables($template['html_template'], $variables);
        
        try {
            // Send the email using reflection to access private method
            $success = $this->sendEmail(
                $user->getEmail(),
                $subject,
                $htmlContent
            );
            
            // Log the email
            $this->logTransactionalEmail($userId, $template['id'], $user->getEmail(), $subject, $success ? 'sent' : 'failed');
            
            return $success;
        } catch (Exception $e) {
            error_log("Failed to send transactional email: " . $e->getMessage());
            $this->logTransactionalEmail($userId, $template['id'], $user->getEmail(), $subject, 'failed', $e->getMessage());
            return false;
        }
    }
    
    /**
     * Schedule delayed transactional emails based on an event
     */
    public function scheduleEmailsForEvent($event, $userId, $variables = []) {
        // Get all enabled templates for this event
        $stmt = $this->db->prepare("
            SELECT * FROM transactional_email_templates
            WHERE trigger_event = ? AND is_enabled = 1
        ");
        $stmt->execute([$event]);
        $templates = $stmt->fetchAll();
        
        foreach ($templates as $template) {
            // Check conditions if any
            if ($template['conditions']) {
                $conditions = json_decode($template['conditions'], true);
                if (!$this->checkConditions($conditions, $userId, $variables)) {
                    continue;
                }
            }
            
            // If no delay, send immediately
            if (empty($template['delay_hours']) || $template['delay_hours'] == 0) {
                $this->sendTransactionalEmail($template['type'], $userId, $variables);
            } else {
                // Calculate scheduled time
                $scheduledFor = date('Y-m-d H:i:s', strtotime("+{$template['delay_hours']} hours"));
                
                // Add to queue
                $stmt = $this->db->prepare("
                    INSERT INTO transactional_email_queue 
                    (user_id, rule_id, template_id, scheduled_for, variables) 
                    VALUES (?, NULL, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $userId,
                    $template['id'],
                    $scheduledFor,
                    json_encode($variables)
                ]);
            }
        }
    }
    
    /**
     * Process the email queue (should be called by cron)
     */
    public function processQueue() {
        // Get pending emails that are due
        $stmt = $this->db->prepare("
            SELECT q.*, t.type as template_type
            FROM transactional_email_queue q
            JOIN transactional_email_templates t ON q.template_id = t.id
            WHERE q.status = 'pending' 
            AND q.scheduled_for <= datetime('now')
            AND q.attempts < 3
            ORDER BY q.scheduled_for ASC
            LIMIT 50
        ");
        $stmt->execute();
        $queueItems = $stmt->fetchAll();
        
        foreach ($queueItems as $item) {
            // Mark as processing
            $this->updateQueueStatus($item['id'], 'processing');
            
            // Get variables
            $variables = json_decode($item['variables'], true) ?: [];
            
            // Send the email
            $success = $this->sendTransactionalEmail($item['template_type'], $item['user_id'], $variables);
            
            if ($success) {
                // Mark as sent
                $this->updateQueueStatus($item['id'], 'sent');
            } else {
                // Increment attempts
                $attempts = $item['attempts'] + 1;
                if ($attempts >= 3) {
                    $this->updateQueueStatus($item['id'], 'failed');
                } else {
                    $this->updateQueueStatus($item['id'], 'pending', $attempts);
                }
            }
        }
    }
    
    /**
     * Get email logs
     */
    public function getEmailLogs($limit = 100, $offset = 0) {
        $stmt = $this->db->prepare("
            SELECT l.*, t.name as template_name, t.type as template_type, u.email as user_email
            FROM transactional_email_logs l
            JOIN transactional_email_templates t ON l.template_id = t.id
            JOIN users u ON l.user_id = u.id
            ORDER BY l.sent_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get queue items
     */
    public function getQueueItems($status = null, $limit = 100, $offset = 0) {
        $sql = "
            SELECT q.*, t.name as template_name, t.type as template_type, u.email as user_email, r.name as rule_name
            FROM transactional_email_queue q
            JOIN transactional_email_templates t ON q.template_id = t.id
            JOIN users u ON q.user_id = u.id
            LEFT JOIN transactional_email_rules r ON q.rule_id = r.id
        ";
        
        $params = [];
        if ($status) {
            $sql .= " WHERE q.status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY q.scheduled_for DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Replace variables in template content
     */
    private function replaceVariables($content, $variables) {
        foreach ($variables as $key => $value) {
            $content = str_replace('{{' . $key . '}}', htmlspecialchars($value), $content);
        }
        return $content;
    }
    
    /**
     * Log transactional email
     */
    private function logTransactionalEmail($userId, $templateId, $email, $subject, $status, $errorMessage = null) {
        $stmt = $this->db->prepare("
            INSERT INTO transactional_email_logs 
            (user_id, template_id, email, subject, status, error_message) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([$userId, $templateId, $email, $subject, $status, $errorMessage]);
    }
    
    /**
     * Update queue item status
     */
    private function updateQueueStatus($id, $status, $attempts = null) {
        if ($attempts !== null) {
            $stmt = $this->db->prepare("
                UPDATE transactional_email_queue 
                SET status = ?, attempts = ?, processed_at = CURRENT_TIMESTAMP 
                WHERE id = ?
            ");
            $stmt->execute([$status, $attempts, $id]);
        } else {
            $stmt = $this->db->prepare("
                UPDATE transactional_email_queue 
                SET status = ?, processed_at = CURRENT_TIMESTAMP 
                WHERE id = ?
            ");
            $stmt->execute([$status, $id]);
        }
    }
    
    /**
     * Check conditions for a rule
     */
    private function checkConditions($conditions, $userId, $variables) {
        $user = User::findById($userId);
        if (!$user) {
            return false;
        }
        
        // Check subscription plan condition
        if (isset($conditions['subscription_plan'])) {
            $allowedPlans = is_array($conditions['subscription_plan']) ? $conditions['subscription_plan'] : [$conditions['subscription_plan']];
            if (!in_array($user->getPlan(), $allowedPlans)) {
                return false;
            }
        }
        
        // Add more condition checks as needed
        
        return true;
    }
    
    /**
     * Get first name from user
     */
    private function getFirstName($user) {
        $name = $user->getName();
        if (empty($name)) {
            // Try to extract from email
            $emailParts = explode('@', $user->getEmail());
            $namePart = $emailParts[0];
            return ucfirst(preg_replace('/[._-].*/', '', $namePart));
        } else {
            // Extract first name from full name
            $nameParts = explode(' ', $name);
            return $nameParts[0];
        }
    }
    
    /**
     * Send email method that delegates to EmailSender
     */
    private function sendEmail($to, $subject, $htmlBody) {
        // Create a reflection to access the private method
        $reflection = new ReflectionClass($this->emailSender);
        $method = $reflection->getMethod('sendEmail');
        $method->setAccessible(true);
        
        return $method->invoke($this->emailSender, $to, $subject, $htmlBody);
    }
}