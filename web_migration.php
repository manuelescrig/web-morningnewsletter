<?php
/**
 * Web-based Migration Script: Multiple Newsletters Per User
 * 
 * This is a web-accessible version of the migration script.
 * Run this from your browser: http://yourdomain.com/web_migration.php
 * 
 * IMPORTANT: 
 * 1. Backup your database before running this script!
 * 2. Remove this file after migration is complete
 * 3. Add ?confirm=yes to the URL to actually run the migration
 */

// Security check - require confirmation parameter
if (!isset($_GET['confirm']) || $_GET['confirm'] !== 'yes') {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Newsletter Migration</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 800px; margin: 40px auto; padding: 20px; }
            .warning { background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; padding: 15px; border-radius: 5px; margin: 20px 0; }
            .button { background: #dc2626; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px 0; }
            .button:hover { background: #b91c1c; }
            pre { background: #f3f4f6; padding: 15px; border-radius: 5px; overflow-x: auto; }
        </style>
    </head>
    <body>
        <h1>🚀 Newsletter Migration Tool</h1>
        
        <div class="warning">
            <h3>⚠️ Important: Database Migration Required</h3>
            <p>This script will modify your database structure to support multiple newsletters per user.</p>
            <p><strong>BACKUP YOUR DATABASE BEFORE PROCEEDING!</strong></p>
        </div>

        <h2>What this migration does:</h2>
        <ul>
            <li>Creates a new <code>newsletters</code> table</li>
            <li>Migrates existing user newsletter data to the new table</li>
            <li>Updates the <code>sources</code> table to link to newsletters instead of users</li>
            <li>Maintains backward compatibility with existing code</li>
            <li>Removes newsletter-specific columns from the <code>users</code> table</li>
        </ul>

        <h2>Current System Status:</h2>
        <pre id="status">Checking system status...</pre>

        <p>If you've backed up your database and are ready to proceed:</p>
        <a href="?confirm=yes" class="button">🚀 Run Migration</a>

        <script>
        // Check current system status
        fetch('?action=status')
            .then(response => response.text())
            .then(data => {
                document.getElementById('status').textContent = data;
            })
            .catch(error => {
                document.getElementById('status').textContent = 'Error checking status: ' + error;
            });
        </script>
    </body>
    </html>
    <?php
    exit;
}

// Handle status check
if (isset($_GET['action']) && $_GET['action'] === 'status') {
    header('Content-Type: text/plain');
    
    try {
        require_once __DIR__ . '/config/database.php';
        $db = Database::getInstance()->getConnection();
        
        // Check if newsletters table exists
        $stmt = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='newsletters'");
        $newslettersExists = $stmt->fetch() !== false;
        
        // Count users
        $stmt = $db->query("SELECT COUNT(*) as count FROM users WHERE email_verified = 1");
        $userCount = $stmt->fetch()['count'];
        
        // Count sources
        $stmt = $db->query("SELECT COUNT(*) as count FROM sources WHERE is_active = 1");
        $sourceCount = $stmt->fetch()['count'];
        
        echo "Database Status:\n";
        echo "- Newsletters table exists: " . ($newslettersExists ? "YES" : "NO") . "\n";
        echo "- Verified users: $userCount\n";
        echo "- Active sources: $sourceCount\n";
        echo "\n";
        
        if ($newslettersExists) {
            $stmt = $db->query("SELECT COUNT(*) as count FROM newsletters WHERE is_active = 1");
            $newsletterCount = $stmt->fetch()['count'];
            echo "Migration Status: ALREADY COMPLETED\n";
            echo "- Active newsletters: $newsletterCount\n";
        } else {
            echo "Migration Status: READY TO RUN\n";
        }
        
    } catch (Exception $e) {
        echo "Error checking status: " . $e->getMessage();
    }
    
    exit;
}

// Set content type for output
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Migration Progress</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 40px auto; padding: 20px; }
        .log { background: #f3f4f6; padding: 15px; border-radius: 5px; height: 400px; overflow-y: auto; white-space: pre-wrap; font-family: monospace; font-size: 14px; }
        .success { color: #059669; }
        .error { color: #dc2626; }
        .info { color: #0369a1; }
    </style>
</head>
<body>
    <h1>🚀 Running Newsletter Migration</h1>
    <div class="log" id="output">Starting migration...\n</div>
    
    <script>
        function log(message, type = 'info') {
            const output = document.getElementById('output');
            const span = document.createElement('span');
            span.className = type;
            span.textContent = new Date().toLocaleTimeString() + ' - ' + message + '\n';
            output.appendChild(span);
            output.scrollTop = output.scrollHeight;
        }
    </script>

<?php
// Flush output immediately
ob_start();

function webLog($message, $type = 'info') {
    echo "<script>log(" . json_encode($message) . ", " . json_encode($type) . ");</script>";
    ob_flush();
    flush();
}

try {
    require_once __DIR__ . '/config/database.php';
    
    webLog("🔧 Loading migration classes...");
    
    // Include the migration class
    class WebNewsletterMigration {
        private $db;
        private $migrationMap = [];
        
        public function __construct() {
            $this->db = Database::getInstance()->getConnection();
        }
        
        public function migrate() {
            webLog("🚀 Starting Newsletter Migration...", 'info');
            
            try {
                $this->db->beginTransaction();
                
                // Step 1: Create newsletters table
                $this->createNewslettersTable();
                
                // Step 2: Migrate existing user data to newsletters
                $this->migrateExistingData();
                
                // Step 3: Update sources table to use newsletter_id
                $this->updateSourcesTable();
                
                // Step 4: Remove newsletter-specific columns from users table
                $this->cleanupUsersTable();
                
                // Step 5: Update email_logs table (optional)
                $this->updateEmailLogsTable();
                
                $this->db->commit();
                
                webLog("✅ Migration completed successfully!", 'success');
                webLog("📊 Summary:", 'info');
                $this->printMigrationSummary();
                
            } catch (Exception $e) {
                $this->db->rollback();
                webLog("❌ Migration failed: " . $e->getMessage(), 'error');
                webLog("💾 Database has been rolled back to original state.", 'error');
                throw $e;
            }
        }
        
        private function createNewslettersTable() {
            webLog("📝 Creating newsletters table...");
            
            $sql = "CREATE TABLE IF NOT EXISTS newsletters (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                title TEXT NOT NULL DEFAULT 'My Newsletter',
                timezone TEXT DEFAULT 'UTC',
                send_time TEXT DEFAULT '06:00',
                is_active INTEGER DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
            )";
            
            $this->db->exec($sql);
            
            // Create indexes
            $this->db->exec("CREATE INDEX IF NOT EXISTS idx_newsletters_user_id ON newsletters(user_id)");
            $this->db->exec("CREATE INDEX IF NOT EXISTS idx_newsletters_active ON newsletters(is_active)");
            
            webLog("✅ Newsletters table created", 'success');
        }
        
        private function migrateExistingData() {
            webLog("📥 Migrating existing user newsletters...");
            
            // Get all users with their newsletter data
            $stmt = $this->db->query("
                SELECT id, newsletter_title, timezone, send_time 
                FROM users 
                WHERE email_verified = 1
            ");
            $users = $stmt->fetchAll();
            
            $insertStmt = $this->db->prepare("
                INSERT INTO newsletters (user_id, title, timezone, send_time) 
                VALUES (?, ?, ?, ?)
            ");
            
            foreach ($users as $user) {
                $title = $user['newsletter_title'] ?? 'My Morning Brief';
                $timezone = $user['timezone'] ?? 'UTC';
                $sendTime = $user['send_time'] ?? '06:00';
                
                $insertStmt->execute([
                    $user['id'],
                    $title,
                    $timezone,
                    $sendTime
                ]);
                
                $newsletterId = $this->db->lastInsertId();
                $this->migrationMap[$user['id']] = $newsletterId;
                
                webLog("  👤 User {$user['id']}: '{$title}' → Newsletter {$newsletterId}");
            }
            
            webLog("✅ Migrated " . count($users) . " user newsletters", 'success');
        }
        
        private function updateSourcesTable() {
            webLog("🔄 Updating sources table schema...");
            
            // First, add newsletter_id column
            try {
                $this->db->exec("ALTER TABLE sources ADD COLUMN newsletter_id INTEGER");
                webLog("  ✅ Added newsletter_id column to sources");
            } catch (Exception $e) {
                if (strpos($e->getMessage(), 'duplicate column name') === false) {
                    throw $e;
                }
                webLog("  ⚠️  newsletter_id column already exists");
            }
            
            // Update existing sources to point to newsletters
            $updateStmt = $this->db->prepare("
                UPDATE sources 
                SET newsletter_id = ? 
                WHERE user_id = ? AND newsletter_id IS NULL
            ");
            
            $sourceCount = 0;
            foreach ($this->migrationMap as $userId => $newsletterId) {
                $result = $updateStmt->execute([$newsletterId, $userId]);
                if ($result) {
                    $count = $updateStmt->rowCount();
                    $sourceCount += $count;
                    if ($count > 0) {
                        webLog("  📊 User {$userId}: Moved {$count} sources to Newsletter {$newsletterId}");
                    }
                }
            }
            
            webLog("✅ Updated {$sourceCount} sources to use newsletters", 'success');
        }
        
        private function cleanupUsersTable() {
            webLog("🧹 Cleaning up users table...");
            
            // SQLite doesn't support DROP COLUMN directly, so we recreate the table
            $this->db->exec("
                CREATE TABLE users_new (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    email TEXT UNIQUE NOT NULL,
                    name TEXT,
                    password_hash TEXT NOT NULL,
                    plan TEXT DEFAULT 'free',
                    email_verified INTEGER DEFAULT 0,
                    unsubscribed INTEGER DEFAULT 0,
                    verification_token TEXT,
                    is_admin INTEGER DEFAULT 0,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )
            ");
            
            // Copy data (excluding newsletter-specific columns)
            $this->db->exec("
                INSERT INTO users_new (id, email, name, password_hash, plan, email_verified, unsubscribed, verification_token, is_admin, created_at, updated_at)
                SELECT id, email, name, password_hash, plan, email_verified, unsubscribed, verification_token, is_admin, created_at, updated_at
                FROM users
            ");
            
            // Replace old table
            $this->db->exec("DROP TABLE users");
            $this->db->exec("ALTER TABLE users_new RENAME TO users");
            
            // Recreate indexes
            $this->db->exec("CREATE INDEX IF NOT EXISTS idx_users_email ON users(email)");
            
            webLog("✅ Removed newsletter-specific columns from users table", 'success');
        }
        
        private function updateEmailLogsTable() {
            webLog("📧 Updating email_logs table...");
            
            // Add newsletter_id column to email_logs for better tracking
            try {
                $this->db->exec("ALTER TABLE email_logs ADD COLUMN newsletter_id INTEGER");
                webLog("✅ Added newsletter_id column to email_logs", 'success');
            } catch (Exception $e) {
                if (strpos($e->getMessage(), 'duplicate column name') === false) {
                    throw $e;
                }
                webLog("⚠️  newsletter_id column already exists in email_logs");
            }
        }
        
        private function printMigrationSummary() {
            // Count newsletters
            $stmt = $this->db->query("SELECT COUNT(*) as count FROM newsletters WHERE is_active = 1");
            $newsletterCount = $stmt->fetch()['count'];
            
            // Count sources
            $stmt = $this->db->query("SELECT COUNT(*) as count FROM sources WHERE is_active = 1");
            $sourceCount = $stmt->fetch()['count'];
            
            // Count users
            $stmt = $this->db->query("SELECT COUNT(*) as count FROM users WHERE email_verified = 1");
            $userCount = $stmt->fetch()['count'];
            
            webLog("  📊 Active Newsletters: {$newsletterCount}");
            webLog("  👥 Verified Users: {$userCount}");
            webLog("  📈 Active Sources: {$sourceCount}");
            webLog("");
            webLog("🎉 Your system now supports multiple newsletters per user!", 'success');
            webLog("🔍 Next steps:");
            webLog("  1. Test with: /test_multi_newsletters.php");
            webLog("  2. Test cron: /cron/send_emails.php?mode=dry-run");
            webLog("  3. Manage newsletters: /dashboard/newsletters.php");
            webLog("  4. REMOVE this migration file for security!");
        }
    }
    
    webLog("🔧 Initializing migration...");
    $migration = new WebNewsletterMigration();
    $migration->migrate();
    
    webLog("🎊 Migration completed successfully!", 'success');
    
} catch (Exception $e) {
    webLog("❌ Fatal error: " . $e->getMessage(), 'error');
    webLog("📋 Stack trace: " . $e->getTraceAsString(), 'error');
}

ob_end_flush();
?>

<p style="margin-top: 20px; padding: 15px; background: #fef3c7; border: 1px solid #f59e0b; border-radius: 5px;">
    <strong>⚠️ Security Notice:</strong> Please delete this file (web_migration.php) after the migration is complete for security reasons.
</p>

</body>
</html>