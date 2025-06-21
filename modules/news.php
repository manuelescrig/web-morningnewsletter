<?php
require_once __DIR__ . '/../core/SourceModule.php';

class NewsModule extends BaseSourceModule {
    public function getTitle(): string {
        return 'Top News Headlines';
    }
    
    public function getData(): array {
        try {
            $apiKey = $this->config['api_key'] ?? '';
            $country = $this->config['country'] ?? 'us';
            $category = $this->config['category'] ?? 'general';
            $limit = $this->config['limit'] ?? 5;
            
            if (empty($apiKey)) {
                throw new Exception('NewsAPI key required');
            }
            
            $apiUrl = "https://newsapi.org/v2/top-headlines?country=$country&category=$category&pageSize=$limit&apikey=$apiKey";
            $response = $this->makeHttpRequest($apiUrl, [
                'User-Agent: MorningNewsletter/1.0'
            ]);
            
            $data = json_decode($response, true);
            
            if (!$data || $data['status'] !== 'ok') {
                throw new Exception('NewsAPI error: ' . ($data['message'] ?? 'Unknown error'));
            }
            
            $result = [];
            
            foreach ($data['articles'] as $index => $article) {
                if ($index >= $limit) break;
                
                $title = $article['title'];
                $source = $article['source']['name'];
                $publishedAt = date('H:i', strtotime($article['publishedAt']));
                
                // Limit title length
                if (strlen($title) > 100) {
                    $title = substr($title, 0, 97) . '...';
                }
                
                $result[] = [
                    'label' => "[$source - $publishedAt]",
                    'value' => $title,
                    'delta' => null
                ];
            }
            
            if (empty($result)) {
                $result[] = [
                    'label' => 'News',
                    'value' => 'No headlines available',
                    'delta' => null
                ];
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log('News module error: ' . $e->getMessage());
            return [
                [
                    'label' => 'Top News',
                    'value' => 'Data unavailable',
                    'delta' => null
                ]
            ];
        }
    }
    
    public function getConfigFields(): array {
        return [
            [
                'name' => 'api_key',
                'type' => 'text',
                'label' => 'NewsAPI Key',
                'required' => true,
                'description' => 'Get your free API key from newsapi.org'
            ],
            [
                'name' => 'country',
                'type' => 'select',
                'label' => 'Country',
                'required' => true,
                'options' => [
                    'us' => 'United States',
                    'gb' => 'United Kingdom',
                    'ca' => 'Canada',
                    'au' => 'Australia',
                    'de' => 'Germany',
                    'fr' => 'France',
                    'jp' => 'Japan',
                    'in' => 'India'
                ],
                'default' => 'us'
            ],
            [
                'name' => 'category',
                'type' => 'select',
                'label' => 'Category',
                'required' => true,
                'options' => [
                    'general' => 'General',
                    'business' => 'Business',
                    'technology' => 'Technology',
                    'science' => 'Science',
                    'sports' => 'Sports',
                    'health' => 'Health'
                ],
                'default' => 'general'
            ],
            [
                'name' => 'limit',
                'type' => 'number',
                'label' => 'Number of Headlines',
                'required' => true,
                'min' => 1,
                'max' => 10,
                'default' => 5
            ]
        ];
    }
    
    public function validateConfig(array $config): bool {
        return !empty($config['api_key']) && 
               !empty($config['country']) && 
               !empty($config['category']) &&
               isset($config['limit']) && 
               $config['limit'] >= 1 && 
               $config['limit'] <= 10;
    }
}