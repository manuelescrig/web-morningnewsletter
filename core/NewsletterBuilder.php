<?php
require_once __DIR__ . '/User.php';
require_once __DIR__ . '/Newsletter.php';
require_once __DIR__ . '/SourceModule.php';
require_once __DIR__ . '/../config/database.php';

// Include all source modules
require_once __DIR__ . '/../modules/bitcoin.php';
require_once __DIR__ . '/../modules/ethereum.php';
require_once __DIR__ . '/../modules/xrp.php';
require_once __DIR__ . '/../modules/binancecoin.php';
require_once __DIR__ . '/../modules/sp500.php';
require_once __DIR__ . '/../modules/stock.php';
require_once __DIR__ . '/../modules/weather.php';
require_once __DIR__ . '/../modules/news.php';
require_once __DIR__ . '/../modules/rss.php';
require_once __DIR__ . '/../modules/appstore.php';
require_once __DIR__ . '/../modules/stripe.php';
require_once __DIR__ . '/WeatherIconProvider.php';

class NewsletterBuilder {
    private $newsletter;
    private $user;
    private $sources;
    
    public function __construct(Newsletter $newsletter, User $user = null) {
        $this->newsletter = $newsletter;
        $this->user = $user ?? User::findById($newsletter->getUserId());
        $this->sources = $newsletter->getSources();
    }
    
    // Backward compatibility constructor
    public static function fromUser(User $user) {
        $newsletter = $user->getDefaultNewsletter();
        if (!$newsletter) {
            throw new Exception("User has no newsletters");
        }
        return new self($newsletter, $user);
    }
    
    public function build() {
        $sourceData = $this->buildSourceData(true); // true = update source results
        $secretKey = 'unsubscribe_secret_key_2025'; // In production, use env variable
        $unsubscribeToken = hash('sha256', $this->user->getId() . $secretKey);
        return $this->renderNewsletter($sourceData, $this->user->getEmail(), $unsubscribeToken);
    }
    
    public function buildWithSourceData() {
        $sourceData = $this->buildSourceData(true); // true = update source results
        $secretKey = 'unsubscribe_secret_key_2025'; // In production, use env variable
        $unsubscribeToken = hash('sha256', $this->user->getId() . $secretKey);
        $content = $this->renderNewsletter($sourceData, $this->user->getEmail(), $unsubscribeToken);
        
        return [
            'content' => $content,
            'sources_data' => $sourceData
        ];
    }
    
    public function buildWithSourceDataAndHistoryId($historyId) {
        $sourceData = $this->buildSourceData(true); // true = update source results
        $secretKey = 'unsubscribe_secret_key_2025'; // In production, use env variable
        $unsubscribeToken = hash('sha256', $this->user->getId() . $secretKey);
        $content = $this->renderNewsletter($sourceData, $this->user->getEmail(), $unsubscribeToken, $historyId);
        
        return [
            'content' => $content,
            'sources_data' => $sourceData
        ];
    }
    
    
    public function buildForPreview() {
        $sourceData = $this->buildSourceData(false); // false = don't update source results for preview
        return $this->renderNewsletter($sourceData, $this->user->getEmail(), 'preview-token', null, true);
    }
    
    private function buildSourceData($updateResults = true) {
        $sourceData = [];
        
        foreach ($this->sources as $source) {
            try {
                $moduleClass = $this->getModuleClass($source['type']);
                $config = json_decode($source['config'], true) ?: [];
                
                if (!$moduleClass) {
                    throw new Exception("Unknown source type: {$source['type']}");
                }
                
                if (!class_exists($moduleClass)) {
                    throw new Exception("Module class not found: $moduleClass");
                }
                
                $module = new $moduleClass($config, $this->user->getTimezone());
                $data = $module->getData();
                
                // Update the source with latest data only if not preview
                if ($updateResults) {
                    try {
                        $this->updateSourceResult($source['id'], $data);
                    } catch (Exception $dbError) {
                        error_log("Failed to update source {$source['id']}: " . $dbError->getMessage());
                        // Don't fail the entire process if database update fails
                    }
                }
                
                $sourceData[] = [
                    'title' => !empty($source['name']) ? $source['name'] : $module->getTitle(),
                    'type' => $source['type'],
                    'data' => $data,
                    'last_updated' => $this->formatDateInUserTimezone('Y-m-d H:i:s')
                ];
                
            } catch (Exception $e) {
                error_log("Error loading source {$source['type']}: " . $e->getMessage());
                
                $sourceData[] = [
                    'title' => (!empty($source['name']) ? $source['name'] : ucfirst($source['type'])) . ' (Error)',
                    'type' => $source['type'],
                    'data' => [
                        [
                            'label' => 'Status',
                            'value' => 'Failed to load data: ' . $e->getMessage(),
                            'delta' => null
                        ]
                    ],
                    'last_updated' => $source['last_updated']
                ];
            }
        }
        
        return $sourceData;
    }
    
