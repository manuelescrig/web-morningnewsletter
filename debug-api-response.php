<?php
/**
 * Debug what the API actually returns when called via HTTP
 */

echo "API Response Debug\n";
echo "==================\n\n";

$testUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/api/geocoding.php?q=London';
echo "Testing URL: $testUrl\n\n";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $testUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_HEADER => true,
    CURLOPT_USERAGENT => 'Mozilla/5.0 (Debug Test)',
    CURLOPT_FOLLOWLOCATION => true
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$headers = substr($response, 0, $headerSize);
$body = substr($response, $headerSize);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Content-Type: $contentType\n";
echo "Headers:\n$headers\n";
echo "Body (first 500 chars):\n" . substr($body, 0, 500) . "\n";

if (strlen($body) > 500) {
    echo "... (body truncated, total length: " . strlen($body) . ")\n";
}

echo "\nBody type analysis:\n";
if (strpos($body, '<!DOCTYPE') === 0 || strpos($body, '<html') === 0) {
    echo "❌ Body contains HTML (likely an error page)\n";
} else if (json_decode($body)) {
    echo "✅ Body contains valid JSON\n";
} else {
    echo "❌ Body is neither HTML nor valid JSON\n";
}
?>