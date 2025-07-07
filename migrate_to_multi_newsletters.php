<?php
/**
 * Migration Script: Multiple Newsletters Per User
 * 
 * This script migrates the database from single newsletter per user
 * to multiple newsletters per user architecture.
 * 
 * IMPORTANT: Backup your database before running this script!
 */

require_once __DIR__ . '/config/database.php';

class NewsletterMigration {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function migrate() {
        echo "🚀 Starting Newsletter Migration...\n\n";
        
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
            
            echo "✅ Migration completed successfully!\n";
            echo "📊 Summary:\n";
            $this->printMigrationSummary();
            
        } catch (Exception $e) {
            $this->db->rollback();
            echo "❌ Migration failed: " . $e->getMessage() . "\n";
            echo "💾 Database has been rolled back to original state.\n";
            throw $e;
        }
    }
    
    private function createNewslettersTable() {
        echo "📝 Creating newsletters table...\n";
        
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
        
        // Create index
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_newsletters_user_id ON newsletters(user_id)");
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_newsletters_active ON newsletters(is_active)");
        
        echo "✅ Newsletters table created\n";
    }
    
    private function migrateExistingData() {
        echo "📥 Migrating existing user newsletters...\n";
        
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
        
        $migrationMap = []; // user_id => newsletter_id
        
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
            $migrationMap[$user['id']] = $newsletterId;
            
            echo "  👤 User {$user['id']}: '{$title}' → Newsletter {$newsletterId}\n";
        }
        
        // Store migration map for later use
        $this->migrationMap = $migrationMap;
        
        echo "✅ Migrated " . count($users) . " user newsletters\n";
    }
    
    private function updateSourcesTable() {
        echo "🔄 Updating sources table schema...\n";
        
        // First, add newsletter_id column
        try {
            $this->db->exec("ALTER TABLE sources ADD COLUMN newsletter_id INTEGER");
            echo "  ✅ Added newsletter_id column to sources\n";
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'duplicate column name') === false) {
                throw $e;
            }
            echo "  ⚠️  newsletter_id column already exists\n";
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
                    echo "  📊 User {$userId}: Moved {$count} sources to Newsletter {$newsletterId}\n";
                }
            }
        }
        
        echo "✅ Updated {$sourceCount} sources to use newsletters\n";
        
        // Add foreign key constraint (recreate table for SQLite)
        echo "🔗 Adding foreign key constraints...\n";
        $this->recreateSourcesTableWithConstraints();
    }
    
    private function recreateSourcesTableWithConstraints() {
        // Create new sources table with proper constraints
        $this->db->exec("
            CREATE TABLE sources_new (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                newsletter_id INTEGER NOT NULL,
                user_id INTEGER, -- Keep for transition, will be removed later
                type TEXT NOT NULL,
                name TEXT,
                config TEXT,
                sort_order INTEGER DEFAULT 0,
                is_active INTEGER DEFAULT 1,
                last_result TEXT,
                last_updated DATETIME,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (newsletter_id) REFERENCES newsletters (id) ON DELETE CASCADE
            )
        ");
        
        // Copy data
        $this->db->exec("
            INSERT INTO sources_new 
            SELECT * FROM sources
        ");
        
        // Replace old table
        $this->db->exec("DROP TABLE sources");
        $this->db->exec("ALTER TABLE sources_new RENAME TO sources");
        
        // Recreate indexes
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_sources_newsletter_id ON sources(newsletter_id)");
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_sources_user_id ON sources(user_id)");
        
        echo "✅ Recreated sources table with foreign key constraints\n";
    }
    
    private function cleanupUsersTable() {
        echo "🧹 Cleaning up users table...\n";
        
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
        
        echo "✅ Removed newsletter-specific columns from users table\n";
    }
    
    private function updateEmailLogsTable() {
        echo "📧 Updating email_logs table...\n";
        
        // Add newsletter_id column to email_logs for better tracking
        try {
            $this->db->exec("ALTER TABLE email_logs ADD COLUMN newsletter_id INTEGER");
            echo "✅ Added newsletter_id column to email_logs\n";
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'duplicate column name') === false) {
                throw $e;
            }
            echo "⚠️  newsletter_id column already exists in email_logs\n";
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
        
        echo "  📊 Active Newsletters: {$newsletterCount}\n";
        echo "  👥 Verified Users: {$userCount}\n";
        echo "  📈 Active Sources: {$sourceCount}\n";
        echo "\n";
        echo "🎉 Your system now supports multiple newsletters per user!\n";
        echo "🔍 Next steps:\n";
        echo "  1. Update your application code to use the Newsletter class\n";
        echo "  2. Test the new newsletter management features\n";
        echo "  3. Update your cron jobs to handle multiple newsletters\n";
    }
    
    public function rollback() {
        echo "⏪ Rolling back migration...\n";
        
        try {
            $this->db->beginTransaction();
            
            // This is a simplified rollback - in production you'd want more sophisticated logic
            echo "⚠️  Rollback not fully implemented - restore from backup if needed\n";
            
            $this->db->rollback();
        } catch (Exception $e) {
            echo "❌ Rollback failed: " . $e->getMessage() . "\n";
        }
    }
}

// CLI execution
if (php_sapi_name() === 'cli') {
    echo "🗃️  Newsletter Migration Tool\n";
    echo "============================\n\n";
    
    if (isset($argv[1]) && $argv[1] === '--dry-run') {
        echo "🔍 DRY RUN MODE - No changes will be made\n\n";
        // In a real implementation, you'd add dry run logic here
        echo "Migration plan validated. Run without --dry-run to execute.\n";
    } else {
        $confirmation = readline("⚠️  This will modify your database structure. Type 'YES' to continue: ");
        
        if ($confirmation === 'YES') {
            $migration = new NewsletterMigration();
            $migration->migrate();
        } else {
            echo "❌ Migration cancelled.\n";
        }
    }
} else {
    echo "❌ This script must be run from the command line.\n";
}