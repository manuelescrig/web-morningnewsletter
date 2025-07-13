<?php
require_once __DIR__ . '/../config/database.php';

class NewsletterHistory {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Save a newsletter to history
     */
    public function saveToHistory($newsletterId, $userId, $title, $content, $sourcesData = null, $scheduledSendTime = null) {
        try {
            $issueNumber = $this->getNextIssueNumber($newsletterId);
            
            $stmt = $this->db->prepare("
                INSERT INTO newsletter_history 
                (newsletter_id, user_id, title, content, sources_data, sent_at, issue_number, scheduled_send_time)
                VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP, ?, ?)
            ");
            
            $stmt->execute([
                $newsletterId,
                $userId,
                $title,
                $content,
                $sourcesData ? json_encode($sourcesData) : null,
                $issueNumber,
                $scheduledSendTime
            ]);
            
            $historyId = $this->db->lastInsertId();
            
            $timeStr = $scheduledSendTime ? " at $scheduledSendTime" : "";
            error_log("Newsletter history: Saved newsletter $newsletterId as issue #$issueNumber (history ID: $historyId)$timeStr");
            
            return $historyId;
            
        } catch (Exception $e) {
            error_log("Error saving newsletter to history: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get newsletter history for a specific newsletter
     */
    public function getHistory($newsletterId, $limit = 50, $offset = 0) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    h.*,
                    n.title as newsletter_title,
                    el.status as email_status,
                    el.error_message
                FROM newsletter_history h
                JOIN newsletters n ON h.newsletter_id = n.id
                LEFT JOIN email_logs el ON h.id = el.history_id
                WHERE h.newsletter_id = ?
                ORDER BY h.sent_at DESC
                LIMIT ? OFFSET ?
            ");
            
            $stmt->execute([$newsletterId, $limit, $offset]);
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Error getting newsletter history: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get newsletter history for a user (all newsletters)
     */
    public function getUserHistory($userId, $limit = 50, $offset = 0) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    h.*,
                    n.title as newsletter_title,
                    el.status as email_status,
                    el.error_message
                FROM newsletter_history h
                JOIN newsletters n ON h.newsletter_id = n.id
                LEFT JOIN email_logs el ON h.id = el.history_id
                WHERE h.user_id = ?
                ORDER BY h.sent_at DESC
                LIMIT ? OFFSET ?
            ");
            
            $stmt->execute([$userId, $limit, $offset]);
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Error getting user newsletter history: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get a specific newsletter history entry
     */
    public function getHistoryEntry($historyId, $userId = null) {
        try {
            $query = "
                SELECT 
                    h.*,
                    n.title as newsletter_title,
                    el.status as email_status,
                    el.error_message
                FROM newsletter_history h
                JOIN newsletters n ON h.newsletter_id = n.id
                LEFT JOIN email_logs el ON h.id = el.history_id
                WHERE h.id = ?
            ";
            
            $params = [$historyId];
            
            if ($userId !== null) {
                $query .= " AND h.user_id = ?";
                $params[] = $userId;
            }
            
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            
            return $stmt->fetch();
            
        } catch (Exception $e) {
            error_log("Error getting newsletter history entry: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get history count for a newsletter
     */
    public function getHistoryCount($newsletterId) {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as count 
                FROM newsletter_history 
                WHERE newsletter_id = ?
            ");
            
            $stmt->execute([$newsletterId]);
            $result = $stmt->fetch();
            
            return $result['count'];
            
        } catch (Exception $e) {
            error_log("Error getting newsletter history count: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get the next issue number for a newsletter
     */
    private function getNextIssueNumber($newsletterId) {
        try {
            $stmt = $this->db->prepare("
                SELECT COALESCE(MAX(issue_number), 0) + 1 as next_issue
                FROM newsletter_history 
                WHERE newsletter_id = ?
            ");
            
            $stmt->execute([$newsletterId]);
            $result = $stmt->fetch();
            
            return $result['next_issue'];
            
        } catch (Exception $e) {
            error_log("Error getting next issue number: " . $e->getMessage());
            return 1;
        }
    }
    
    /**
     * Get latest issue for a newsletter
     */
    public function getLatestIssue($newsletterId) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    h.*,
                    n.title as newsletter_title,
                    el.status as email_status,
                    el.error_message
                FROM newsletter_history h
                JOIN newsletters n ON h.newsletter_id = n.id
                LEFT JOIN email_logs el ON h.id = el.history_id
                WHERE h.newsletter_id = ?
                ORDER BY h.sent_at DESC
                LIMIT 1
            ");
            
            $stmt->execute([$newsletterId]);
            return $stmt->fetch();
            
        } catch (Exception $e) {
            error_log("Error getting latest newsletter issue: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete old history entries (cleanup)
     */
    public function cleanupOldHistory($daysToKeep = 365) {
        try {
            $stmt = $this->db->prepare("
                DELETE FROM newsletter_history 
                WHERE sent_at < datetime('now', '-' || ? || ' days')
            ");
            
            $stmt->execute([$daysToKeep]);
            $deletedCount = $stmt->rowCount();
            
            if ($deletedCount > 0) {
                error_log("Newsletter history cleanup: Deleted $deletedCount old entries (older than $daysToKeep days)");
            }
            
            return $deletedCount;
            
        } catch (Exception $e) {
            error_log("Error cleaning up newsletter history: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get statistics for a newsletter
     */
    public function getNewsletterStats($newsletterId) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as total_sent,
                    MAX(issue_number) as latest_issue,
                    MIN(sent_at) as first_sent,
                    MAX(sent_at) as last_sent,
                    SUM(CASE WHEN email_sent = 1 THEN 1 ELSE 0 END) as emails_sent,
                    AVG(CASE WHEN email_sent = 1 THEN 1.0 ELSE 0.0 END) * 100 as success_rate
                FROM newsletter_history h
                LEFT JOIN email_logs el ON h.id = el.history_id
                WHERE h.newsletter_id = ?
            ");
            
            $stmt->execute([$newsletterId]);
            return $stmt->fetch();
            
        } catch (Exception $e) {
            error_log("Error getting newsletter statistics: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Search newsletter history
     */
    public function searchHistory($userId, $searchTerm, $newsletterId = null, $limit = 20) {
        try {
            $query = "
                SELECT 
                    h.*,
                    n.title as newsletter_title,
                    el.status as email_status
                FROM newsletter_history h
                JOIN newsletters n ON h.newsletter_id = n.id
                LEFT JOIN email_logs el ON h.id = el.history_id
                WHERE h.user_id = ? 
                AND (h.title LIKE ? OR h.content LIKE ?)
            ";
            
            $params = [$userId, "%$searchTerm%", "%$searchTerm%"];
            
            if ($newsletterId) {
                $query .= " AND h.newsletter_id = ?";
                $params[] = $newsletterId;
            }
            
            $query .= " ORDER BY h.sent_at DESC LIMIT ?";
            $params[] = $limit;
            
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Error searching newsletter history: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Update email log to reference history
     */
    public function linkEmailLog($historyId, $emailLogId) {
        try {
            $stmt = $this->db->prepare("
                UPDATE email_logs 
                SET history_id = ? 
                WHERE id = ?
            ");
            
            return $stmt->execute([$historyId, $emailLogId]);
            
        } catch (Exception $e) {
            error_log("Error linking email log to history: " . $e->getMessage());
            return false;
        }
    }
}