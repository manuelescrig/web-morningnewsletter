<?php
require_once __DIR__ . '/User.php';
require_once __DIR__ . '/SourceModule.php';

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
                
                $sourceData[] = [
                    'title' => $module->getTitle(),
                    'type' => $source['type'],
                    'data' => $data,
                    'last_updated' => $source['last_updated']
                ];
                
                // Update last_result in database
                $this->updateSourceResult($source['id'], $data);
            } catch (Exception $e) {
                error_log("Error fetching data for source {$source['type']}: " . $e->getMessage());
                
                // Add a placeholder for failed sources so users know something went wrong
                $sourceData[] = [
                    'title' => ucfirst($source['type']) . ' (Error)',
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
        
        return $this->renderNewsletter($sourceData);
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
    
    private function renderNewsletter($sourceData) {
        $html = $this->getEmailTemplate();
        
        // Replace placeholders
        $html = str_replace('{{USER_EMAIL}}', $this->user->getEmail(), $html);
        $html = str_replace('{{DATE}}', date('F j, Y'), $html);
        $html = str_replace('{{SOURCES_CONTENT}}', $this->renderSources($sourceData), $html);
        
        return $html;
    }
    
    private function renderSources($sourceData) {
        $content = '';
        
        foreach ($sourceData as $source) {
            $content .= $this->renderSource($source);
        }
        
        return $content;
    }
    
    private function renderSource($source) {
        $html = '<div class="source-section" style="margin-bottom: 30px; padding: 20px; background-color: #f8f9fa; border-radius: 8px;">';
        $html .= '<h2 style="color: #333; margin-bottom: 15px; font-size: 18px;">' . htmlspecialchars($source['title']) . '</h2>';
        
        if (is_array($source['data'])) {
            foreach ($source['data'] as $item) {
                $html .= '<div style="margin-bottom: 10px;">';
                
                if (isset($item['label']) && isset($item['value'])) {
                    $html .= '<strong>' . htmlspecialchars($item['label']) . ':</strong> ';
                    $html .= htmlspecialchars($item['value']);
                    
                    if (isset($item['delta']) && !empty($item['delta'])) {
                        $deltaColor = $item['delta']['color'] ?? 'black';
                        $html .= ' <span style="color: ' . $deltaColor . '; font-weight: bold;">';
                        $html .= htmlspecialchars($item['delta']['value']);
                        $html .= '</span>';
                    }
                }
                
                $html .= '</div>';
            }
        }
        
        $html .= '</div>';
        return $html;
    }
    
    private function getEmailTemplate() {
        $templatePath = __DIR__ . '/../templates/email_template.php';
        if (file_exists($templatePath)) {
            ob_start();
            include $templatePath;
            return ob_get_clean();
        }
        
        // Fallback template
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Your Morning Brief</title>
        </head>
        <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
            <header style="text-align: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 2px solid #e9ecef;">
                <h1 style="color: #2563eb; margin-bottom: 5px;">Your Morning Brief</h1>
                <p style="color: #666; margin: 0;">{{DATE}}</p>
            </header>
            
            <main>
                {{SOURCES_CONTENT}}
            </main>
            
            <footer style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #e9ecef; text-align: center; color: #666; font-size: 12px;">
                <p>You are receiving this because you subscribed to MorningNewsletter.</p>
                <p><a href="#" style="color: #2563eb;">Unsubscribe</a> | <a href="#" style="color: #2563eb;">Update Preferences</a></p>
            </footer>
        </body>
        </html>';
    }
}