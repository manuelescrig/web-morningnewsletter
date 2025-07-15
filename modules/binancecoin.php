<?php
require_once __DIR__ . '/../core/SourceModule.php';

class BinancecoinModule extends BaseSourceModule {
    public function getTitle(): string {
        return 'Binance Coin (BNB)';
    }
    
    public function getData(): array {
        try {
            // Use Binance API for BNB price data
            $apiUrl = 'https://api.binance.com/api/v3/ticker/24hr?symbol=BNBUSDT';
            $response = $this->makeHttpRequest($apiUrl);
            $data = json_decode($response, true);
            
            if (!$data || !isset($data['lastPrice'])) {
                throw new Exception('Invalid API response from Binance');
            }
            
            $currentPrice = (float)$data['lastPrice'];
            $change24h = (float)$data['priceChangePercent'];
            
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