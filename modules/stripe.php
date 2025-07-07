<?php
require_once __DIR__ . '/../core/SourceModule.php';

class StripeModule extends BaseSourceModule {
    public function getTitle(): string {
        return 'Stripe Revenue';
    }
    
    public function getData(): array {
        try {
            $secretKey = $this->config['secret_key'] ?? '';
            
            if (empty($secretKey)) {
                throw new Exception('Stripe secret key required');
            }
            
            // Get today's and yesterday's revenue
            $today = $this->getRevenue($secretKey, 'today');
            $yesterday = $this->getRevenue($secretKey, 'yesterday');
            $thisMonth = $this->getRevenue($secretKey, 'this_month');
            
            $result = [
                [
                    'label' => 'Today\'s Revenue',
                    'value' => '$' . $this->formatNumber($today / 100, 2),
                    'delta' => $yesterday > 0 ? $this->formatDelta($today, $yesterday) : null
                ],
                [
                    'label' => 'This Month',
                    'value' => '$' . $this->formatNumber($thisMonth / 100, 2),
                    'delta' => null
                ]
            ];
            
            // Get recent transactions count
            $transactionCount = $this->getTransactionCount($secretKey);
            if ($transactionCount !== null) {
                $result[] = [
                    'label' => 'Transactions Today',
                    'value' => (string)$transactionCount,
                    'delta' => null
                ];
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log('Stripe module error: ' . $e->getMessage());
            return [
                [
                    'label' => 'Stripe Revenue',
                    'value' => 'Configuration required',
                    'delta' => null
                ]
            ];
        }
    }
    
    private function getRevenue($secretKey, $period) {
        $now = time();
        
        switch ($period) {
            case 'today':
                $startOfDay = strtotime('today');
                $endOfDay = strtotime('tomorrow') - 1;
                break;
            case 'yesterday':
                $startOfDay = strtotime('yesterday');
                $endOfDay = strtotime('today') - 1;
                break;
            case 'this_month':
                $startOfDay = strtotime(date('Y-m-01'));
                $endOfDay = $now;
                break;
            default:
                return 0;
        }
        
        $url = 'https://api.stripe.com/v1/charges?' . http_build_query([
            'created[gte]' => $startOfDay,
            'created[lte]' => $endOfDay,
            'limit' => 100
        ]);
        
        $response = $this->makeStripeRequest($url, $secretKey);
        $data = json_decode($response, true);
        
        if (!$data || !isset($data['data'])) {
            throw new Exception('Invalid Stripe API response');
        }
        
        $totalRevenue = 0;
        foreach ($data['data'] as $charge) {
            if ($charge['status'] === 'succeeded' && !$charge['refunded']) {
                $totalRevenue += $charge['amount'];
            }
        }
        
        return $totalRevenue;
    }
    
    private function getTransactionCount($secretKey) {
        try {
            $startOfDay = strtotime('today');
            $endOfDay = strtotime('tomorrow') - 1;
            
            $url = 'https://api.stripe.com/v1/charges?' . http_build_query([
                'created[gte]' => $startOfDay,
                'created[lte]' => $endOfDay,
                'limit' => 1
            ]);
            
            $response = $this->makeStripeRequest($url, $secretKey);
            $data = json_decode($response, true);
            
            return $data['total_count'] ?? null;
        } catch (Exception $e) {
            return null;
        }
    }
    
    private function makeStripeRequest($url, $secretKey) {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => [
                    'Authorization: Bearer ' . $secretKey,
                    'User-Agent: MorningNewsletter/1.0 (https://morningnewsletter.com; hello@morningnewsletter.com)'
                ],
                'timeout' => 10
            ]
        ]);
        
        $response = @file_get_contents($url, false, $context);
        
        if ($response === FALSE) {
            throw new Exception("Failed to fetch data from Stripe API");
        }
        
        return $response;
    }
    
    public function getConfigFields(): array {
        return [
            [
                'name' => 'secret_key',
                'type' => 'password',
                'label' => 'Stripe Secret Key',
                'required' => true,
                'description' => 'Your Stripe secret key (starts with sk_)'
            ]
        ];
    }
    
    public function validateConfig(array $config): bool {
        return !empty($config['secret_key']) && 
               (strpos($config['secret_key'], 'sk_') === 0);
    }
}