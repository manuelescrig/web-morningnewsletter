<?php

class DashboardController extends Controller {
    public function index() {
        $this->requireAuth();

        // Get user's newsletters
        $stmt = $this->db->prepare('
            SELECT n.*, 
                   COUNT(DISTINCT r.id) as recipient_count,
                   COUNT(DISTINCT d.id) as delivery_count
            FROM newsletters n
            LEFT JOIN recipients r ON n.id = r.newsletter_id
            LEFT JOIN newsletter_deliveries d ON n.id = d.newsletter_id
            WHERE n.user_id = :user_id
            GROUP BY n.id
            ORDER BY n.created_at DESC
        ');
        $stmt->bindValue(':user_id', $this->user['id'], SQLITE3_INTEGER);
        $result = $stmt->execute();
        
        $newsletters = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $newsletters[] = $row;
        }

        // Get recent deliveries
        $stmt = $this->db->prepare('
            SELECT d.*, n.title as newsletter_title
            FROM newsletter_deliveries d
            JOIN newsletters n ON d.newsletter_id = n.id
            WHERE n.user_id = :user_id
            ORDER BY d.created_at DESC
            LIMIT 5
        ');
        $stmt->bindValue(':user_id', $this->user['id'], SQLITE3_INTEGER);
        $result = $stmt->execute();
        
        $recent_deliveries = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $recent_deliveries[] = $row;
        }

        $this->render('dashboard/index', [
            'title' => 'Dashboard',
            'newsletters' => $newsletters,
            'recent_deliveries' => $recent_deliveries
        ]);
    }
} 