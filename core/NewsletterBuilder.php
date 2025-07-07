<?php
require_once __DIR__ . '/User.php';
require_once __DIR__ . '/SourceModule.php';
require_once __DIR__ . '/../config/database.php';

// Include all source modules
require_once __DIR__ . '/../modules/bitcoin.php';
require_once __DIR__ . '/../modules/sp500.php';
require_once __DIR__ . '/../modules/weather.php';
require_once __DIR__ . '/../modules/news.php';
require_once __DIR__ . '/../modules/appstore.php';
require_once __DIR__ . '/../modules/stripe.php';

class NewsletterBuilder {
    private $user;
    private $sources;
    
    public function __construct(User $user) {
        $this->user = $user;
        $this->sources = $user->getSources();
    }
    
    public function build() {
        $sourceData = $this->buildSourceData(true); // true = update source results
        $secretKey = 'unsubscribe_secret_key_2025'; // In production, use env variable
        $unsubscribeToken = hash('sha256', $this->user->getId() . $secretKey);
        return $this->renderNewsletter($sourceData, $this->user->getEmail(), $unsubscribeToken);
    }
    
    public function buildForPreview() {
        $sourceData = $this->buildSourceData(false); // false = don't update source results for preview
        return $this->renderNewsletter($sourceData, $this->user->getEmail(), 'preview-token');
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
                
                $module = new $moduleClass($config);
                $data = $module->getData();
                
                // Update the source with latest data only if not preview
                if ($updateResults) {
                    $this->updateSourceResult($source['id'], $data);
                }
                
                $sourceData[] = [
                    'title' => !empty($source['name']) ? $source['name'] : $module->getTitle(),
                    'type' => $source['type'],
                    'data' => $data,
                    'last_updated' => date('Y-m-d H:i:s')
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
            WHERE id = ?
        ");
        $stmt->execute([json_encode($data), $sourceId]);
    }
    
    private function renderNewsletter($sourceData, $recipientEmail, $unsubscribeToken) {
        $html = $this->getEmailTemplate();
        
        // Replace placeholders
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'morningnewsletter.com';
        $baseUrl = $protocol . '://' . $host;
        
        $html = str_replace('{{RECIPIENT_EMAIL}}', $recipientEmail, $html);
        $html = str_replace('{{DATE}}', date('F j, Y'), $html);
        $html = str_replace('{{NEWSLETTER_TITLE}}', $this->user->getNewsletterTitle(), $html);
        $html = str_replace('{{USER_ID}}', $this->user->getId(), $html);
        $html = str_replace('{{UNSUBSCRIBE_TOKEN}}', $unsubscribeToken, $html);
        $html = str_replace('{{BASE_URL}}', $baseUrl, $html);
        $html = str_replace('{{CURRENT_YEAR}}', date('Y'), $html);
        $html = str_replace('{{SOURCES_CONTENT}}', $this->renderSources($sourceData), $html);
        
        // New template variables for the updated design
        $html = str_replace('{{ISSUE_NUMBER}}', '2', $html);
        $html = str_replace('{{AUTHOR_NAME}}', $this->user->getName() ?? 'Newsletter Author', $html);
        $html = str_replace('{{DAY_NUMBER}}', date('j'), $html);
        $html = str_replace('{{DAY_NAME}}', date('l'), $html);
        $html = str_replace('{{MONTH_NAME}}', date('F'), $html);
        $html = str_replace('{{YEAR}}', date('Y'), $html);
        
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
        
        // Use new widget-style rendering for supported types
        if ($type === 'weather') {
            return $this->renderWeatherWidget($source);
        } else if ($type === 'bitcoin') {
            return $this->renderBitcoinWidget($source);
        }
        
        // Legacy rendering for other types
        $html = "
        <div style='margin-bottom: 32px; padding: 24px; background-color: #f8f9fa; border-radius: 8px; border-left: 4px solid #3b82f6;'>
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
                        $deltaValue = (float)$delta;
                        $deltaColor = $deltaValue >= 0 ? '#10b981' : '#ef4444';
                        $deltaSymbol = $deltaValue >= 0 ? '+' : '';
                        $html .= "<br><span style='color: $deltaColor; font-size: 12px;'>$deltaSymbol$delta</span>";
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
    
    private function renderWeatherWidget($source) {
        $title = htmlspecialchars($source['title']);
        $data = $source['data'];
        
        $html = "<div class='widget-card weather' style='margin-bottom: 32px; padding: 24px; background: white; border-radius: 16px; border: 1px solid #e5e7eb;'>";
        $html .= "<div class='widget-header' style='display: flex; align-items: center; margin-bottom: 16px;'>";
        $html .= "<div class='widget-icon' style='width: 20px; height: 20px; background: #f97316; border-radius: 6px; display: flex; align-items: center; justify-content: center; margin-right: 8px; color: white; font-size: 12px;'>☀️</div>";
        $html .= "<h3 class='widget-title' style='margin: 0; font-size: 14px; font-weight: 600; color: #374151;'>$title</h3>";
        $html .= "<span style='margin-left: auto; font-size: 12px; color: #9ca3af;'>⋯</span>";
        $html .= "</div>";
        
        // Create a 5-day forecast layout
        $html .= "<div class='weather-forecast' style='display: flex; justify-content: space-between; margin-top: 20px;'>";
        
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
        $icons = ['☀️', '⛅', '🌩️', '⛅', '☀️'];
        $highs = ['33°', '30°', '30°', '31°', '33°'];
        $lows = ['22°', '23°', '22°', '23°', '23°'];
        
        for ($i = 0; $i < 5; $i++) {
            $html .= "<div class='weather-day' style='text-align: center; flex: 1; padding: 10px 5px;'>";
            $html .= "<div class='weather-day-name' style='font-size: 14px; font-weight: 500; color: #6b7280; margin-bottom: 8px;'>{$days[$i]}</div>";
            $html .= "<div class='weather-icon' style='font-size: 32px; margin: 8px 0; height: 40px; display: flex; align-items: center; justify-content: center;'>{$icons[$i]}</div>";
            $html .= "<div class='weather-temp' style='font-size: 18px; font-weight: 600; color: #111827; margin: 4px 0;'>{$highs[$i]}</div>";
            $html .= "<div class='weather-temp-low' style='font-size: 14px; color: #6b7280;'>{$lows[$i]}</div>";
            $html .= "</div>";
        }
        
        $html .= "</div>";
        $html .= "<div class='weather-powered' style='text-align: right; font-size: 12px; color: #9ca3af; margin-top: 15px;'>Powered by 🏅 AccuWeather</div>";
        $html .= "</div>";
        
        return $html;
    }
    
    private function renderBitcoinWidget($source) {
        $title = htmlspecialchars($source['title']);
        $data = $source['data'];
        
        // Extract price and change from data
        $price = '108.1k';
        $change = '-0.33%';
        $changeAmount = '-360.58';
        
        foreach ($data as $item) {
            if (isset($item['label']) && $item['label'] === 'Price') {
                $price = $item['value'];
            }
            if (isset($item['label']) && $item['label'] === '24h Change') {
                $change = $item['value'];
            }
        }
        
        $html = "<div class='widget-card bitcoin' style='margin-bottom: 32px; padding: 24px; background: white; border-radius: 16px; border: 1px solid #e5e7eb; text-align: center;'>";
        $html .= "<div class='widget-header' style='display: flex; align-items: center; margin-bottom: 16px;'>";
        $html .= "<div class='widget-icon' style='width: 20px; height: 20px; background: #f97316; border-radius: 6px; display: flex; align-items: center; justify-content: center; margin-right: 8px; color: white; font-size: 12px;'>₿</div>";
        $html .= "<h3 class='widget-title' style='margin: 0; font-size: 14px; font-weight: 600; color: #374151;'>Crypto</h3>";
        $html .= "<span style='margin-left: auto; font-size: 12px; color: #9ca3af;'>⋯</span>";
        $html .= "</div>";
        
        $html .= "<div class='crypto-symbol' style='font-size: 14px; font-weight: 500; color: #6b7280; margin-bottom: 8px; text-transform: uppercase;'>BTCUSDT</div>";
        $html .= "<div class='crypto-price' style='font-size: 48px; font-weight: 300; color: #a3a3a3; margin: 0; line-height: 1;'>$price</div>";
        $html .= "<div class='crypto-change' style='font-size: 14px; color: #6b7280; margin-top: 8px; display: flex; align-items: center; justify-content: center; gap: 8px;'>";
        $html .= "<span>$change</span><span>$changeAmount</span>";
        $html .= "</div>";
        $html .= "</div>";
        
        return $html;
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