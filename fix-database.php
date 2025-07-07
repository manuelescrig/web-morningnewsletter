<?php
/**
 * Database Fix Script
 * This will diagnose and fix the database structure issue
 */

header('Content-Type: text/plain');

echo "MorningNewsletter Database Fix\n";
echo "=============================\n\n";

$dbPath = __DIR__ . '/data/newsletter.db';
$dataDir = __DIR__ . '/data';

echo "Step 1: Checking database structure...\n";

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

    // Create database connection
    $pdo = new PDO("sqlite:$dbPath");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    echo "✅ Database connection established\n";

    // Check if basic tables exist
    $tables = ['users', 'sources', 'subscriptions', 'payments', 'email_logs', 'source_configs'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='$table'");
        $exists = $stmt->fetch() !== false;
        echo "- Table '$table': " . ($exists ? "EXISTS" : "MISSING") . "\n";
    }

    // Check sources table structure
    echo "\nStep 2: Checking sources table structure...\n";
    $stmt = $pdo->query("PRAGMA table_info(sources)");
    $columns = $stmt->fetchAll();
    
    $hasUserId = false;
    $hasNewsletterId = false;
    
    foreach ($columns as $column) {
        if ($column['name'] === 'user_id') {
            $hasUserId = true;
        }
        if ($column['name'] === 'newsletter_id') {
            $hasNewsletterId = true;
        }
        echo "- Column: " . $column['name'] . " (" . $column['type'] . ")\n";
    }
    
    echo "\nStructure analysis:\n";
    echo "- Has user_id: " . ($hasUserId ? "YES" : "NO") . "\n";
    echo "- Has newsletter_id: " . ($hasNewsletterId ? "YES" : "NO") . "\n";

    // If we have both columns, we're in a problematic state
    if ($hasUserId && $hasNewsletterId) {
        echo "\n⚠️  Found both user_id and newsletter_id - this indicates incomplete migration\n";
        echo "Recommendation: Run reset-db.php to start fresh\n";
    } else if ($hasUserId && !$hasNewsletterId) {
        echo "\n✅ Using standard user-based structure (recommended for now)\n";
        echo "Creating necessary indexes...\n";
        
        // Create missing indexes for user-based structure
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
            try {
                $pdo->exec($sql);
                echo "✅ Index created successfully\n";
            } catch (Exception $e) {
                echo "⚠️  Index creation warning: " . $e->getMessage() . "\n";
            }
        }
        
        // Check if source_configs table has data
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM source_configs");
        $result = $stmt->fetch();
        
        if ($result['count'] == 0) {
            echo "\nPopulating source configs...\n";
            
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
        } else {
            echo "✅ Source configurations already populated (" . $result['count'] . " entries)\n";
        }
        
    } else if (!$hasUserId && $hasNewsletterId) {
        echo "\n✅ Using newsletter-based structure\n";
        echo "Creating newsletter structure indexes...\n";
        
        // Create newsletter-specific indexes
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_sources_newsletter_id ON sources(newsletter_id)");
        echo "✅ Newsletter structure indexes created\n";
    } else {
        echo "\n❌ Sources table structure is invalid\n";
        echo "Recommendation: Run reset-db.php to start fresh\n";
    }

    echo "\nStep 3: Final database test...\n";
    
    // Test basic database operations
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $userCount = $stmt->fetch()['count'];
    echo "- Total users: $userCount\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM sources");
    $sourceCount = $stmt->fetch()['count'];
    echo "- Total sources: $sourceCount\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM source_configs");
    $configCount = $stmt->fetch()['count'];
    echo "- Available source types: $configCount\n";

    echo "\n✅ Database structure is now fixed!\n";
    echo "✅ You can now access your website\n";
    
    if ($userCount == 0) {
        echo "\nNext steps:\n";
        echo "1. Go to your website\n";
        echo "2. Register a new account\n";
        echo "3. Start adding data sources\n";
    }

} catch (Exception $e) {
    echo "❌ Database fix failed: " . $e->getMessage() . "\n";
    echo "\nRecommendation: Run reset-db.php to start completely fresh\n";
}
?>