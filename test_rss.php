<?php
// RSS Feed Test Script
header('Content-Type: text/plain');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/modules/rss.php';

echo "=== RSS FEED TEST ===\n\n";

// Test feed URL (you can change this to test your specific feed)
$testFeedUrl = isset($_GET['url']) ? $_GET['url'] : 'https://rss.nytimes.com/services/xml/rss/nyt/HomePage.xml';

echo "Testing RSS feed: $testFeedUrl\n\n";

// Test 1: Check if URL is valid
echo "1. URL Validation:\n";
if (filter_var($testFeedUrl, FILTER_VALIDATE_URL)) {
    echo "✓ URL is valid\n\n";
} else {
    echo "✗ URL is invalid\n\n";
}

// Test 2: Try to fetch the feed directly
echo "2. Direct Fetch Test:\n";
try {
    // Check cURL availability
    echo "cURL available: " . (extension_loaded('curl') ? 'Yes' : 'No') . "\n";
    echo "allow_url_fopen: " . (ini_get('allow_url_fopen') ? 'Yes' : 'No') . "\n\n";
    
    // Try with cURL
    if (extension_loaded('curl')) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $testFeedUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_USERAGENT => 'MorningNewsletter/1.0'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($response === false) {
            echo "✗ cURL Error: $error\n\n";
        } else {
            echo "✓ cURL Success - HTTP Code: $httpCode\n";
            echo "  Response size: " . strlen($response) . " bytes\n\n";
        }
    }
    
    // Try with file_get_contents
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 10,
            'user_agent' => 'MorningNewsletter/1.0'
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false
        ]
    ]);
    
    $response2 = @file_get_contents($testFeedUrl, false, $context);
    if ($response2 === false) {
        $error = error_get_last();
        echo "✗ file_get_contents Error: " . ($error ? $error['message'] : 'Unknown') . "\n\n";
    } else {
        echo "✓ file_get_contents Success\n";
        echo "  Response size: " . strlen($response2) . " bytes\n\n";
    }
    
} catch (Exception $e) {
    echo "✗ Fetch Error: " . $e->getMessage() . "\n\n";
}

// Test 3: Try to parse the feed
echo "3. XML Parsing Test:\n";
if (isset($response) && $response !== false) {
    $xml = @simplexml_load_string($response);
    if ($xml === false) {
        echo "✗ Failed to parse XML\n";
        $errors = libxml_get_errors();
        foreach ($errors as $error) {
            echo "  - " . $error->message;
        }
        echo "\n";
    } else {
        echo "✓ XML parsed successfully\n";
        
        // Check feed type
        if (isset($xml->channel->item)) {
            echo "  Feed type: RSS 2.0\n";
            echo "  Title: " . (string)$xml->channel->title . "\n";
            echo "  Items: " . count($xml->channel->item) . "\n";
        } elseif (isset($xml->entry)) {
            echo "  Feed type: Atom\n";
            echo "  Title: " . (string)$xml->title . "\n";
            echo "  Items: " . count($xml->entry) . "\n";
        } else {
            echo "  Feed type: Unknown\n";
        }
        echo "\n";
    }
}

// Test 4: Test the RSS module
echo "4. RSS Module Test:\n";
try {
    $config = [
        'feed_url' => $testFeedUrl,
        'item_limit' => '3',
        'display_name' => 'Test Feed'
    ];
    
    $rssModule = new RSSModule($config);
    $data = $rssModule->getData();
    
    echo "✓ Module executed successfully\n";
    echo "  Items returned: " . count($data) . "\n\n";
    
    echo "5. Module Output:\n";
    foreach ($data as $item) {
        echo "  - " . $item['label'] . "\n";
        if (isset($item['value'])) {
            echo "    " . substr($item['value'], 0, 60) . "...\n";
        }
    }
    
} catch (Exception $e) {
    echo "✗ Module Error: " . $e->getMessage() . "\n";
}

echo "\n=== END TEST ===\n";