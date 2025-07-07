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
        // Create basic tables first (user-centric model)
        $basicQueries = [
            "CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                email TEXT UNIQUE NOT NULL,
                name TEXT,
                password_hash TEXT NOT NULL,
                plan TEXT DEFAULT 'free',
                timezone TEXT DEFAULT 'UTC',
                email_verified INTEGER DEFAULT 0,
                unsubscribed INTEGER DEFAULT 0,
                send_time TEXT DEFAULT '06:00',
                newsletter_title TEXT DEFAULT 'Your Morning Brief',
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
                sort_order INTEGER DEFAULT 0,
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
            )"
        ];
        
        // Execute basic table creation
        foreach ($basicQueries as $query) {
            $this->pdo->exec($query);
        }
        
        // Create basic indexes
        $basicIndexes = [
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
        
        foreach ($basicIndexes as $query) {
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
            
            // Check if unsubscribed column exists in users table, if not add it
            $stmt = $this->pdo->query("PRAGMA table_info(users)");
            $userColumns = $stmt->fetchAll();
            
            $hasUnsubscribedColumn = false;
            foreach ($userColumns as $column) {
                if ($column['name'] === 'unsubscribed') {
                    $hasUnsubscribedColumn = true;
                    break;
                }
            }
            
            if (!$hasUnsubscribedColumn) {
                $this->pdo->exec("ALTER TABLE users ADD COLUMN unsubscribed INTEGER DEFAULT 0");
                error_log("Database migration: Added 'unsubscribed' column to users table");
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
            
            // Populate source configs table with default data
            $this->populateSourceConfigs();
            
            // Set up default admin user
            $this->setupDefaultAdmin();
            
        } catch (Exception $e) {
            error_log("Database migration error: " . $e->getMessage());
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
    
    private function setupDefaultAdmin() {
        try {
            $adminEmail = 'manuelescrig@gmail.com';
            
            // Check if this user exists and promote to admin
            $stmt = $this->pdo->prepare("SELECT id, is_admin FROM users WHERE email = ?");
            $stmt->execute([$adminEmail]);
            $user = $stmt->fetch();
            
            if ($user) {
                if (!$user['is_admin']) {
                    // User exists but is not admin, promote them
                    $stmt = $this->pdo->prepare("UPDATE users SET is_admin = 1, updated_at = CURRENT_TIMESTAMP WHERE email = ?");
                    $stmt->execute([$adminEmail]);
                    error_log("Database setup: Promoted $adminEmail to admin");
                } else {
                    error_log("Database setup: $adminEmail is already an admin");
                }
            } else {
                error_log("Database setup: Admin user $adminEmail not found - will be promoted when account is created");
            }
            
        } catch (Exception $e) {
            error_log("Database setup error during admin promotion: " . $e->getMessage());
        }
    }
}