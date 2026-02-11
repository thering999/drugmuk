<?php

namespace App\Core;

use PDO;

/**
 * Audit Logger
 * 
 * Logs important system events for security and compliance
 */
class AuditLogger
{
    private $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->ensureTableExists();
    }
    
    /**
     * Ensure audit_logs table exists
     */
    private function ensureTableExists(): void
    {
        try {
            $this->db->query("SELECT 1 FROM audit_logs LIMIT 1");
        } catch (\Exception $e) {
            $sql = "CREATE TABLE IF NOT EXISTS `audit_logs` (
                `id` BIGINT(20) NOT NULL AUTO_INCREMENT,
                `user_id` INT(11) NULL,
                `username` VARCHAR(100) NULL,
                `action` VARCHAR(100) NOT NULL,
                `entity_type` VARCHAR(100) NULL,
                `entity_id` VARCHAR(100) NULL,
                `old_values` TEXT NULL,
                `new_values` TEXT NULL,
                `ip_address` VARCHAR(45) NULL,
                `user_agent` VARCHAR(500) NULL,
                `url` VARCHAR(500) NULL,
                `method` VARCHAR(10) NULL,
                `status` VARCHAR(20) DEFAULT 'success',
                `message` TEXT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `user_id` (`user_id`),
                KEY `action` (`action`),
                KEY `entity_type` (`entity_type`),
                KEY `created_at` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='System audit trail'";
            
            $this->db->exec($sql);
        }
    }
    
    /**
     * Log an audit event
     * 
     * @param string $action Action performed (e.g., 'user.login', 'order.create')
     * @param array $data Additional data
     * @return bool
     */
    public function log(string $action, array $data = []): bool
    {
        try {
            $sql = "INSERT INTO audit_logs (
                user_id, username, action, entity_type, entity_id,
                old_values, new_values, ip_address, user_agent,
                url, method, status, message
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            
            $stmt->execute([
                $_SESSION['user_id'] ?? null,
                $_SESSION['username'] ?? null,
                $action,
                $data['entity_type'] ?? null,
                $data['entity_id'] ?? null,
                isset($data['old_values']) ? json_encode($data['old_values']) : null,
                isset($data['new_values']) ? json_encode($data['new_values']) : null,
                $this->getClientIp(),
                $_SERVER['HTTP_USER_AGENT'] ?? null,
                $_SERVER['REQUEST_URI'] ?? null,
                $_SERVER['REQUEST_METHOD'] ?? null,
                $data['status'] ?? 'success',
                $data['message'] ?? null
            ]);
            
            return true;
        } catch (\Exception $e) {
            error_log("Audit log failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Log a login attempt
     * 
     * @param string $username
     * @param bool $success
     * @param string $message
     */
    public static function logLogin(string $username, bool $success, string $message = ''): void
    {
        $logger = new self();
        $logger->log('user.login', [
            'status' => $success ? 'success' : 'failed',
            'message' => $message,
            'new_values' => ['username' => $username]
        ]);
    }
    
    /**
     * Log a logout
     */
    public static function logLogout(): void
    {
        $logger = new self();
        $logger->log('user.logout');
    }
    
    /**
     * Log data access
     * 
     * @param string $entityType
     * @param mixed $entityId
     * @param string $action
     */
    public static function logAccess(string $entityType, $entityId, string $action = 'view'): void
    {
        $logger = new self();
        $logger->log("$entityType.$action", [
            'entity_type' => $entityType,
            'entity_id' => $entityId
        ]);
    }
    
    /**
     * Log data modification
     * 
     * @param string $entityType
     * @param mixed $entityId
     * @param array $oldValues
     * @param array $newValues
     * @param string $action
     */
    public static function logModification(
        string $entityType,
        $entityId,
        array $oldValues,
        array $newValues,
        string $action = 'update'
    ): void {
        $logger = new self();
        $logger->log("$entityType.$action", [
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'old_values' => $oldValues,
            'new_values' => $newValues
        ]);
    }
    
    /**
     * Log security event
     * 
     * @param string $event
     * @param string $message
     * @param string $status
     */
    public static function logSecurity(string $event, string $message, string $status = 'warning'): void
    {
        $logger = new self();
        $logger->log("security.$event", [
            'status' => $status,
            'message' => $message
        ]);
    }
    
    /**
     * Get audit logs
     * 
     * @param array $filters
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getLogs(array $filters = [], int $limit = 100, int $offset = 0): array
    {
        $where = [];
        $params = [];
        
        if (!empty($filters['user_id'])) {
            $where[] = "user_id = ?";
            $params[] = $filters['user_id'];
        }
        
        if (!empty($filters['action'])) {
            $where[] = "action LIKE ?";
            $params[] = $filters['action'] . '%';
        }
        
        if (!empty($filters['entity_type'])) {
            $where[] = "entity_type = ?";
            $params[] = $filters['entity_type'];
        }
        
        if (!empty($filters['start_date'])) {
            $where[] = "created_at >= ?";
            $params[] = $filters['start_date'];
        }
        
        if (!empty($filters['end_date'])) {
            $where[] = "created_at <= ?";
            $params[] = $filters['end_date'];
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $sql = "SELECT * FROM audit_logs 
                $whereClause 
                ORDER BY created_at DESC 
                LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get client IP address
     */
    private function getClientIp(): string
    {
        $ipKeys = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR'
        ];
        
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ips = explode(',', $ip);
                    $ip = trim($ips[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        
        return '0.0.0.0';
    }
    
    /**
     * Clean old audit logs
     * 
     * @param int $days Keep logs for this many days
     * @return int Number of deleted records
     */
    public function cleanup(int $days = 90): int
    {
        $sql = "DELETE FROM audit_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$days]);
        
        return $stmt->rowCount();
    }
}
