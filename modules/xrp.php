<?php
require_once __DIR__ . '/../core/SourceModule.php';

class XrpModule extends BaseSourceModule {
    public function getTitle(): string {
        return 'XRP (Ripple)';
    }
    
    public function getData(): array {
        try {
            // Use Binance US API for XRP price data
            $apiUrl = 'https://api.binance.us/api/v3/ticker/price?symbol=XRPUSDT';
            $response = $this->makeHttpRequest($apiUrl);
            $data = json_decode($response, true);
            
            if (!$data || !isset($data['price'])) {
                throw new Exception('Invalid API response from Binance US');
            }
            
            $currentPrice = (float)$data['price'];
            
            // Format current price (XRP typically has more decimal places)
            $formattedCurrentPrice = '$' . number_format($currentPrice, 4);
            
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
                
                // Format holdings amount
                $formattedAmount = number_format($holdingsAmount, 0);
                
                // Format holdings value
                $formattedHoldingsValue = '$' . number_format($holdingsValue, 2);
                
                $result[] = [
                    'label' => "Holdings ({$formattedAmount} XRP)",
                    'value' => $formattedHoldingsValue,
                    'delta' => null,
                    'is_holdings' => true
                ];
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log('XRP module error: ' . $e->getMessage());
            return [
                [
                    'label' => 'XRP Price',
                    'value' => 'Data unavailable',
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
                'description' => 'Display the value of your XRP holdings'
            ],
            [
                'name' => 'holdings_amount',
                'type' => 'number',
                'label' => 'Holdings amount',
                'required' => false,
                'placeholder' => '1000',
                'description' => 'Amount of XRP you hold (e.g., 100, 1000, 10000)',
                'step' => '1',
                'min' => '0'
            ]
        ];
    }
    
    public function validateConfig(array $config): bool {
        return true; // No configuration to validate
    }
}