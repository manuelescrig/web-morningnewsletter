<?php
// Simple test to check geocoding API
echo "Testing geocoding API...\n";

// Test 1: Check if file exists
$apiFile = __DIR__ . '/api/geocoding.php';
if (file_exists($apiFile)) {
    echo "✅ API file exists\n";
} else {
    echo "❌ API file not found: $apiFile\n";
    exit(1);
}

// Test 2: Check if we can access it via HTTP
$testUrl = 'http://localhost/api/geocoding/?q=London';
echo "Testing HTTP access to: $testUrl\n";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $testUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($response === false) {
    echo "❌ HTTP request failed: $error\n";
} else {
    echo "✅ HTTP request successful (Status: $httpCode)\n";
    echo "Response: $response\n";
}

// Test 3: Direct API test
echo "\nTesting API directly...\n";
$_GET['q'] = 'London';
$_SERVER['REQUEST_METHOD'] = 'GET';

ob_start();
include $apiFile;
$output = ob_get_clean();

echo "Direct API Response:\n$output\n";

$data = json_decode($output, true);
if ($data && isset($data['success']) && $data['success']) {
    echo "✅ Direct API test successful!\n";
} else {
    echo "❌ Direct API test failed!\n";
    if (isset($data['error'])) {
        echo "Error: " . $data['error'] . "\n";
    }
}
?> 