<?php
require_once __DIR__ . '/../core/SourceModule.php';

class BinancecoinModule extends BaseSourceModule {
    public function getTitle(): string {
        return 'Binance Coin (BNB)';
    }
    
    public function getData(): array {
        try {
            // Use Binance US API for BNB price data
            $apiUrl = 'https://api.binance.us/api/v3/ticker/price?symbol=BNBUSDT';
            $response = $this->makeHttpRequest($apiUrl);
            $data = json_decode($response, true);
            
            if (!$data || !isset($data['price'])) {
                throw new Exception('Invalid API response from Binance US');
            }
            
            $currentPrice = (float)$data['price'];
            
            // Format current price (show full numbers for crypto)
            $formattedCurrentPrice = '$' . number_format($currentPrice, 2);
            
            return [
                [
                    'label' => 'Current Price',
                    'value' => $formattedCurrentPrice,
                    'delta' => null,
                    'timestamp' => $this->formatTimestamp()
                ]
            ];
            
        } catch (Exception $e) {
            error_log('Binance Coin module error: ' . $e->getMessage());
            return [
                [
                    'label' => 'Binance Coin Price',
                    'value' => 'Data unavailable',
                    'delta' => null
                ]
            ];
        }
    }
    
    public function getConfigFields(): array {
        return []; // No configuration needed for BNB price
    }
    
    public function validateConfig(array $config): bool {
        return true; // No configuration to validate
    }
}