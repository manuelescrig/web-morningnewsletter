<?php
// Simple email test page - remove this in production
require_once __DIR__ . '/core/EmailSender.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $testEmail = $_POST['email'] ?? '';
    
    if (!empty($testEmail) && filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
        $emailSender = new EmailSender();
        $testToken = bin2hex(random_bytes(16));
        
        try {
            $success = $emailSender->sendVerificationEmail($testEmail, $testToken);
            $message = $success ? "Test email sent successfully!" : "Failed to send test email.";
            $messageClass = $success ? "text-green-600" : "text-red-600";
        } catch (Exception $e) {
            $message = "Error: " . $e->getMessage();
            $messageClass = "text-red-600";
        }
    } else {
        $message = "Please enter a valid email address.";
        $messageClass = "text-red-600";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Test - MorningNewsletter</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full bg-white rounded-lg shadow-lg p-6">
        <h1 class="text-2xl font-bold text-gray-900 mb-6 text-center">Email Test</h1>
        
        <?php if (isset($message)): ?>
        <div class="mb-4 p-3 rounded-md bg-gray-100">
            <p class="<?php echo $messageClass; ?>"><?php echo htmlspecialchars($message); ?></p>
        </div>
        <?php endif; ?>
        
        <form method="POST" class="space-y-4">
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Test Email Address</label>
                <input type="email" id="email" name="email" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                       placeholder="Enter email to test"
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>
            
            <button type="submit" 
                    class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                Send Test Email
            </button>
        </form>
        
        <div class="mt-6 text-sm text-gray-600">
            <h3 class="font-medium mb-2">Email Configuration Status:</h3>
            <ul class="space-y-1">
                <li>• SMTP Host: <?php echo $_ENV['SMTP_HOST'] ?? 'localhost (default)'; ?></li>
                <li>• SMTP Port: <?php echo $_ENV['SMTP_PORT'] ?? '587 (default)'; ?></li>
                <li>• From Email: <?php echo $_ENV['FROM_EMAIL'] ?? 'noreply@morningnewsletter.com (default)'; ?></li>
                <li>• Mail Function: <?php echo function_exists('mail') ? '✓ Available' : '✗ Not available'; ?></li>
            </ul>
        </div>
        
        <div class="mt-4 text-xs text-gray-500">
            <p><strong>Note:</strong> This test page should be removed in production.</p>
            <p>If emails aren't working, check your server's mail configuration or set up SMTP credentials.</p>
        </div>
        
        <div class="mt-4 text-center">
            <a href="/" class="text-blue-600 hover:text-blue-500 text-sm">← Back to Homepage</a>
        </div>
    </div>
</body>
</html>