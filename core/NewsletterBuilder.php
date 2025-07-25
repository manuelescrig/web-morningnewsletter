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
        
        // Check if this is RSS source for custom layout
        if ($source['type'] === 'rss') {
            return $this->renderRSSSource($source);
        }
        
        // Default rendering for other sources
        $title = htmlspecialchars($source['title']);
        $type = htmlspecialchars($source['type']);
        $lastUpdated = $source['last_updated'];
        
        // Get icon emoji for source type
        $iconEmoji = $this->getSourceIcon($type);
        
        $html = "<div style='margin-bottom: 20px; padding: 20px; background-color: #ffffff; border-radius: 12px; border: 1px solid #e5e7eb;'>
            <h2 style='margin: 0 0 0px 0; color: #111827; font-size: 16px; font-weight: 600;'>$iconEmoji $title</h2>";
        
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
        
        // Header with location
        $html .= "<div style='display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;'>
                    <h2 style='margin: 0; color: #111827; font-size: 18px; font-weight: 600;'>$title</h2>
                    <span style='color: #6b7280; font-size: 14px;'>" . htmlspecialchars($main['location']) . "</span>
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
                    $arrowUp = '<svg width="10" height="10" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 19V5M12 5l-7 7M12 5l7 7" stroke="#9ca3af" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>';
                    $arrowDown = '<svg width="10" height="10" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 5v14M12 19l7-7M12 19l-7-7" stroke="#9ca3af" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>';
                    
                    // Use vertical layout for temperature to prevent column breaking
                    // Show min (subtitle) on top, max (value) on bottom
                    $html .= "<div style='text-align: center;'>
                                <div style='margin-bottom: 4px;'>
                                    <span style='display: inline-block; vertical-align: middle; margin-right: 4px;'>{$arrowDown}</span>
                                    <span style='font-size: 20px; font-weight: 600; color: #111827; vertical-align: middle;'>" . htmlspecialchars($column['subtitle']) . "</span>
                                </div>
                                <div>
                                    <span style='display: inline-block; vertical-align: middle; margin-right: 4px;'>{$arrowUp}</span>
                                    <span style='font-size: 20px; font-weight: 600; color: #111827; vertical-align: middle;'>" . htmlspecialchars($column['value']) . "</span>
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
        
        // Get icon emoji for RSS
        $iconEmoji = $this->getSourceIcon('rss');
        
        $html = "<div style='margin-bottom: 20px; padding: 20px; background-color: #ffffff; border-radius: 12px; border: 1px solid #e5e7eb;'>
            <h2 style='margin: 0 0 16px 0; color: #111827; font-size: 16px; font-weight: 600;'>$iconEmoji $title</h2>";
        
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
    
    private function getWeatherSvgIcon($iconClass) {
        // Using Feather Icons - simple, clean SVGs that work well in emails
        $icons = [
            'fa-sun' => '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>',
            'fa-cloud-sun' => '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.5 1.5v2m-6.364.636l1.414 1.414M1.5 10.5h2m.636 6.364l1.414-1.414"></path><circle cx="10.5" cy="10.5" r="3.5" stroke="#f59e0b"></circle><path d="M16 16.13A4 4 0 0014.11 8a6 6 0 10-6.09 9.89"></path><path d="M18 10h.01M22 10a4 4 0 01-4 4H8a4 4 0 110-8c.085 0 .17.003.254.009A6 6 0 1116 16.13"></path></svg>',
            'fa-cloud' => '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 10h-1.26A8 8 0 109 20h9a5 5 0 000-10z"></path></svg>',
            'fa-cloud-rain' => '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="16" y1="13" x2="16" y2="21" stroke="#3b82f6"></line><line x1="8" y1="13" x2="8" y2="21" stroke="#3b82f6"></line><line x1="12" y1="15" x2="12" y2="23" stroke="#3b82f6"></line><path d="M20 16.58A5 5 0 0018 7h-1.26A8 8 0 104 15.25"></path></svg>',
            'fa-snowflake' => '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#60a5fa" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="2" x2="12" y2="22"></line><line x1="22" y1="12" x2="2" y2="12"></line><path d="M17 7l-5 5-5-5m10 10l-5-5-5 5"></path></svg>',
            'fa-cloud-bolt' => '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 16.9A5 5 0 0018 7h-1.26a8 8 0 10-11.62 9"></path><polyline points="13 11 9 17 15 17 11 23" stroke="#f59e0b"></polyline></svg>',
            'fa-smog' => '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>',
            'fa-cloud-meatball' => '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 17.58A5 5 0 0018 8h-1.26A8 8 0 104 16.25"></path><line x1="8" y1="16" x2="8.01" y2="16" stroke="#60a5fa" stroke-width="4"></line><line x1="8" y1="20" x2="8.01" y2="20" stroke="#60a5fa" stroke-width="4"></line><line x1="12" y1="18" x2="12.01" y2="18" stroke="#60a5fa" stroke-width="4"></line><line x1="12" y1="22" x2="12.01" y2="22" stroke="#60a5fa" stroke-width="4"></line><line x1="16" y1="16" x2="16.01" y2="16" stroke="#60a5fa" stroke-width="4"></line><line x1="16" y1="20" x2="16.01" y2="20" stroke="#60a5fa" stroke-width="4"></line></svg>'
        ];
        
        return $icons[$iconClass] ?? $icons['fa-cloud'];
    }
    
    private function getWeatherColumnIcon($iconClass) {
        // Smaller Feather Icons for columns
        $icons = [
            'fa-droplet' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#6b7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2.69l5.66 5.66a8 8 0 11-11.31 0z"></path></svg>',
            'fa-wind' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#6b7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9.59 4.59A2 2 0 1111 8H2m10.59 11.41A2 2 0 1014 16H2m15.73-8.27A2.5 2.5 0 1119.5 12H2"></path></svg>',
            'fa-gauge' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#6b7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"></path></svg>',
            'fa-temperature-half' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#6b7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 14.76V3.5a2.5 2.5 0 00-5 0v11.26a4.5 4.5 0 105 0z"></path></svg>'
        ];
        
        // Use weather icons for forecast - smaller versions
        if (strpos($iconClass, 'fa-') === 0 && !isset($icons[$iconClass])) {
            $mainIcons = [
                'fa-sun' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>',
                'fa-cloud-sun' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4" stroke="#f59e0b"></circle><path d="M12 2v4M21.95 12.95h-4M17.64 17.64l-2.83-2.83M12 22v-4M6.36 17.64l2.83-2.83M2.05 12.95h4M6.36 6.36l2.83 2.83"></path><path d="M15.91 16.64A5 5 0 0114 8.02 7 7 0 105.2 16.2"></path></svg>',
                'fa-cloud' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 10h-1.26A8 8 0 109 20h9a5 5 0 000-10z"></path></svg>',
                'fa-cloud-rain' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="16" y1="13" x2="16" y2="21" stroke="#3b82f6"></line><line x1="8" y1="13" x2="8" y2="21" stroke="#3b82f6"></line><line x1="12" y1="15" x2="12" y2="23" stroke="#3b82f6"></line><path d="M20 16.58A5 5 0 0018 7h-1.26A8 8 0 104 15.25"></path></svg>',
                'fa-snowflake' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#60a5fa" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="2" x2="12" y2="22"></line><line x1="22" y1="12" x2="2" y2="12"></line><path d="M17 7l-5 5-5-5m10 10l-5-5-5 5"></path></svg>'
            ];
            return $mainIcons[$iconClass] ?? $icons['fa-temperature-half'];
        }
        
        return $icons[$iconClass] ?? '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#6b7280" stroke-width="2"><circle cx="12" cy="12" r="10"></circle></svg>';
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
    
    private function getSourceIcon($type) {
        $icons = [
            'bitcoin' => '₿',
            'ethereum' => 'Ξ',
            'xrp' => '💰',
            'binancecoin' => '🪙',
            'sp500' => '📈',
            'stock' => '📊',
            'weather' => '🌤️',
            'news' => '📰',
            'rss' => '📰',
            'stripe' => '💳',
            'appstore' => '📱'
        ];
        
        return $icons[$type] ?? '📊';
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