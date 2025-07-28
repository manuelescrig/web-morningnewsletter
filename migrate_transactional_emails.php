<?php
// Migration script to add transactional email tables to existing database

require_once __DIR__ . '/config/database.php';

try {
    echo "Adding transactional email tables...\n";
    
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // Check if tables already exist
    $tables_to_check = [
        'transactional_email_templates',
        'transactional_email_logs', 
        'transactional_email_rules',
        'transactional_email_queue'
    ];
    
    $existing_tables = [];
    foreach ($tables_to_check as $table) {
        $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='$table'");
        if ($stmt->fetch()) {
            $existing_tables[] = $table;
        }
    }
    
    if (count($existing_tables) == 4) {
        echo "All transactional email tables already exist. No migration needed.\n";
        exit(0);
    }
    
    // Create transactional_email_templates table
    if (!in_array('transactional_email_templates', $existing_tables)) {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS transactional_email_templates (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                type TEXT UNIQUE NOT NULL,
                name TEXT NOT NULL,
                description TEXT,
                subject TEXT NOT NULL,
                html_template TEXT NOT NULL,
                is_enabled INTEGER DEFAULT 1,
                variables TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
        echo "Created transactional_email_templates table\n";
    }
    
    // Create transactional_email_logs table
    if (!in_array('transactional_email_logs', $existing_tables)) {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS transactional_email_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                template_id INTEGER NOT NULL,
                email TEXT NOT NULL,
                subject TEXT NOT NULL,
                status TEXT NOT NULL,
                error_message TEXT,
                metadata TEXT,
                sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
                FOREIGN KEY (template_id) REFERENCES transactional_email_templates (id)
            )
        ");
        echo "Created transactional_email_logs table\n";
    }
    
    // Create transactional_email_rules table
    if (!in_array('transactional_email_rules', $existing_tables)) {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS transactional_email_rules (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                trigger_event TEXT NOT NULL,
                delay_hours INTEGER DEFAULT 0,
                template_id INTEGER NOT NULL,
                is_enabled INTEGER DEFAULT 1,
                conditions TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (template_id) REFERENCES transactional_email_templates (id) ON DELETE CASCADE
            )
        ");
        echo "Created transactional_email_rules table\n";
    }
    
    // Create transactional_email_queue table
    if (!in_array('transactional_email_queue', $existing_tables)) {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS transactional_email_queue (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                rule_id INTEGER,
                template_id INTEGER NOT NULL,
                scheduled_for DATETIME NOT NULL,
                status TEXT DEFAULT 'pending',
                attempts INTEGER DEFAULT 0,
                variables TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                processed_at DATETIME,
                FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
                FOREIGN KEY (rule_id) REFERENCES transactional_email_rules (id) ON DELETE CASCADE,
                FOREIGN KEY (template_id) REFERENCES transactional_email_templates (id)
            )
        ");
        echo "Created transactional_email_queue table\n";
    }
    
    // Check if we need to insert default templates
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM transactional_email_templates");
    $result = $stmt->fetch();
    
    if ($result['count'] == 0) {
        // Insert default templates
        $defaultTemplates = [
            [
                'type' => 'email_verification',
                'name' => 'Email Verification',
                'description' => 'Sent when a user needs to verify their email address',
                'subject' => 'Verify your MorningNewsletter account',
                'html_template' => '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Your Account</title>
</head>
<body style="font-family: ui-sans-serif, system-ui, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="text-align: center; margin-bottom: 30px;">
        <h1 style="color: #468BE6;">Welcome to MorningNewsletter!</h1>
    </div>
    
    <div style="background-color: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
        <p>Hi {{first_name}},</p>
        <p>Thank you for signing up! Please verify your email address to complete your registration.</p>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{verification_url}}" 
               style="background-color: #468BE6; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block;">
                Verify Email Address
            </a>
        </div>
        
        <p style="font-size: 14px; color: #666;">
            If the button doesn\'t work, copy and paste this link into your browser:
            <br>{{verification_url}}
        </p>
    </div>
</body>
</html>',
                'variables' => json_encode(['email', 'first_name', 'verification_url']),
                'is_enabled' => 1
            ]
        ];
        
        $stmt = $pdo->prepare("
            INSERT INTO transactional_email_templates 
            (type, name, description, subject, html_template, variables, is_enabled) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        foreach ($defaultTemplates as $template) {
            $stmt->execute([
                $template['type'],
                $template['name'],
                $template['description'],
                $template['subject'],
                $template['html_template'],
                $template['variables'],
                $template['is_enabled']
            ]);
        }
        
        echo "Inserted default email templates\n";
    }
    
    echo "\nMigration completed successfully!\n";
    
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}