    private function getModuleClass($type) {
        $moduleMap = [
            'bitcoin' => 'BitcoinModule',
            'ethereum' => 'EthereumModule',
            'xrp' => 'XrpModule',
            'binancecoin' => 'BinancecoinModule',
            'sp500' => 'SP500Module',
            'stock' => 'StockModule',
            'weather' => 'WeatherModule',
            'news' => 'NewsModule',
            'rss' => 'RSSModule',
            'appstore' => 'AppStoreModule',
            'stripe' => 'StripeModule'
        ];
        
        return $moduleMap[$type] ?? null;
    }
    
    private function updateSourceResult($sourceId, $data) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            UPDATE sources 
            SET last_result = ?, last_updated = CURRENT_TIMESTAMP 
            WHERE id = ? AND newsletter_id = ?
        ");
        $stmt->execute([json_encode($data), $sourceId, $this->newsletter->getId()]);
    }
    
    private function renderNewsletter($sourceData, $recipientEmail, $unsubscribeToken, $historyId = null, $isPreview = false) {
        $html = $this->getEmailTemplate();
        
        // Replace placeholders
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'morningnewsletter.com';
        $baseUrl = $protocol . '://' . $host;
        
        // Get dynamic values
        $greeting = $this->getTimeBasedGreeting();
        $dateSubtitle = $this->getFormattedDateSubtitle();
        $issueNumber = $historyId ? $this->getIssueNumberFromHistory($historyId) : $this->getNextIssueNumber();
        
        // Generate URLs
        $viewUrl = '#';
        $editUrl = '#';
        
        if ($historyId && !$isPreview) {
            $viewSecretKey = 'newsletter_view_secret_2025'; // In production, use env variable
            $viewToken = hash('sha256', $historyId . $this->user->getId() . $viewSecretKey);
            $viewUrl = $baseUrl . '/newsletter-view.php?id=' . $historyId . '&token=' . $viewToken;
            $editUrl = $baseUrl . '/dashboard/newsletter.php?id=' . $this->newsletter->getId();
        } elseif (!$isPreview) {
            // For non-history emails, still provide edit URL
            $editUrl = $baseUrl . '/dashboard/newsletter.php?id=' . $this->newsletter->getId();
        }
        
        
        // Handle footer links based on preview mode
        if ($isPreview) {
            // Gray out footer links for preview/history view
            $baseUrl = '#';
            $unsubscribeToken = '#';
        }
        
        $html = str_replace('{{RECIPIENT_EMAIL}}', $recipientEmail, $html);
        $html = str_replace('{{DATE}}', $this->formatDateInUserTimezone('F j, Y'), $html);
        $html = str_replace('{{GREETING}}', $greeting, $html);
        $html = str_replace('{{DATE_SUBTITLE}}', $dateSubtitle, $html);
        $html = str_replace('{{ISSUE_NUMBER}}', $issueNumber, $html);
        $html = str_replace('{{VIEW_URL}}', htmlspecialchars($viewUrl), $html);
        $html = str_replace('{{EDIT_URL}}', htmlspecialchars($editUrl), $html);
        $html = str_replace('{{NEWSLETTER_TITLE}}', $this->newsletter->getTitle(), $html);
        $html = str_replace('{{NEWSLETTER_ID}}', $this->newsletter->getId(), $html);
        $html = str_replace('{{USER_ID}}', $this->user->getId(), $html);
        $html = str_replace('{{UNSUBSCRIBE_TOKEN}}', $unsubscribeToken, $html);
        $html = str_replace('{{BASE_URL}}', $baseUrl, $html);
        $html = str_replace('{{CURRENT_YEAR}}', $this->formatDateInUserTimezone('Y'), $html);
        $html = str_replace('{{SOURCES_CONTENT}}', $this->renderSources($sourceData), $html);
        
        return $html;
    }
    
    private function renderSources($sourceData) {
        if (empty($sourceData)) {
            return '<div style="text-align: center; padding: 40px 20px; color: #6b7280;">
                        <p>No data sources configured yet.</p>
                        <p>Add some sources in your dashboard to see content here!</p>
                    </div>';
        }
        
        $html = '';
        foreach ($sourceData as $source) {
            $html .= $this->renderSource($source);
        }
        
        return $html;
    }
    
    private function renderSource($source) {
        // Check if this source has custom layout
        if ($source['type'] === 'weather' && isset($source['data']['main'])) {
            return $this->renderWeatherSource($source);
        }
        
        // Check if this is RSS source for custom layout
        if ($source['type'] === 'rss') {
            return $this->renderRSSSource($source);
        }
        
        // Default rendering for other sources
        $title = htmlspecialchars($source['title']);
        $lastUpdated = $source['last_updated'];
        
        $html = "<div style='margin-bottom: 20px; padding: 20px; background-color: #ffffff; border-radius: 12px; border: 1px solid #e5e7eb;'>
            <h2 style='margin: 0 0 0px 0; color: #111827; font-size: 16px; font-weight: 600;'>$title</h2>";
        
        if (!empty($source['data']) && is_array($source['data'])) {
            $html .= "<div style='space-y: 12px;'>";
            
            foreach ($source['data'] as $item) {
                if (is_array($item) && isset($item['label'], $item['value'])) {
                    $label = htmlspecialchars($item['label']);
                    $value = htmlspecialchars($item['value']);
                    $delta = $item['delta'] ?? null;
                    
                    // Use table layout for better email client compatibility
                    $html .= "<table style='width: 100%; border-collapse: collapse; margin: 6px 0;'>
                                <tr>
                                    <td style='padding: 6px 0; border-bottom: 1px solid #e5e7eb; color: #6b7280; font-weight: 500; text-align: left;'>$label:</td>
                                    <td style='padding: 6px 0; border-bottom: 1px solid #e5e7eb; text-align: right;'>
                                        <span style='color: #1f2937; font-weight: 600; font-size: 20px; letter-spacing: 0.5px;'>$value</span>";
                    
                    if ($delta !== null) {
                        if (is_array($delta) && isset($delta['value'], $delta['color'])) {
                            // New format with formatted value and color
                            $deltaText = htmlspecialchars($delta['value']);
                            $deltaColor = $delta['color'] === 'green' ? '#10b981' : '#ef4444';
                            $html .= "<br><span style='color: $deltaColor; font-size: 12px;'>$deltaText</span>";
                        } else {
                            // Legacy format - numeric value
                            $deltaValue = (float)$delta;
                            $deltaColor = $deltaValue >= 0 ? '#10b981' : '#ef4444';
                            $deltaSymbol = $deltaValue >= 0 ? '+' : '';
                            $html .= "<br><span style='color: $deltaColor; font-size: 12px;'>$deltaSymbol$delta</span>";
                        }
                    }
                    
                    $html .= "</td></tr></table>";
                }
            }
            
            $html .= "</div>";
        } else {
            $html .= "<p style='color: #6b7280; font-style: italic;'>No data available</p>";
        }
        
        if ($lastUpdated) {
            $html .= "<div style='margin-top: 4px; text-align: right;'>
                        <span style='color: #9ca3af; font-size: 12px;'>Updated: $lastUpdated</span>
                      </div>";
        }
        
        $html .= "</div>";
        
        return $html;
    }
    
    private function renderWeatherSource($source) {
        $title = htmlspecialchars($source['title']);
        $data = $source['data'];
        $main = $data['main'];
        $columns = $data['columns'] ?? [];
        $lastUpdated = $source['last_updated'];
        
        $html = "<div style='margin-bottom: 20px; padding: 24px; background-color: #ffffff; border-radius: 16px; border: 1px solid #e5e7eb;'>";
        
        // Header with location using table for Gmail compatibility
        $html .= "<table style='width: 100%; margin-bottom: 20px;'>
                    <tr>
                        <td style='text-align: left;'>
                            <h2 style='margin: 0; color: #111827; font-size: 18px; font-weight: 600;'>$title</h2>
                        </td>
                        <td style='text-align: right;'>
                            <span style='color: #6b7280; font-size: 14px;'>" . htmlspecialchars($main['location']) . "</span>
                        </td>
                    </tr>
                  </table>";
        
        // Main temperature section
        $iconClass = $main['icon_class'] ?? 'fa-cloud-sun';
        $iconHtml = WeatherIconProvider::getHtmlIcon($iconClass, 'large');
        $html .= "<div style='text-align: center; margin-bottom: 24px;'>
                    <div style='margin-bottom: 8px; line-height: 1;'>{$iconHtml}</div>
                    <div style='font-size: 56px; font-weight: 700; color: #111827; line-height: 1;'>" . htmlspecialchars($main['temperature']) . "</div>
                    <div style='font-size: 18px; color: #6b7280; margin-top: 8px;'>" . htmlspecialchars($main['description']) . "</div>
                  </div>";
        
        // Columns section
        if (!empty($columns)) {
            $columnCount = count($columns);
            $columnWidth = $columnCount > 0 ? floor(100 / $columnCount) : 100;
            
            $html .= "<div style='display: table; width: 100%; table-layout: fixed; border-top: 1px solid #e5e7eb; padding-top: 20px;'>";
            
            foreach ($columns as $index => $column) {
                $borderStyle = $index < $columnCount - 1 ? 'border-right: 1px solid #e5e7eb;' : '';
                $iconClass = $column['icon_class'] ?? 'fa-circle';
                
                $html .= "<div style='display: table-cell; width: {$columnWidth}%; padding: 0 12px; text-align: center; vertical-align: top; $borderStyle'>";
                
                // Icon - Fixed height container to ensure consistent alignment
                $columnIconHtml = WeatherIconProvider::getHtmlIcon($iconClass, 'small');
                $html .= "<div style='height: 28px; line-height: 28px; margin-bottom: 4px; vertical-align: middle;'>{$columnIconHtml}</div>";
                
                // Label
                $html .= "<div style='font-size: 12px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;'>" . htmlspecialchars($column['label']) . "</div>";
                
                // Handle high/low temperature display
                if (!empty($column['high_low'])) {
                    // High/Low temperatures with same font size
                    // Show min (subtitle) on top, max (value) on bottom without icons
                    $html .= "<div style='text-align: center;'>
                                <div style='margin-bottom: 4px;'>
                                    <span style='font-size: 20px; font-weight: 600; color: #6b7280;'>" . htmlspecialchars($column['subtitle']) . "</span>
                                </div>
                                <div>
                                    <span style='font-size: 20px; font-weight: 600; color: #111827;'>" . htmlspecialchars($column['value']) . "</span>
                                </div>
                              </div>";
                } else {
                    // Regular value display
                    $html .= "<div style='font-size: 24px; font-weight: 600; color: #111827;'>" . htmlspecialchars($column['value']) . "</div>";
                    
                    if (!empty($column['subtitle'])) {
                        $html .= "<div style='font-size: 14px; color: #6b7280; margin-top: 2px;'>" . htmlspecialchars($column['subtitle']) . "</div>";
                    }
                }
                
                $html .= "</div>";
            }
            
            $html .= "</div>";
        }
        
        if ($lastUpdated) {
            $html .= "<div style='margin-top: 16px; text-align: right;'>
                        <span style='color: #9ca3af; font-size: 12px;'>Updated: $lastUpdated</span>
                      </div>";
        }
        
        $html .= "</div>";
        
        return $html;
    }
    
    private function renderRSSSource($source) {
        $title = htmlspecialchars($source['title']);
        $lastUpdated = $source['last_updated'];
        
        $html = "<div style='margin-bottom: 20px; padding: 20px; background-color: #ffffff; border-radius: 12px; border: 1px solid #e5e7eb;'>
            <h2 style='margin: 0 0 16px 0; color: #111827; font-size: 16px; font-weight: 600;'>$title</h2>";
        
        if (!empty($source['data']) && is_array($source['data'])) {
            foreach ($source['data'] as $item) {
                if (is_array($item) && isset($item['label'], $item['value'])) {
                    // Skip header items
                    if (isset($item['is_header']) && $item['is_header']) {
                        continue;
                    }
                    
                    $label = htmlspecialchars($item['label']);
                    $value = htmlspecialchars($item['value']);
                    $delta = $item['delta'] ?? null;
                    
                    // Single column layout with stacked title and description
                    $html .= "<div style='margin-bottom: 16px; padding-bottom: 16px; border-bottom: 1px solid #e5e7eb;'>
                                <div style='color: #111827; font-weight: 600; font-size: 15px; margin-bottom: 4px;'>$label</div>
                                <div style='color: #6b7280; font-size: 14px; line-height: 1.5;'>$value</div>";
                    
                    // Add "Read more" link if available
                    if ($delta !== null && is_array($delta) && isset($delta['value'], $delta['link']) && !empty($delta['link'])) {
                        $linkText = htmlspecialchars($delta['value']);
                        $linkUrl = htmlspecialchars($delta['link']);
                        $html .= "<div style='margin-top: 6px;'>
                                    <a href='$linkUrl' style='color: #468BE6; text-decoration: none; font-size: 13px;'>$linkText</a>
                                  </div>";
                    }
                    
                    $html .= "</div>";
                }
            }
            
            // Remove border from last item
            $html = preg_replace('/(border-bottom: 1px solid #e5e7eb;)(?!.*border-bottom: 1px solid #e5e7eb;)/', '', $html);
        } else {
            $html .= "<p style='color: #6b7280; font-style: italic;'>No data available</p>";
        }
        
        if ($lastUpdated) {
            $html .= "<div style='margin-top: 16px; text-align: right;'>
                        <span style='color: #9ca3af; font-size: 12px;'>Updated: $lastUpdated</span>
                      </div>";
        }
        
        $html .= "</div>";
        
        return $html;
    }
    
    private function formatDateInUserTimezone($format) {
        try {
            // Get the user's timezone or fallback to newsletter timezone
            $userTimezone = $this->user->getTimezone() ?? $this->newsletter->getTimezone() ?? 'UTC';
            $dateTime = new DateTime('now', new DateTimeZone($userTimezone));
            return $dateTime->format($format);
        } catch (Exception $e) {
            // Fallback to server timezone if user timezone is invalid
            return date($format);
        }
    }
    
    private function getTimeBasedGreeting() {
        try {
            // Get the user's timezone or fallback to newsletter timezone
            $userTimezone = $this->user->getTimezone() ?? $this->newsletter->getTimezone() ?? 'UTC';
            $dateTime = new DateTime('now', new DateTimeZone($userTimezone));
            $hour = (int)$dateTime->format('G'); // 0-23 hour format
            
            if ($hour >= 5 && $hour < 12) {
                return 'Good morning';
            } elseif ($hour >= 12 && $hour < 17) {
                return 'Good afternoon';
            } elseif ($hour >= 17 && $hour < 21) {
                return 'Good evening';
            } else {
                return 'Good night';
            }
        } catch (Exception $e) {
            // Fallback to morning greeting if timezone is invalid
            return 'Good morning';
        }
    }
    
    private function getFormattedDateSubtitle() {
        try {
            // Get the user's timezone or fallback to newsletter timezone
            $userTimezone = $this->user->getTimezone() ?? $this->newsletter->getTimezone() ?? 'UTC';
            $dateTime = new DateTime('now', new DateTimeZone($userTimezone));
            // Format: "It's Monday, July 21"
            return "It's " . $dateTime->format('l, F j');
        } catch (Exception $e) {
            // Fallback format if timezone is invalid
            return "It's " . date('l, F j');
        }
    }
    
    private function getNextIssueNumber() {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("
                SELECT COALESCE(MAX(issue_number), 0) + 1 as next_issue 
                FROM newsletter_history 
                WHERE newsletter_id = ?
            ");
            $stmt->execute([$this->newsletter->getId()]);
            $result = $stmt->fetch();
            return $result['next_issue'] ?? 1;
        } catch (Exception $e) {
            error_log("Error getting next issue number: " . $e->getMessage());
            return 1;
        }
    }
    
    private function getIssueNumberFromHistory($historyId) {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("
                SELECT issue_number 
                FROM newsletter_history 
                WHERE id = ?
            ");
            $stmt->execute([$historyId]);
            $result = $stmt->fetch();
            return $result['issue_number'] ?? 1;
        } catch (Exception $e) {
            error_log("Error getting issue number from history: " . $e->getMessage());
            return 1;
        }
    }
    
    private function getEmailTemplate() {
        $templatePath = __DIR__ . '/../templates/email_template.php';
        if (file_exists($templatePath)) {
            return file_get_contents($templatePath);
        }
        
        // Fallback template
        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{NEWSLETTER_TITLE}}</title>
</head>
<body>
    <h1>{{NEWSLETTER_TITLE}} - {{DATE}}</h1>
    {{SOURCES_CONTENT}}
    <p><a href="mailto:hello@morningnewsletter.com?subject=Unsubscribe&body=Token: {{UNSUBSCRIBE_TOKEN}}">Unsubscribe</a></p>
</body>
</html>';
    }
}