<?php
require_once __DIR__ . '/../core/SourceModule.php';

class BitcoinModule extends BaseSourceModule {
    public function getTitle(): string {
        return 'Bitcoin (BTC)';
    }
    
    public function getData(): array {
        try {
            // Use Binance US API - accessible from US locations
            $apiUrl = 'https://api.binance.us/api/v3/ticker/price?symbol=BTCUSDT';
            $response = $this->makeHttpRequest($apiUrl);
            $data = json_decode($response, true);
            
            if (!$data || !isset($data['price'])) {
                throw new Exception('Invalid API response from Binance US');
            }
            
            $currentPrice = (float)$data['price'];
            
            // Format current price (show full numbers for crypto)
            $formattedCurrentPrice = '$' . number_format($currentPrice, 2);
            
            $result = [
                [
                    'label' => 'Current Price',
                    'value' => $formattedCurrentPrice,
                    'delta' => null,
                    'timestamp' => $this->formatTimestamp()
                ]
            ];
            
            // Add holdings value if enabled
            $showHoldings = isset($this->config['show_holdings']) && $this->config['show_holdings'] === 'on';
            $holdingsAmount = floatval($this->config['holdings_amount'] ?? 0);
            
            if ($showHoldings && $holdingsAmount > 0) {
                $holdingsValue = $currentPrice * $holdingsAmount;
                
                // Format holdings amount (show up to 8 decimal places for BTC)
                $formattedAmount = rtrim(rtrim(number_format($holdingsAmount, 8), '0'), '.');
                
                // Format holdings value
                $formattedHoldingsValue = '$' . number_format($holdingsValue, 2);
                
                $result[] = [
                    'label' => "Holdings ({$formattedAmount} BTC)",
                    'value' => $formattedHoldingsValue,
                    'delta' => null,
                    'is_holdings' => true
                ];
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log('BITCOIN ERROR: ' . $e->getMessage());
            error_log('BITCOIN ERROR TRACE: ' . $e->getTraceAsString());
            return [
                [
                    'label' => 'Bitcoin Price',
                    'value' => 'Data unavailable - ' . $e->getMessage(),
                    'delta' => null
                ]
            ];
        }
    }
    
    public function getConfigFields(): array {
        return [
            [
                'name' => 'show_holdings',
                'type' => 'checkbox',
                'label' => 'Show holding value',
                'required' => false,
                'description' => 'Display the value of your BTC holdings'
            ],
            [
                'name' => 'holdings_amount',
                'type' => 'number',
                'label' => 'Holdings amount',
                'required' => false,
                'placeholder' => '0.5',
                'description' => 'Amount of BTC you hold (e.g., 0.5, 1.25, 100)',
                'step' => '0.00000001',
                'min' => '0'
            ]
        ];
    }
    
    public function validateConfig(array $config): bool {
        return true; // No configuration to validate
    }
}