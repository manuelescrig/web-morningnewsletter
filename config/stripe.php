<?php

class StripeConfig {
    // Stripe API Keys - These should be set in environment variables or config file
    private const STRIPE_PUBLISHABLE_KEY = 'pk_test_your_publishable_key_here';
    private const STRIPE_SECRET_KEY = 'sk_test_your_secret_key_here';
    private const STRIPE_WEBHOOK_SECRET = 'whsec_your_webhook_secret_here';
    
    // Product/Price IDs from your Stripe Dashboard
    private const PRICE_IDS = [
        'starter' => 'price_starter_monthly',  // $5/month
        'pro' => 'price_pro_monthly',         // $15/month  
        'unlimited' => 'price_unlimited_monthly' // $19/month
    ];
    
    public static function getPublishableKey(): string {
        return $_ENV['STRIPE_PUBLISHABLE_KEY'] ?? self::STRIPE_PUBLISHABLE_KEY;
    }
    
    public static function getSecretKey(): string {
        return $_ENV['STRIPE_SECRET_KEY'] ?? self::STRIPE_SECRET_KEY;
    }
    
    public static function getWebhookSecret(): string {
        return $_ENV['STRIPE_WEBHOOK_SECRET'] ?? self::STRIPE_WEBHOOK_SECRET;
    }
    
    public static function getPriceId(string $plan): ?string {
        return self::PRICE_IDS[$plan] ?? null;
    }
    
    public static function getPlanFromPriceId(string $priceId): ?string {
        return array_search($priceId, self::PRICE_IDS) ?: null;
    }
    
    public static function getAllPlans(): array {
        return [
            'starter' => [
                'name' => 'Starter',
                'price' => 5,
                'currency' => 'usd',
                'price_id' => self::PRICE_IDS['starter']
            ],
            'pro' => [
                'name' => 'Pro',
                'price' => 15,
                'currency' => 'usd',
                'price_id' => self::PRICE_IDS['pro']
            ],
            'unlimited' => [
                'name' => 'Unlimited',
                'price' => 19,
                'currency' => 'usd',
                'price_id' => self::PRICE_IDS['unlimited']
            ]
        ];
    }
}

class StripeHelper {
    private $secretKey;
    
    public function __construct() {
        $this->secretKey = StripeConfig::getSecretKey();
    }
    
    /**
     * Create a Stripe Checkout Session
     */
    public function createCheckoutSession(int $userId, string $plan, string $successUrl, string $cancelUrl): array {
        $priceId = StripeConfig::getPriceId($plan);
        if (!$priceId) {
            throw new Exception("Invalid plan: $plan");
        }
        
        // Get user details
        $db = Database::getInstance();
        $stmt = $db->getConnection()->prepare("SELECT email FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user) {
            throw new Exception("User not found");
        }
        
        $data = [
            'payment_method_types' => ['card'],
            'line_items' => [
                [
                    'price' => $priceId,
                    'quantity' => 1,
                ]
            ],
            'mode' => 'subscription',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'customer_email' => $user['email'],
            'metadata' => [
                'user_id' => $userId,
                'plan' => $plan
            ],
            'subscription_data' => [
                'metadata' => [
                    'user_id' => $userId,
                    'plan' => $plan
                ]
            ]
        ];
        
        return $this->makeStripeRequest('POST', 'checkout/sessions', $data);
    }
    
    /**
     * Create or retrieve a Stripe Customer
     */
    public function createOrGetCustomer(int $userId, string $email): array {
        // First check if customer already exists
        $db = Database::getInstance();
        $stmt = $db->getConnection()->prepare("SELECT stripe_customer_id FROM subscriptions WHERE user_id = ? AND stripe_customer_id IS NOT NULL LIMIT 1");
        $stmt->execute([$userId]);
        $existing = $stmt->fetch();
        
        if ($existing && $existing['stripe_customer_id']) {
            // Retrieve existing customer
            return $this->makeStripeRequest('GET', 'customers/' . $existing['stripe_customer_id']);
        }
        
        // Create new customer
        $data = [
            'email' => $email,
            'metadata' => [
                'user_id' => $userId
            ]
        ];
        
        return $this->makeStripeRequest('POST', 'customers', $data);
    }
    
    /**
     * Cancel a subscription
     */
    public function cancelSubscription(string $subscriptionId): array {
        return $this->makeStripeRequest('DELETE', 'subscriptions/' . $subscriptionId);
    }
    
    /**
     * Update subscription to cancel at period end
     */
    public function cancelSubscriptionAtPeriodEnd(string $subscriptionId): array {
        $data = ['cancel_at_period_end' => true];
        return $this->makeStripeRequest('POST', 'subscriptions/' . $subscriptionId, $data);
    }
    
    /**
     * Retrieve subscription details
     */
    public function getSubscription(string $subscriptionId): array {
        return $this->makeStripeRequest('GET', 'subscriptions/' . $subscriptionId);
    }
    
    /**
     * Get customer portal URL for subscription management
     */
    public function createCustomerPortalSession(string $customerId, string $returnUrl): array {
        $data = [
            'customer' => $customerId,
            'return_url' => $returnUrl
        ];
        
        return $this->makeStripeRequest('POST', 'billing_portal/sessions', $data);
    }
    
    /**
     * Make API request to Stripe
     */
    public function makeStripeRequest(string $method, string $endpoint, array $data = []): array {
        $url = 'https://api.stripe.com/v1/' . $endpoint;
        
        $headers = [
            'Authorization: Bearer ' . $this->secretKey,
            'Content-Type: application/x-www-form-urlencoded'
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CUSTOMREQUEST => $method
        ]);
        
        if ($method === 'POST' && !empty($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($response === false) {
            throw new Exception('Failed to connect to Stripe API');
        }
        
        $decoded = json_decode($response, true);
        
        if ($httpCode >= 400) {
            $errorMessage = $decoded['error']['message'] ?? 'Unknown Stripe API error';
            throw new Exception("Stripe API error: $errorMessage");
        }
        
        return $decoded;
    }
    
    /**
     * Verify webhook signature
     */
    public function verifyWebhookSignature(string $payload, string $signature): bool {
        $secret = StripeConfig::getWebhookSecret();
        
        $elements = explode(',', $signature);
        $signatureHash = '';
        $timestamp = '';
        
        foreach ($elements as $element) {
            list($key, $value) = explode('=', $element, 2);
            if ($key === 'v1') {
                $signatureHash = $value;
            } elseif ($key === 't') {
                $timestamp = $value;
            }
        }
        
        if (empty($signatureHash) || empty($timestamp)) {
            return false;
        }
        
        // Check timestamp tolerance (5 minutes)
        if ((time() - $timestamp) > 300) {
            return false;
        }
        
        $expectedSignature = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);
        
        return hash_equals($expectedSignature, $signatureHash);
    }
}