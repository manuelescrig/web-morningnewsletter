<?php
require_once __DIR__ . '/../core/SourceModule.php';

class BitcoinModule extends BaseSourceModule {
    public function getTitle(): string {
        return 'Bitcoin Price';
    }
    
    public function getData(): array {
        try {
            // Using CoinGecko API - more reliable and no rate limits for basic usage
            $apiUrl = 'https://api.coingecko.com/api/v3/simple/price?ids=bitcoin&vs_currencies=usd&include_24hr_change=true';
            $response = $this->makeHttpRequest($apiUrl);
            $data = json_decode($response, true);
            
            if (!$data || !isset($data['bitcoin']['usd'])) {
                throw new Exception('Invalid API response from CoinGecko');
            }
            
            $currentPrice = $data['bitcoin']['usd'];
            $change24h = $data['bitcoin']['usd_24h_change'] ?? null;
            
            $formattedPrice = '$' . $this->formatNumber($currentPrice, 0);
            
            // Calculate delta from 24h change
            $delta = null;
            if ($change24h !== null) {
                $symbol = $change24h >= 0 ? '↑' : '↓';
                $color = $change24h >= 0 ? 'green' : 'red';
                $delta = [
                    'value' => $symbol . ' ' . number_format(abs($change24h), 2) . '%',
                    'color' => $color,
                    'raw_delta' => $change24h
                ];
            }
            
            return [
                [
                    'label' => 'Current Price',
                    'value' => $formattedPrice,
                    'delta' => $delta,
                    'timestamp' => date('Y-m-d H:i:s')
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