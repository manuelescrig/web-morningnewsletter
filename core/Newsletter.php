<?php
require_once __DIR__ . '/../config/database.php';

class Newsletter {
    private $db;
    private $id;
    private $user_id;
    private $title;
    private $timezone;
    private $send_time;
    private $is_active;
    private $created_at;
    private $updated_at;
    
    // New scheduling fields
    private $frequency;
    private $days_of_week;
    private $day_of_month;
    private $months;
    private $daily_times;
    private $is_paused;
    
    public function __construct($newsletterData = null) {
        $this->db = Database::getInstance()->getConnection();
        
        if ($newsletterData) {
            $this->id = $newsletterData['id'];
            $this->user_id = $newsletterData['user_id'];
            $this->title = $newsletterData['title'];
            $this->timezone = $newsletterData['timezone'];
            $this->send_time = $newsletterData['send_time'];
            $this->is_active = $newsletterData['is_active'];
            $this->created_at = $newsletterData['created_at'];
            $this->updated_at = $newsletterData['updated_at'];
            
            // New scheduling fields with defaults
            $this->frequency = $newsletterData['frequency'] ?? 'daily';
            $this->days_of_week = $newsletterData['days_of_week'] ?? '';
            $this->day_of_month = $newsletterData['day_of_month'] ?? 1;
            $this->months = $newsletterData['months'] ?? '';
            $this->daily_times = $newsletterData['daily_times'] ?? '';
            $this->is_paused = $newsletterData['is_paused'] ?? 0;
        }
    }
    
    public function create($userId, $title, $timezone = 'UTC', $sendTime = '06:00') {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO newsletters (user_id, title, timezone, send_time) 
                VALUES (?, ?, ?, ?)
            ");
            
            $success = $stmt->execute([$userId, $title, $timezone, $sendTime]);
            
            if ($success) {
                $this->id = $this->db->lastInsertId();
                $this->user_id = $userId;
                $this->title = $title;
                $this->timezone = $timezone;
                $this->send_time = $sendTime;
                $this->is_active = 1;
                
                return $this->id;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Error creating newsletter: " . $e->getMessage());
            return false;
        }
    }
    
    public static function findById($id) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM newsletters WHERE id = ? AND is_active = 1");
        $stmt->execute([$id]);
        $newsletterData = $stmt->fetch();
        
