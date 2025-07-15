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
            
            return [
                [
                    'label' => 'Current Price',
                    'value' => $formattedCurrentPrice,
                    'delta' => null,
                    'timestamp' => $this->formatTimestamp()
                ]
            ];
            
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
        return []; // No configuration needed for XRP price
    }
    
    public function validateConfig(array $config): bool {
        return true; // No configuration to validate
    }
}