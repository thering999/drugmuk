<?php

namespace App\Models;

use PDO;
use PDOException;

/**
 * AuditTrail Model
 * บันทึกประวัติการแก้ไขข้อมูลทั้งหมด
 */
class AuditTrail
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = \App\Core\Database::getInstance()->getConnection();
        $this->ensureTableExists();
    }

    /**
     * สร้างตารางถ้ายังไม่มี
     */
    private function ensureTableExists()
    {
        try {
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS `audit_trail` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `table_name` varchar(100) NOT NULL,
                    `record_id` int(11) NOT NULL,
                    `action` enum('create','update','delete','merge','import') NOT NULL,
                    `old_values` json DEFAULT NULL,
                    `new_values` json DEFAULT NULL,
                    `changed_fields` text DEFAULT NULL,
                    `user_id` int(11) DEFAULT NULL,
                    `user_name` varchar(100) DEFAULT NULL,
                    `ip_address` varchar(45) DEFAULT NULL,
                    `user_agent` varchar(255) DEFAULT NULL,
                    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    KEY `idx_table_record` (`table_name`, `record_id`),
                    KEY `idx_user` (`user_id`),
                    KEY `idx_action` (`action`),
                    KEY `idx_created` (`created_at`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        } catch (PDOException $e) {
            error_log("AuditTrail table creation error: " . $e->getMessage());
        }
    }

    /**
     * บันทึกการเปลี่ยนแปลง
     */
    public function log($tableName, $recordId, $action, $oldValues = null, $newValues = null, $userId = null)
    {
        try {
            $userName = null;
            if ($userId) {
                $stmt = $this->pdo->prepare("SELECT full_name FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $userName = $stmt->fetchColumn();
            }

            // หา fields ที่เปลี่ยนแปลง
            $changedFields = [];
            if ($oldValues && $newValues) {
                $oldArr = is_string($oldValues) ? json_decode($oldValues, true) : $oldValues;
                $newArr = is_string($newValues) ? json_decode($newValues, true) : $newValues;
                
                if ($oldArr && $newArr) {
                    foreach ($newArr as $key => $value) {
                        if (!isset($oldArr[$key]) || $oldArr[$key] !== $value) {
                            $changedFields[] = $key;
                        }
                    }
                }
            }

            $stmt = $this->pdo->prepare("
                INSERT INTO audit_trail (table_name, record_id, action, old_values, new_values, changed_fields, user_id, user_name, ip_address, user_agent)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            return $stmt->execute([
                $tableName,
                $recordId,
                $action,
                is_string($oldValues) ? $oldValues : json_encode($oldValues),
                is_string($newValues) ? $newValues : json_encode($newValues),
                implode(',', $changedFields),
                $userId,
                $userName,
                $_SERVER['REMOTE_ADDR'] ?? null,
                substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255)
            ]);
        } catch (PDOException $e) {
            error_log("AuditTrail log error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * ดึงประวัติของ record
     */
    public function getHistory($tableName, $recordId, $limit = 50)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM audit_trail 
                WHERE table_name = ? AND record_id = ?
                ORDER BY created_at DESC
                LIMIT ?
            ");
            $stmt->execute([$tableName, $recordId, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * ดึงประวัติทั้งหมด
     */
    public function getAll($filters = [], $limit = 100, $offset = 0)
    {
        try {
            $sql = "SELECT * FROM audit_trail WHERE 1=1";
            $params = [];

            if (!empty($filters['table_name'])) {
                $sql .= " AND table_name = ?";
                $params[] = $filters['table_name'];
            }

            if (!empty($filters['action'])) {
                $sql .= " AND action = ?";
                $params[] = $filters['action'];
            }

            if (!empty($filters['user_id'])) {
                $sql .= " AND user_id = ?";
                $params[] = $filters['user_id'];
            }

            if (!empty($filters['date_from'])) {
                $sql .= " AND DATE(created_at) >= ?";
                $params[] = $filters['date_from'];
            }

            if (!empty($filters['date_to'])) {
                $sql .= " AND DATE(created_at) <= ?";
                $params[] = $filters['date_to'];
            }

            $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
            $params[] = (int)$limit;
            $params[] = (int)$offset;

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * นับจำนวนรายการ
     */
    public function count($filters = [])
    {
        try {
            $sql = "SELECT COUNT(*) FROM audit_trail WHERE 1=1";
            $params = [];

            if (!empty($filters['table_name'])) {
                $sql .= " AND table_name = ?";
                $params[] = $filters['table_name'];
            }

            if (!empty($filters['action'])) {
                $sql .= " AND action = ?";
                $params[] = $filters['action'];
            }

            if (!empty($filters['user_id'])) {
                $sql .= " AND user_id = ?";
                $params[] = $filters['user_id'];
            }

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            return 0;
        }
    }

    /**
     * ดึงสถิติการเปลี่ยนแปลง
     */
    public function getStatistics($days = 30)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    DATE(created_at) as date,
                    action,
                    COUNT(*) as count
                FROM audit_trail
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY DATE(created_at), action
                ORDER BY date DESC
            ");
            $stmt->execute([$days]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * ดึงผู้ใช้ที่มีการเปลี่ยนแปลงมากที่สุด
     */
    public function getTopUsers($limit = 10, $days = 30)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    user_id,
                    user_name,
                    COUNT(*) as total_changes,
                    COUNT(DISTINCT table_name) as tables_affected
                FROM audit_trail
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                AND user_id IS NOT NULL
                GROUP BY user_id, user_name
                ORDER BY total_changes DESC
                LIMIT ?
            ");
            $stmt->execute([$days, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
}
