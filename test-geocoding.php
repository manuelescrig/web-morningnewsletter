<?php
/**
 * Test script for geocoding API
 * Remove after testing
 */

echo "Testing Geocoding API\n";
echo "====================\n\n";

// Test 1: Direct API call
echo "Test 1: Testing API directly...\n";
$_GET['q'] = 'London';
ob_start();
try {
    include 'api/geocoding.php';
    $output = ob_get_clean();
    $data = json_decode($output, true);
    
    if ($data && isset($data['success'])) {
        echo "✅ API test successful! Found " . count($data['results']) . " results\n";
    } else {
        echo "❌ API test failed. Output: $output\n";
    }
} catch (Exception $e) {
    ob_end_clean();
    echo "❌ API test error: " . $e->getMessage() . "\n";
}

// Test 2: Check cache directory
echo "\nTest 2: Checking cache directory...\n";
$cacheDir = __DIR__ . '/cache';
if (!is_dir($cacheDir)) {
    echo "❌ Cache directory doesn't exist\n";
} else if (!is_writable($cacheDir)) {
    echo "❌ Cache directory not writable\n";
} else {
    echo "✅ Cache directory OK\n";
}

// Test 3: Test external API call
echo "\nTest 3: Testing external API access...\n";
$url = 'https://nominatim.openstreetmap.org/search?q=London&format=json&limit=1';
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_USERAGENT => 'MorningNewsletter/1.0'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($response === false) {
    echo "❌ cURL error: $error\n";
} else if ($httpCode !== 200) {
    echo "❌ HTTP error: $httpCode\n";
} else {
    $data = json_decode($response, true);
    if (is_array($data) && count($data) > 0) {
        echo "✅ External API working! Found location: " . $data[0]['display_name'] . "\n";
    } else {
        echo "❌ External API returned invalid data\n";
    }
}

echo "\nTesting complete!\n";
?>