<?php

require_once __DIR__ . '/../config/database.php';

class SubscriptionManager {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Create a new subscription record
     */
    public function createSubscription(array $data): int {
        $sql = "INSERT INTO subscriptions (
            user_id, stripe_subscription_id, stripe_customer_id, plan, status,
            current_period_start, current_period_end, created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, datetime('now'), datetime('now'))";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $data['user_id'],
            $data['stripe_subscription_id'],
            $data['stripe_customer_id'],
            $data['plan'],
            $data['status'],
            $data['current_period_start'],
            $data['current_period_end']
        ]);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Update subscription status and details
     */
    public function updateSubscription(string $stripeSubscriptionId, array $data): bool {
        $setParts = [];
        $params = [];
        
        foreach ($data as $key => $value) {
            $setParts[] = "$key = ?";
            $params[] = $value;
        }
        
        $setParts[] = "updated_at = datetime('now')";
        $params[] = $stripeSubscriptionId;
        
        $sql = "UPDATE subscriptions SET " . implode(', ', $setParts) . " WHERE stripe_subscription_id = ?";
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute($params);
    }
    
    /**
     * Get subscription by Stripe subscription ID
     */
    public function getSubscriptionByStripeId(string $stripeSubscriptionId): ?array {
        $sql = "SELECT * FROM subscriptions WHERE stripe_subscription_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$stripeSubscriptionId]);
        
        $result = $stmt->fetch();
        return $result ?: null;
    }
    
    /**
     * Get active subscription for user
     */
    public function getUserActiveSubscription(int $userId): ?array {
        $sql = "SELECT * FROM subscriptions 
                WHERE user_id = ? AND status IN ('active', 'trialing', 'past_due') 
                ORDER BY created_at DESC LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        
        $result = $stmt->fetch();
        return $result ?: null;
    }
    
    /**
     * Get all subscriptions for user
     */
    public function getUserSubscriptions(int $userId): array {
        $sql = "SELECT * FROM subscriptions WHERE user_id = ? ORDER BY created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Cancel subscription
     */
    public function cancelSubscription(string $stripeSubscriptionId, ?string $canceledAt = null): bool {
        $canceledAt = $canceledAt ?: date('Y-m-d H:i:s');
        
        $sql = "UPDATE subscriptions SET 
                status = 'canceled', 
                canceled_at = ?, 
                updated_at = datetime('now') 
                WHERE stripe_subscription_id = ?";
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute([$canceledAt, $stripeSubscriptionId]);
    }
    
    /**
     * Update user plan based on subscription
     */
    public function updateUserPlan(int $userId, string $plan): bool {
        $sql = "UPDATE users SET plan = ?, updated_at = datetime('now') WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute([$plan, $userId]);
    }
    
    /**
     * Record a payment
     */
    public function recordPayment(array $data): int {
        $sql = "INSERT INTO payments (
            user_id, stripe_payment_intent_id, subscription_id, amount, currency, status, description, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, datetime('now'))";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $data['user_id'],
            $data['stripe_payment_intent_id'],
            $data['subscription_id'] ?? null,
            $data['amount'],
            $data['currency'] ?? 'usd',
            $data['status'],
            $data['description'] ?? null
        ]);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Get payment history for user
     */
    public function getUserPayments(int $userId, int $limit = 10): array {
        $sql = "SELECT p.*, s.plan, s.stripe_subscription_id 
                FROM payments p 
                LEFT JOIN subscriptions s ON p.subscription_id = s.id 
                WHERE p.user_id = ? 
                ORDER BY p.created_at DESC 
                LIMIT ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $limit]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Get user's current plan with subscription details
     */
    public function getUserPlanInfo(int $userId): array {
        $sql = "SELECT u.plan as user_plan, s.* 
                FROM users u 
                LEFT JOIN subscriptions s ON u.id = s.user_id 
                    AND s.status IN ('active', 'trialing', 'past_due')
                WHERE u.id = ? 
                ORDER BY s.created_at DESC 
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        
        $result = $stmt->fetch();
        
        if (!$result) {
            return [
                'plan' => 'free',
                'subscription_status' => null,
                'current_period_end' => null,
                'cancel_at_period_end' => false
            ];
        }
        
        return [
            'plan' => $result['user_plan'] ?? 'free',
            'subscription_status' => $result['status'],
            'current_period_end' => $result['current_period_end'],
            'cancel_at_period_end' => (bool)$result['cancel_at_period_end'],
            'stripe_customer_id' => $result['stripe_customer_id'],
            'stripe_subscription_id' => $result['stripe_subscription_id']
        ];
    }
    
    /**
     * Handle subscription created event from Stripe webhook
     */
    public function handleSubscriptionCreated(array $subscription): bool {
        $userId = $subscription['metadata']['user_id'] ?? null;
        $plan = $subscription['metadata']['plan'] ?? null;
        
        if (!$userId || !$plan) {
            error_log('Missing user_id or plan in subscription metadata');
            return false;
        }
        
        try {
            $this->db->beginTransaction();
            
            // Create subscription record
            $subscriptionData = [
                'user_id' => $userId,
                'stripe_subscription_id' => $subscription['id'],
                'stripe_customer_id' => $subscription['customer'],
                'plan' => $plan,
                'status' => $subscription['status'],
                'current_period_start' => date('Y-m-d H:i:s', $subscription['current_period_start']),
                'current_period_end' => date('Y-m-d H:i:s', $subscription['current_period_end'])
            ];
            
            $this->createSubscription($subscriptionData);
            
            // Update user's plan
            $this->updateUserPlan($userId, $plan);
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('Error handling subscription created: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Handle subscription updated event from Stripe webhook
     */
    public function handleSubscriptionUpdated(array $subscription): bool {
        try {
            $updateData = [
                'status' => $subscription['status'],
                'current_period_start' => date('Y-m-d H:i:s', $subscription['current_period_start']),
                'current_period_end' => date('Y-m-d H:i:s', $subscription['current_period_end']),
                'cancel_at_period_end' => $subscription['cancel_at_period_end'] ? 1 : 0
            ];
            
            if ($subscription['canceled_at']) {
                $updateData['canceled_at'] = date('Y-m-d H:i:s', $subscription['canceled_at']);
            }
            
            return $this->updateSubscription($subscription['id'], $updateData);
            
        } catch (Exception $e) {
            error_log('Error handling subscription updated: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Handle subscription deleted event from Stripe webhook
     */
    public function handleSubscriptionDeleted(array $subscription): bool {
        try {
            $this->db->beginTransaction();
            
            // Cancel subscription
            $this->cancelSubscription($subscription['id']);
            
            // Downgrade user to free plan
            $userId = $subscription['metadata']['user_id'] ?? null;
            if ($userId) {
                $this->updateUserPlan($userId, 'free');
            }
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('Error handling subscription deleted: ' . $e->getMessage());
            return false;
        }
    }
}