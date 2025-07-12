<?php
/**
 * Public Newsletter View Page
 * 
 * Allows users to view their newsletter in the browser
 * URL: /newsletter-view.php?id=HISTORY_ID&token=TOKEN
 */

require_once __DIR__ . '/core/User.php';
require_once __DIR__ . '/core/NewsletterHistory.php';
require_once __DIR__ . '/config/database.php';

// Get parameters
$historyId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$token = isset($_GET['token']) ?? '';

if (!$historyId || !$token) {
    http_response_code(404);
    die('Newsletter not found');
}

// Get newsletter history entry
$historyManager = new NewsletterHistory();
$historyEntry = $historyManager->getHistoryEntry($historyId);

if (!$historyEntry) {
    http_response_code(404);
    die('Newsletter not found');
}

// Verify token - simple token based on history ID and user ID
$secretKey = 'newsletter_view_secret_2025'; // In production, use env variable
$expectedToken = hash('sha256', $historyId . $historyEntry['user_id'] . $secretKey);

if (!hash_equals($expectedToken, $token)) {
    http_response_code(403);
    die('Invalid access token');
}

// Get user information for context
$user = User::findById($historyEntry['user_id']);

if (!$user) {
    http_response_code(404);
    die('User not found');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($historyEntry['title']); ?> - MorningNewsletter</title>
    <link rel="icon" href="/favicon.ico">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 0;
            text-align: center;
        }
        
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        
        .header p {
            margin: 5px 0 0 0;
            opacity: 0.9;
            font-size: 14px;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .newsletter-content {
            padding: 0;
        }
        
        .footer {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            border-top: 1px solid #e9ecef;
            color: #6c757d;
            font-size: 12px;
        }
        
        .footer a {
            color: #007bff;
            text-decoration: none;
        }
        
        .footer a:hover {
            text-decoration: underline;
        }
        
        /* Newsletter content styling */
        .newsletter-content iframe,
        .newsletter-content img {
            max-width: 100% !important;
            height: auto !important;
        }
        
        .newsletter-content table {
            max-width: 100% !important;
        }
        
        /* Print styles */
        @media print {
            .header, .footer {
                display: none !important;
            }
            
            body {
                background: white !important;
            }
            
            .container {
                box-shadow: none !important;
                max-width: none !important;
            }
        }
        
        /* Mobile responsive */
        @media (max-width: 768px) {
            .container {
                margin: 0;
                box-shadow: none;
            }
            
            .header {
                padding: 15px 20px;
            }
            
            .header h1 {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><?php echo htmlspecialchars($historyEntry['title']); ?></h1>
            <p>
                Issue #<?php echo $historyEntry['issue_number']; ?> • 
                <?php echo date('F j, Y g:i A', strtotime($historyEntry['sent_at'])); ?>
            </p>
        </div>
        
        <div class="newsletter-content">
            <?php echo $historyEntry['content']; ?>
        </div>
        
        <div class="footer">
            <p>
                <strong>MorningNewsletter</strong> • 
                Personalized morning briefings delivered to your inbox
            </p>
            <p>
                <a href="/">Visit MorningNewsletter</a> • 
                <a href="/auth/login.php">Login to Dashboard</a>
            </p>
            <p style="margin-top: 15px; font-size: 11px; color: #868e96;">
                This newsletter was sent to <?php echo htmlspecialchars($user->getEmail()); ?> • 
                <a href="mailto:hello@morningnewsletter.com">Contact Support</a>
            </p>
        </div>
    </div>
</body>
</html>