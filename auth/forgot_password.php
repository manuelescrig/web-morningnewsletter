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
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="/assets/css/auth.css">
</head>
<body class="auth-container">
    <div class="auth-card">
        <!-- Header -->
        <div class="text-center">
            <?php renderLogo('md'); ?>
            <h2 class="mt-6 text-3xl font-extrabold text-gray-900">
                Forgot your password?
            </h2>
            <p class="mt-2 text-sm text-gray-600">
                Enter your email address and we'll send you a link to reset your password.
            </p>
        </div>

        <!-- Alerts -->
        <?php if ($error): ?>
            <div class="auth-alert auth-alert-error">
                <i class="fas fa-exclamation-triangle"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="auth-alert auth-alert-success">
                <i class="fas fa-check-circle"></i>
                <div>
                    <div><?php echo htmlspecialchars($success); ?></div>
                    <div class="mt-3 text-sm">
                        <a href="/auth/login.php" class="auth-link">
                            Back to Sign In
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Password Reset Form -->
        <?php if (!$success): ?>
            <form class="auth-form" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                
                <div class="auth-input-group">
                    <label for="email" class="auth-label">Email address</label>
                    <input id="email" 
                           name="email" 
                           type="email" 
                           autocomplete="email" 
                           required
                           class="auth-input"
                           placeholder="Enter your email address"
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>

                <div class="mt-6">
                    <button type="submit" class="btn-pill auth-button bg-primary hover-bg-primary-dark text-white px-8 py-3 font-semibold transition-colors duration-200 shadow-lg hover:shadow-xl">
                        Send Reset Link
                        <span class="auth-button-icon">
                            <i class="fas fa-paper-plane"></i>
                        </span>
                    </button>
                </div>

                <div class="text-center mt-4">
                    <a href="/auth/login.php" class="auth-link">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Back to Sign In
                    </a>
                </div>
            </form>
        <?php endif; ?>
    </div>
    
    <script src="/assets/js/auth.js"></script>
</body>
</html>