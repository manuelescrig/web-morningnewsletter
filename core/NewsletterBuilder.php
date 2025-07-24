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
        
        // Generate view in browser section
        $viewInBrowserSection = '';
        if ($historyId) {
            if ($isPreview) {
                // Gray out the view in browser link for preview/history view
                $viewInBrowserSection = '<p style="margin: 15px 0 0 0; font-size: 12px; color: #6b7280; text-align: center;">
                    Having trouble viewing this email? 
                    <span style="color: #9ca3af; text-decoration: none; font-weight: 500; cursor: not-allowed;">View in browser</span>
                </p>';
            } else {
                $viewInBrowserSection = '<p style="margin: 15px 0 0 0; font-size: 12px; color: #6b7280; text-align: center;">
                    Having trouble viewing this email? 
                    <a href="' . htmlspecialchars($viewUrl) . '" style="color: #468BE6; text-decoration: none; font-weight: 500;">View in browser</a>
                </p>';
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
        // Check if this source has custom layout
        if ($source['type'] === 'weather' && isset($source['data']['main'])) {
            return $this->renderWeatherSource($source);
        }
        
        // Default rendering for other sources
        $title = htmlspecialchars($source['title']);
        $type = htmlspecialchars($source['type']);
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
                    
                    $html .= "<div style='display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #e5e7eb; flex-direction: row; align-items: baseline;'>
                                <span style='color: #6b7280; font-weight: 500;'>$label:</span>
                                <div style='text-align: right;'>
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
                    
                    $html .= "</div></div>";
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
        
        // Header with location
        $html .= "<div style='display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;'>
                    <h2 style='margin: 0; color: #111827; font-size: 18px; font-weight: 600;'>$title</h2>
                    <span style='color: #6b7280; font-size: 14px;'>📍 " . htmlspecialchars($main['location']) . "</span>
                  </div>";
        
        // Main temperature section
        $iconClass = $main['icon_class'] ?? 'fa-cloud-sun';
        $svgIcon = $this->getWeatherSvgIcon($iconClass);
        $html .= "<div style='text-align: center; margin-bottom: 24px;'>
                    <div style='margin-bottom: 8px; display: inline-block;'>{$svgIcon}</div>
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
                
                // Icon
                $svgColumnIcon = $this->getWeatherColumnIcon($iconClass);
                $html .= "<div style='margin-bottom: 4px; display: inline-block;'>{$svgColumnIcon}</div>";
                
                // Label
                $html .= "<div style='font-size: 12px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;'>" . htmlspecialchars($column['label']) . "</div>";
                
                // Handle high/low temperature display
                if (!empty($column['high_low'])) {
                    // High/Low temperatures with same font size
                    $arrowUp = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 19V5M12 5l-7 7M12 5l7 7" stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
                    $arrowDown = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 5v14M12 19l7-7M12 19l-7-7" stroke="#3b82f6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
                    
                    $html .= "<div style='display: inline-block;'>
                                <div style='display: inline-block; margin-right: 8px;'>
                                    <span style='display: inline-block; vertical-align: middle; margin-right: 2px;'>{$arrowUp}</span>
                                    <span style='font-size: 20px; font-weight: 600; color: #111827; vertical-align: middle;'>" . htmlspecialchars($column['value']) . "</span>
                                </div>
                                <div style='display: inline-block;'>
                                    <span style='display: inline-block; vertical-align: middle; margin-right: 2px;'>{$arrowDown}</span>
                                    <span style='font-size: 20px; font-weight: 600; color: #111827; vertical-align: middle;'>" . htmlspecialchars($column['subtitle']) . "</span>
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
    
    private function getWeatherSvgIcon($iconClass) {
        // Simplified SVG icons for email compatibility
        $icons = [
            'fa-sun' => '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12" r="5" stroke="#f59e0b" stroke-width="2" fill="#fbbf24"/><path stroke="#f59e0b" stroke-width="2" stroke-linecap="round" d="M12 2v4M12 18v4M22 12h-4M6 12H2M19.07 4.93l-2.83 2.83M7.76 16.24l-2.83 2.83M19.07 19.07l-2.83-2.83M7.76 7.76L4.93 4.93"/></svg>',
            'fa-cloud-sun' => '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="10" cy="7" r="4" stroke="#f59e0b" stroke-width="1.5" fill="#fbbf24"/><path d="M18 10h-.35A5.65 5.65 0 0012 4.35V4M5.08 11.42A7 7 0 1012 21h6a5 5 0 10-1.84-9.78" stroke="#9ca3af" stroke-width="2" stroke-linecap="round" fill="#e5e7eb"/></svg>',
            'fa-cloud' => '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M18 10h-.35A5.65 5.65 0 0012 4.35v-.01A7 7 0 105.08 11.4 5 5 0 108 21h10a5 5 0 000-10z" stroke="#9ca3af" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="#e5e7eb"/></svg>',
            'fa-cloud-rain' => '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M18 10h-.35A5.65 5.65 0 0012 4.35v-.01A7 7 0 105.08 11.4 5 5 0 108 21h10a5 5 0 000-10z" stroke="#9ca3af" stroke-width="2" fill="#e5e7eb"/><path stroke="#3b82f6" stroke-width="2" stroke-linecap="round" d="M8 19v2M8 13v2M16 19v2M16 13v2M12 21v2M12 15v2"/></svg>',
            'fa-snowflake' => '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path stroke="#60a5fa" stroke-width="2" stroke-linecap="round" d="M12 2v20M12 2l3 3M12 2l-3 3M12 22l3-3M12 22l-3-3M20.66 7L3.34 17M20.66 7l-4.24 1.5M20.66 7L19 4M3.34 17l4.24-1.5M3.34 17L5 20M3.34 7l17.32 10M3.34 7l4.24 1.5M3.34 7L5 4M20.66 17l-4.24-1.5M20.66 17L19 20"/></svg>',
            'fa-cloud-bolt' => '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M19 10.13A8 8 0 115.2 10a5 5 0 109.6 6H19a5 5 0 110-5.87z" stroke="#9ca3af" stroke-width="2" fill="#e5e7eb"/><path d="M13 11l-4 6h6l-4 6" stroke="#f59e0b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/></svg>',
            'fa-smog' => '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path stroke="#9ca3af" stroke-width="2" stroke-linecap="round" d="M3 12h18M5 16h14M7 20h10M4 8h16"/></svg>',
            'fa-cloud-meatball' => '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M18 10h-.35A5.65 5.65 0 0012 4.35v-.01A7 7 0 105.08 11.4 5 5 0 108 21h10a5 5 0 000-10z" stroke="#9ca3af" stroke-width="2" fill="#e5e7eb"/><circle cx="8" cy="19" r="1" fill="#60a5fa"/><circle cx="12" cy="19" r="1" fill="#60a5fa"/><circle cx="16" cy="19" r="1" fill="#60a5fa"/></svg>'
        ];
        
        return $icons[$iconClass] ?? $icons['fa-cloud'];
    }
    
    private function getWeatherColumnIcon($iconClass) {
        // Smaller SVG icons for columns
        $icons = [
            'fa-droplet' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 2.69l5.66 5.66a8 8 0 11-11.31 0L12 2.69z" stroke="#3b82f6" stroke-width="2" stroke-linejoin="round" fill="#dbeafe"/></svg>',
            'fa-wind' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path stroke="#6b7280" stroke-width="2" stroke-linecap="round" d="M9.59 4.59A2 2 0 1111 8H2m10.59 11.41A2 2 0 1014 16H2m15.73-8.27A2.5 2.5 0 1119.5 12H2"/></svg>',
            'fa-gauge' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 13a1 1 0 100-2 1 1 0 000 2z" fill="#6b7280"/><path d="M12 2a10 10 0 100 20 10 10 0 000-20zm0 18a8 8 0 110-16 8 8 0 010 16z" fill="#6b7280"/><path d="M12 6v6" stroke="#6b7280" stroke-width="2" stroke-linecap="round"/></svg>',
            'fa-temperature-half' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M14 14.76V3.5a2.5 2.5 0 00-5 0v11.26a4.5 4.5 0 105 0z" stroke="#6b7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="#e5e7eb"/></svg>'
        ];
        
        // Use weather icons for forecast
        if (strpos($iconClass, 'fa-') === 0 && !isset($icons[$iconClass])) {
            $mainIcons = [
                'fa-sun' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12" r="5" stroke="#f59e0b" stroke-width="2" fill="#fbbf24"/><path stroke="#f59e0b" stroke-width="2" stroke-linecap="round" d="M12 2v4M12 18v4M22 12h-4M6 12H2M19.07 4.93l-2.83 2.83M7.76 16.24l-2.83 2.83M19.07 19.07l-2.83-2.83M7.76 7.76L4.93 4.93"/></svg>',
                'fa-cloud-sun' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="10" cy="7" r="4" stroke="#f59e0b" stroke-width="1.5" fill="#fbbf24"/><path d="M18 10h-.35A5.65 5.65 0 0012 4.35V4M5.08 11.42A7 7 0 1012 21h6a5 5 0 10-1.84-9.78" stroke="#9ca3af" stroke-width="2" stroke-linecap="round" fill="#e5e7eb"/></svg>',
                'fa-cloud' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M18 10h-.35A5.65 5.65 0 0012 4.35v-.01A7 7 0 105.08 11.4 5 5 0 108 21h10a5 5 0 000-10z" stroke="#9ca3af" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="#e5e7eb"/></svg>',
                'fa-cloud-rain' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M18 10h-.35A5.65 5.65 0 0012 4.35v-.01A7 7 0 105.08 11.4 5 5 0 108 21h10a5 5 0 000-10z" stroke="#9ca3af" stroke-width="2" fill="#e5e7eb"/><path stroke="#3b82f6" stroke-width="2" stroke-linecap="round" d="M8 19v2M8 13v2M16 19v2M16 13v2M12 21v2M12 15v2"/></svg>',
                'fa-snowflake' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path stroke="#60a5fa" stroke-width="2" stroke-linecap="round" d="M12 2v20M12 2l3 3M12 2l-3 3M12 22l3-3M12 22l-3-3M20.66 7L3.34 17M20.66 7l-4.24 1.5M20.66 7L19 4M3.34 17l4.24-1.5M3.34 17L5 20M3.34 7l17.32 10M3.34 7l4.24 1.5M3.34 7L5 4M20.66 17l-4.24-1.5M20.66 17L19 20"/></svg>'
            ];
            return $mainIcons[$iconClass] ?? $icons['fa-temperature-half'];
        }
        
        return $icons[$iconClass] ?? '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12" r="10" stroke="#6b7280" stroke-width="2" fill="none"/></svg>';
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