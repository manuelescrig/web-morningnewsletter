<?php
require_once __DIR__ . '/../core/SourceModule.php';

class BitcoinModule extends BaseSourceModule {
    public function getTitle(): string {
        return 'Bitcoin Price';
    }
    
    public function getData(): array {
        try {
            $apiUrl = 'https://api.coindesk.com/v1/bpi/currentprice.json';
            $response = $this->makeHttpRequest($apiUrl);
            $data = json_decode($response, true);
            
            if (!$data || !isset($data['bpi']['USD']['rate_float'])) {
                throw new Exception('Invalid API response');
            }
            
            $currentPrice = $data['bpi']['USD']['rate_float'];
            $formattedPrice = '$' . $this->formatNumber($currentPrice, 0);
            
            // Get previous price for delta calculation (simplified - would need database storage)
            $previousPrice = $currentPrice; // Placeholder - implement proper tracking
            $delta = $this->formatDelta($currentPrice, $previousPrice);
            
            return [
                [
                    'label' => 'Current Price',
                    'value' => $formattedPrice,
                    'delta' => $delta,
                    'timestamp' => $data['time']['updated']
                ]
            ];
            
        } catch (Exception $e) {
            error_log('Bitcoin module error: ' . $e->getMessage());
            return [
                [
                    'label' => 'Bitcoin Price',
                    'value' => 'Data unavailable',
                    'delta' => null
                ]
            ];
        }
    }
    
    public function getConfigFields(): array {
        return []; // No configuration needed for basic Bitcoin price
    }
    
    public function validateConfig(array $config): bool {
        return true; // No configuration to validate
    }
}