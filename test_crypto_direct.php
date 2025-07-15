<?php
// Direct test of crypto API calls
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing CoinGecko API directly...\n";
echo "Working directory: " . getcwd() . "\n";
echo "PHP SAPI: " . php_sapi_name() . "\n";

$apiUrl = 'https://api.coingecko.com/api/v3/simple/price?ids=bitcoin&vs_currencies=usd&include_24hr_change=true';
echo "Testing URL: $apiUrl\n";

// Test 1: file_get_contents
echo "\n=== Test 1: file_get_contents ===\n";
$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'timeout' => 30,
        'user_agent' => 'MorningNewsletter/1.0'
    ],
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false,
        'allow_self_signed' => true
    ]
]);

$response = @file_get_contents($apiUrl, false, $context);
if ($response === FALSE) {
    $error = error_get_last();
    echo "file_get_contents FAILED: " . ($error ? $error['message'] : 'Unknown error') . "\n";
} else {
    echo "file_get_contents SUCCESS: " . strlen($response) . " bytes\n";
    echo "Response: " . $response . "\n";
}

// Test 2: cURL
echo "\n=== Test 2: cURL ===\n";
if (extension_loaded('curl')) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $apiUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
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
        echo "cURL FAILED: $error\n";
    } else {
        echo "cURL SUCCESS: HTTP $httpCode, " . strlen($response) . " bytes\n";
        echo "Response: " . $response . "\n";
    }
} else {
    echo "cURL extension not loaded\n";
}

// Test 3: Check if we can reach the domain
echo "\n=== Test 3: DNS/Network test ===\n";
$host = 'api.coingecko.com';
echo "Testing DNS resolution for $host...\n";
$ip = gethostbyname($host);
echo "DNS result: $ip\n";

if ($ip !== $host) {
    echo "DNS resolution successful\n";
} else {
    echo "DNS resolution failed\n";
}

// Test 4: PHP configuration
echo "\n=== Test 4: PHP Configuration ===\n";
echo "allow_url_fopen: " . (ini_get('allow_url_fopen') ? 'enabled' : 'disabled') . "\n";
echo "user_agent: " . ini_get('user_agent') . "\n";
echo "curl_version: " . (extension_loaded('curl') ? curl_version()['version'] : 'not loaded') . "\n";
echo "openssl_version: " . (extension_loaded('openssl') ? OPENSSL_VERSION_TEXT : 'not loaded') . "\n";

?>