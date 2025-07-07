<?php
require_once __DIR__ . '/core/User.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/logo.php';

$token = $_GET['token'] ?? '';
$action = $_GET['action'] ?? 'unsubscribe'; // unsubscribe or resubscribe
$success = false;
$error = '';

if (empty($token)) {
    $error = 'Invalid link. Please contact support if you need assistance.';
} else {
    try {
        $db = Database::getInstance()->getConnection();
        
        // For preview emails, the token is 'preview-token'
        if ($token === 'preview-token') {
            $error = 'This is a preview email. ' . ucfirst($action) . ' links work only in actual newsletters.';
        } else {
            // Find user by checking if the token matches any recent email
            // Since we generate random tokens, we'll need to add an unsubscribe_token column or use a different approach
            // For now, let's implement a simple hash-based system using user ID
            
            // Try to decode the token (assuming it's a simple hash of user ID + secret)
            $users = $db->query("SELECT id, email, unsubscribed FROM users WHERE email_verified = 1")->fetchAll();
            $foundUser = null;
            
            foreach ($users as $user) {
                // For now, let's use a hash of user ID + a secret
                $secretKey = 'unsubscribe_secret_key_2025'; // In production, use env variable
                $expectedToken = hash('sha256', $user['id'] . $secretKey);
                
                if (hash_equals($expectedToken, $token)) {
                    $foundUser = $user;
                    break;
                }
            }
            
            if ($foundUser) {
                if ($action === 'resubscribe') {
                    if ($foundUser['unsubscribed'] == 0) {
                        $error = 'You are already subscribed to newsletters.';
                    } else {
                        // Mark user as resubscribed
                        $stmt = $db->prepare("UPDATE users SET unsubscribed = 0 WHERE id = ?");
                        $stmt->execute([$foundUser['id']]);
                        
                        $success = true;
                    }
                } else {
                    // Default action is unsubscribe
                    if ($foundUser['unsubscribed'] == 1) {
                        $error = 'You are already unsubscribed from newsletters.';
                    } else {
                        // Mark user as unsubscribed without affecting their account verification
                        $stmt = $db->prepare("UPDATE users SET unsubscribed = 1 WHERE id = ?");
                        $stmt->execute([$foundUser['id']]);
                        
                        $success = true;
                    }
                }
            } else {
                $error = 'Invalid or expired link. Please contact support if you need assistance.';
            }
        }
    } catch (Exception $e) {
        error_log("Unsubscribe error: " . $e->getMessage());
        $error = 'An error occurred while processing your request. Please contact support.';
    }
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
            <?php renderLogo('md'); ?>
            <h2 class="mt-6 text-3xl font-extrabold text-gray-900"><?php echo $action === 'resubscribe' ? 'Resubscribe' : 'Unsubscribe'; ?></h2>
        </div>

        <?php if ($success): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded">
            <i class="fas fa-check-circle mr-2"></i>
            <?php if ($action === 'resubscribe'): ?>
                You have been successfully resubscribed to MorningNewsletter. You will start receiving daily newsletters again.
            <?php else: ?>
                You have been successfully unsubscribed from MorningNewsletter. You will no longer receive daily newsletters.
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="bg-yellow-50 border border-yellow-200 text-yellow-700 px-4 py-3 rounded">
            <i class="fas fa-info-circle mr-2"></i>
            <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>
        
        <div class="text-center">
            <a href="/" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                <i class="fas fa-arrow-left mr-2"></i>
                Return to Homepage
            </a>
        </div>

        <div class="text-center text-sm text-gray-600">
            <p>Need help? Contact us at <a href="mailto:hello@morningnewsletter.com" class="text-blue-600 hover:text-blue-800">hello@morningnewsletter.com</a></p>
        </div>
    </div>
</body>
</html>