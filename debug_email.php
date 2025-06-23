<?php
/**
 * Debug script for email functionality
 * Usage: https://domain.com/debug_email.php?secret=debug_2024
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/core/EmailSender.php';

// Simple security check
$secret = $_GET['secret'] ?? '';
if ($secret !== 'debug_2024') {
    die('Access denied. Add ?secret=debug_2024 to the URL.');
}

echo "<h2>Email System Debug</h2>";

// 1. Check configuration
echo "<h3>1. Configuration Check</h3>";
try {
    $config = require __DIR__ . '/config/email.php';
    echo "<div style='background: #e8f5e8; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
    echo "<strong>✓ Config loaded successfully</strong><br>";
    echo "Provider: " . $config['provider'] . "<br>";
    echo "API Key: " . substr($config['maileroo']['api_key'], 0, 10) . "...<br>";
    echo "From Email: " . $config['maileroo']['from_email'] . "<br>";
    echo "</div>";
} catch (Exception $e) {
    echo "<div style='background: #ffe8e8; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
    echo "<strong>✗ Config Error:</strong> " . $e->getMessage();
    echo "</div>";
}

// 2. Test EmailSender instantiation
echo "<h3>2. EmailSender Class Test</h3>";
try {
    $emailSender = new EmailSender();
    echo "<div style='background: #e8f5e8; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
    echo "<strong>✓ EmailSender created successfully</strong>";
    echo "</div>";
} catch (Exception $e) {
    echo "<div style='background: #ffe8e8; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
    echo "<strong>✗ EmailSender Error:</strong> " . $e->getMessage();
    echo "</div>";
    exit;
}

// 3. Test multiple Maileroo API endpoints
echo "<h3>3. Maileroo API Endpoint Discovery</h3>";
$testApiKey = $config['maileroo']['api_key'];
$testFrom = $config['maileroo']['from_email'];
$testTo = $_GET['to'] ?? 'test@example.com';

$postFields = [
    'from' => $testFrom,
    'to' => $testTo,
    'subject' => 'Debug Test Email',
    'html' => '<h2>Debug Test</h2><p>This is a direct API test at ' . date('Y-m-d H:i:s') . '</p>'
];

// Test multiple possible endpoints
$endpoints = [
    'https://maileroo.com/api/send',
    'https://api.maileroo.com/send',
    'https://api.maileroo.com/v1/send',
    'https://api.maileroo.com/email',
    'https://app.maileroo.com/api/send'
];

foreach ($endpoints as $endpoint) {
    echo "<h4>Testing: " . $endpoint . "</h4>";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $endpoint);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'X-API-Key: ' . $testApiKey
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    echo "<div style='background: #f5f5f5; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
    echo "<strong>HTTP Code:</strong> " . $httpCode . "<br>";
    if ($error) {
        echo "<strong>cURL Error:</strong> " . $error . "<br>";
    }
    echo "<strong>Response:</strong> " . htmlspecialchars(substr($response, 0, 200)) . "<br>";
    echo "</div>";
    
    if ($httpCode >= 200 && $httpCode < 300) {
        echo "<div style='background: #e8f5e8; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
        echo "<strong>✓ Found working endpoint: " . $endpoint . "</strong>";
        echo "</div>";
        break;
    } elseif ($httpCode == 404) {
        echo "<div style='background: #ffe8e8; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
        echo "<strong>✗ Endpoint not found</strong>";
        echo "</div>";
    } else {
        echo "<div style='background: #fff8dc; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
        echo "<strong>? Different response (might be working)</strong>";
        echo "</div>";
    }
    
    echo "<br>";
}

// 4. Test password reset functionality
echo "<h3>4. Password Reset Test</h3>";
if (!empty($_GET['test_email'])) {
    $testEmailAddr = $_GET['test_email'];
    if (filter_var($testEmailAddr, FILTER_VALIDATE_EMAIL)) {
        echo "<p>Testing password reset for: " . htmlspecialchars($testEmailAddr) . "</p>";
        
        try {
            require_once __DIR__ . '/core/User.php';
            $user = User::findByEmail($testEmailAddr);
            
            if ($user) {
                $result = $user->sendPasswordResetEmail();
                echo "<div style='background: " . ($result['success'] ? '#e8f5e8' : '#ffe8e8') . "; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
                echo "<strong>" . ($result['success'] ? '✓' : '✗') . " Password Reset Result:</strong> " . $result['message'];
                echo "</div>";
            } else {
                echo "<div style='background: #ffe8e8; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
                echo "<strong>✗ User not found</strong>";
                echo "</div>";
            }
        } catch (Exception $e) {
            echo "<div style='background: #ffe8e8; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
            echo "<strong>✗ Error:</strong> " . $e->getMessage();
            echo "</div>";
        }
    }
}

echo "<hr>";
echo "<h3>Test Links:</h3>";
echo "<p><a href='?secret=debug_2024&to=your@email.com'>Test API with your email</a></p>";
echo "<p><a href='?secret=debug_2024&test_email=your@email.com'>Test password reset for your email</a></p>";
?>