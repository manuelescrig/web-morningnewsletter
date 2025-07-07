<?php
class Database {
    private static $instance = null;
    private $pdo;
    
    private function __construct() {
        $dbPath = __DIR__ . '/../data/newsletter.db';
        $dbDir = dirname($dbPath);
        
        if (!is_dir($dbDir)) {
            mkdir($dbDir, 0755, true);
        }
        
        try {
            $this->pdo = new PDO("sqlite:$dbPath");
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->initializeTables();
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->pdo;
    }
    
    private function initializeTables() {
        $queries = [
            "CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                email TEXT UNIQUE NOT NULL,
                name TEXT,
                password_hash TEXT NOT NULL,
                plan TEXT DEFAULT 'free',
                timezone TEXT DEFAULT 'UTC',
                email_verified INTEGER DEFAULT 0,
                send_time TEXT DEFAULT '06:00',
                newsletter_title TEXT DEFAULT 'Your Morning Brief',
                verification_token TEXT,
                is_admin INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )",
            
            "CREATE TABLE IF NOT EXISTS newsletters (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                title TEXT DEFAULT 'Your Morning Brief',
                send_time TEXT DEFAULT '06:00',
                timezone TEXT DEFAULT 'UTC',
                is_active INTEGER DEFAULT 1,
                unsubscribe_token TEXT UNIQUE,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
            )",
            
            "CREATE TABLE IF NOT EXISTS newsletter_recipients (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                newsletter_id INTEGER NOT NULL,
                email TEXT NOT NULL,
                is_active INTEGER DEFAULT 1,
                unsubscribe_token TEXT UNIQUE,
                unsubscribed_at DATETIME,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (newsletter_id) REFERENCES newsletters (id) ON DELETE CASCADE,
                UNIQUE(newsletter_id, email)
            )",
            
