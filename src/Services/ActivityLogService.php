<?php
/**
 * Activity Log Service
 * บันทึกและแสดง Activity Log ของระบบ
 * 
 * @package Drugmuk
 * @version 3.4.0
 */

namespace App\Services;

use App\Core\Database;

class ActivityLogService
{
    private \PDO $db;
    private string $table = 'activity_logs';
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->ensureTableExists();
    }
    
    /**
     * สร้างตารางถ้ายังไม่มี
     */
    private function ensureTableExists(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table} (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            username VARCHAR(100) NULL,
            action VARCHAR(50) NOT NULL,
            module VARCHAR(50) NOT NULL,
            description TEXT NULL,
            target_type VARCHAR(50) NULL,
            target_id INT NULL,
            ip_address VARCHAR(45) NULL,
            user_agent VARCHAR(255) NULL,
            extra_data JSON NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user (user_id),
            INDEX idx_action (action),
            INDEX idx_module (module),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $this->db->exec($sql);
    }
    
    /**
     * บันทึก Activity Log
     */
    public function log(
        string $action,
        string $module,
        ?string $description = null,
        ?string $targetType = null,
        ?int $targetId = null,
        ?array $extraData = null
    ): bool {
        $sql = "INSERT INTO {$this->table} 
                (user_id, username, action, module, description, target_type, target_id, ip_address, user_agent, extra_data)
                VALUES (:user_id, :username, :action, :module, :description, :target_type, :target_id, :ip_address, :user_agent, :extra_data)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'user_id' => $_SESSION['user_id'] ?? null,
            'username' => $_SESSION['username'] ?? $_SESSION['full_name'] ?? 'system',
            'action' => $action,
            'module' => $module,
            'description' => $description,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'ip_address' => $this->getClientIP(),
            'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            'extra_data' => $extraData ? json_encode($extraData) : null
        ]);
    }
    
    /**
     * Log การเข้าสู่ระบบ
     */
    public function logLogin(int $userId, string $username): bool
    {
        return $this->log('login', 'auth', "ผู้ใช้ $username เข้าสู่ระบบ", 'user', $userId);
    }
    
    /**
     * Log การออกจากระบบ
     */
    public function logLogout(): bool
    {
        $username = $_SESSION['username'] ?? $_SESSION['full_name'] ?? 'unknown';
        return $this->log('logout', 'auth', "ผู้ใช้ $username ออกจากระบบ");
    }
    
    /**
     * Log การจ่ายยา
     */
    public function logDispensing(int $dispensingId, string $patientHN, array $drugs): bool
    {
        $drugList = implode(', ', array_column($drugs, 'name'));
        return $this->log(
            'dispense',
            'dispensing',
            "จ่ายยาให้ผู้ป่วย HN: $patientHN - ยา: $drugList",
            'dispensing',
            $dispensingId,
            ['patient_hn' => $patientHN, 'drugs' => $drugs]
        );
    }
    
    /**
     * Log การรับยาเข้าคลัง
     */
    public function logReceive(int $transactionId, string $drugName, int $quantity, string $lotNo): bool
    {
        return $this->log(
            'receive',
            'inventory',
            "รับยา $drugName จำนวน $quantity Lot: $lotNo",
            'transaction',
            $transactionId,
            ['drug_name' => $drugName, 'quantity' => $quantity, 'lot_no' => $lotNo]
        );
    }
    
    /**
     * Log การสร้างใบสั่งซื้อ
     */
    public function logOrderCreate(int $orderId, string $orderNo, float $totalAmount): bool
    {
        return $this->log(
            'create',
            'orders',
            "สร้างใบสั่งซื้อ $orderNo มูลค่า " . number_format($totalAmount, 2) . " บาท",
            'order',
            $orderId,
            ['order_no' => $orderNo, 'total_amount' => $totalAmount]
        );
    }
    
    /**
     * Log การแก้ไขข้อมูลยา
     */
    public function logDrugUpdate(int $drugId, string $drugName, array $changes): bool
    {
        return $this->log(
            'update',
            'drugs',
            "แก้ไขข้อมูลยา $drugName",
            'drug',
            $drugId,
            ['changes' => $changes]
        );
    }
    
    /**
     * Log การ Export ข้อมูล
     */
    public function logExport(string $reportType, string $format): bool
    {
        return $this->log(
            'export',
            'reports',
            "Export รายงาน $reportType เป็น $format"
        );
    }
    
    /**
     * Log การตั้งค่าระบบ
     */
    public function logSettingsChange(string $settingName, $oldValue, $newValue): bool
    {
        return $this->log(
            'settings_change',
            'settings',
            "เปลี่ยนการตั้งค่า $settingName",
            null,
            null,
            ['setting' => $settingName, 'old_value' => $oldValue, 'new_value' => $newValue]
        );
    }
    
    /**
     * ดึง Activity Logs
     */
    public function getLogs(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        $where = ['1=1'];
        $params = [];
        
        if (!empty($filters['user_id'])) {
            $where[] = 'user_id = :user_id';
            $params['user_id'] = $filters['user_id'];
        }
        
        if (!empty($filters['action'])) {
            $where[] = 'action = :action';
            $params['action'] = $filters['action'];
        }
        
        if (!empty($filters['module'])) {
            $where[] = 'module = :module';
            $params['module'] = $filters['module'];
        }
        
        if (!empty($filters['date_from'])) {
            $where[] = 'DATE(created_at) >= :date_from';
            $params['date_from'] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $where[] = 'DATE(created_at) <= :date_to';
            $params['date_to'] = $filters['date_to'];
        }
        
        if (!empty($filters['search'])) {
            $where[] = '(description LIKE :search OR username LIKE :search)';
            $params['search'] = '%' . $filters['search'] . '%';
        }
        
        $whereClause = implode(' AND ', $where);
        
        $sql = "SELECT * FROM {$this->table} 
                WHERE $whereClause 
                ORDER BY created_at DESC 
                LIMIT $limit OFFSET $offset";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
    
    /**
     * นับจำนวน Logs
     */
    public function countLogs(array $filters = []): int
    {
        $where = ['1=1'];
        $params = [];
        
        if (!empty($filters['user_id'])) {
            $where[] = 'user_id = :user_id';
            $params['user_id'] = $filters['user_id'];
        }
        
        if (!empty($filters['module'])) {
            $where[] = 'module = :module';
            $params['module'] = $filters['module'];
        }
        
        $whereClause = implode(' AND ', $where);
        
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE $whereClause";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return (int)$stmt->fetch()['count'];
    }
    
    /**
     * ดึง Logs ล่าสุด
     */
    public function getRecentLogs(int $limit = 20): array
    {
        return $this->getLogs([], $limit);
    }
    
    /**
     * ดึงสถิติ Activity
     */
    public function getStatistics(int $days = 7): array
    {
        $sql = "SELECT 
                    action,
                    COUNT(*) as count
                FROM {$this->table}
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
                GROUP BY action
                ORDER BY count DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['days' => $days]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * ดึงสถิติรายวัน
     */
    public function getDailyStatistics(int $days = 7): array
    {
        $sql = "SELECT 
                    DATE(created_at) as date,
                    COUNT(*) as count
                FROM {$this->table}
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
                GROUP BY DATE(created_at)
                ORDER BY date ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['days' => $days]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * ลบ Logs เก่า
     */
    public function cleanOldLogs(int $retentionDays = 90): int
    {
        $sql = "DELETE FROM {$this->table} WHERE created_at < DATE_SUB(NOW(), INTERVAL :days DAY)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['days' => $retentionDays]);
        
        return $stmt->rowCount();
    }
    
    /**
     * Get Client IP
     */
    private function getClientIP(): string
    {
        $ipKeys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = explode(',', $_SERVER[$key])[0];
                return trim($ip);
            }
        }
        
        return '0.0.0.0';
    }
    
    /**
     * Format Action สำหรับแสดงผล
     */
    public static function formatAction(string $action): array
    {
        $actions = [
            'login' => ['icon' => '🔐', 'label' => 'เข้าสู่ระบบ', 'color' => '#10b981'],
            'logout' => ['icon' => '🚪', 'label' => 'ออกจากระบบ', 'color' => '#6b7280'],
            'dispense' => ['icon' => '💊', 'label' => 'จ่ายยา', 'color' => '#f59e0b'],
            'receive' => ['icon' => '📦', 'label' => 'รับยา', 'color' => '#3b82f6'],
            'create' => ['icon' => '➕', 'label' => 'สร้าง', 'color' => '#10b981'],
            'update' => ['icon' => '✏️', 'label' => 'แก้ไข', 'color' => '#8b5cf6'],
            'delete' => ['icon' => '🗑️', 'label' => 'ลบ', 'color' => '#ef4444'],
            'export' => ['icon' => '📥', 'label' => 'Export', 'color' => '#06b6d4'],
            'settings_change' => ['icon' => '⚙️', 'label' => 'ตั้งค่า', 'color' => '#6366f1']
        ];
        
        return $actions[$action] ?? ['icon' => '📝', 'label' => $action, 'color' => '#6b7280'];
    }
}
