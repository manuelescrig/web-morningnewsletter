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
                verification_token TEXT,
                is_admin INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )",
            
            "CREATE TABLE IF NOT EXISTS sources (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                type TEXT NOT NULL,
                name TEXT,
                config TEXT,
                is_active INTEGER DEFAULT 1,
                last_result TEXT,
                last_updated DATETIME,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
            )",
            
            "CREATE TABLE IF NOT EXISTS email_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                status TEXT NOT NULL,
                error_message TEXT,
                sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
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
            "CREATE INDEX IF NOT EXISTS idx_sources_user_id ON sources(user_id)",
            "CREATE INDEX IF NOT EXISTS idx_email_logs_user_id ON email_logs(user_id)",
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
        
        // Add missing columns to existing tables (migrations)
        $this->runMigrations();
    }
    
    private function runMigrations() {
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
            
            // Migrate plan names from old system to new system
            $this->migratePlanNames();
            
            // Populate source configs table with default data
            $this->populateSourceConfigs();
            
        } catch (Exception $e) {
            error_log("Database migration error: " . $e->getMessage());
        }
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
}