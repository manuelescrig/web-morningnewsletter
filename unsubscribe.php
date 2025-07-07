<?php
require_once __DIR__ . '/core/User.php';
require_once __DIR__ . '/includes/logo.php';

$token = $_GET['token'] ?? '';
$success = false;
$error = '';

if (empty($token)) {
    $error = 'Invalid unsubscribe link. Please contact support if you need assistance.';
} else {
    // Simple placeholder - in user-centric model, unsubscribe would disable user's account
    // or mark them as unsubscribed in the users table
    $error = 'Unsubscribe functionality is temporarily unavailable. Please contact support to unsubscribe from newsletters.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unsubscribe - MorningNewsletter</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full space-y-8 p-8">
        <div class="text-center">
            <?php renderLogo('lg'); ?>
            <h2 class="mt-6 text-3xl font-extrabold text-gray-900">Unsubscribe</h2>
        </div>

        <div class="bg-yellow-50 border border-yellow-200 text-yellow-700 px-4 py-3 rounded">
            <i class="fas fa-info-circle mr-2"></i>
            <?php echo htmlspecialchars($error); ?>
        </div>
        
        <div class="text-center">
            <a href="/" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                <i class="fas fa-arrow-left mr-2"></i>
                Return to Homepage
            </a>
        </div>

        <div class="text-center text-sm text-gray-600">
            <p>Need help? Contact us at <a href="mailto:support@morningnewsletter.com" class="text-blue-600 hover:text-blue-800">support@morningnewsletter.com</a></p>
        </div>
    </div>
</body>
</html>