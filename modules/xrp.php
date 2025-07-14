<?php
require_once __DIR__ . '/../core/SourceModule.php';

class XrpModule extends BaseSourceModule {
    public function getTitle(): string {
        return 'XRP (Ripple)';
    }
    
    public function getData(): array {
        try {
            // Use CoinGecko API - more globally accessible than Binance
            $apiUrl = 'https://api.coingecko.com/api/v3/simple/price?ids=ripple&vs_currencies=usd&include_24hr_change=true&include_24hr_vol=true&include_last_updated_at=true';
            $response = $this->makeHttpRequest($apiUrl);
            $data = json_decode($response, true);
            
            if (!$data || !isset($data['ripple']['usd'])) {
                throw new Exception('Invalid API response from CoinGecko');
            }
            
            $currentPrice = $data['ripple']['usd'];
            $change24h = $data['ripple']['usd_24h_change'] ?? null;
            
            // Calculate 24h ago price from current price and percentage change
            $price24hAgo = $change24h ? $currentPrice / (1 + ($change24h / 100)) : $currentPrice;
            $priceChange = $currentPrice - $price24hAgo;
            
            // Format current price (XRP typically has more decimal places)
            $formattedCurrentPrice = '$' . number_format($currentPrice, 4);
            $formatted24hPrice = '$' . number_format($price24hAgo, 4);
            
            // Format price change
            $symbol = $priceChange >= 0 ? '↑' : '↓';
            $color = $priceChange >= 0 ? 'green' : 'red';
            $formattedPriceChange = ($priceChange >= 0 ? '+' : '') . '$' . number_format(abs($priceChange), 4);
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
                    'timestamp' => date('Y-m-d H:i:s')
                ],
                [
                    'label' => '24h Ago Price',
                    'value' => $formatted24hPrice,
                    'delta' => null,
                    'timestamp' => date('Y-m-d H:i:s', strtotime('-24 hours'))
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