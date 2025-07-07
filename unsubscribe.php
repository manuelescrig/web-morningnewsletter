<?php
require_once __DIR__ . '/core/NewsletterRecipient.php';
require_once __DIR__ . '/core/Newsletter.php';
require_once __DIR__ . '/includes/logo.php';

$token = $_GET['token'] ?? '';
$success = false;
$error = '';
$recipient = null;
$newsletter = null;

if (empty($token)) {
    $error = 'Invalid unsubscribe link. Please contact support if you need assistance.';
} else {
    // Try to find recipient by token
    $recipient = NewsletterRecipient::findByUnsubscribeToken($token);
    
    if (!$recipient) {
        $error = 'Invalid or expired unsubscribe link. You may have already unsubscribed or the link may be outdated.';
    } else {
        $newsletter = $recipient->getNewsletter();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['confirm_unsubscribe'])) {
                try {
                    if ($recipient->unsubscribe()) {
                        $success = true;
                    } else {
                        $error = 'Failed to unsubscribe. Please try again or contact support.';
                    }
                } catch (Exception $e) {
                    $error = 'An error occurred: ' . $e->getMessage();
                }
            }
        }
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
            <?php renderLogo('lg'); ?>
            <h2 class="mt-6 text-3xl font-extrabold text-gray-900">Unsubscribe</h2>
        </div>

        <?php if ($error): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            <?php echo htmlspecialchars($error); ?>
        </div>
        <div class="text-center">
            <a href="/" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                <i class="fas fa-arrow-left mr-2"></i>
                Return to Homepage
            </a>
        </div>

        <?php elseif ($success): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded">
            <i class="fas fa-check-circle mr-2"></i>
            You have been successfully unsubscribed from "<?php echo htmlspecialchars($newsletter->getTitle()); ?>".
        </div>
        
        <div class="bg-blue-50 border border-blue-200 text-blue-700 px-4 py-3 rounded">
            <i class="fas fa-info-circle mr-2"></i>
            <p class="mb-2">We're sorry to see you go! You will no longer receive this newsletter.</p>
            <p>If you change your mind, you can always re-subscribe by creating a new account or contacting support.</p>
        </div>

        <div class="text-center space-y-4">
            <a href="/" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                <i class="fas fa-home mr-2"></i>
                Return to Homepage
            </a>
            <div class="text-sm text-gray-500">
                <a href="/auth/register.php" class="text-blue-600 hover:text-blue-500">
                    Create a new account
                </a>
            </div>
        </div>

        <?php elseif ($recipient && $newsletter): ?>
        <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 rounded">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            <p class="mb-2">You are about to unsubscribe from:</p>
            <p class="font-semibold"><?php echo htmlspecialchars($newsletter->getTitle()); ?></p>
            <p class="text-sm mt-1">Email: <?php echo htmlspecialchars($recipient->getEmail()); ?></p>
        </div>

        <form method="POST" class="space-y-6">
            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Are you sure you want to unsubscribe?</h3>
                
                <div class="space-y-4">
                    <p class="text-sm text-gray-600">
                        If you unsubscribe, you will no longer receive the "<?php echo htmlspecialchars($newsletter->getTitle()); ?>" newsletter.
                    </p>
                    
                    <div class="bg-gray-50 p-4 rounded">
                        <p class="text-sm font-medium text-gray-900 mb-2">Before you go, consider:</p>
                        <ul class="text-sm text-gray-600 space-y-1">
                            <li>• You can always manage your email preferences in your account settings</li>
                            <li>• You might miss important updates and insights</li>
                            <li>• Re-subscribing requires creating a new account</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="flex space-x-4">
                <button type="submit" name="confirm_unsubscribe" value="1" 
                        class="flex-1 inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700">
                    <i class="fas fa-times mr-2"></i>
                    Yes, Unsubscribe
                </button>
                <a href="/" 
                   class="flex-1 inline-flex justify-center items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Keep Subscription
                </a>
            </div>
        </form>

        <div class="text-center text-sm text-gray-500">
            <p>Having trouble? <a href="mailto:hello@morningnewsletter.com" class="text-blue-600 hover:text-blue-500">Contact support</a></p>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>