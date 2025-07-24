<?php
require_once __DIR__ . '/../core/SourceModule.php';

class StockModule extends BaseSourceModule {
    public function getTitle(): string {
        return 'Stock Price';
    }
    
    public function getData(): array {
        try {
            $symbol = strtoupper($this->config['symbol'] ?? '');
            
            if (empty($symbol)) {
                throw new Exception('Stock symbol required');
            }
            
            // Using Yahoo Finance API through a free proxy service
            // Alternative: Use Alpha Vantage with API key like SP500 module
            $apiUrl = "https://query1.finance.yahoo.com/v8/finance/chart/{$symbol}";
            
            $headers = [
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'Accept: application/json'
            ];
            
            $response = $this->makeHttpRequest($apiUrl, $headers);
            $data = json_decode($response, true);
            
            if (!$data || !isset($data['chart']['result'][0])) {
                throw new Exception('Invalid API response or symbol not found');
            }
            
            $result = $data['chart']['result'][0];
            $meta = $result['meta'];
            
            $currentPrice = $meta['regularMarketPrice'];
            $previousClose = $meta['chartPreviousClose'];
            $change = $currentPrice - $previousClose;
            $changePercent = ($change / $previousClose) * 100;
            
            $currency = $meta['currency'] ?? 'USD';
            $currencySymbol = $currency === 'USD' ? '$' : $currency . ' ';
            
            $formattedPrice = $currencySymbol . $this->formatNumber($currentPrice, 2);
            $formattedChange = ($change >= 0 ? '+' : '') . $currencySymbol . $this->formatNumber(abs($change), 2);
            $formattedPercent = ($changePercent >= 0 ? '↑' : '↓') . ' ' . $this->formatNumber(abs($changePercent), 2) . '%';
            
            // Just use the symbol as display name
            $displayName = $symbol;
            
            $result = [
                [
                    'label' => $displayName,
                    'value' => $formattedPrice,
                    'delta' => [
                        'value' => $formattedChange . ' (' . $formattedPercent . ')',
                        'color' => $change >= 0 ? 'green' : 'red',
                        'raw_delta' => $change
                    ]
                ]
            ];
            
            // Add holdings value if enabled
            $showHoldings = isset($this->config['show_holdings']) && $this->config['show_holdings'] === 'on';
            $holdingsAmount = floatval($this->config['holdings_amount'] ?? 0);
            
            if ($showHoldings && $holdingsAmount > 0) {
                $holdingsValue = $currentPrice * $holdingsAmount;
                
                // Format holdings amount
                $formattedAmount = number_format($holdingsAmount, 0);
                
                // Format holdings value
                $formattedHoldingsValue = $currencySymbol . number_format($holdingsValue, 2);
                
                // Calculate holdings change
                $holdingsChange = $change * $holdingsAmount;
                $formattedHoldingsChange = ($holdingsChange >= 0 ? '+' : '') . $currencySymbol . number_format(abs($holdingsChange), 2);
                
                $result[] = [
                    'label' => "Holdings ({$formattedAmount} shares)",
                    'value' => $formattedHoldingsValue,
                    'delta' => [
                        'value' => $formattedHoldingsChange . ' (' . $formattedPercent . ')',
                        'color' => $holdingsChange >= 0 ? 'green' : 'red',
                        'raw_delta' => $holdingsChange
                    ],
                    'is_holdings' => true
                ];
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log('Stock module error: ' . $e->getMessage());
            $symbol = $this->config['symbol'] ?? 'Stock';
            return [
                [
                    'label' => strtoupper($symbol),
                    'value' => 'Data unavailable',
                    'delta' => null
                ]
            ];
        }
    }
    
    public function getConfigFields(): array {
        return [
            [
                'name' => 'symbol',
                'type' => 'stock_search',
                'label' => 'Stock Symbol',
                'required' => true,
                'description' => 'Search and select a stock (e.g., AAPL for Apple)',
                'placeholder' => 'Search for a stock...'
            ],
            [
                'name' => 'show_holdings',
                'type' => 'checkbox',
                'label' => 'Show holding value',
                'required' => false,
                'description' => 'Display the value of your stock holdings'
            ],
            [
                'name' => 'holdings_amount',
                'type' => 'number',
                'label' => 'Number of shares',
                'required' => false,
                'placeholder' => '100',
                'description' => 'Number of shares you hold (e.g., 10, 100, 1000)',
                'step' => '1',
                'min' => '0'
            ]
        ];
    }
    
    public function validateConfig(array $config): bool {
        return !empty($config['symbol']);
    }
}