            "CREATE TABLE IF NOT EXISTS sources (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER,
                newsletter_id INTEGER,
                type TEXT NOT NULL,
                name TEXT,
                config TEXT,
                sort_order INTEGER DEFAULT 0,
                is_active INTEGER DEFAULT 1,
                last_result TEXT,
                last_updated DATETIME,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
                FOREIGN KEY (newsletter_id) REFERENCES newsletters (id) ON DELETE CASCADE
            )",
            
            "CREATE TABLE IF NOT EXISTS email_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                newsletter_id INTEGER,
                recipient_email TEXT,
                status TEXT NOT NULL,
                error_message TEXT,
                sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
                FOREIGN KEY (newsletter_id) REFERENCES newsletters (id) ON DELETE SET NULL
            )",
            
            "CREATE TABLE IF NOT EXISTS subscriptions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                stripe_subscription_id TEXT UNIQUE,
                stripe_customer_id TEXT,
                plan TEXT NOT NULL,
                status TEXT NOT NULL,
                current_period_start DATETIME,
                current_period_end DATETIME,
                cancel_at_period_end INTEGER DEFAULT 0,
                canceled_at DATETIME,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
            )",
            
            "CREATE TABLE IF NOT EXISTS payments (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                stripe_payment_intent_id TEXT UNIQUE,
                subscription_id INTEGER,
                amount INTEGER NOT NULL,
                currency TEXT DEFAULT 'usd',
                status TEXT NOT NULL,
                description TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
                FOREIGN KEY (subscription_id) REFERENCES subscriptions (id) ON DELETE SET NULL
            )",
            
            "CREATE TABLE IF NOT EXISTS source_configs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                type TEXT UNIQUE NOT NULL,
                name TEXT NOT NULL,
                description TEXT,
                is_enabled INTEGER DEFAULT 1,
                api_required INTEGER DEFAULT 0,
                default_config TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )",
            
            "CREATE INDEX IF NOT EXISTS idx_users_email ON users(email)",
            "CREATE INDEX IF NOT EXISTS idx_newsletters_user_id ON newsletters(user_id)",
            "CREATE INDEX IF NOT EXISTS idx_newsletters_unsubscribe_token ON newsletters(unsubscribe_token)",
            "CREATE INDEX IF NOT EXISTS idx_newsletter_recipients_newsletter_id ON newsletter_recipients(newsletter_id)",
            "CREATE INDEX IF NOT EXISTS idx_newsletter_recipients_email ON newsletter_recipients(email)",
            "CREATE INDEX IF NOT EXISTS idx_newsletter_recipients_unsubscribe_token ON newsletter_recipients(unsubscribe_token)",
            "CREATE INDEX IF NOT EXISTS idx_sources_newsletter_id ON sources(newsletter_id)",
            "CREATE INDEX IF NOT EXISTS idx_email_logs_user_id ON email_logs(user_id)",
            "CREATE INDEX IF NOT EXISTS idx_email_logs_newsletter_id ON email_logs(newsletter_id)",
            "CREATE INDEX IF NOT EXISTS idx_email_logs_sent_at ON email_logs(sent_at)",
            "CREATE INDEX IF NOT EXISTS idx_subscriptions_user_id ON subscriptions(user_id)",
            "CREATE INDEX IF NOT EXISTS idx_subscriptions_stripe_id ON subscriptions(stripe_subscription_id)",
            "CREATE INDEX IF NOT EXISTS idx_payments_user_id ON payments(user_id)",
            "CREATE INDEX IF NOT EXISTS idx_payments_stripe_id ON payments(stripe_payment_intent_id)",
            "CREATE INDEX IF NOT EXISTS idx_source_configs_type ON source_configs(type)"
        ];
        
        foreach ($queries as $query) {
            $this->pdo->exec($query);
        }
        
        // Add missing columns to existing tables (migrations) - but only for basic migrations
        $this->runBasicMigrations();
    }
    
    private function runBasicMigrations() {
        try {
            // Check if name column exists in users table, if not add it
            $stmt = $this->pdo->query("PRAGMA table_info(users)");
            $columns = $stmt->fetchAll();
            
            $hasNameColumn = false;
            foreach ($columns as $column) {
                if ($column['name'] === 'name') {
                    $hasNameColumn = true;
                    break;
                }
            }
            
            if (!$hasNameColumn) {
                $this->pdo->exec("ALTER TABLE users ADD COLUMN name TEXT");
                error_log("Database migration: Added 'name' column to users table");
            }
            
            // Check if name column exists in sources table, if not add it
            $stmt = $this->pdo->query("PRAGMA table_info(sources)");
            $columns = $stmt->fetchAll();
            
            $hasSourceNameColumn = false;
            foreach ($columns as $column) {
                if ($column['name'] === 'name') {
                    $hasSourceNameColumn = true;
                    break;
                }
            }
            
            if (!$hasSourceNameColumn) {
                $this->pdo->exec("ALTER TABLE sources ADD COLUMN name TEXT");
                error_log("Database migration: Added 'name' column to sources table");
            }
            
            // Check if sort_order column exists in sources table, if not add it
            $hasSortOrderColumn = false;
            foreach ($columns as $column) {
                if ($column['name'] === 'sort_order') {
                    $hasSortOrderColumn = true;
                    break;
                }
            }
            
            if (!$hasSortOrderColumn) {
                $this->pdo->exec("ALTER TABLE sources ADD COLUMN sort_order INTEGER DEFAULT 0");
                error_log("Database migration: Added 'sort_order' column to sources table");
            }
            
            // Check if newsletter_title column exists in users table, if not add it
            $stmt = $this->pdo->query("PRAGMA table_info(users)");
            $userColumns = $stmt->fetchAll();
            
            $hasNewsletterTitleColumn = false;
            foreach ($userColumns as $column) {
                if ($column['name'] === 'newsletter_title') {
                    $hasNewsletterTitleColumn = true;
                    break;
                }
            }
            
            if (!$hasNewsletterTitleColumn) {
                $this->pdo->exec("ALTER TABLE users ADD COLUMN newsletter_title TEXT DEFAULT 'Your Morning Brief'");
                error_log("Database migration: Added 'newsletter_title' column to users table");
            }
            
            // Migrate plan names from old system to new system
            $this->migratePlanNames();
            
            // Populate source configs table with default data
            $this->populateSourceConfigs();
            
        } catch (Exception $e) {
            error_log("Database migration error: " . $e->getMessage());
        }
    }
    
    public function runNewsletterMigration() {
        // This method can be called separately to run the newsletter migration
        $this->migrateToNewsletterStructure();
    }
    
    private function migratePlanNames() {
        try {
            // Check if we need to migrate plan names
            $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM users WHERE plan IN ('medium', 'premium')");
            $result = $stmt->fetch();
            
            if ($result['count'] > 0) {
                error_log("Database migration: Starting plan name migration for {$result['count']} users");
                
                // Begin transaction
                $this->pdo->beginTransaction();
                
                // Update plan names: medium -> starter, premium -> pro
                $migrations = [
                    'medium' => 'starter',
                    'premium' => 'pro'
                ];
                
                $totalUpdated = 0;
                
                foreach ($migrations as $oldPlan => $newPlan) {
                    $stmt = $this->pdo->prepare("UPDATE users SET plan = ?, updated_at = CURRENT_TIMESTAMP WHERE plan = ?");
                    $stmt->execute([$newPlan, $oldPlan]);
                    $updated = $stmt->rowCount();
                    $totalUpdated += $updated;
                    
                    if ($updated > 0) {
                        error_log("Database migration: Migrated {$updated} users from '{$oldPlan}' to '{$newPlan}'");
                    }
                }
                
                // Also update subscriptions table if it has records
                foreach ($migrations as $oldPlan => $newPlan) {
                    $stmt = $this->pdo->prepare("UPDATE subscriptions SET plan = ?, updated_at = CURRENT_TIMESTAMP WHERE plan = ?");
                    $stmt->execute([$newPlan, $oldPlan]);
                    $updated = $stmt->rowCount();
                    
                    if ($updated > 0) {
                        error_log("Database migration: Updated {$updated} subscription records from '{$oldPlan}' to '{$newPlan}'");
                    }
                }
                
                // Commit transaction
                $this->pdo->commit();
                
                error_log("Database migration: Plan name migration completed successfully! Total users updated: {$totalUpdated}");
                error_log("Database migration: New plan structure - free: 1 source, starter: 5 sources, pro: 15 sources, unlimited: unlimited sources");
            }
            
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log("Database migration error during plan name migration: " . $e->getMessage());
        }
    }
    
    private function populateSourceConfigs() {
        try {
            // Check if source_configs table is empty
            $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM source_configs");
            $result = $stmt->fetch();
            
            if ($result['count'] == 0) {
                error_log("Database migration: Populating source_configs table with default data");
                
                $defaultSources = [
                    [
                        'type' => 'bitcoin',
                        'name' => 'Bitcoin Price',
                        'description' => 'Track Bitcoin price and 24-hour changes',
                        'is_enabled' => 1,
                        'api_required' => 0,
                        'default_config' => json_encode([])
                    ],
                    [
                        'type' => 'sp500',
                        'name' => 'S&P 500 Index',
                        'description' => 'Monitor S&P 500 index performance and trends',
                        'is_enabled' => 1,
                        'api_required' => 0,
                        'default_config' => json_encode([])
                    ],
                    [
                        'type' => 'weather',
                        'name' => 'Weather',
                        'description' => 'Weather forecast using Norwegian Meteorological Institute',
                        'is_enabled' => 1,
                        'api_required' => 0,
                        'default_config' => json_encode(['city' => 'New York'])
                    ],
                    [
                        'type' => 'news',
                        'name' => 'News Headlines',
                        'description' => 'Top headlines from trusted news sources',
                        'is_enabled' => 1,
                        'api_required' => 0,
                        'default_config' => json_encode([])
                    ],
                    [
                        'type' => 'appstore',
                        'name' => 'App Store Sales',
                        'description' => 'App Store Connect revenue and sales tracking',
                        'is_enabled' => 1,
                        'api_required' => 1,
                        'default_config' => json_encode(['api_key' => '', 'app_id' => ''])
                    ],
                    [
                        'type' => 'stripe',
                        'name' => 'Stripe Revenue',
                        'description' => 'Track your Stripe payments and revenue',
                        'is_enabled' => 1,
                        'api_required' => 1,
                        'default_config' => json_encode(['api_key' => ''])
                    ]
                ];
                
                $stmt = $this->pdo->prepare("
                    INSERT INTO source_configs (type, name, description, is_enabled, api_required, default_config) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                
                foreach ($defaultSources as $source) {
                    $stmt->execute([
                        $source['type'],
                        $source['name'],
                        $source['description'],
                        $source['is_enabled'],
                        $source['api_required'],
                        $source['default_config']
                    ]);
                }
                
                error_log("Database migration: Successfully populated source_configs table with " . count($defaultSources) . " source types");
            }
            
        } catch (Exception $e) {
            error_log("Database migration error during source configs population: " . $e->getMessage());
        }
    }
    
    private function migrateToNewsletterStructure() {
        try {
            // First, check if newsletters table exists
            $stmt = $this->pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='newsletters'");
            $newslettersTableExists = $stmt->fetch() !== false;
            
            if (!$newslettersTableExists) {
                error_log("Database migration: Newsletters table doesn't exist yet, skipping migration");
                return;
            }
            
            // Check if we need to migrate by seeing if newsletters table is empty
            $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM newsletters");
            $newsletterCount = $stmt->fetch()['count'];
            
            // Also check if sources table still references user_id directly
            $stmt = $this->pdo->query("PRAGMA table_info(sources)");
            $columns = $stmt->fetchAll();
            $hasUserIdColumn = false;
            $hasNewsletterIdColumn = false;
            
            foreach ($columns as $column) {
                if ($column['name'] === 'user_id') {
                    $hasUserIdColumn = true;
                }
                if ($column['name'] === 'newsletter_id') {
                    $hasNewsletterIdColumn = true;
                }
            }
            
            // Only migrate if newsletters table is empty and we still have old structure
            if ($newsletterCount == 0 && $hasUserIdColumn && !$hasNewsletterIdColumn) {
                error_log("Database migration: Starting newsletter structure migration");
                
                // Begin transaction for safe migration
                $this->pdo->beginTransaction();
                
                try {
                    // Step 1: Get all users who need newsletters
                    $stmt = $this->pdo->query("
                        SELECT id, email, newsletter_title, send_time, timezone 
                        FROM users 
                        ORDER BY id
                    ");
                    $users = $stmt->fetchAll();
                    
                    $migratedUsers = 0;
                    $migratedSources = 0;
                    
                    foreach ($users as $user) {
                        // Generate unique unsubscribe token for newsletter
                        $unsubscribeToken = bin2hex(random_bytes(32));
                        
                        // Create newsletter for this user
                        $stmt = $this->pdo->prepare("
                            INSERT INTO newsletters (user_id, title, send_time, timezone, unsubscribe_token) 
                            VALUES (?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([
                            $user['id'],
                            $user['newsletter_title'] ?: 'Your Morning Brief',
                            $user['send_time'] ?: '06:00',
                            $user['timezone'] ?: 'UTC',
                            $unsubscribeToken
                        ]);
                        
                        $newsletterId = $this->pdo->lastInsertId();
                        
                        // Create newsletter recipient record (user subscribes to their own newsletter)
                        $recipientToken = bin2hex(random_bytes(32));
                        $stmt = $this->pdo->prepare("
                            INSERT INTO newsletter_recipients (newsletter_id, email, unsubscribe_token) 
                            VALUES (?, ?, ?)
                        ");
                        $stmt->execute([$newsletterId, $user['email'], $recipientToken]);
                        
                        // Migrate sources from user_id to newsletter_id
                        if ($hasUserIdColumn) {
                            $stmt = $this->pdo->prepare("
                                SELECT COUNT(*) as count FROM sources WHERE user_id = ?
                            ");
                            $stmt->execute([$user['id']]);
                            $userSourceCount = $stmt->fetch()['count'];
                            
                            if ($userSourceCount > 0) {
                                if ($hasNewsletterIdColumn) {
                                    // Update existing sources to reference newsletter
                                    $stmt = $this->pdo->prepare("
                                        UPDATE sources 
                                        SET newsletter_id = ? 
                                        WHERE user_id = ?
                                    ");
                                    $stmt->execute([$newsletterId, $user['id']]);
                                } else {
                                    // Add newsletter_id column and update
                                    $this->pdo->exec("ALTER TABLE sources ADD COLUMN newsletter_id INTEGER");
                                    $stmt = $this->pdo->prepare("
                                        UPDATE sources 
                                        SET newsletter_id = ? 
                                        WHERE user_id = ?
                                    ");
                                    $stmt->execute([$newsletterId, $user['id']]);
                                    $hasNewsletterIdColumn = true;
                                }
                                
                                $migratedSources += $userSourceCount;
                            }
                        }
                        
                        $migratedUsers++;
                    }
                    
                    // Step 2: Remove user_id column from sources if it exists and we have newsletter_id
                    if ($hasUserIdColumn && $hasNewsletterIdColumn) {
                        // SQLite doesn't support DROP COLUMN, so we need to recreate the table
                        $this->pdo->exec("
                            CREATE TABLE sources_new (
                                id INTEGER PRIMARY KEY AUTOINCREMENT,
                                newsletter_id INTEGER NOT NULL,
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
                        
                        // Copy data to new table
                        $this->pdo->exec("
                            INSERT INTO sources_new 
                            (id, newsletter_id, type, name, config, sort_order, is_active, last_result, last_updated, created_at)
                            SELECT id, newsletter_id, type, name, config, sort_order, is_active, last_result, last_updated, created_at
                            FROM sources
                        ");
                        
                        // Replace old table
                        $this->pdo->exec("DROP TABLE sources");
                        $this->pdo->exec("ALTER TABLE sources_new RENAME TO sources");
                        
                        // Recreate index
                        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_sources_newsletter_id ON sources(newsletter_id)");
                    }
                    
                    // Step 3: Update email_logs table to include newsletter_id and recipient_email
                    $stmt = $this->pdo->query("PRAGMA table_info(email_logs)");
                    $emailLogColumns = $stmt->fetchAll();
                    
                    $hasNewsletterIdInLogs = false;
                    $hasRecipientEmail = false;
                    foreach ($emailLogColumns as $column) {
                        if ($column['name'] === 'newsletter_id') {
                            $hasNewsletterIdInLogs = true;
                        }
                        if ($column['name'] === 'recipient_email') {
                            $hasRecipientEmail = true;
                        }
                    }
                    
                    if (!$hasNewsletterIdInLogs || !$hasRecipientEmail) {
                        // Recreate email_logs table with new structure
                        $this->pdo->exec("
                            CREATE TABLE email_logs_new (
                                id INTEGER PRIMARY KEY AUTOINCREMENT,
                                user_id INTEGER NOT NULL,
                                newsletter_id INTEGER,
                                recipient_email TEXT,
                                status TEXT NOT NULL,
                                error_message TEXT,
                                sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                                FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
                                FOREIGN KEY (newsletter_id) REFERENCES newsletters (id) ON DELETE SET NULL
                            )
                        ");
                        
                        // Copy existing data
                        $this->pdo->exec("
                            INSERT INTO email_logs_new (id, user_id, status, error_message, sent_at)
                            SELECT id, user_id, status, error_message, sent_at
                            FROM email_logs
                        ");
                        
                        // Replace table
                        $this->pdo->exec("DROP TABLE email_logs");
                        $this->pdo->exec("ALTER TABLE email_logs_new RENAME TO email_logs");
                        
                        // Recreate indexes
                        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_email_logs_user_id ON email_logs(user_id)");
                        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_email_logs_newsletter_id ON email_logs(newsletter_id)");
                        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_email_logs_sent_at ON email_logs(sent_at)");
                        
                        error_log("Database migration: Updated email_logs table structure");
                    }
                    
                    // Commit transaction
                    $this->pdo->commit();
                    
                    error_log("Database migration: Newsletter structure migration completed successfully!");
                    error_log("Database migration: Migrated $migratedUsers users to newsletter structure");
                    error_log("Database migration: Migrated $migratedSources sources to newsletter references");
                    
                } catch (Exception $e) {
                    $this->pdo->rollBack();
                    throw $e;
                }
                
            } else {
                if ($newsletterCount > 0) {
                    error_log("Database migration: Newsletter structure already migrated (found $newsletterCount newsletters)");
                } else {
                    error_log("Database migration: No newsletter migration needed (new installation)");
                }
            }
            
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log("Database migration error during newsletter structure migration: " . $e->getMessage());
        }
    }
}