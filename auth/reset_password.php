<?php
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/User.php';
require_once __DIR__ . '/../includes/logo.php';

$auth = Auth::getInstance();
$error = '';
$success = '';
$token = $_GET['token'] ?? '';

// Redirect if already logged in
if ($auth->isLoggedIn()) {
    header('Location: /dashboard/');
    exit;
}

// Validate token
if (empty($token)) {
    $error = 'Invalid reset link. Please request a new password reset.';
} else {
    $user = User::findByResetToken($token);
    if (!$user) {
        $error = 'Invalid or expired reset link. Please request a new password reset.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $csrfToken = $_POST['csrf_token'] ?? '';
    
    if (!$auth->validateCSRFToken($csrfToken)) {
        $error = 'Invalid request. Please try again.';
    } else {
        if (empty($password) || strlen($password) < 8) {
            $error = 'Password must be at least 8 characters long.';
        } elseif ($password !== $confirmPassword) {
            $error = 'Passwords do not match.';
        } else {
            $result = $user->resetPassword($token, $password);
            if ($result['success']) {
                $success = 'Your password has been reset successfully. You can now sign in with your new password.';
            } else {
                $error = $result['message'];
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
    <title>Reset Password - MorningNewsletter</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="/assets/css/auth.css">
</head>
<body class="auth-container">
    <div class="auth-card">
        <div class="text-center">
            <?php renderLogo('md'); ?>
            <h2 class="mt-6 text-3xl font-extrabold text-gray-900">Reset your password</h2>
            <p class="mt-2 text-sm text-gray-600">
                Enter your new password below.
            </p>
        </div>

        <?php if ($error): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            <?php echo htmlspecialchars($error); ?>
            <div class="mt-3 text-sm">
                <a href="/auth/forgot_password.php" class="font-medium text-red-600 hover:text-red-500">
                    Request a new password reset
                </a>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($success): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded">
            <i class="fas fa-check-circle mr-2"></i>
            <?php echo htmlspecialchars($success); ?>
            <div class="mt-3 text-sm">
                <a href="/auth/login.php" class="font-medium text-green-600 hover:text-green-500">
                    <i class="fas fa-sign-in-alt mr-1"></i>
                    Sign In Now
                </a>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!$error && !$success): ?>
        <form class="mt-8 space-y-6" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
            
            <div class="space-y-4">
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">New Password</label>
                    <div class="mt-1">
                        <input id="password" name="password" type="password" autocomplete="new-password" required
                               class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus-ring-primary border-primary sm:text-sm"
                               placeholder="Enter your new password"
                               minlength="8">
                    </div>
                    <p class="mt-1 text-xs text-gray-500">Password must be at least 8 characters long.</p>
                </div>

                <div>
                    <label for="confirm_password" class="block text-sm font-medium text-gray-700">Confirm New Password</label>
                    <div class="mt-1">
                        <input id="confirm_password" name="confirm_password" type="password" autocomplete="new-password" required
                               class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus-ring-primary border-primary sm:text-sm"
                               placeholder="Confirm your new password"
                               minlength="8">
                    </div>
                </div>
            </div>

            <div>
                <button type="submit"
                        class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-primary hover-bg-primary-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus-ring-primary">
                    <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                        <i class="fas fa-key text-primary group-hover:text-primary-dark"></i>
                    </span>
                    Reset Password
                </button>
            </div>

            <div class="text-center">
                <a href="/auth/login.php" class="font-medium text-primary hover:text-primary-dark">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back to Sign In
                </a>
            </div>
        </form>
        <?php endif; ?>
    </div>

    <script>
        // Add password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (confirmPassword && password !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
        
        document.getElementById('password').addEventListener('input', function() {
            const confirmPassword = document.getElementById('confirm_password');
            if (confirmPassword.value) {
                confirmPassword.dispatchEvent(new Event('input'));
            }
        });
    </script>
</body>
</html>