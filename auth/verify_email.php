<?php
require_once __DIR__ . '/../core/Auth.php';

$auth = Auth::getInstance();
$message = '';
$success = false;

$token = $_GET['token'] ?? '';

if (empty($token)) {
    $message = 'Invalid verification link.';
} else {
    $result = $auth->verifyEmail($token);
    $success = $result['success'];
    $message = $result['message'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - MorningNewsletter</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full space-y-8 p-8">
        <div class="text-center">
            <a href="/" class="text-3xl font-bold text-blue-600">MorningNewsletter</a>
            <h2 class="mt-6 text-3xl font-extrabold text-gray-900">Email Verification</h2>
        </div>

        <div class="text-center">
            <?php if ($success): ?>
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded mb-6">
                    <i class="fas fa-check-circle text-2xl mb-2"></i>
                    <p class="font-medium"><?php echo htmlspecialchars($message); ?></p>
                </div>
                
                <a href="/auth/login.php" 
                   class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <i class="fas fa-sign-in-alt mr-2"></i>
                    Sign In Now
                </a>
            <?php else: ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-6">
                    <i class="fas fa-exclamation-triangle text-2xl mb-2"></i>
                    <p class="font-medium"><?php echo htmlspecialchars($message); ?></p>
                </div>
                
                <div class="space-y-4">
                    <a href="/auth/register.php" 
                       class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <i class="fas fa-user-plus mr-2"></i>
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
                <i class="fas fa-arrow-left mr-1"></i>
                Back to homepage
            </a>
        </div>
    </div>
</body>
</html>