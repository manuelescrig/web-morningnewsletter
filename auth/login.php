<?php
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../includes/logo.php';

$auth = Auth::getInstance();
$error = '';
$success = '';

// Get return URL if provided
$returnUrl = isset($_GET['return']) ? $_GET['return'] : '/dashboard/';

// Redirect if already logged in
if ($auth->isLoggedIn()) {
    header('Location: ' . $returnUrl);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $csrfToken = $_POST['csrf_token'] ?? '';
    $returnUrl = $_POST['return_url'] ?? '/dashboard/';
    
    if (!$auth->validateCSRFToken($csrfToken)) {
        $error = 'Invalid request. Please try again.';
    } else {
        $result = $auth->login($email, $password);
        
        if ($result['success']) {
            // Redirect to the return URL or dashboard after successful login
            header('Location: ' . $returnUrl);
            exit;
        } else {
            $error = $result['message'];
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
    <title>Sign In - MorningNewsletter</title>
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
                Sign in to your account
            </h2>
            <p class="mt-2 text-sm text-gray-600">
                Or
                <a href="/auth/register.php" class="auth-link">
                    create a new account
                </a>
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
                <span><?php echo htmlspecialchars($success); ?></span>
            </div>
        <?php endif; ?>

        <!-- Login Form -->
        <form class="auth-form" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
            <input type="hidden" name="return_url" value="<?php echo htmlspecialchars($returnUrl); ?>">
            
            <div class="space-y-4">
                <div class="auth-input-group">
                    <label for="email" class="sr-only">Email address</label>
                    <input id="email" 
                           name="email" 
                           type="email" 
                           autocomplete="email" 
                           required
                           class="auth-input"
                           placeholder="Email address"
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
                
                <div class="auth-input-group">
                    <label for="password" class="sr-only">Password</label>
                    <input id="password" 
                           name="password" 
                           type="password" 
                           autocomplete="current-password" 
                           required
                           class="auth-input"
                           placeholder="Password">
                </div>
            </div>

            <div class="flex items-center justify-between mt-4">
                <div class="text-sm">
                    <a href="/auth/forgot_password.php" class="auth-link">
                        Forgot your password?
                    </a>
                </div>
            </div>

            <div class="mt-6">
                <button type="submit" class="btn-pill auth-button bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 font-semibold transition-colors duration-200 shadow-lg hover:shadow-xl">
                    Sign in
                    <span class="auth-button-icon">
                        <i class="fas fa-sign-in-alt"></i>
                    </span>
                </button>
            </div>
        </form>
    </div>
    
    <script src="/assets/js/auth.js"></script>
</body>
</html>