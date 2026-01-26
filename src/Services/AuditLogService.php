<?php
/**
 * Audit Log Service
 * 
 * Comprehensive activity logging for compliance and security
 * 
 * @package Drugmuk
 * @subpackage Services
 * @version 1.0
 * @since Phase 6.3
 */

namespace App\Services;

use PDO;

class AuditLogService
{
    private $db;
    private static $instance = null;
    
    private function __construct()
    {
        $this->db = \App\Core\Database::getInstance()->getConnection();
    }
    
    /**
     * Get singleton instance
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Log an action
     * 
     * @param string $action Action performed
     * @param string|null $entityType Entity type (e.g., 'drug', 'order')
     * @param int|null $entityId Entity ID
     * @param array|null $oldValues Old values before change
     * @param array|null $newValues New values after change
     * @return bool Success status
     */
    public function log($action, $entityType = null, $entityId = null, $oldValues = null, $newValues = null)
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO audit_logs 
                (user_id, action, entity_type, entity_id, old_values, new_values, 
                 ip_address, user_agent, request_method, request_url)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            return $stmt->execute([
                $_SESSION['user_id'] ?? null,
                $action,
                $entityType,
                $entityId,
                $oldValues ? json_encode($oldValues) : null,
                $newValues ? json_encode($newValues) : null,
                $this->getClientIP(),
                $_SERVER['HTTP_USER_AGENT'] ?? null,
                $_SERVER['REQUEST_METHOD'] ?? null,
                $_SERVER['REQUEST_URI'] ?? null
            ]);
            
        } catch (\Exception $e) {
            error_log("Audit log error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Log user login
     * 
     * @param int $userId User ID
     * @param bool $success Login success status
     * @param string|null $failureReason Reason for failure
     */
    public function logLogin($userId, $success = true, $failureReason = null)
    {
        try {
            // Log to audit_logs
            $this->log($success ? 'user.login.success' : 'user.login.failed');
            
            // Log to login_attempts
            $stmt = $this->db->prepare("
                INSERT INTO login_attempts 
                (username, ip_address, user_agent, success, failure_reason)
                SELECT username, ?, ?, ?, ?
                FROM users WHERE id = ?
            ");
            
            $stmt->execute([
                $this->getClientIP(),
                $_SERVER['HTTP_USER_AGENT'] ?? null,
                $success,
                $failureReason,
                $userId
            ]);
            
        } catch (\Exception $e) {
            error_log("Login audit error: " . $e->getMessage());
        }
    }
    
    /**
     * Log user logout
     * 
     * @param int $userId User ID
     */
    public function logLogout($userId)
    {
        $this->log('user.logout');
    }
    
    /**
     * Log data access
     * 
     * @param string $entityType Entity type
     * @param int $entityId Entity ID
     */
    public function logAccess($entityType, $entityId)
    {
        $this->log("$entityType.view", $entityType, $entityId);
    }
    
    /**
     * Log data creation
     * 
     * @param string $entityType Entity type
     * @param int $entityId Entity ID
     * @param array $data Created data
     */
    public function logCreate($entityType, $entityId, $data)
    {
        $this->log("$entityType.create", $entityType, $entityId, null, $data);
    }
    
    /**
     * Log data update
     * 
     * @param string $entityType Entity type
     * @param int $entityId Entity ID
     * @param array $oldData Old data
     * @param array $newData New data
     */
    public function logUpdate($entityType, $entityId, $oldData, $newData)
    {
        $this->log("$entityType.update", $entityType, $entityId, $oldData, $newData);
    }
    
    /**
     * Log data deletion
     * 
     * @param string $entityType Entity type
     * @param int $entityId Entity ID
     * @param array $data Deleted data
     */
    public function logDelete($entityType, $entityId, $data)
    {
        $this->log("$entityType.delete", $entityType, $entityId, $data, null);
    }
    
    /**
     * Get user activity log
     * 
     * @param int $userId User ID
     * @param int $limit Number of records
     * @param int $offset Offset for pagination
     * @return array Activity logs
     */
    public function getUserActivity($userId, $limit = 100, $offset = 0)
    {
        $stmt = $this->db->prepare("
            SELECT 
                al.*,
                u.username
            FROM audit_logs al
            LEFT JOIN users u ON al.user_id = u.id
            WHERE al.user_id = ?
            ORDER BY al.created_at DESC
            LIMIT ? OFFSET ?
        ");
        
        $stmt->execute([$userId, $limit, $offset]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get entity history
     * 
     * @param string $entityType Entity type
     * @param int $entityId Entity ID
     * @return array Entity history
     */
    public function getEntityHistory($entityType, $entityId)
    {
        $stmt = $this->db->prepare("
            SELECT 
                al.*,
                u.username
            FROM audit_logs al
            LEFT JOIN users u ON al.user_id = u.id
            WHERE al.entity_type = ? AND al.entity_id = ?
            ORDER BY al.created_at DESC
        ");
        
        $stmt->execute([$entityType, $entityId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get recent activity
     * 
     * @param int $limit Number of records
     * @return array Recent logs
     */
    public function getRecentActivity($limit = 50)
    {
        $stmt = $this->db->prepare("
            SELECT 
                al.*,
                u.username
            FROM audit_logs al
            LEFT JOIN users u ON al.user_id = u.id
            ORDER BY al.created_at DESC
            LIMIT ?
        ");
        
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Search audit logs
     * 
     * @param array $filters Search filters
     * @param int $limit Limit
     * @param int $offset Offset
     * @return array Search results
     */
    public function search($filters = [], $limit = 100, $offset = 0)
    {
        $where = [];
        $params = [];
        
        if (!empty($filters['user_id'])) {
            $where[] = "al.user_id = ?";
            $params[] = $filters['user_id'];
        }
        
        if (!empty($filters['action'])) {
            $where[] = "al.action LIKE ?";
            $params[] = '%' . $filters['action'] . '%';
        }
        
        if (!empty($filters['entity_type'])) {
            $where[] = "al.entity_type = ?";
            $params[] = $filters['entity_type'];
        }
        
        if (!empty($filters['date_from'])) {
            $where[] = "al.created_at >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $where[] = "al.created_at <= ?";
            $params[] = $filters['date_to'];
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $stmt = $this->db->prepare("
            SELECT 
                al.*,
                u.username
            FROM audit_logs al
            LEFT JOIN users u ON al.user_id = u.id
            $whereClause
            ORDER BY al.created_at DESC
            LIMIT ? OFFSET ?
        ");
        
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Export audit logs to CSV
     * 
     * @param array $filters Search filters
     * @return string CSV content
     */
    public function exportToCSV($filters = [])
    {
        $logs = $this->search($filters, 10000, 0);
        
        $csv = "ID,User,Action,Entity Type,Entity ID,IP Address,Date\n";
        
        foreach ($logs as $log) {
            $csv .= sprintf(
                "%d,%s,%s,%s,%s,%s,%s\n",
                $log['id'],
                $log['username'] ?? 'System',
                $log['action'],
                $log['entity_type'] ?? '',
                $log['entity_id'] ?? '',
                $log['ip_address'] ?? '',
                $log['created_at']
            );
        }
        
        return $csv;
    }
    
    /**
     * Get statistics
     * 
     * @param string $period Period (today, week, month)
     * @return array Statistics
     */
    public function getStatistics($period = 'today')
    {
        $dateFilter = match($period) {
            'today' => "DATE(created_at) = CURDATE()",
            'week' => "created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)",
            'month' => "created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)",
            default => "1=1"
        };
        
        $stmt = $this->db->query("
            SELECT 
                COUNT(*) as total_actions,
                COUNT(DISTINCT user_id) as active_users,
                COUNT(DISTINCT entity_type) as entity_types,
                COUNT(DISTINCT DATE(created_at)) as active_days
            FROM audit_logs
            WHERE $dateFilter
        ");
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get client IP address
     * 
     * @return string IP address
     */
    private function getClientIP()
    {
        $ipKeys = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];
        
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER)) {
                $ip = $_SERVER[$key];
                
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
                
                // Handle multiple IPs
                $ips = explode(',', $ip);
                foreach ($ips as $singleIP) {
                    $singleIP = trim($singleIP);
                    if (filter_var($singleIP, FILTER_VALIDATE_IP)) {
                        return $singleIP;
                    }
                }
            }
        }
        
        return '0.0.0.0';
    }
    
    /**
     * Clean old logs
     * 
     * @param int $days Days to keep
     * @return int Number of deleted records
     */
    public function cleanOldLogs($days = 365)
    {
        $stmt = $this->db->prepare("
            DELETE FROM audit_logs
            WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
        ");
        
        $stmt->execute([$days]);
        return $stmt->rowCount();
    }
}
