<?php
require_once __DIR__ . '/../core/SourceModule.php';

class TetherModule extends BaseSourceModule {
    public function getTitle(): string {
        return 'Tether (USDT)';
    }
    
    public function getData(): array {
        try {
            // Use Binance US API for USDT price data
            $apiUrl = 'https://api.binance.us/api/v3/ticker/price?symbol=USDTUSDC';
            $response = $this->makeHttpRequest($apiUrl);
            $data = json_decode($response, true);
            
            if (!$data || !isset($data['price'])) {
                throw new Exception('Invalid API response from Binance US');
            }
            
            $currentPrice = (float)$data['price'];
            
            // Format current price with more precision for USDT (should be close to $1)
            $formattedCurrentPrice = '$' . number_format($currentPrice, 4);
            
            return [
                [
                    'label' => 'Current Price',
                    'value' => $formattedCurrentPrice,
                    'delta' => null,
                    'timestamp' => $this->formatTimestamp()
                ]
            ];
            
        } catch (Exception $e) {
            error_log('Tether module error: ' . $e->getMessage());
            return [
                [
                    'label' => 'Tether Price',
                    'value' => 'Data unavailable',
                    'delta' => null
                ]
            ];
        }
    }
    
    public function getConfigFields(): array {
        return []; // No configuration needed for Tether price
    }
    
    public function validateConfig(array $config): bool {
        return true; // No configuration to validate
    }
}