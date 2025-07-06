<?php
// Debug script to test the geocoding API step by step
echo "=== Geocoding API Debug ===\n\n";

// Test 1: Check if we can make HTTP requests
echo "1. Testing cURL availability...\n";
if (function_exists('curl_init')) {
    echo "✅ cURL is available\n";
} else {
    echo "❌ cURL is not available\n";
    exit(1);
}

// Test 2: Test basic HTTP request
echo "\n2. Testing basic HTTP request...\n";
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => 'https://httpbin.org/get',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($response === false) {
    echo "❌ Basic HTTP request failed: $error\n";
} else {
    echo "✅ Basic HTTP request successful (Status: $httpCode)\n";
}

// Test 3: Test Nominatim directly
echo "\n3. Testing Nominatim API directly...\n";
$nominatimUrl = 'https://nominatim.openstreetmap.org/search?q=London&format=json&limit=1';
echo "URL: $nominatimUrl\n";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $nominatimUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_MAXREDIRS => 3,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
    CURLOPT_USERAGENT => 'MorningNewsletter/1.0 (https://morningnewsletter.com; contact@morningnewsletter.com)',
    CURLOPT_HTTPHEADER => [
        'Accept: application/json',
        'Accept-Language: en-US,en;q=0.9'
    ]
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($response === false) {
    echo "❌ Nominatim request failed: $error\n";
} else {
    echo "✅ Nominatim request successful (Status: $httpCode)\n";
    echo "Response length: " . strlen($response) . " bytes\n";
    echo "Response preview: " . substr($response, 0, 200) . "...\n";
    
    $data = json_decode($response, true);
    if (is_array($data) && !empty($data)) {
        echo "✅ Nominatim returned valid JSON with " . count($data) . " results\n";
    } else {
        echo "❌ Nominatim returned invalid or empty JSON\n";
    }
}

// Test 4: Test our geocoding API
echo "\n4. Testing our geocoding API...\n";
$_GET['q'] = 'London';
$_SERVER['REQUEST_METHOD'] = 'GET';

// Capture output
ob_start();
include __DIR__ . '/api/geocoding.php';
$output = ob_get_clean();

echo "API Output length: " . strlen($output) . " bytes\n";
echo "API Output: $output\n";

$data = json_decode($output, true);
if ($data && isset($data['success']) && $data['success']) {
    echo "✅ Our API is working correctly!\n";
    echo "Found " . count($data['results']) . " results\n";
} else {
    echo "❌ Our API failed!\n";
    if (isset($data['error'])) {
        echo "Error: " . $data['error'] . "\n";
    }
}

echo "\n=== Debug Complete ===\n";
?> 