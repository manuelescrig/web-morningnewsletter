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
require_once __DIR__ . '/../modules/weather.php';
require_once __DIR__ . '/../modules/news.php';
require_once __DIR__ . '/../modules/appstore.php';
require_once __DIR__ . '/../modules/stripe.php';

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
            'weather' => 'WeatherModule',
            'news' => 'NewsModule',
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
        
        // Generate view in browser section
        $viewInBrowserSection = '';
        if ($historyId) {
            if ($isPreview) {
                // Gray out the view in browser link for preview/history view
                $viewInBrowserSection = '<div style="background-color: #f8f9fa; padding: 8px 20px; text-align: center; border-bottom: 1px solid #e5e7eb;">
                    <p style="margin: 0; font-size: 12px; color: #6b7280;">
                        Having trouble viewing this email? 
                        <span style="color: #9ca3af; text-decoration: none; font-weight: 500; cursor: not-allowed;">View in browser</span>
                    </p>
                </div>';
            } else {
                $viewSecretKey = 'newsletter_view_secret_2025'; // In production, use env variable
                $viewToken = hash('sha256', $historyId . $this->user->getId() . $viewSecretKey);
                $viewInBrowserUrl = $baseUrl . '/newsletter-view.php?id=' . $historyId . '&token=' . $viewToken;
                
                $viewInBrowserSection = '<div style="background-color: #f8f9fa; padding: 8px 20px; text-align: center; border-bottom: 1px solid #e5e7eb;">
                    <p style="margin: 0; font-size: 12px; color: #6b7280;">
                        Having trouble viewing this email? 
                        <a href="' . htmlspecialchars($viewInBrowserUrl) . '" style="color: #468BE6; text-decoration: none; font-weight: 500;">View in browser</a>
                    </p>
                </div>';
            }
        }
        
        // Handle footer links based on preview mode
        if ($isPreview) {
            // Gray out footer links for preview/history view
            $baseUrl = '#';
            $unsubscribeToken = '#';
        }
        
        $html = str_replace('{{RECIPIENT_EMAIL}}', $recipientEmail, $html);
        $html = str_replace('{{DATE}}', $this->formatDateInUserTimezone('F j, Y'), $html);
        $html = str_replace('{{NEWSLETTER_TITLE}}', $this->newsletter->getTitle(), $html);
        $html = str_replace('{{NEWSLETTER_ID}}', $this->newsletter->getId(), $html);
        $html = str_replace('{{USER_ID}}', $this->user->getId(), $html);
        $html = str_replace('{{UNSUBSCRIBE_TOKEN}}', $unsubscribeToken, $html);
        $html = str_replace('{{BASE_URL}}', $baseUrl, $html);
        $html = str_replace('{{CURRENT_YEAR}}', $this->formatDateInUserTimezone('Y'), $html);
        $html = str_replace('{{VIEW_IN_BROWSER_SECTION}}', $viewInBrowserSection, $html);
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
        $title = htmlspecialchars($source['title']);
        $type = htmlspecialchars($source['type']);
        $lastUpdated = $source['last_updated'];
        
        $html = "
        <div style='margin-bottom: 32px; padding: 24px; background-color: #f8f9fa; border-radius: 8px; border-left: 4px solid #468BE6;'>
            <h2 style='margin: 0 0 16px 0; color: #1f2937; font-size: 20px; font-weight: 600;'>$title</h2>";
        
        if (!empty($source['data']) && is_array($source['data'])) {
            $html .= "<div style='space-y: 12px;'>";
            
            foreach ($source['data'] as $item) {
                if (is_array($item) && isset($item['label'], $item['value'])) {
                    $label = htmlspecialchars($item['label']);
                    $value = htmlspecialchars($item['value']);
                    $delta = $item['delta'] ?? null;
                    
                    $html .= "<div style='display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid #e5e7eb;'>
                                <span style='color: #6b7280; font-weight: 500;'>$label:</span>
                                <div style='text-align: right;'>
                                    <span style='color: #1f2937; font-weight: 600;'>$value</span>";
                    
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
                    
                    $html .= "</div></div>";
                }
            }
            
            $html .= "</div>";
        } else {
            $html .= "<p style='color: #6b7280; font-style: italic;'>No data available</p>";
        }
        
        if ($lastUpdated) {
            $html .= "<div style='margin-top: 12px; text-align: right;'>
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