<?php
require_once __DIR__ . '/../core/SourceModule.php';

class SP500Module extends BaseSourceModule {
    public function getTitle(): string {
        return 'S&P 500 Index';
    }
    
    public function getData(): array {
        try {
            // Using Alpha Vantage free tier (requires API key in config)
            $apiKey = $this->config['api_key'] ?? '';
            
            if (empty($apiKey)) {
                throw new Exception('Alpha Vantage API key required');
            }
            
            $apiUrl = "https://www.alphavantage.co/query?function=GLOBAL_QUOTE&symbol=SPY&apikey=$apiKey";
            $response = $this->makeHttpRequest($apiUrl);
            $data = json_decode($response, true);
            
            if (!$data || !isset($data['Global Quote'])) {
                throw new Exception('Invalid API response');
            }
            
            $quote = $data['Global Quote'];
            $currentPrice = floatval($quote['05. price']);
            $previousClose = floatval($quote['08. previous close']);
            $change = floatval($quote['09. change']);
            $changePercent = floatval(str_replace('%', '', $quote['10. change percent']));
            
            $formattedPrice = '$' . $this->formatNumber($currentPrice, 2);
            $formattedChange = ($change >= 0 ? '+' : '') . '$' . $this->formatNumber(abs($change), 2);
            $formattedPercent = ($changePercent >= 0 ? '↑' : '↓') . ' ' . abs($changePercent) . '%';
            
            return [
                [
                    'label' => 'S&P 500 (SPY)',
                    'value' => $formattedPrice,
                    'delta' => [
                        'value' => $formattedChange . ' (' . $formattedPercent . ')',
                        'color' => $change >= 0 ? 'green' : 'red',
                        'raw_delta' => $change
                    ]
                ]
            ];
            
        } catch (Exception $e) {
            error_log('S&P 500 module error: ' . $e->getMessage());
            return [
                [
                    'label' => 'S&P 500 Index',
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
                'label' => 'Alpha Vantage API Key',
                'required' => true,
                'description' => 'Get your free API key from alphavantage.co'
            ]
        ];
    }
    
    public function validateConfig(array $config): bool {
        return !empty($config['api_key']);
    }
}