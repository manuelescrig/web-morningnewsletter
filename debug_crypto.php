<?php
// Simple test to debug crypto API calls
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/NewsletterBuilder.php';
require_once __DIR__ . '/core/Newsletter.php';

$auth = Auth::getInstance();
$auth->requireAuth();
$user = $auth->getCurrentUser();

// Get first newsletter
$newsletters = Newsletter::findByUser($user->getId());
if (empty($newsletters)) {
    die("No newsletters found");
}
$newsletter = $newsletters[0];

echo "Testing crypto API calls...\n";
echo "Working directory: " . getcwd() . "\n";
echo "PHP SAPI: " . php_sapi_name() . "\n";

// Test the newsletter builder
$builder = new NewsletterBuilder($newsletter, $user);
$result = $builder->buildWithSourceData();

echo "Newsletter built successfully!\n";
echo "Source data count: " . count($result['sources_data']) . "\n";

foreach ($result['sources_data'] as $source) {
    echo "Source: " . $source['title'] . "\n";
    echo "Type: " . $source['type'] . "\n";
    echo "Data items: " . count($source['data']) . "\n";
    
    foreach ($source['data'] as $item) {
        echo "  - " . $item['label'] . ": " . $item['value'] . "\n";
    }
    echo "\n";
}
?>