        if ($newsletterData) {
            return new self($newsletterData);
        }
        return null;
    }
    
    public static function findByUser($userId) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT * FROM newsletters 
            WHERE user_id = ? AND is_active = 1 
            ORDER BY created_at ASC
        ");
        $stmt->execute([$userId]);
        $newsletters = [];
        
        while ($row = $stmt->fetch()) {
            $newsletters[] = new self($row);
        }
        
        return $newsletters;
    }
    
    public function update($data) {
        try {
            $allowedFields = ['title', 'timezone', 'send_time', 'frequency', 'days_of_week', 'day_of_month', 'months', 'daily_times', 'is_paused'];
            $updates = [];
            $values = [];
            
            foreach ($data as $field => $value) {
                if (in_array($field, $allowedFields)) {
                    $updates[] = "$field = ?";
                    $values[] = $value;
                }
            }
            
            if (empty($updates)) {
                return false;
            }
            
            $values[] = $this->id;
            $sql = "UPDATE newsletters SET " . implode(', ', $updates) . ", updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            $success = $stmt->execute($values);
            
            // Update local properties if successful
            if ($success) {
                foreach ($data as $field => $value) {
                    if (in_array($field, $allowedFields)) {
                        $this->$field = $value;
                    }
                }
            }
            
            return $success;
        } catch (Exception $e) {
            error_log("Error updating newsletter: " . $e->getMessage());
            return false;
        }
    }
    
    public function delete() {
        try {
            $this->db->beginTransaction();
            
            // Delete newsletter sources
            $stmt = $this->db->prepare("DELETE FROM sources WHERE newsletter_id = ?");
            $stmt->execute([$this->id]);
            
            // Soft delete the newsletter
            $stmt = $this->db->prepare("
                UPDATE newsletters 
                SET is_active = 0, updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?
            ");
            $success = $stmt->execute([$this->id]);
            
            if ($success) {
                $this->db->commit();
                $this->is_active = 0;
                return true;
            } else {
                $this->db->rollback();
                return false;
            }
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Error deleting newsletter: " . $e->getMessage());
            return false;
        }
    }
    
    public function getSources() {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM sources 
                WHERE newsletter_id = ? AND is_active = 1 
                ORDER BY sort_order, created_at
            ");
            $stmt->execute([$this->id]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error getting newsletter sources: " . $e->getMessage());
            return [];
        }
    }
    
    public function getSourceCount() {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as count 
                FROM sources 
                WHERE newsletter_id = ? AND is_active = 1
            ");
            $stmt->execute([$this->id]);
            return $stmt->fetch()['count'];
        } catch (Exception $e) {
            error_log("Error getting newsletter source count: " . $e->getMessage());
            return 0;
        }
    }
    
    public function addSource($type, $config = [], $name = null) {
        try {
            // Get the next sort order
            $stmt = $this->db->prepare("
                SELECT COALESCE(MAX(sort_order), 0) + 1 as next_order 
                FROM sources 
                WHERE newsletter_id = ? AND is_active = 1
            ");
            $stmt->execute([$this->id]);
            $nextOrder = $stmt->fetch()['next_order'];
            
            $stmt = $this->db->prepare("
                INSERT INTO sources (user_id, newsletter_id, type, name, config, sort_order) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            return $stmt->execute([
                $this->user_id,
                $this->id, 
                $type, 
                $name,
                json_encode($config),
                $nextOrder
            ]);
        } catch (Exception $e) {
            error_log("Error adding source to newsletter: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function removeSource($sourceId) {
        try {
            $stmt = $this->db->prepare("
                UPDATE sources 
                SET is_active = 0 
                WHERE id = ? AND newsletter_id = ?
            ");
            
            return $stmt->execute([$sourceId, $this->id]);
        } catch (Exception $e) {
            error_log("Error removing source from newsletter: " . $e->getMessage());
            return false;
        }
    }
    
    public function updateSource($sourceId, $config = null, $name = null) {
        $updates = [];
        $values = [];
        
        if ($config !== null) {
            $updates[] = "config = ?";
            $values[] = json_encode($config);
        }
        
        if ($name !== null) {
            $updates[] = "name = ?";
            $values[] = $name;
        }
        
        if (empty($updates)) {
            return false;
        }
        
        $values[] = $sourceId;
        $values[] = $this->id;
        
        $stmt = $this->db->prepare("
            UPDATE sources 
            SET " . implode(', ', $updates) . ", last_updated = CURRENT_TIMESTAMP 
            WHERE id = ? AND newsletter_id = ?
        ");
        
        return $stmt->execute($values);
    }
    
    public function updateSourceOrder($sourceIds) {
        try {
            $this->db->beginTransaction();
            
            $stmt = $this->db->prepare("
                UPDATE sources 
                SET sort_order = ?
                WHERE id = ? AND newsletter_id = ?
            ");
            
            foreach ($sourceIds as $order => $sourceId) {
                $stmt->execute([$order + 1, $sourceId, $this->id]);
            }
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error updating source order for newsletter: " . $e->getMessage());
            return false;
        }
    }
    
    // Getters
    public function getId() { return $this->id; }
    public function getUserId() { return $this->user_id; }
    public function getTitle() { return $this->title; }
    public function getTimezone() { return $this->timezone; }
    public function getSendTime() { return $this->send_time; }
    public function isActive() { return (bool)$this->is_active; }
    public function setActive($isActive) {
        $stmt = $this->db->prepare("UPDATE newsletters SET is_active = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        if ($stmt->execute([$isActive ? 1 : 0, $this->id])) {
            $this->is_active = $isActive ? 1 : 0;
            return true;
        }
        return false;
    }
    
    public function setPaused($isPaused) {
        $stmt = $this->db->prepare("UPDATE newsletters SET is_paused = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        if ($stmt->execute([$isPaused ? 1 : 0, $this->id])) {
            $this->is_paused = $isPaused ? 1 : 0;
            return true;
        }
        return false;
    }
    public function getCreatedAt() { return $this->created_at; }
    public function getUpdatedAt() { return $this->updated_at; }
    
    // New scheduling getters
    public function getFrequency() { return $this->frequency ?? 'daily'; }
    public function getDaysOfWeek() { 
        return $this->days_of_week ? json_decode($this->days_of_week, true) : []; 
    }
    public function getDayOfMonth() { return $this->day_of_month ?? 1; }
    public function getMonths() { 
        return $this->months ? json_decode($this->months, true) : []; 
    }
    public function getDailyTimes() { 
        return $this->daily_times ? json_decode($this->daily_times, true) : []; 
    }
    public function isPaused() { return (bool)$this->is_paused; }
    
    // Setter methods for scheduling
    public function setDailyTimes($dailyTimes) {
        $this->daily_times = is_array($dailyTimes) ? json_encode($dailyTimes) : $dailyTimes;
        return $this->update(['daily_times' => $this->daily_times]);
    }
    
    public function setDaysOfWeek($daysOfWeek) {
        $this->days_of_week = is_array($daysOfWeek) ? json_encode($daysOfWeek) : $daysOfWeek;
        return $this->update(['days_of_week' => $this->days_of_week]);
    }
    
    public function setDayOfMonth($dayOfMonth) {
        $this->day_of_month = $dayOfMonth;
        return $this->update(['day_of_month' => $this->day_of_month]);
    }
    
    // Helper methods for scheduling
    public function getDaysOfWeekString() { return $this->days_of_week ?? ''; }
    public function getMonthsString() { return $this->months ?? ''; }
    public function getDailyTimesString() { return $this->daily_times ?? ''; }
}