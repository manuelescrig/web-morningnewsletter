<?php
// Simple Bitcoin API test - remove in production
require_once __DIR__ . '/core/SourceModule.php';
require_once __DIR__ . '/modules/bitcoin.php';

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bitcoin API Test</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 p-8">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-2xl font-bold mb-6">Bitcoin API Test</h1>
        
        <?php
        try {
            echo "<h2 class='text-lg font-semibold mb-4'>Testing Bitcoin Module:</h2>";
            
            // Test the Bitcoin module
            $bitcoin = new BitcoinModule();
            
            echo "<div class='bg-white p-4 rounded-lg shadow mb-4'>";
            echo "<h3 class='font-medium mb-2'>Module Title:</h3>";
            echo "<p>" . htmlspecialchars($bitcoin->getTitle()) . "</p>";
            echo "</div>";
            
            echo "<div class='bg-white p-4 rounded-lg shadow mb-4'>";
            echo "<h3 class='font-medium mb-2'>Config Fields:</h3>";
            $configFields = $bitcoin->getConfigFields();
            if (empty($configFields)) {
                echo "<p>No configuration required</p>";
            } else {
                echo "<pre>" . htmlspecialchars(print_r($configFields, true)) . "</pre>";
            }
            echo "</div>";
            
            echo "<div class='bg-white p-4 rounded-lg shadow mb-4'>";
            echo "<h3 class='font-medium mb-2'>Getting Data...</h3>";
            
            $startTime = microtime(true);
            $data = $bitcoin->getData();
            $endTime = microtime(true);
            $duration = round(($endTime - $startTime) * 1000, 2);
            
            echo "<p><strong>Response time:</strong> {$duration}ms</p>";
            echo "<h4 class='font-medium mt-4 mb-2'>Data returned:</h4>";
            echo "<pre class='bg-gray-100 p-2 rounded text-sm overflow-x-auto'>" . htmlspecialchars(print_r($data, true)) . "</pre>";
            echo "</div>";
            
        } catch (Exception $e) {
            echo "<div class='bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-4'>";
            echo "<h3 class='font-medium mb-2'>Error:</h3>";
            echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<h4 class='font-medium mt-2 mb-1'>Stack trace:</h4>";
            echo "<pre class='text-xs'>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
            echo "</div>";
        }
        
        // Test the raw API directly
        echo "<h2 class='text-lg font-semibold mb-4 mt-8'>Direct API Test:</h2>";
        
        try {
            echo "<div class='bg-white p-4 rounded-lg shadow mb-4'>";
            echo "<h3 class='font-medium mb-2'>Testing CoinDesk API directly:</h3>";
            
            $apiUrl = 'https://api.coindesk.com/v1/bpi/currentprice.json';
            echo "<p><strong>URL:</strong> <a href='$apiUrl' target='_blank' class='text-blue-600'>$apiUrl</a></p>";
            
            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'timeout' => 10,
                    'user_agent' => 'MorningNewsletter/1.0'
                ]
            ]);
            
            $startTime = microtime(true);
            $response = file_get_contents($apiUrl, false, $context);
            $endTime = microtime(true);
            $duration = round(($endTime - $startTime) * 1000, 2);
            
            if ($response === FALSE) {
                echo "<p class='text-red-600'><strong>Failed to fetch data from API</strong></p>";
                
                // Check if allow_url_fopen is enabled
                if (!ini_get('allow_url_fopen')) {
                    echo "<p class='text-red-600'><strong>Issue:</strong> allow_url_fopen is disabled in PHP configuration</p>";
                }
            } else {
                echo "<p><strong>Response time:</strong> {$duration}ms</p>";
                echo "<p><strong>Response size:</strong> " . strlen($response) . " bytes</p>";
                
                $data = json_decode($response, true);
                if ($data) {
                    echo "<h4 class='font-medium mt-4 mb-2'>Parsed JSON:</h4>";
                    echo "<pre class='bg-gray-100 p-2 rounded text-sm overflow-x-auto'>" . htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT)) . "</pre>";
                } else {
                    echo "<p class='text-red-600'><strong>Failed to parse JSON response</strong></p>";
                    echo "<h4 class='font-medium mt-4 mb-2'>Raw response:</h4>";
                    echo "<pre class='bg-gray-100 p-2 rounded text-sm overflow-x-auto'>" . htmlspecialchars($response) . "</pre>";
                }
            }
            echo "</div>";
            
        } catch (Exception $e) {
            echo "<div class='bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded'>";
            echo "<p><strong>API Test Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "</div>";
        }
        
        // System info
        echo "<h2 class='text-lg font-semibold mb-4 mt-8'>System Information:</h2>";
        echo "<div class='bg-white p-4 rounded-lg shadow'>";
        echo "<ul class='space-y-1 text-sm'>";
        echo "<li><strong>PHP Version:</strong> " . phpversion() . "</li>";
        echo "<li><strong>allow_url_fopen:</strong> " . (ini_get('allow_url_fopen') ? 'Enabled' : 'Disabled') . "</li>";
        echo "<li><strong>curl extension:</strong> " . (extension_loaded('curl') ? 'Available' : 'Not available') . "</li>";
        echo "<li><strong>openssl extension:</strong> " . (extension_loaded('openssl') ? 'Available' : 'Not available') . "</li>";
        echo "<li><strong>User Agent:</strong> " . ($_SERVER['HTTP_USER_AGENT'] ?? 'Not set') . "</li>";
        echo "</ul>";
        echo "</div>";
        ?>
        
        <div class="mt-8 text-center">
            <a href="/dashboard/" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                ← Back to Dashboard
            </a>
        </div>
    </div>
</body>
</html>