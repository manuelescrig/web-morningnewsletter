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
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        /* Header */
        .email-header {
            background: linear-gradient(135deg, #468BE6 0%, #1A5799 100%);
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
        
        /* Modern widget sections */
        .widget-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .widget-card {
            background-color: #ffffff;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
            position: relative;
            overflow: hidden;
        }
        
        .widget-card.full-width {
            grid-column: span 2;
        }
        
        .widget-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }
        
        .widget-title {
            font-size: 14px;
            font-weight: 600;
            color: #374151;
            margin: 0;
            display: flex;
            align-items: center;
        }
        
        .widget-icon {
            width: 20px;
            height: 20px;
            margin-right: 8px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
        }
        
        .widget-value {
            font-size: 32px;
            font-weight: 700;
            color: #111827;
            margin: 0;
            line-height: 1.1;
        }
        
        .widget-subtitle {
            font-size: 14px;
            color: #6b7280;
            margin: 8px 0 0 0;
        }
        
        .widget-change {
            display: flex;
            align-items: center;
            font-size: 14px;
            font-weight: 500;
            margin-top: 8px;
            padding: 6px 12px;
            border-radius: 20px;
            width: fit-content;
        }
        
        .widget-change.positive {
            background-color: #dcfce7;
            color: #166534;
        }
        
        .widget-change.negative {
            background-color: #fef2f2;
            color: #dc2626;
        }
        
        .widget-change.neutral {
            background-color: #f3f4f6;
            color: #6b7280;
        }
        
        .widget-change-icon {
            margin-right: 4px;
        }
        
        .widget-details {
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid #f3f4f6;
        }
        
        .widget-detail-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 4px 0;
            font-size: 14px;
        }
        
        .widget-detail-label {
            color: #6b7280;
        }
        
        .widget-detail-value {
            color: #111827;
            font-weight: 500;
        }
        
        /* Bitcoin widget specific styles */
        .widget-card.bitcoin {
            background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
            color: white;
        }
        
        .widget-card.bitcoin .widget-title,
        .widget-card.bitcoin .widget-value,
        .widget-card.bitcoin .widget-subtitle {
            color: white;
        }
        
        .widget-card.bitcoin .widget-icon {
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
        }
        
        .widget-card.bitcoin .widget-change {
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
        }
        
        .widget-card.bitcoin .widget-details {
            border-top-color: rgba(255, 255, 255, 0.2);
        }
        
        .widget-card.bitcoin .widget-detail-label {
            color: rgba(255, 255, 255, 0.8);
        }
        
        .widget-card.bitcoin .widget-detail-value {
            color: white;
        }
        
        /* Weather widget specific styles */
        .widget-card.weather {
            background: linear-gradient(135deg, #1f2937 0%, #374151 100%);
            color: white;
        }
        
        .widget-card.weather .widget-title,
        .widget-card.weather .widget-value,
        .widget-card.weather .widget-subtitle {
            color: white;
        }
        
        .widget-card.weather .widget-icon {
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
        }
        
        .widget-card.weather .widget-details {
            border-top-color: rgba(255, 255, 255, 0.2);
        }
        
        .widget-card.weather .widget-detail-label {
            color: rgba(255, 255, 255, 0.8);
        }
        
        .widget-card.weather .widget-detail-value {
            color: white;
        }
        
        /* Legacy source sections for non-widget sources */
        .source-section {
            margin-bottom: 30px;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #468BE6;
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
            color: #468BE6;
            text-decoration: none;
        }
        
        .email-footer a:hover {
            text-decoration: underline;
        }
        
        .email-footer a[href="#"] {
            color: #9ca3af;
            cursor: not-allowed;
        }
        
        .email-footer a[href="#"]:hover {
            text-decoration: none;
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
            
            .widget-container {
                grid-template-columns: 1fr;
                gap: 16px;
            }
            
            .widget-card {
                padding: 20px;
            }
            
            .widget-card.full-width {
                grid-column: span 1;
            }
            
            .widget-value {
                font-size: 28px;
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
        <!-- View in Browser Link -->
        {{VIEW_IN_BROWSER_SECTION}}
        
        <!-- Header -->
        <div class="email-header">
            <h1>🌅 {{NEWSLETTER_TITLE}}</h1>
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
            <p>You're receiving this because you subscribed to "{{NEWSLETTER_TITLE}}"</p>
            <p>
                <a href="{{BASE_URL}}/dashboard/">Manage Preferences</a> | 
                <a href="{{BASE_URL}}/unsubscribe.php?token={{UNSUBSCRIBE_TOKEN}}">Unsubscribe</a>
            </p>
            <p style="margin-top: 15px; color: #9ca3af;">
                © {{CURRENT_YEAR}} MorningNewsletter. All rights reserved.
            </p>
        </div>
    </div>
</body>
</html>