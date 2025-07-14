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
    
    // Invisible captcha fields
    $honeypot = $_POST['website'] ?? ''; // Should be empty
    $formStartTime = $_POST['form_start_time'] ?? 0;
    $honeypot2 = $_POST['confirm_email'] ?? ''; // Should be empty
    
    $csrfToken = $_POST['csrf_token'] ?? '';
    
    if (!$auth->validateCSRFToken($csrfToken)) {
        $error = 'Invalid request. Please try again.';
    } elseif (!empty($honeypot) || !empty($honeypot2)) {
        // Bot detected - honeypot fields should be empty
        $error = 'Invalid submission detected.';
        error_log("Bot registration attempt detected: honeypot filled - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    } elseif (time() - (int)$formStartTime < 3) {
        // Form submitted too quickly (less than 3 seconds)
        $error = 'Please take a moment to review your information.';
        error_log("Bot registration attempt detected: too fast submission - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    } elseif ($auth->isRateLimited($_SERVER['REMOTE_ADDR'] ?? '')) {
        // Rate limiting check
        $error = 'Too many registration attempts. Please try again later.';
        error_log("Rate limited registration attempt - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    } else {
        // Record registration attempt for rate limiting
        $auth->recordRegistrationAttempt($_SERVER['REMOTE_ADDR'] ?? '');
        
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

// Generate form start time for invisible captcha
$formStartTime = time();

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
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
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
                <i data-lucide="alert-triangle" class="w-4 h-4"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="auth-alert auth-alert-success">
                <i data-lucide="check-circle" class="w-4 h-4"></i>
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

                <!-- Invisible captcha fields (honeypots) -->
                <div class="honeypot" aria-hidden="true">
                    <label for="website">Website (leave blank)</label>
                    <input type="text" name="website" id="website" tabindex="-1" autocomplete="nope">
                    
                    <label for="confirm_email">Confirm Email (leave blank)</label>
                    <input type="email" name="confirm_email" id="confirm_email" tabindex="-1" autocomplete="nope">
                </div>
                
                <!-- Form timing for bot detection -->
                <input type="hidden" name="form_start_time" value="<?php echo $formStartTime; ?>">

                <!-- Hidden timezone field - automatically detected -->
                <input type="hidden" id="timezone" name="timezone" value="UTC">
            </div>

            <div class="mt-6">
                <button type="submit" class="auth-button bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-lg font-semibold transition-colors duration-200 shadow-lg hover:shadow-xl">
                    Create Account
                    <span class="auth-button-icon">
                        <i data-lucide="user-plus" class="w-4 h-4"></i>
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
    <script>
        // Initialize Lucide icons
        document.addEventListener('DOMContentLoaded', function() {
            lucide.createIcons();
        });
    </script>
</body>
</html>