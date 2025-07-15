<?php
require_once __DIR__ . '/../core/SourceModule.php';

class EthereumModule extends BaseSourceModule {
    public function getTitle(): string {
        return 'Ethereum (ETH)';
    }
    
    public function getData(): array {
        try {
            // Use Binance US API - accessible from US locations
            $apiUrl = 'https://api.binance.us/api/v3/ticker/price?symbol=ETHUSDT';
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
            error_log('ETHEREUM ERROR: ' . $e->getMessage());
            return [
                [
                    'label' => 'Ethereum Price',
                    'value' => 'Data unavailable - ' . $e->getMessage(),
                    'delta' => null
                ]
            ];
        }
    }
    
    public function getConfigFields(): array {
        return []; // No configuration needed for Ethereum price
    }
    
    public function validateConfig(array $config): bool {
        return true; // No configuration to validate
    }
}