<?php
/**
 * Database Reset Script
 * This will completely reset the database to a clean state
 * Use this if you're having migration issues
 */

header('Content-Type: text/plain');

echo "MorningNewsletter Database Reset\n";
echo "===============================\n\n";

$dbPath = __DIR__ . '/data/newsletter.db';
$dataDir = __DIR__ . '/data';

echo "Step 1: Checking current database...\n";

// Check if database file exists
if (file_exists($dbPath)) {
    echo "- Found existing database at: $dbPath\n";
    echo "- Database size: " . filesize($dbPath) . " bytes\n";
} else {
    echo "- No existing database found\n";
}

echo "\nStep 2: Backing up current database (if exists)...\n";

// Create backup if database exists
if (file_exists($dbPath)) {
    $backupPath = $dbPath . '.backup.' . date('Y-m-d-H-i-s');
    if (copy($dbPath, $backupPath)) {
        echo "✅ Backup created: $backupPath\n";
    } else {
        echo "⚠️  Could not create backup\n";
    }
}

echo "\nStep 3: Removing old database...\n";

// Remove old database
if (file_exists($dbPath)) {
    if (unlink($dbPath)) {
        echo "✅ Old database removed\n";
    } else {
        echo "❌ Could not remove old database - check file permissions\n";
        exit(1);
    }
} else {
    echo "- No old database to remove\n";
}

echo "\nStep 4: Creating fresh database structure...\n";

try {
    // Create data directory if it doesn't exist
    if (!is_dir($dataDir)) {
        if (mkdir($dataDir, 0755, true)) {
            echo "✅ Created data directory\n";
        } else {
            echo "❌ Could not create data directory\n";
            exit(1);
        }
    }

    // Create new database connection
    $pdo = new PDO("sqlite:$dbPath");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    echo "✅ Database connection established\n";

    // Create basic tables first (without newsletter dependencies)
    $basicTables = [
        "CREATE TABLE users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            email TEXT UNIQUE NOT NULL,
            name TEXT,
            password_hash TEXT NOT NULL,
            plan TEXT DEFAULT 'free',
            timezone TEXT DEFAULT 'UTC',
            send_time TEXT DEFAULT '06:00',
            newsletter_title TEXT DEFAULT 'Your Morning Brief',
            email_verified INTEGER DEFAULT 0,
            verification_token TEXT,
            is_admin INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )",
        
        "CREATE TABLE sources (
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
        
        "CREATE TABLE subscriptions (
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
        
        "CREATE TABLE payments (
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
        
        "CREATE TABLE email_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            status TEXT NOT NULL,
            error_message TEXT,
            sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
        )",
        
        "CREATE TABLE source_configs (
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

    foreach ($basicTables as $sql) {
        $pdo->exec($sql);
    }
    
    echo "✅ Basic tables created\n";

    // Create indexes
    $indexes = [
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

    foreach ($indexes as $sql) {
        $pdo->exec($sql);
    }
    
    echo "✅ Indexes created\n";

    // Populate source configs
    $defaultSources = [
        ['bitcoin', 'Bitcoin Price', 'Track Bitcoin price and 24-hour changes', 1, 0, '[]'],
        ['sp500', 'S&P 500 Index', 'Monitor S&P 500 index performance and trends', 1, 0, '[]'],
        ['weather', 'Weather', 'Weather forecast using Norwegian Meteorological Institute', 1, 0, '{"city":"New York"}'],
        ['news', 'News Headlines', 'Top headlines from trusted news sources', 1, 0, '[]'],
        ['appstore', 'App Store Sales', 'App Store Connect revenue and sales tracking', 1, 1, '{"api_key":"","app_id":""}'],
        ['stripe', 'Stripe Revenue', 'Track your Stripe payments and revenue', 1, 1, '{"api_key":""}']
    ];

    $stmt = $pdo->prepare("INSERT INTO source_configs (type, name, description, is_enabled, api_required, default_config) VALUES (?, ?, ?, ?, ?, ?)");
    
    foreach ($defaultSources as $source) {
        $stmt->execute($source);
    }
    
    echo "✅ Source configurations populated\n";

    echo "\nStep 5: Database reset completed successfully!\n";
    echo "\n✅ Your database is now ready to use\n";
    echo "✅ You can access your dashboard\n";
    echo "✅ If you had existing data, it's backed up\n";
    
    echo "\nNext steps:\n";
    echo "1. Go to your dashboard\n";
    echo "2. Create a new account or log in\n";
    echo "3. The system will work with the standard user-based structure\n";
    echo "4. Newsletter migration can be done later if needed\n";

} catch (Exception $e) {
    echo "❌ Database reset failed: " . $e->getMessage() . "\n";
    echo "\nPlease check:\n";
    echo "1. File permissions on the data directory\n";
    echo "2. SQLite is installed and working\n";
    echo "3. Disk space is available\n";
}
?>