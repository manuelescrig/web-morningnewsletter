<?php
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/User.php';
require_once __DIR__ . '/../includes/logo.php';

$auth = Auth::getInstance();
$message = '';
$success = false;

$token = $_GET['token'] ?? '';
$type = $_GET['type'] ?? 'regular';

if (empty($token)) {
    $message = 'Invalid verification link.';
} else {
    if ($type === 'email_change') {
        // Handle email change verification
        $result = $auth->verifyEmailChange($token);
        $success = $result['success'];
        $message = $result['message'];
    } else {
        // Handle regular email verification
        $result = $auth->verifyEmail($token);
        $success = $result['success'];
        $message = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $type === 'email_change' ? 'Email Change Verification' : 'Email Verification'; ?> - MorningNewsletter</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full space-y-8 p-8">
        <div class="text-center">
            <?php renderLogo('md'); ?>
            <h2 class="mt-6 text-3xl font-extrabold text-gray-900">
                <?php echo $type === 'email_change' ? 'Email Change Verification' : 'Email Verification'; ?>
            </h2>
        </div>

        <div class="text-center">
            <?php if ($success): ?>
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded mb-6">
                    <i data-lucide="check-circle" class="text-2xl mb-2 w-8 h-8"></i>
                    <p class="font-medium"><?php echo htmlspecialchars($message); ?></p>
                </div>
                
                <?php if ($type === 'email_change'): ?>
                    <a href="/dashboard/account.php" 
                       class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <i data-lucide="settings" class="mr-2 w-4 h-4"></i>
                        Go to Account Settings
                    </a>
                <?php else: ?>
                    <a href="/auth/login.php" 
                       class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <i data-lucide="log-in" class="mr-2 w-4 h-4"></i>
                        Sign In Now
                    </a>
                <?php endif; ?>
            <?php else: ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-6">
                    <i data-lucide="alert-triangle" class="text-2xl mb-2 w-8 h-8"></i>
                    <p class="font-medium"><?php echo htmlspecialchars($message); ?></p>
                </div>
                
                <div class="space-y-4">
                    <a href="/auth/register.php" 
                       class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <i data-lucide="user-plus" class="mr-2 w-4 h-4"></i>
                        Create New Account
                    </a>
                    
                    <div class="text-sm text-gray-600">
                        <p>Already have an account?</p>
                        <a href="/auth/login.php" class="text-blue-600 hover:text-blue-500">Sign in here</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="text-center">
            <a href="/" class="text-sm text-gray-600 hover:text-gray-900">
                <i data-lucide="arrow-left" class="mr-1 w-4 h-4"></i>
                Back to homepage
            </a>
        </div>
    </div>
    <script>
        // Initialize Lucide icons
        document.addEventListener('DOMContentLoaded', function() {
            lucide.createIcons();
        });
    </script>
</body>
</html>