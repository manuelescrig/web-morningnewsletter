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
            
            "CREATE TABLE IF NOT EXISTS newsletters (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                title TEXT NOT NULL,
                timezone TEXT DEFAULT 'UTC',
                send_time TEXT DEFAULT '06:00',
                is_active INTEGER DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
            )",
            
            "CREATE TABLE IF NOT EXISTS sources (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                newsletter_id INTEGER NOT NULL,
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
                category TEXT DEFAULT 'general',
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
            "CREATE INDEX IF NOT EXISTS idx_newsletters_user_id ON newsletters(user_id)",
            "CREATE INDEX IF NOT EXISTS idx_sources_user_id ON sources(user_id)",
            "CREATE INDEX IF NOT EXISTS idx_sources_newsletter_id ON sources(newsletter_id)",
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
            
            // Check if discovery_source column exists in users table, if not add it
            $hasDiscoverySourceColumn = false;
            foreach ($userColumns as $column) {
                if ($column['name'] === 'discovery_source') {
                    $hasDiscoverySourceColumn = true;
                    break;
                }
            }
            
            if (!$hasDiscoverySourceColumn) {
                $this->pdo->exec("ALTER TABLE users ADD COLUMN discovery_source TEXT");
                error_log("Database migration: Added 'discovery_source' column to users table");
            }
            
            // Check if newsletter_id column exists in sources table, if not add it
            $stmt = $this->pdo->query("PRAGMA table_info(sources)");
            $sourceColumns = $stmt->fetchAll();
            
            $hasNewsletterIdColumn = false;
            foreach ($sourceColumns as $column) {
                if ($column['name'] === 'newsletter_id') {
                    $hasNewsletterIdColumn = true;
                    break;
                }
            }
            
            if (!$hasNewsletterIdColumn) {
                $this->pdo->exec("ALTER TABLE sources ADD COLUMN newsletter_id INTEGER");
                error_log("Database migration: Added 'newsletter_id' column to sources table");
                
                // Create default newsletters for existing users and link their sources
                $this->migrateToNewsletterModel();
            }
            
            // Populate source configs table with default data
            $this->populateSourceConfigs();
            
            // Ensure all source types are in database and add category column
            $this->ensureAllSourceTypesExist();
            
            // Run newsletter history and scheduling migrations
            $this->runNewsletterHistoryMigrations();
            
            // Set up default admin user
            $this->setupDefaultAdmin();
            
        } catch (Exception $e) {
            error_log("Database migration error: " . $e->getMessage());
        }
    }
    
    private function migrateToNewsletterModel() {
        try {
            // Get all users who have sources but no newsletter
            $stmt = $this->pdo->query("
                SELECT DISTINCT u.id, u.newsletter_title, u.timezone, u.send_time
                FROM users u
                LEFT JOIN newsletters n ON u.id = n.user_id
                LEFT JOIN sources s ON u.id = s.user_id
                WHERE n.id IS NULL AND s.id IS NOT NULL
            ");
            
            $usersToMigrate = $stmt->fetchAll();
            
            foreach ($usersToMigrate as $user) {
                // Create default newsletter for this user
                $insertStmt = $this->pdo->prepare("
                    INSERT INTO newsletters (user_id, title, timezone, send_time)
                    VALUES (?, ?, ?, ?)
                ");
                
                $insertStmt->execute([
                    $user['id'],
                    $user['newsletter_title'] ?? 'My Morning Brief',
                    $user['timezone'] ?? 'UTC',
                    $user['send_time'] ?? '06:00'
                ]);
                
                $newsletterId = $this->pdo->lastInsertId();
                
                // Update all sources for this user to link to the new newsletter
                $updateStmt = $this->pdo->prepare("
                    UPDATE sources 
                    SET newsletter_id = ?
                    WHERE user_id = ? AND newsletter_id IS NULL
                ");
                
                $updateStmt->execute([$newsletterId, $user['id']]);
                
                error_log("Database migration: Created default newsletter (ID: {$newsletterId}) for user {$user['id']} and linked existing sources");
            }
            
            // Also create default newsletters for users who have no sources
            $stmt = $this->pdo->query("
                SELECT u.id, u.newsletter_title, u.timezone, u.send_time
                FROM users u
                LEFT JOIN newsletters n ON u.id = n.user_id
                WHERE n.id IS NULL
            ");
            
            $usersWithoutNewsletters = $stmt->fetchAll();
            
            foreach ($usersWithoutNewsletters as $user) {
                $insertStmt = $this->pdo->prepare("
                    INSERT INTO newsletters (user_id, title, timezone, send_time)
                    VALUES (?, ?, ?, ?)
                ");
                
                $insertStmt->execute([
                    $user['id'],
                    $user['newsletter_title'] ?? 'My Morning Brief',
                    $user['timezone'] ?? 'UTC',
                    $user['send_time'] ?? '06:00'
                ]);
                
                $newsletterId = $this->pdo->lastInsertId();
                error_log("Database migration: Created default newsletter (ID: {$newsletterId}) for user {$user['id']}");
            }
            
        } catch (Exception $e) {
            error_log("Database migration error during newsletter model migration: " . $e->getMessage());
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
                        'category' => 'crypto',
                        'is_enabled' => 1,
                        'api_required' => 0,
                        'default_config' => json_encode([])
                    ],
                    [
                        'type' => 'ethereum',
                        'name' => 'Ethereum Price',
                        'description' => 'Track Ethereum price and market performance',
                        'category' => 'crypto',
                        'is_enabled' => 1,
                        'api_required' => 0,
                        'default_config' => json_encode([])
                    ],
                    [
                        'type' => 'xrp',
                        'name' => 'XRP Price',
                        'description' => 'Track XRP (Ripple) price and market trends',
                        'category' => 'crypto',
                        'is_enabled' => 1,
                        'api_required' => 0,
                        'default_config' => json_encode([])
                    ],
                    [
                        'type' => 'binancecoin',
                        'name' => 'Binance Coin Price',
                        'description' => 'Track BNB (Binance Coin) price and performance',
                        'category' => 'crypto',
                        'is_enabled' => 1,
                        'api_required' => 0,
                        'default_config' => json_encode([])
                    ],
                    [
                        'type' => 'sp500',
                        'name' => 'S&P 500 Index',
                        'description' => 'Monitor S&P 500 index performance and trends',
                        'category' => 'finance',
                        'is_enabled' => 1,
                        'api_required' => 0,
                        'default_config' => json_encode([])
                    ],
                    [
                        'type' => 'weather',
                        'name' => 'Weather',
                        'description' => 'Weather forecast using Norwegian Meteorological Institute',
                        'category' => 'lifestyle',
                        'is_enabled' => 1,
                        'api_required' => 0,
                        'default_config' => json_encode(['city' => 'New York'])
                    ],
                    [
                        'type' => 'news',
                        'name' => 'News Headlines',
                        'description' => 'Top headlines from trusted news sources',
                        'category' => 'news',
                        'is_enabled' => 1,
                        'api_required' => 0,
                        'default_config' => json_encode([])
                    ],
                    [
                        'type' => 'appstore',
                        'name' => 'App Store Sales',
                        'description' => 'App Store Connect revenue and sales tracking',
                        'category' => 'business',
                        'is_enabled' => 1,
                        'api_required' => 1,
                        'default_config' => json_encode(['api_key' => '', 'app_id' => ''])
                    ],
                    [
                        'type' => 'stripe',
                        'name' => 'Stripe Revenue',
                        'description' => 'Track your Stripe payments and revenue',
                        'category' => 'business',
                        'is_enabled' => 1,
                        'api_required' => 1,
                        'default_config' => json_encode(['api_key' => ''])
                    ]
                ];
                
                $stmt = $this->pdo->prepare("
                    INSERT INTO source_configs (type, name, description, category, is_enabled, api_required, default_config) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                
                foreach ($defaultSources as $source) {
                    $stmt->execute([
                        $source['type'],
                        $source['name'],
                        $source['description'],
                        $source['category'],
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
    
    private function ensureAllSourceTypesExist() {
        try {
            // Add category column if it doesn't exist
            $stmt = $this->pdo->query("PRAGMA table_info(source_configs)");
            $columns = $stmt->fetchAll();
            $hasCategoryColumn = false;
            
            foreach ($columns as $column) {
                if ($column['name'] === 'category') {
                    $hasCategoryColumn = true;
                    break;
                }
            }
            
            if (!$hasCategoryColumn) {
                $this->pdo->exec("ALTER TABLE source_configs ADD COLUMN category TEXT DEFAULT 'general'");
                error_log("Database migration: Added 'category' column to source_configs table");
            }
            
            // Define all available source types with their details
            $allSourceTypes = [
                [
                    'type' => 'bitcoin',
                    'name' => 'Bitcoin Price',
                    'description' => 'Track Bitcoin price and 24-hour changes',
                    'category' => 'crypto',
                    'is_enabled' => 1,
                    'api_required' => 0,
                    'default_config' => json_encode([])
                ],
                [
                    'type' => 'ethereum',
                    'name' => 'Ethereum Price',
                    'description' => 'Track Ethereum price and market performance',
                    'category' => 'crypto',
                    'is_enabled' => 1,
                    'api_required' => 0,
                    'default_config' => json_encode([])
                ],
                [
                    'type' => 'xrp',
                    'name' => 'XRP Price',
                    'description' => 'Track XRP (Ripple) price and market trends',
                    'category' => 'crypto',
                    'is_enabled' => 1,
                    'api_required' => 0,
                    'default_config' => json_encode([])
                ],
                [
                    'type' => 'binancecoin',
                    'name' => 'Binance Coin Price',
                    'description' => 'Track BNB (Binance Coin) price and performance',
                    'category' => 'crypto',
                    'is_enabled' => 1,
                    'api_required' => 0,
                    'default_config' => json_encode([])
                ],
                [
                    'type' => 'sp500',
                    'name' => 'S&P 500 Index',
                    'description' => 'Monitor S&P 500 index performance and trends',
                    'category' => 'finance',
                    'is_enabled' => 1,
                    'api_required' => 0,
                    'default_config' => json_encode([])
                ],
                [
                    'type' => 'weather',
                    'name' => 'Weather',
                    'description' => 'Weather forecast using Norwegian Meteorological Institute',
                    'category' => 'lifestyle',
                    'is_enabled' => 1,
                    'api_required' => 0,
                    'default_config' => json_encode(['city' => 'New York'])
                ],
                [
                    'type' => 'news',
                    'name' => 'News Headlines',
                    'description' => 'Top headlines from trusted news sources',
                    'category' => 'news',
                    'is_enabled' => 1,
                    'api_required' => 0,
                    'default_config' => json_encode([])
                ],
                [
                    'type' => 'appstore',
                    'name' => 'App Store Sales',
                    'description' => 'App Store Connect revenue and sales tracking',
                    'category' => 'business',
                    'is_enabled' => 1,
                    'api_required' => 1,
                    'default_config' => json_encode(['api_key' => '', 'app_id' => ''])
                ],
                [
                    'type' => 'stripe',
                    'name' => 'Stripe Revenue',
                    'description' => 'Track your Stripe payments and revenue',
                    'category' => 'business',
                    'is_enabled' => 1,
                    'api_required' => 1,
                    'default_config' => json_encode(['api_key' => ''])
                ]
            ];
            
            // Check which source types already exist
            $stmt = $this->pdo->query("SELECT type FROM source_configs");
            $existingTypes = array_column($stmt->fetchAll(), 'type');
            
            // Insert missing source types
            $insertStmt = $this->pdo->prepare("
                INSERT INTO source_configs (type, name, description, category, is_enabled, api_required, default_config) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $addedCount = 0;
            foreach ($allSourceTypes as $source) {
                if (!in_array($source['type'], $existingTypes)) {
                    $insertStmt->execute([
                        $source['type'],
                        $source['name'],
                        $source['description'],
                        $source['category'],
                        $source['is_enabled'],
                        $source['api_required'],
                        $source['default_config']
                    ]);
                    $addedCount++;
                    error_log("Database migration: Added missing source type '{$source['type']}'");
                }
            }
            
            // Update existing records to set category if it's null or empty
            if ($hasCategoryColumn) {
                $updateStmt = $this->pdo->prepare("UPDATE source_configs SET category = ? WHERE type = ? AND (category IS NULL OR category = '')");
                foreach ($allSourceTypes as $source) {
                    if (in_array($source['type'], $existingTypes)) {
                        $updateStmt->execute([$source['category'], $source['type']]);
                    }
                }
                error_log("Database migration: Updated categories for existing source types");
            }
            
            if ($addedCount > 0) {
                error_log("Database migration: Successfully added $addedCount missing source types");
            }
            
        } catch (Exception $e) {
            error_log("Database migration error during source types check: " . $e->getMessage());
        }
    }
    
    private function runNewsletterHistoryMigrations() {
        try {
            // Create newsletter_history table
            $this->pdo->exec("CREATE TABLE IF NOT EXISTS newsletter_history (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                newsletter_id INTEGER NOT NULL,
                user_id INTEGER NOT NULL,
                title TEXT NOT NULL,
                content TEXT NOT NULL,
                sources_data TEXT,
                sent_at DATETIME NOT NULL,
                email_sent INTEGER DEFAULT 1,
                issue_number INTEGER DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (newsletter_id) REFERENCES newsletters(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )");
            
            // Create indexes for newsletter_history
            $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_newsletter_history_newsletter_id ON newsletter_history(newsletter_id)");
            $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_newsletter_history_user_id ON newsletter_history(user_id)");
            $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_newsletter_history_sent_at ON newsletter_history(sent_at)");
            
            // Check and add scheduling fields to newsletters table
            $stmt = $this->pdo->query("PRAGMA table_info(newsletters)");
            $columns = $stmt->fetchAll();
            $existingColumns = array_column($columns, 'name');
            
            $schedulingFields = [
                'frequency' => 'ALTER TABLE newsletters ADD COLUMN frequency TEXT DEFAULT \'daily\'',
                'days_of_week' => 'ALTER TABLE newsletters ADD COLUMN days_of_week TEXT DEFAULT \'\'',
                'day_of_month' => 'ALTER TABLE newsletters ADD COLUMN day_of_month INTEGER DEFAULT 1',
                'months' => 'ALTER TABLE newsletters ADD COLUMN months TEXT DEFAULT \'\'',
                'daily_times' => 'ALTER TABLE newsletters ADD COLUMN daily_times TEXT DEFAULT \'\'',
                'is_paused' => 'ALTER TABLE newsletters ADD COLUMN is_paused INTEGER DEFAULT 0'
            ];
            
            foreach ($schedulingFields as $fieldName => $alterQuery) {
                if (!in_array($fieldName, $existingColumns)) {
                    $this->pdo->exec($alterQuery);
                    error_log("Database migration: Added '$fieldName' column to newsletters table");
                }
            }
            
            // Check and add newsletter_id to email_logs if it doesn't exist
            $stmt = $this->pdo->query("PRAGMA table_info(email_logs)");
            $emailLogColumns = $stmt->fetchAll();
            $emailLogColumnNames = array_column($emailLogColumns, 'name');
            
            if (!in_array('newsletter_id', $emailLogColumnNames)) {
                $this->pdo->exec("ALTER TABLE email_logs ADD COLUMN newsletter_id INTEGER");
                $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_email_logs_newsletter_id ON email_logs(newsletter_id)");
                error_log("Database migration: Added 'newsletter_id' column to email_logs table");
            }
            
            if (!in_array('history_id', $emailLogColumnNames)) {
                $this->pdo->exec("ALTER TABLE email_logs ADD COLUMN history_id INTEGER");
                $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_email_logs_history_id ON email_logs(history_id)");
                error_log("Database migration: Added 'history_id' column to email_logs table");
            }
            
            // Check and add scheduled_send_time to newsletter_history if it doesn't exist
            $stmt = $this->pdo->query("PRAGMA table_info(newsletter_history)");
            $historyColumns = $stmt->fetchAll();
            $historyColumnNames = array_column($historyColumns, 'name');
            
            if (!in_array('scheduled_send_time', $historyColumnNames)) {
                $this->pdo->exec("ALTER TABLE newsletter_history ADD COLUMN scheduled_send_time TIME");
                error_log("Database migration: Added 'scheduled_send_time' column to newsletter_history table");
            }
            
            error_log("Database migration: Newsletter history and scheduling migrations completed successfully");
            
        } catch (Exception $e) {
            error_log("Database migration error during newsletter history migrations: " . $e->getMessage());
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