<?php
require_once __DIR__ . '/../core/SourceModule.php';

class EthereumModule extends BaseSourceModule {
    public function getTitle(): string {
        return 'Ethereum (ETH)';
    }
    
    public function getData(): array {
        try {
            // Get current price from Binance API
            $currentPriceUrl = 'https://api.binance.com/api/v3/ticker/price?symbol=ETHUSDT';
            $currentResponse = $this->makeHttpRequest($currentPriceUrl);
            $currentData = json_decode($currentResponse, true);
            
            if (!$currentData || !isset($currentData['price'])) {
                throw new Exception('Invalid current price response from Binance API');
            }
            
            $currentPrice = floatval($currentData['price']);
            
            // Get 24h stats from Binance API
            $statsUrl = 'https://api.binance.com/api/v3/ticker/24hr?symbol=ETHUSDT';
            $statsResponse = $this->makeHttpRequest($statsUrl);
            $statsData = json_decode($statsResponse, true);
            
            if (!$statsData || !isset($statsData['openPrice'])) {
                throw new Exception('Invalid 24h stats response from Binance API');
            }
            
            $price24hAgo = floatval($statsData['openPrice']);
            $priceChange = $currentPrice - $price24hAgo;
            $percentageChange = ($priceChange / $price24hAgo) * 100;
            
            // Format current price
            $formattedCurrentPrice = '$' . $this->formatNumber($currentPrice, 2);
            $formatted24hPrice = '$' . $this->formatNumber($price24hAgo, 2);
            
            // Format price change
            $symbol = $priceChange >= 0 ? '↑' : '↓';
            $color = $priceChange >= 0 ? 'green' : 'red';
            $formattedPriceChange = ($priceChange >= 0 ? '+' : '') . '$' . $this->formatNumber(abs($priceChange), 2);
            $formattedPercentChange = ($percentageChange >= 0 ? '+' : '') . number_format($percentageChange, 2) . '%';
            
            $delta = [
                'value' => $symbol . ' ' . $formattedPercentChange . ' (' . $formattedPriceChange . ')',
                'color' => $color,
                'raw_delta' => $percentageChange
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
            error_log('Ethereum module error: ' . $e->getMessage());
            return [
                [
                    'label' => 'Ethereum Price',
                    'value' => 'Data unavailable',
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