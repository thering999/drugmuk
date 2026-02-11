<?php

namespace App\Services;

use App\Core\Database;
use PDO;

/**
 * Enhanced Notification Manager
 * 
 * Manages in-app notifications with database persistence
 */
class NotificationManager
{
    private $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->ensureTableExists();
    }
    
    /**
     * Ensure notifications table exists
     */
    private function ensureTableExists(): void
    {
        try {
            $this->db->query("SELECT 1 FROM user_notifications LIMIT 1");
        } catch (\Exception $e) {
            $sql = "CREATE TABLE IF NOT EXISTS `user_notifications` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `user_id` INT(11) NULL COMMENT 'NULL = broadcast to all',
                `type` VARCHAR(50) NOT NULL COMMENT 'info, warning, error, success',
                `category` VARCHAR(50) NULL COMMENT 'stock, order, safety, system',
                `title` VARCHAR(255) NOT NULL,
                `message` TEXT NOT NULL,
                `action_url` VARCHAR(500) NULL,
                `action_label` VARCHAR(100) NULL,
                `data` TEXT NULL COMMENT 'JSON data',
                `is_read` TINYINT(1) DEFAULT 0,
                `priority` ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
                `expires_at` DATETIME NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `read_at` DATETIME NULL,
                PRIMARY KEY (`id`),
                KEY `user_id` (`user_id`),
                KEY `is_read` (`is_read`),
                KEY `created_at` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            
            $this->db->exec($sql);
        }
    }
    
    /**
     * Create a notification
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO user_notifications (
            user_id, type, category, title, message,
            action_url, action_label, data, priority, expires_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $data['user_id'] ?? null,
            $data['type'] ?? 'info',
            $data['category'] ?? null,
            $data['title'],
            $data['message'],
            $data['action_url'] ?? null,
            $data['action_label'] ?? null,
            isset($data['data']) ? json_encode($data['data']) : null,
            $data['priority'] ?? 'medium',
            $data['expires_at'] ?? null
        ]);
        
        return (int)$this->db->lastInsertId();
    }
    
    /**
     * Get user notifications
     */
    public function getUserNotifications(int $userId, bool $unreadOnly = false, int $limit = 50): array
    {
        $sql = "SELECT * FROM user_notifications 
                WHERE (user_id = ? OR user_id IS NULL)
                  AND (expires_at IS NULL OR expires_at > NOW())";
        
        if ($unreadOnly) {
            $sql .= " AND is_read = 0";
        }
        
        $sql .= " ORDER BY 
                    CASE priority
                        WHEN 'critical' THEN 1
                        WHEN 'high' THEN 2
                        WHEN 'medium' THEN 3
                        WHEN 'low' THEN 4
                    END,
                    created_at DESC
                  LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $limit]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Mark as read
     */
    public function markAsRead(int $notificationId, int $userId): bool
    {
        $sql = "UPDATE user_notifications 
                SET is_read = 1, read_at = NOW()
                WHERE id = ? AND (user_id = ? OR user_id IS NULL)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$notificationId, $userId]);
        
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Get unread count
     */
    public function getUnreadCount(int $userId): int
    {
        $sql = "SELECT COUNT(*) FROM user_notifications 
                WHERE (user_id = ? OR user_id IS NULL)
                  AND is_read = 0
                  AND (expires_at IS NULL OR expires_at > NOW())";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        
        return (int)$stmt->fetchColumn();
    }
}
