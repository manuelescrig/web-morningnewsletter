<?php
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/User.php';
require_once __DIR__ . '/../includes/logo.php';

$auth = Auth::getInstance();
$error = '';
$success = '';

// Redirect if already logged in
if ($auth->isLoggedIn()) {
    header('Location: /dashboard/');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $csrfToken = $_POST['csrf_token'] ?? '';
    
    if (!$auth->validateCSRFToken($csrfToken)) {
        $error = 'Invalid request. Please try again.';
    } else {
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            // Always show success message for security (don't reveal if email exists)
            $success = 'If an account with that email exists, we have sent you a password reset link.';
            
            // Try to find user and send reset email
            try {
                $user = User::findByEmail($email);
                if ($user) {
                    error_log("Password reset requested for user: {$email} (ID: {$user->getId()})");
                    $result = $user->sendPasswordResetEmail();
                    error_log("Password reset result for {$email}: " . json_encode($result));
                    if (!$result['success']) {
                        error_log("Failed to send password reset email to {$email}: " . $result['message']);
                    }
                } else {
                    error_log("Password reset requested for non-existent email: {$email}");
                }
            } catch (Exception $e) {
                error_log("Error in password reset for {$email}: " . $e->getMessage());
            }
        }
    }
}

$csrfToken = $auth->generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - MorningNewsletter</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full space-y-8 p-8">
        <div class="text-center">
            <?php renderLogo('md'); ?>
            <h2 class="mt-6 text-3xl font-extrabold text-gray-900">Forgot your password?</h2>
            <p class="mt-2 text-sm text-gray-600">
                Enter your email address and we'll send you a link to reset your password.
            </p>
        </div>

        <?php if ($error): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <?php if ($success): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded">
            <i class="fas fa-check-circle mr-2"></i>
            <?php echo htmlspecialchars($success); ?>
            <div class="mt-3 text-sm">
                <a href="/auth/login.php" class="font-medium text-green-600 hover:text-green-500">
                    Back to Sign In
                </a>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!$success): ?>
        <form class="mt-8 space-y-6" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
            
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Email address</label>
                <div class="mt-1">
                    <input id="email" name="email" type="email" autocomplete="email" required
                           class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                           placeholder="Enter your email address"
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
            </div>

            <div>
                <button type="submit"
                        class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                        <i class="fas fa-paper-plane text-blue-500 group-hover:text-blue-400"></i>
                    </span>
                    Send Reset Link
                </button>
            </div>

            <div class="text-center">
                <a href="/auth/login.php" class="font-medium text-blue-600 hover:text-blue-500">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back to Sign In
                </a>
            </div>
        </form>
        <?php endif; ?>
    </div>
</body>
</html>