<?php
require_once __DIR__ . '/../core/Auth.php';

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
    $csrfToken = $_POST['csrf_token'] ?? '';
    
    if (!$auth->validateCSRFToken($csrfToken)) {
        $error = 'Invalid request. Please try again.';
    } else {
        $result = $auth->register($email, $password, $confirmPassword, $timezone);
        
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
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full space-y-8 p-8">
        <div class="text-center">
            <a href="/" class="text-3xl font-bold text-blue-600">MorningNewsletter</a>
            <h2 class="mt-6 text-3xl font-extrabold text-gray-900">Create your account</h2>
            <p class="mt-2 text-sm text-gray-600">
                Or
                <a href="/auth/login.php" class="font-medium text-blue-600 hover:text-blue-500">
                    sign in to your existing account
                </a>
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
            <?php echo $success; ?>
            <div class="mt-2">
                <a href="/auth/login.php" class="font-medium text-green-600 hover:text-green-500">
                    Click here to sign in
                </a>
            </div>
        </div>
        <?php endif; ?>

        <form class="mt-8 space-y-6" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
            
            <div class="space-y-4">
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Email address</label>
                    <input id="email" name="email" type="email" autocomplete="email" required
                           class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                           placeholder="Enter your email"
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                    <input id="password" name="password" type="password" autocomplete="new-password" required
                           class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                           placeholder="Choose a password (min 8 characters)">
                    <p class="mt-1 text-xs text-gray-500">Must be at least 8 characters long</p>
                </div>

                <div>
                    <label for="confirm_password" class="block text-sm font-medium text-gray-700">Confirm Password</label>
                    <input id="confirm_password" name="confirm_password" type="password" autocomplete="new-password" required
                           class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                           placeholder="Confirm your password">
                </div>

                <div>
                    <label for="timezone" class="block text-sm font-medium text-gray-700">Timezone</label>
                    <select id="timezone" name="timezone" required
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <?php
                        $timezones = [
                            'America/New_York' => 'Eastern Time (EST/EDT)',
                            'America/Chicago' => 'Central Time (CST/CDT)',
                            'America/Denver' => 'Mountain Time (MST/MDT)',
                            'America/Los_Angeles' => 'Pacific Time (PST/PDT)',
                            'Europe/London' => 'London (GMT/BST)',
                            'Europe/Paris' => 'Paris (CET/CEST)',
                            'Europe/Berlin' => 'Berlin (CET/CEST)',
                            'Asia/Tokyo' => 'Tokyo (JST)',
                            'Asia/Shanghai' => 'Shanghai (CST)',
                            'Australia/Sydney' => 'Sydney (AEST/AEDT)',
                            'UTC' => 'UTC'
                        ];
                        
                        $selectedTimezone = $_POST['timezone'] ?? $userTimezone;
                        
                        foreach ($timezones as $value => $label) {
                            $selected = ($value === $selectedTimezone) ? 'selected' : '';
                            echo "<option value=\"$value\" $selected>$label</option>";
                        }
                        ?>
                    </select>
                    <p class="mt-1 text-xs text-gray-500">Your newsletter will be delivered based on this timezone</p>
                </div>
            </div>

            <div>
                <button type="submit"
                        class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                        <i class="fas fa-user-plus text-blue-500 group-hover:text-blue-400"></i>
                    </span>
                    Create Account
                </button>
            </div>

            <div class="text-xs text-gray-500 text-center">
                By creating an account, you agree to our 
                <a href="/terms" class="text-blue-600 hover:text-blue-500">Terms of Service</a> 
                and 
                <a href="/privacy" class="text-blue-600 hover:text-blue-500">Privacy Policy</a>
            </div>
        </form>
    </div>
</body>
</html>