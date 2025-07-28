<?php
// Migration to add trigger fields to transactional_email_templates table

require_once __DIR__ . '/config/database.php';

try {
    echo "Adding trigger fields to transactional email templates...\n";
    
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // Check if columns already exist
    $stmt = $pdo->query("PRAGMA table_info(transactional_email_templates)");
    $columns = $stmt->fetchAll();
    $columnNames = array_column($columns, 'name');
    
    // Add trigger_event column if it doesn't exist
    if (!in_array('trigger_event', $columnNames)) {
        $pdo->exec("ALTER TABLE transactional_email_templates ADD COLUMN trigger_event TEXT");
        echo "Added trigger_event column\n";
    }
    
    // Add delay_hours column if it doesn't exist
    if (!in_array('delay_hours', $columnNames)) {
        $pdo->exec("ALTER TABLE transactional_email_templates ADD COLUMN delay_hours INTEGER DEFAULT 0");
        echo "Added delay_hours column\n";
    }
    
    // Add conditions column if it doesn't exist
    if (!in_array('conditions', $columnNames)) {
        $pdo->exec("ALTER TABLE transactional_email_templates ADD COLUMN conditions TEXT");
        echo "Added conditions column\n";
    }
    
    // Migrate data from rules to templates if rules exist
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM sqlite_master WHERE type='table' AND name='transactional_email_rules'");
    $result = $stmt->fetch();
    
    if ($result['count'] > 0) {
        echo "\nMigrating rules to templates...\n";
        
        // Get all rules with their template info
        $stmt = $pdo->query("
            SELECT r.*, t.type as template_type 
            FROM transactional_email_rules r
            JOIN transactional_email_templates t ON r.template_id = t.id
            WHERE r.is_enabled = 1
        ");
        $rules = $stmt->fetchAll();
        
        foreach ($rules as $rule) {
            // Update the template with the rule data
            $updateStmt = $pdo->prepare("
                UPDATE transactional_email_templates 
                SET trigger_event = ?, delay_hours = ?, conditions = ?
                WHERE id = ?
            ");
            
            $updateStmt->execute([
                $rule['trigger_event'],
                $rule['delay_hours'],
                $rule['conditions'],
                $rule['template_id']
            ]);
            
            echo "Migrated rule '{$rule['name']}' to template\n";
        }
        
        // Optional: Drop the rules table after migration
        // $pdo->exec("DROP TABLE transactional_email_rules");
        // echo "\nDropped transactional_email_rules table\n";
    }
    
    // Update default templates with trigger events
    $defaultTriggers = [
        'email_verification' => ['event' => 'user_registered', 'delay' => 0],
        'password_reset' => ['event' => 'password_reset_requested', 'delay' => 0],
        'welcome' => ['event' => 'email_verified', 'delay' => 0],
        'subscription_created' => ['event' => 'subscription_created', 'delay' => 0],
        'subscription_cancelled' => ['event' => 'subscription_cancelled', 'delay' => 0]
    ];
    
    foreach ($defaultTriggers as $type => $trigger) {
        $stmt = $pdo->prepare("
            UPDATE transactional_email_templates 
            SET trigger_event = ?, delay_hours = ?
            WHERE type = ? AND trigger_event IS NULL
        ");
        $stmt->execute([$trigger['event'], $trigger['delay'], $type]);
    }
    
    echo "\nMigration completed successfully!\n";
    
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}