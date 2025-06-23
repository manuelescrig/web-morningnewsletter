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
            
            "CREATE INDEX IF NOT EXISTS idx_users_email ON users(email)",
            "CREATE INDEX IF NOT EXISTS idx_sources_user_id ON sources(user_id)",
            "CREATE INDEX IF NOT EXISTS idx_email_logs_user_id ON email_logs(user_id)",
            "CREATE INDEX IF NOT EXISTS idx_email_logs_sent_at ON email_logs(sent_at)",
            "CREATE INDEX IF NOT EXISTS idx_subscriptions_user_id ON subscriptions(user_id)",
            "CREATE INDEX IF NOT EXISTS idx_subscriptions_stripe_id ON subscriptions(stripe_subscription_id)",
            "CREATE INDEX IF NOT EXISTS idx_payments_user_id ON payments(user_id)",
            "CREATE INDEX IF NOT EXISTS idx_payments_stripe_id ON payments(stripe_payment_intent_id)"
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
            
        } catch (Exception $e) {
            error_log("Database migration error: " . $e->getMessage());
        }
    }
}