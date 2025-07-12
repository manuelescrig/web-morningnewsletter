<?php
require_once __DIR__ . '/../core/Auth.php';
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
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $timezone = $_POST['timezone'] ?? 'UTC';
    $discoverySource = $_POST['discovery_source'] ?? '';
    $captchaAnswer = $_POST['captcha_answer'] ?? '';
    $captchaExpected = $_POST['captcha_expected'] ?? '';
    $csrfToken = $_POST['csrf_token'] ?? '';
    
    if (!$auth->validateCSRFToken($csrfToken)) {
        $error = 'Invalid request. Please try again.';
    } elseif (empty($captchaAnswer) || (int)$captchaAnswer !== (int)$captchaExpected) {
        $error = 'Please solve the math problem correctly.';
    } else {
        $result = $auth->register($email, $password, $confirmPassword, $timezone, $discoverySource);
        
        if ($result['success']) {
            $success = $result['message'];
            
            // If email sending failed, provide manual verification link
            if (!$result['email_sent'] && isset($result['verification_token'])) {
                $manualVerificationUrl = "/auth/verify_email.php?token=" . $result['verification_token'];
                $success .= "<br><br><strong>Manual Verification:</strong> <a href='$manualVerificationUrl' class='text-blue-600 hover:text-blue-500'>Click here to verify your email manually</a>";
            }
        } else {
            $error = $result['message'];
        }
    }
}

$csrfToken = $auth->generateCSRFToken();

// Generate simple math captcha
$captchaNum1 = rand(1, 10);
$captchaNum2 = rand(1, 10);
$captchaResult = $captchaNum1 + $captchaNum2;

// Get user's timezone for default selection
$userTimezone = date_default_timezone_get();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - MorningNewsletter</title>
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
                Create your account
            </h2>
            <p class="mt-2 text-sm text-gray-600">
                Or
                <a href="/auth/login.php" class="auth-link">
                    sign in to your existing account
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
                <div>
                    <div><?php echo $success; ?></div>
                    <div class="mt-2">
                        <a href="/auth/login.php" class="auth-link">
                            Click here to sign in
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Registration Form -->
        <form class="auth-form" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
            
            <div class="space-y-4">
                <div class="auth-input-group">
                    <label for="email" class="auth-label">Email address</label>
                    <input id="email" 
                           name="email" 
                           type="email" 
                           autocomplete="email" 
                           required
                           class="auth-input"
                           placeholder="Enter your email"
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>

                <div class="auth-input-group">
                    <label for="password" class="auth-label">Password</label>
                    <input id="password" 
                           name="password" 
                           type="password" 
                           autocomplete="new-password" 
                           required
                           class="auth-input"
                           placeholder="Choose a password (min 8 characters)">
                    <p class="auth-helper">Must be at least 8 characters long</p>
                </div>

                <div class="auth-input-group">
                    <label for="confirm_password" class="auth-label">Confirm Password</label>
                    <input id="confirm_password" 
                           name="confirm_password" 
                           type="password" 
                           autocomplete="new-password" 
                           required
                           class="auth-input"
                           placeholder="Confirm your password">
                </div>

                <div class="auth-input-group">
                    <label for="discovery_source" class="auth-label">How did you discover MorningNewsletter? (optional)</label>
                    <select id="discovery_source" 
                            name="discovery_source"
                            class="auth-input">
                        <option value="">Select an option</option>
                        <option value="chatgpt" <?php echo ($_POST['discovery_source'] ?? '') === 'chatgpt' ? 'selected' : ''; ?>>ChatGPT</option>
                        <option value="friend" <?php echo ($_POST['discovery_source'] ?? '') === 'friend' ? 'selected' : ''; ?>>Friend</option>
                        <option value="google_search" <?php echo ($_POST['discovery_source'] ?? '') === 'google_search' ? 'selected' : ''; ?>>Google Search</option>
                        <option value="newsletter" <?php echo ($_POST['discovery_source'] ?? '') === 'newsletter' ? 'selected' : ''; ?>>Newsletter</option>
                        <option value="podcast" <?php echo ($_POST['discovery_source'] ?? '') === 'podcast' ? 'selected' : ''; ?>>Podcast</option>
                        <option value="product_hunt" <?php echo ($_POST['discovery_source'] ?? '') === 'product_hunt' ? 'selected' : ''; ?>>Product Hunt</option>
                        <option value="reddit" <?php echo ($_POST['discovery_source'] ?? '') === 'reddit' ? 'selected' : ''; ?>>Reddit</option>
                        <option value="teammate" <?php echo ($_POST['discovery_source'] ?? '') === 'teammate' ? 'selected' : ''; ?>>Teammate / Co-worker</option>
                        <option value="x_twitter" <?php echo ($_POST['discovery_source'] ?? '') === 'x_twitter' ? 'selected' : ''; ?>>X/Twitter</option>
                        <option value="other" <?php echo ($_POST['discovery_source'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>

                <div class="auth-input-group">
                    <label for="captcha_answer" class="auth-label">Security Check</label>
                    <div class="flex items-center space-x-3">
                        <div class="captcha-question">
                            <?php echo $captchaNum1; ?> + <?php echo $captchaNum2; ?> = ?
                        </div>
                        <input type="number" 
                               name="captcha_answer" 
                               id="captcha_answer" 
                               required
                               class="auth-input captcha-input"
                               placeholder="?"
                               min="0" 
                               max="20">
                    </div>
                    <input type="hidden" name="captcha_expected" value="<?php echo $captchaResult; ?>">
                    <p class="auth-helper">Please solve the simple math problem above</p>
                </div>

                <!-- Hidden timezone field - automatically detected -->
                <input type="hidden" id="timezone" name="timezone" value="UTC">
            </div>

            <div class="mt-6">
                <button type="submit" class="auth-button">
                    Create Account
                    <span class="auth-button-icon">
                        <i class="fas fa-user-plus"></i>
                    </span>
                </button>
            </div>

            <div class="auth-legal">
                By creating an account, you agree to our 
                <a href="/legal/terms/" target="_blank" class="auth-link">Terms of Service</a> 
                and 
                <a href="/legal/privacy/" target="_blank" class="auth-link">Privacy Policy</a>
            </div>
        </form>
    </div>
    
    <script src="/assets/js/auth.js"></script>
</body>
</html>