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
            font-family: ui-sans-serif, system-ui, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";
            font-size: 14px;
            line-height: 1.6;
            color: #333333;
            background-color: #ffffff;
        }
        
        /* Main content */
        .email-content {
            padding: 30px 20px;
        }
        
        /* Widget container */
        .widget-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        /* Content boxes */
        .widget-card {
            background-color: #ffffff;
            border-radius: 12px;
            padding: 24px;
            border: 1px solid #e5e7eb;
        }
        
        .widget-card.full-width {
            grid-column: span 2;
        }
        
        .widget-title {
            font-size: 14px;
            font-weight: 600;
            color: #374151;
            margin: 0 0 16px 0;
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
            display: inline-block;
            font-size: 14px;
            font-weight: 500;
            margin-top: 8px;
            padding: 6px 12px;
            border-radius: 20px;
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
        
        /* Legacy source sections */
        .source-section {
            margin-bottom: 20px;
            padding: 20px;
            background-color: #ffffff;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
        }
        
        .source-title {
            color: #111827;
            font-size: 16px;
            font-weight: 600;
            margin: 0 0 15px 0;
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
            color: #111827;
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
            background-color: #ffffff;
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
        
        /* Mobile responsive */
        @media only screen and (max-width: 600px) {
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
    </style>
</head>
<body>
    <!-- Email Container with proper width constraint -->
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" width="600" style="margin: auto; background-color: #ffffff;">
        <tr>
            <td>
                <!-- Header Section -->
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #ffffff;">
                    <tr>
                        <td style="padding: 30px 20px 20px 20px; text-align: left;">
                            <!-- Title (darkest black) -->
                            <h2 style="margin: 0; padding: 0; font-size: 32px; font-weight: 700; color: #000000; text-transform: capitalize; letter-spacing: 0.5px; line-height: 38px;">
                                {{GREETING}}
                            </h2>
                            <!-- Subtitle (medium black) -->
                            <p style="margin: 8px 0 0 0; padding: 0; font-size: 20px; color: #333333; font-weight: 600; letter-spacing: 0.3px; line-height: 26px;">
                                {{DATE_SUBTITLE}}
                            </p>
                            <!-- Caption (light gray) -->
                            <p style="margin: 8px 0 0 0; padding: 0; font-size: 14px; color: #666666; font-weight: 400; line-height: 20px;">
                                {{NEWSLETTER_TITLE}} · Issue #{{ISSUE_NUMBER}} · 
                                <a href="{{VIEW_URL}}" style="color: #468BE6; text-decoration: none;">View in browser</a> · 
                                <a href="{{EDIT_URL}}" style="color: #468BE6; text-decoration: none;">Edit</a>
                            </p>
                        </td>
                    </tr>
                    <!-- Dividing Line -->
                    <tr>
                        <td style="padding: 0 20px;">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                <tr>
                                    <td style="border-bottom: 1px solid #e5e7eb; line-height: 1px; font-size: 1px;">&nbsp;</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
                
                <!-- Main Content -->
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                    <tr>
                        <td style="padding: 30px 20px;" class="email-content">
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
                        </td>
                    </tr>
                </table>
                
                <!-- Footer -->
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" class="email-footer">
                    <tr>
                        <td style="padding: 20px; text-align: center;">
                            <p style="margin: 0 0 10px 0; font-size: 12px; color: #6b7280;">You're receiving this because you subscribed to "{{NEWSLETTER_TITLE}}"</p>
                            <p style="margin: 0 0 10px 0; font-size: 12px;">
                                <a href="{{BASE_URL}}/dashboard/" style="color: #468BE6; text-decoration: none;">Manage Preferences</a> | 
                                <a href="{{BASE_URL}}/unsubscribe.php?token={{UNSUBSCRIBE_TOKEN}}" style="color: #468BE6; text-decoration: none;">Unsubscribe</a>
                            </p>
                            <p style="margin: 15px 0 0 0; color: #9ca3af; font-size: 12px;">
                                © {{CURRENT_YEAR}} MorningNewsletter. All rights reserved.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>