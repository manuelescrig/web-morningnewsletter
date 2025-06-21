<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Morning Brief - {{DATE}}</title>
    <style>
        /* Reset styles */
        body, table, td, p, a, li, blockquote {
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }
        
        /* Base styles */
        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 14px;
            line-height: 1.6;
            color: #333333;
            background-color: #f8f9fa;
        }
        
        /* Container */
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        /* Header */
        .email-header {
            background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        
        .email-header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        
        .email-header .date {
            margin: 8px 0 0 0;
            font-size: 16px;
            opacity: 0.9;
        }
        
        /* Main content */
        .email-content {
            padding: 30px 20px;
        }
        
        .greeting {
            font-size: 16px;
            margin-bottom: 25px;
            color: #374151;
        }
        
        /* Source sections */
        .source-section {
            margin-bottom: 30px;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #2563eb;
        }
        
        .source-title {
            color: #1f2937;
            font-size: 18px;
            font-weight: 600;
            margin: 0 0 15px 0;
            display: flex;
            align-items: center;
        }
        
        .source-title::before {
            content: '📊';
            margin-right: 8px;
            font-size: 16px;
        }
        
        .source-item {
            margin-bottom: 12px;
            padding: 8px 0;
        }
        
        .source-item:last-child {
            margin-bottom: 0;
        }
        
        .source-label {
            font-weight: 500;
            color: #374151;
            margin-right: 8px;
        }
        
        .source-value {
            color: #1f2937;
            font-weight: 400;
        }
        
        .delta-positive {
            color: #059669;
            font-weight: 500;
        }
        
        .delta-negative {
            color: #dc2626;
            font-weight: 500;
        }
        
        .delta-neutral {
            color: #6b7280;
            font-weight: 500;
        }
        
        /* Custom icons for different source types */
        .source-section[data-type="bitcoin"] .source-title::before { content: '₿'; }
        .source-section[data-type="sp500"] .source-title::before { content: '📈'; }
        .source-section[data-type="weather"] .source-title::before { content: '🌤️'; }
        .source-section[data-type="news"] .source-title::before { content: '📰'; }
        .source-section[data-type="stripe"] .source-title::before { content: '💳'; }
        .source-section[data-type="appstore"] .source-title::before { content: '📱'; }
        
        /* Footer */
        .email-footer {
            background-color: #f3f4f6;
            padding: 20px;
            text-align: center;
            border-top: 1px solid #e5e7eb;
        }
        
        .email-footer p {
            margin: 0 0 10px 0;
            font-size: 12px;
            color: #6b7280;
        }
        
        .email-footer a {
            color: #2563eb;
            text-decoration: none;
        }
        
        .email-footer a:hover {
            text-decoration: underline;
        }
        
        /* Mobile responsive */
        @media only screen and (max-width: 600px) {
            .email-container {
                margin: 0;
                border-radius: 0;
            }
            
            .email-header {
                padding: 20px 15px;
            }
            
            .email-header h1 {
                font-size: 20px;
            }
            
            .email-content {
                padding: 20px 15px;
            }
            
            .source-section {
                padding: 15px;
                margin-bottom: 20px;
            }
            
            .source-title {
                font-size: 16px;
            }
        }
        
        /* Dark mode support */
        @media (prefers-color-scheme: dark) {
            .email-container {
                background-color: #1f2937;
            }
            
            .email-content {
                color: #f9fafb;
            }
            
            .source-section {
                background-color: #374151;
            }
            
            .source-title {
                color: #f9fafb;
            }
            
            .source-label {
                color: #d1d5db;
            }
            
            .source-value {
                color: #f3f4f6;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="email-header">
            <h1>🌅 Your Morning Brief</h1>
            <p class="date">{{DATE}}</p>
        </div>
        
        <!-- Main Content -->
        <div class="email-content">
            <p class="greeting">Good morning! Here's your personalized morning update:</p>
            
            <!-- Sources Content (Will be replaced by PHP) -->
            {{SOURCES_CONTENT}}
            
            <!-- Empty state (shown when no sources) -->
            <div style="display: none;" id="empty-state">
                <div style="text-align: center; padding: 40px 20px; color: #6b7280;">
                    <div style="font-size: 48px; margin-bottom: 16px;">📋</div>
                    <h3 style="margin: 0 0 8px 0; color: #374151;">No sources configured</h3>
                    <p style="margin: 0; font-size: 14px;">Add data sources in your dashboard to start receiving updates.</p>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="email-footer">
            <p>You're receiving this because you subscribed to MorningNewsletter</p>
            <p>
                <a href="http://<?php echo $_SERVER['HTTP_HOST'] ?? 'morningnewsletter.com'; ?>/dashboard/">Manage Preferences</a> | 
                <a href="http://<?php echo $_SERVER['HTTP_HOST'] ?? 'morningnewsletter.com'; ?>/unsubscribe.php?email={{USER_EMAIL}}">Unsubscribe</a>
            </p>
            <p style="margin-top: 15px; color: #9ca3af;">
                © <?php echo date('Y'); ?> MorningNewsletter. All rights reserved.
            </p>
        </div>
    </div>
</body>
</html>