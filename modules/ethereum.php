<?php
require_once __DIR__ . '/../core/SourceModule.php';

class EthereumModule extends BaseSourceModule {
    public function getTitle(): string {
        return 'Ethereum (ETH)';
    }
    
    public function getData(): array {
        try {
            // Use CoinGecko API - more globally accessible than Binance
            $apiUrl = 'https://api.coingecko.com/api/v3/simple/price?ids=ethereum&vs_currencies=usd&include_24hr_change=true&include_24hr_vol=true&include_last_updated_at=true';
            $response = $this->makeHttpRequest($apiUrl);
            $data = json_decode($response, true);
            
            if (!$data || !isset($data['ethereum']['usd'])) {
                throw new Exception('Invalid API response from CoinGecko');
            }
            
            $currentPrice = $data['ethereum']['usd'];
            $change24h = $data['ethereum']['usd_24h_change'] ?? null;
            
            // Calculate 24h ago price from current price and percentage change
            $price24hAgo = $change24h ? $currentPrice / (1 + ($change24h / 100)) : $currentPrice;
            $priceChange = $currentPrice - $price24hAgo;
            
            // Format current price (show full numbers for crypto)
            $formattedCurrentPrice = '$' . number_format($currentPrice, 2);
            $formatted24hPrice = '$' . number_format($price24hAgo, 2);
            
            // Format price change
            $symbol = $priceChange >= 0 ? '↑' : '↓';
            $color = $priceChange >= 0 ? 'green' : 'red';
            $formattedPriceChange = ($priceChange >= 0 ? '+' : '') . '$' . number_format(abs($priceChange), 2);
            $formattedPercentChange = ($change24h >= 0 ? '+' : '') . number_format($change24h, 2) . '%';
            
            $delta = [
                'value' => $symbol . ' ' . $formattedPercentChange . ' (' . $formattedPriceChange . ')',
                'color' => $color,
                'raw_delta' => $change24h
            ];
            
            return [
                [
                    'label' => 'Current Price',
                    'value' => $formattedCurrentPrice,
                    'delta' => $delta,
                    'timestamp' => $this->formatTimestamp()
                ],
                [
                    'label' => '24h Ago Price',
                    'value' => $formatted24hPrice,
                    'delta' => null,
                    'timestamp' => $this->formatTimestamp(strtotime('-24 hours'))
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