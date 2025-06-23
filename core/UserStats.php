<?php
/**
 * UserStats Service
 * Handles user statistics and metrics
 */

require_once __DIR__ . '/Database.php';

class UserStats {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Get total user count for social proof
     * @return array ['count' => int, 'display' => string]
     */
    public function getTotalUsers() {
        try {
            $stmt = $this->db->query("SELECT COUNT(*) as user_count FROM users");
            $result = $stmt->fetch();
            $userCount = $result['user_count'] ?? 0;
            
            return [
                'count' => $userCount,
                'display' => $userCount > 0 ? $userCount . '+' : '9000+'
            ];
        } catch (Exception $e) {
            error_log("UserStats::getTotalUsers() error: " . $e->getMessage());
            return [
                'count' => 0,
                'display' => '9000+'
            ];
        }
    }
    
    /**
     * Get users who signed up today
     * @return int
     */
    public function getTodaySignups() {
        try {
            $stmt = $this->db->query("SELECT COUNT(*) as today_count FROM users WHERE DATE(created_at) = DATE('now')");
            $result = $stmt->fetch();
            return $result['today_count'] ?? 0;
        } catch (Exception $e) {
            error_log("UserStats::getTodaySignups() error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get social proof data for landing page
     * @return array
     */
    public function getSocialProofData() {
        $totalUsers = $this->getTotalUsers();
        $todaySignups = $this->getTodaySignups();
        
        return [
            'total_count' => $totalUsers['count'],
            'display_count' => $totalUsers['display'],
            'today_signups' => $todaySignups
        ];
    }
}
?>