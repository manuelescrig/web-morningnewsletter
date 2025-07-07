<?php
require_once __DIR__ . '/User.php';
require_once __DIR__ . '/Newsletter.php';
require_once __DIR__ . '/NewsletterRecipient.php';
require_once __DIR__ . '/SourceModule.php';

// Include all source modules
require_once __DIR__ . '/../modules/bitcoin.php';
require_once __DIR__ . '/../modules/sp500.php';
require_once __DIR__ . '/../modules/weather.php';
require_once __DIR__ . '/../modules/news.php';
require_once __DIR__ . '/../modules/appstore.php';
require_once __DIR__ . '/../modules/stripe.php';

class NewsletterBuilder {
    private $newsletter;
    private $user;
    private $sources;
    
    public function __construct(Newsletter $newsletter) {
        $this->newsletter = $newsletter;
        $this->user = User::findById($newsletter->getUserId());
        $this->sources = $newsletter->getSources();
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
        
        return $this->renderNewslettersForRecipients($sourceData);
    }
    
    public function buildForPreview() {
        // Build newsletter content without generating for all recipients (for preview purposes)
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
            } catch (Exception $e) {
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
        
        // Return single newsletter for preview (use owner email and a placeholder token)
        return $this->renderNewsletter($sourceData, $this->user->getEmail(), 'preview-token');
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
    
    private function renderNewslettersForRecipients($sourceData) {
        $recipients = $this->newsletter->getRecipients();
        $newsletters = [];
        
        foreach ($recipients as $recipient) {
            $newsletters[] = [
                'email' => $recipient->getEmail(),
                'html' => $this->renderNewsletter($sourceData, $recipient->getEmail(), $recipient->getUnsubscribeToken()),
                'newsletter_id' => $this->newsletter->getId(),
                'recipient_token' => $recipient->getUnsubscribeToken()
            ];
        }
        
        return $newsletters;
    }
    
    private function renderNewsletter($sourceData, $recipientEmail, $unsubscribeToken) {
        $html = $this->getEmailTemplate();
        
        // Replace placeholders
        $html = str_replace('{{RECIPIENT_EMAIL}}', $recipientEmail, $html);
        $html = str_replace('{{DATE}}', date('F j, Y'), $html);
        $html = str_replace('{{NEWSLETTER_TITLE}}', $this->newsletter->getTitle(), $html);
        $html = str_replace('{{NEWSLETTER_ID}}', $this->newsletter->getId(), $html);
        $html = str_replace('{{UNSUBSCRIBE_TOKEN}}', $unsubscribeToken, $html);
        $html = str_replace('{{SOURCES_CONTENT}}', $this->renderSources($sourceData), $html);
        
        return $html;
    }
    
    private function renderSources($sourceData) {
        if (empty($sourceData)) {
            return '<div style="text-align: center; padding: 40px 20px; color: #6b7280;">
                        <div style="font-size: 48px; margin-bottom: 16px;">📋</div>
                        <h3 style="margin: 0 0 8px 0; color: #374151;">No sources configured</h3>
                        <p style="margin: 0; font-size: 14px;">Add data sources in your dashboard to start receiving updates.</p>
                    </div>';
        }
        
        $widgetSources = [];
        $legacySources = [];
        
        // Separate widget-compatible sources from legacy sources
        foreach ($sourceData as $source) {
            if (in_array($source['type'], ['bitcoin', 'weather'])) {
                $widgetSources[] = $source;
            } else {
                $legacySources[] = $source;
            }
        }
        
        $content = '';
        
        // Render widgets in grid layout
        if (!empty($widgetSources)) {
            $content .= '<div class="widget-container">';
            foreach ($widgetSources as $source) {
                $content .= $this->renderWidget($source);
            }
            $content .= '</div>';
        }
        
        // Render legacy sources
        foreach ($legacySources as $source) {
            $content .= $this->renderLegacySource($source);
        }
        
        return $content;
    }
    
    private function renderWidget($source) {
        $type = $source['type'];
        $data = $source['data'];
        
        if ($type === 'bitcoin') {
            return $this->renderBitcoinWidget($source);
        } elseif ($type === 'weather') {
            return $this->renderWeatherWidget($source);
        }
        
        // Fallback to legacy rendering
        return $this->renderLegacySource($source);
    }
    
    private function renderBitcoinWidget($source) {
        $data = $source['data'];
        
        if (empty($data)) {
            return $this->renderLegacySource($source);
        }
        
        $priceData = $data[0];
        $price = $priceData['value'] ?? 'N/A';
        $delta = $priceData['delta'] ?? null;
        
        $changeClass = 'neutral';
        $changeIcon = '→';
        $changeText = 'No change';
        
        if ($delta) {
            if (isset($delta['raw_delta'])) {
                if ($delta['raw_delta'] > 0) {
                    $changeClass = 'positive';
                    $changeIcon = '▲';
                } elseif ($delta['raw_delta'] < 0) {
                    $changeClass = 'negative';
                    $changeIcon = '▼';
                }
            }
            $changeText = $delta['value'] ?? $changeText;
        }
        
        return '<div class="widget-card bitcoin">
                    <div class="widget-header">
                        <h3 class="widget-title">
                            <div class="widget-icon">₿</div>
                            Bitcoin
                        </h3>
                        <div style="opacity: 0.6; font-size: 12px;">●</div>
                    </div>
                    <div class="widget-value">' . htmlspecialchars($price) . '</div>
                    <div class="widget-change ' . $changeClass . '">
                        <span class="widget-change-icon">' . $changeIcon . '</span>
                        ' . htmlspecialchars($changeText) . '
                    </div>
                </div>';
    }
    
    private function renderWeatherWidget($source) {
        $data = $source['data'];
        
        if (empty($data)) {
            return $this->renderLegacySource($source);
        }
        
        $temperature = '';
        $conditions = '';
        $location = '';
        $humidity = '';
        $todayRange = '';
        
        foreach ($data as $item) {
            $label = $item['label'] ?? '';
            $value = $item['value'] ?? '';
            
            if (strpos($label, 'Temperature') !== false) {
                $temperature = $value;
            } elseif (strpos($label, 'Conditions') !== false) {
                $conditions = $value;
            } elseif (strpos($label, 'Location') !== false) {
                $location = str_replace('📍 ', '', $value);
            } elseif (strpos($label, 'Humidity') !== false) {
                $humidity = $value;
            } elseif (strpos($label, 'Range') !== false) {
                $todayRange = $value;
            }
        }
        
        // Extract temperature number and emoji
        $tempParts = explode(' ', $temperature);
        $tempValue = end($tempParts);
        $weatherEmoji = isset($tempParts[0]) ? $tempParts[0] : '🌤️';
        
        return '<div class="widget-card weather">
                    <div class="widget-header">
                        <h3 class="widget-title">
                            <div class="widget-icon">' . $weatherEmoji . '</div>
                            ' . htmlspecialchars($location) . '
                        </h3>
                        <div style="opacity: 0.6; font-size: 12px;">☆☆☆</div>
                    </div>
                    <div class="widget-value">' . htmlspecialchars($tempValue) . '</div>
                    <div class="widget-subtitle">' . htmlspecialchars($conditions) . '</div>
                    <div class="widget-details">
                        <div class="widget-detail-item">
                            <span class="widget-detail-label">Humidity</span>
                            <span class="widget-detail-value">' . htmlspecialchars(str_replace('💧 ', '', $humidity)) . '</span>
                        </div>
                        ' . ($todayRange ? '<div class="widget-detail-item">
                            <span class="widget-detail-label">Today\'s Range</span>
                            <span class="widget-detail-value">' . htmlspecialchars(str_replace('📊 ', '', $todayRange)) . '</span>
                        </div>' : '') . '
                    </div>
                </div>';
    }
    
    private function renderLegacySource($source) {
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