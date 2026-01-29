<?php

namespace App\Models;

use PDO;
use PDOException;

/**
 * Notification Model
 * ระบบแจ้งเตือนอัตโนมัติ
 */
class Notification
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
                CREATE TABLE IF NOT EXISTS `notifications` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `type` enum('low_stock','expiring','data_quality','order','system') NOT NULL,
                    `title` varchar(255) NOT NULL,
                    `message` text NOT NULL,
                    `severity` enum('info','warning','danger','success') DEFAULT 'info',
                    `link` varchar(255) DEFAULT NULL,
                    `is_read` tinyint(1) DEFAULT 0,
                    `user_id` int(11) DEFAULT NULL,
                    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `read_at` timestamp NULL DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    KEY `idx_user_unread` (`user_id`, `is_read`),
                    KEY `idx_type` (`type`),
                    KEY `idx_created` (`created_at`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");

            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS `notification_settings` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `user_id` int(11) NOT NULL,
                    `notify_low_stock` tinyint(1) DEFAULT 1,
                    `notify_expiring` tinyint(1) DEFAULT 1,
                    `notify_data_quality` tinyint(1) DEFAULT 1,
                    `notify_orders` tinyint(1) DEFAULT 1,
                    `email_enabled` tinyint(1) DEFAULT 0,
                    `email_address` varchar(255) DEFAULT NULL,
                    `line_enabled` tinyint(1) DEFAULT 0,
                    `line_token` varchar(255) DEFAULT NULL,
                    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `unique_user` (`user_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        } catch (PDOException $e) {
            error_log("Notification table creation error: " . $e->getMessage());
        }
    }

    /**
     * สร้างการแจ้งเตือนใหม่
     */
    public function create($type, $title, $message, $severity = 'info', $link = null, $userId = null)
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO notifications (type, title, message, severity, link, user_id)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$type, $title, $message, $severity, $link, $userId]);
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            error_log("Notification create error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * ดึงการแจ้งเตือนที่ยังไม่ได้อ่าน
     */
    public function getUnread($userId = null, $limit = 20)
    {
        try {
            $sql = "SELECT * FROM notifications WHERE is_read = 0";
            $params = [];
            
            if ($userId) {
                $sql .= " AND (user_id = ? OR user_id IS NULL)";
                $params[] = $userId;
            }
            
            $sql .= " ORDER BY created_at DESC LIMIT ?";
            $params[] = (int)$limit;
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * ดึงการแจ้งเตือนทั้งหมด
     */
    public function getAll($userId = null, $limit = 50, $offset = 0)
    {
        try {
            $sql = "SELECT * FROM notifications WHERE 1=1";
            $params = [];
            
            if ($userId) {
                $sql .= " AND (user_id = ? OR user_id IS NULL)";
                $params[] = $userId;
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
     * ทำเครื่องหมายว่าอ่านแล้ว
     */
    public function markAsRead($id)
    {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE notifications SET is_read = 1, read_at = NOW() WHERE id = ?
            ");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * ทำเครื่องหมายว่าอ่านทั้งหมด
     */
    public function markAllAsRead($userId = null)
    {
        try {
            $sql = "UPDATE notifications SET is_read = 1, read_at = NOW() WHERE is_read = 0";
            $params = [];
            
            if ($userId) {
                $sql .= " AND (user_id = ? OR user_id IS NULL)";
                $params[] = $userId;
            }
            
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * นับจำนวนการแจ้งเตือนที่ยังไม่ได้อ่าน
     */
    public function countUnread($userId = null)
    {
        try {
            $sql = "SELECT COUNT(*) FROM notifications WHERE is_read = 0";
            $params = [];
            
            if ($userId) {
                $sql .= " AND (user_id = ? OR user_id IS NULL)";
                $params[] = $userId;
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            return 0;
        }
    }

    /**
     * ส่งแจ้งเตือนผ่าน LINE
     */
    public function sendLine($token, $message)
    {
        if (empty($token)) return false;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://notify-api.line.me/api/notify");
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "message=" . $message);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer " . $token]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($result, true);
    }

    /**
     * ส่งแจ้งเตือนผ่าน Email
     */
    public function sendEmail($to, $subject, $body)
    {
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: Drugmuk System <noreply@drugmuk.local>\r\n";
        
        return mail($to, $subject, $body, $headers);
    }

    /**
     * ตรวจสอบและสร้างการแจ้งเตือนอัตโนมัติ
     */
    public function generateAutoNotifications()
    {
        $results = ['low_stock' => 0, 'expiring' => 0, 'data_quality' => 0];
        
        try {
            // 1. ยาใกล้หมดสต็อก
            $stmt = $this->pdo->query("
                SELECT d.id, d.name, COALESCE(SUM(i.quantity), 0) as current_stock, d.min_stock
                FROM drugs d
                LEFT JOIN inventory i ON d.id = i.drug_id
                GROUP BY d.id
                HAVING current_stock <= d.min_stock AND d.min_stock > 0
                LIMIT 10
            ");
            $lowStock = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($lowStock as $item) {
                $this->create(
                    'low_stock',
                    'ยาใกล้หมดสต็อก',
                    "ยา {$item['name']} เหลือ {$item['current_stock']} หน่วย (ขั้นต่ำ: {$item['min_stock']})",
                    'warning',
                    '/orders/what-to-buy'
                );
                $results['low_stock']++;
            }

            // 2. ยาใกล้หมดอายุ (90 วัน)
            $stmt = $this->pdo->query("
                SELECT d.name, i.lot_no, i.expire_date, DATEDIFF(i.expire_date, CURDATE()) as days_left
                FROM inventory i
                JOIN drugs d ON i.drug_id = d.id
                WHERE i.expire_date <= DATE_ADD(CURDATE(), INTERVAL 90 DAY)
                AND i.quantity > 0
                ORDER BY i.expire_date
                LIMIT 10
            ");
            $expiring = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($expiring as $item) {
                $severity = $item['days_left'] <= 30 ? 'danger' : 'warning';
                $this->create(
                    'expiring',
                    'ยาใกล้หมดอายุ',
                    "ยา {$item['name']} (Lot: {$item['lot_no']}) หมดอายุใน {$item['days_left']} วัน",
                    $severity,
                    '/inventory/expiring'
                );
                $results['expiring']++;
            }

            // 3. ปัญหาคุณภาพข้อมูล
            $stmt = $this->pdo->query("
                SELECT COUNT(*) FROM orphaned_records WHERE status = 'pending'
            ");
            $orphanedCount = (int)$stmt->fetchColumn();
            
            if ($orphanedCount > 0) {
                $this->create(
                    'data_quality',
                    'พบปัญหาคุณภาพข้อมูล',
                    "มี {$orphanedCount} รายการที่ต้องตรวจสอบ",
                    'info',
                    '/admin/data-cleansing'
                );
                $results['data_quality']++;
            }

        } catch (PDOException $e) {
            error_log("Auto notification error: " . $e->getMessage());
        }
        
        return $results;
    }

    /**
     * ดึงการตั้งค่าการแจ้งเตือนของผู้ใช้
     */
    public function getSettings($userId)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM notification_settings WHERE user_id = ?");
            $stmt->execute([$userId]);
            $settings = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$settings) {
                // สร้างค่าเริ่มต้น
                $this->pdo->prepare("INSERT INTO notification_settings (user_id) VALUES (?)")->execute([$userId]);
                return $this->getSettings($userId);
            }
            
            return $settings;
        } catch (PDOException $e) {
            return null;
        }
    }

    /**
     * บันทึกการตั้งค่าการแจ้งเตือน
     */
    public function saveSettings($userId, $data)
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO notification_settings (user_id, notify_low_stock, notify_expiring, notify_data_quality, notify_orders, email_enabled, email_address, line_enabled, line_token)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    notify_low_stock = VALUES(notify_low_stock),
                    notify_expiring = VALUES(notify_expiring),
                    notify_data_quality = VALUES(notify_data_quality),
                    notify_orders = VALUES(notify_orders),
                    email_enabled = VALUES(email_enabled),
                    email_address = VALUES(email_address),
                    line_enabled = VALUES(line_enabled),
                    line_token = VALUES(line_token)
            ");
            
            return $stmt->execute([
                $userId,
                $data['notify_low_stock'] ?? 1,
                $data['notify_expiring'] ?? 1,
                $data['notify_data_quality'] ?? 1,
                $data['notify_orders'] ?? 1,
                $data['email_enabled'] ?? 0,
                $data['email_address'] ?? null,
                $data['line_enabled'] ?? 0,
                $data['line_token'] ?? null
            ]);
        } catch (PDOException $e) {
            return false;
        }
    }
}
