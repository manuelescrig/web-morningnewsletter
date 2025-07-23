<?php
require_once __DIR__ . '/../core/SourceModule.php';

class RSSModule extends BaseSourceModule {
    public function getTitle(): string {
        return 'RSS Feed';
    }
    
    public function getData(): array {
        try {
            $feedUrl = $this->config['feed_url'] ?? '';
            $itemLimit = intval($this->config['item_limit'] ?? 3);
            
            if (empty($feedUrl)) {
                throw new Exception('RSS feed URL required');
            }
            
            // Validate the URL
            if (!filter_var($feedUrl, FILTER_VALIDATE_URL)) {
                throw new Exception('Invalid RSS feed URL');
            }
            
            // Fetch the RSS feed
            $response = $this->makeHttpRequest($feedUrl);
            
            // Parse the RSS feed
            $xml = @simplexml_load_string($response);
            if ($xml === false) {
                throw new Exception('Failed to parse RSS feed');
            }
            
            $result = [];
            $items = [];
            
            // Handle different RSS formats (RSS 2.0 and Atom)
            if (isset($xml->channel->item)) {
                // RSS 2.0
                $items = $xml->channel->item;
                $feedTitle = (string)$xml->channel->title;
            } elseif (isset($xml->entry)) {
                // Atom
                $items = $xml->entry;
                $feedTitle = (string)$xml->title;
            } else {
                throw new Exception('Unsupported RSS format');
            }
            
            // Get custom display name or use feed title
            $displayName = !empty($this->config['display_name']) ? $this->config['display_name'] : $feedTitle;
            
            // Process items up to the limit
            $count = 0;
            foreach ($items as $item) {
                if ($count >= $itemLimit) break;
                
                if (isset($item->title)) {
                    // RSS 2.0
                    $title = (string)$item->title;
                    $link = (string)$item->link;
                    $description = (string)$item->description;
                    $pubDate = (string)$item->pubDate;
                } else {
                    // Atom
                    $title = (string)$item->title;
                    $link = '';
                    foreach ($item->link as $l) {
                        if ((string)$l['rel'] === 'alternate' || !isset($l['rel'])) {
                            $link = (string)$l['href'];
                            break;
                        }
                    }
                    $description = (string)($item->summary ?? $item->content ?? '');
                    $pubDate = (string)($item->published ?? $item->updated ?? '');
                }
                
                // Clean and truncate description
                $cleanDescription = strip_tags($description);
                $cleanDescription = html_entity_decode($cleanDescription, ENT_QUOTES | ENT_HTML5);
                $cleanDescription = preg_replace('/\s+/', ' ', $cleanDescription);
                $cleanDescription = trim($cleanDescription);
                
                if (strlen($cleanDescription) > 150) {
                    $cleanDescription = substr($cleanDescription, 0, 147) . '...';
                }
                
                // Format the item
                $result[] = [
                    'label' => $title,
                    'value' => $cleanDescription,
                    'delta' => [
                        'value' => $link ? "Read more →" : null,
                        'color' => 'blue',
                        'link' => $link
                    ]
                ];
                
                $count++;
            }
            
            // Add header if we have items
            if (!empty($result)) {
                array_unshift($result, [
                    'label' => '📰 ' . $displayName,
                    'value' => 'Latest updates',
                    'delta' => null,
                    'is_header' => true
                ]);
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log('RSS module error: ' . $e->getMessage());
            $feedName = $this->config['display_name'] ?? 'RSS Feed';
            return [
                [
                    'label' => $feedName,
                    'value' => 'Feed unavailable',
                    'delta' => null
                ]
            ];
        }
    }
    
    public function getConfigFields(): array {
        return [
            [
                'name' => 'feed_url',
                'type' => 'url',
                'label' => 'RSS Feed URL',
                'required' => true,
                'description' => 'Enter the full URL of the RSS feed',
                'placeholder' => 'https://example.com/feed.xml'
            ],
            [
                'name' => 'item_limit',
                'type' => 'select',
                'label' => 'Number of items',
                'required' => true,
                'options' => [
                    '1' => '1 item',
                    '3' => '3 items',
                    '5' => '5 items'
                ],
                'default' => '3',
                'description' => 'How many items to show from the feed'
            ],
            [
                'name' => 'display_name',
                'type' => 'text',
                'label' => 'Display Name (Optional)',
                'required' => false,
                'description' => 'Custom name to display instead of feed title',
                'placeholder' => 'e.g., Tech News'
            ]
        ];
    }
    
    public function validateConfig(array $config): bool {
        if (empty($config['feed_url'])) {
            return false;
        }
        
        // Validate URL format
        if (!filter_var($config['feed_url'], FILTER_VALIDATE_URL)) {
            return false;
        }
        
        // Optionally, we could make a HEAD request to validate the feed exists
        // But this might slow down the UI, so we'll validate during getData()
        
        return true;
    }
}