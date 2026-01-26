<?php

namespace App\Services;

/**
 * System Health Service
 * 
 * Monitor system health and performance
 */
class SystemHealthService
{
    private $db;
    
    public function __construct()
    {
        $this->db = \App\Core\Database::getInstance()->getConnection();
    }
    
    /**
     * Get complete system health status
     */
    public function getHealthStatus(): array
    {
        return [
            'overall_status' => $this->getOverallStatus(),
            'system' => $this->getSystemMetrics(),
            'database' => $this->getDatabaseHealth(),
            'disk' => $this->getDiskHealth(),
            'application' => $this->getApplicationHealth(),
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Get overall system status
     */
    private function getOverallStatus(): string
    {
        $health = $this->getHealthStatus();
        
        // Check critical metrics
        if ($health['disk']['free_percent'] < 10) return 'critical';
        if ($health['database']['status'] !== 'healthy') return 'warning';
        if ($health['system']['memory_percent'] > 90) return 'warning';
        
        return 'healthy';
    }
    
    /**
     * Get system metrics
     */
    public function getSystemMetrics(): array
    {
        $load = sys_getloadavg();
        
        return [
            'cpu_load_1min' => round($load[0], 2),
            'cpu_load_5min' => round($load[1], 2),
            'cpu_load_15min' => round($load[2], 2),
            'memory_total' => $this->getMemoryTotal(),
            'memory_used' => $this->getMemoryUsed(),
            'memory_percent' => $this->getMemoryPercent(),
            'uptime' => $this->getUptime(),
            'php_version' => PHP_VERSION
        ];
    }
    
    /**
     * Get database health
     */
    public function getDatabaseHealth(): array
    {
        try {
            // Test connection
            $this->db->query('SELECT 1');
            
            // Get database size
            $dbName = getenv('DB_NAME') ?: 'drugmuk';
            $stmt = $this->db->query(
                "SELECT 
                    SUM(data_length + index_length) as size,
                    COUNT(*) as table_count
                 FROM information_schema.TABLES 
                 WHERE table_schema = '{$dbName}'"
            );
            $dbInfo = $stmt->fetch();
            
            // Get connection count
            $connections = $this->db->query("SHOW STATUS LIKE 'Threads_connected'")->fetch();
            
            // Get slow queries
            $slowQueries = $this->db->query("SHOW STATUS LIKE 'Slow_queries'")->fetch();
            
            return [
                'status' => 'healthy',
                'size_mb' => round($dbInfo['size'] / 1024 / 1024, 2),
                'table_count' => $dbInfo['table_count'],
                'connections' => $connections['Value'] ?? 0,
                'slow_queries' => $slowQueries['Value'] ?? 0,
                'version' => $this->db->query('SELECT VERSION()')->fetchColumn()
            ];
            
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get disk health
     */
    public function getDiskHealth(): array
    {
        $rootPath = __DIR__ . '/../..';
        
        $totalSpace = disk_total_space($rootPath);
        $freeSpace = disk_free_space($rootPath);
        $usedSpace = $totalSpace - $freeSpace;
        
        return [
            'total_gb' => round($totalSpace / 1024 / 1024 / 1024, 2),
            'used_gb' => round($usedSpace / 1024 / 1024 / 1024, 2),
            'free_gb' => round($freeSpace / 1024 / 1024 / 1024, 2),
            'free_percent' => round(($freeSpace / $totalSpace) * 100, 2),
            'status' => $freeSpace / $totalSpace > 0.1 ? 'healthy' : 'warning'
        ];
    }
    
    /**
     * Get application health
     */
    public function getApplicationHealth(): array
    {
        // Error rate (last hour)
        $errorRate = $this->getErrorRate();
        
        // Active users
        $activeUsers = $this->getActiveUsers();
        
        // Cache hit rate
        $cacheHitRate = $this->getCacheHitRate();
        
        return [
            'error_rate' => $errorRate,
            'active_users' => $activeUsers,
            'cache_hit_rate' => $cacheHitRate,
            'status' => $errorRate < 5 ? 'healthy' : 'warning'
        ];
    }
    
    /**
     * Get memory total (MB)
     */
    private function getMemoryTotal(): float
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return 0; // Not easily available on Windows
        }
        
        $meminfo = file_get_contents('/proc/meminfo');
        preg_match('/MemTotal:\s+(\d+)/', $meminfo, $matches);
        return round($matches[1] / 1024, 2);
    }
    
    /**
     * Get memory used (MB)
     */
    private function getMemoryUsed(): float
    {
        return round(memory_get_usage(true) / 1024 / 1024, 2);
    }
    
    /**
     * Get memory usage percentage
     */
    private function getMemoryPercent(): float
    {
        $total = $this->getMemoryTotal();
        if ($total == 0) return 0;
        
        return round(($this->getMemoryUsed() / $total) * 100, 2);
    }
    
    /**
     * Get system uptime
     */
    private function getUptime(): string
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return 'N/A';
        }
        
        $uptime = file_get_contents('/proc/uptime');
        $seconds = (int)explode(' ', $uptime)[0];
        
        $days = floor($seconds / 86400);
        $hours = floor(($seconds % 86400) / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        
        return "{$days}d {$hours}h {$minutes}m";
    }
    
    /**
     * Get error rate (last hour)
     */
    private function getErrorRate(): float
    {
        try {
            $stmt = $this->db->query(
                "SELECT COUNT(*) as error_count 
                 FROM error_logs 
                 WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)"
            );
            $result = $stmt->fetch();
            
            return $result['error_count'] ?? 0;
            
        } catch (\Exception $e) {
            return 0;
        }
    }
    
    /**
     * Get active users count
     */
    private function getActiveUsers(): int
    {
        try {
            $stmt = $this->db->query(
                "SELECT COUNT(DISTINCT user_id) as count
                 FROM user_activity
                 WHERE created_at >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)"
            );
            $result = $stmt->fetch();
            
            return $result['count'] ?? 0;
            
        } catch (\Exception $e) {
            return 0;
        }
    }
    
    /**
     * Get cache hit rate
     */
    private function getCacheHitRate(): float
    {
        // This would need cache statistics
        // Placeholder for now
        return 85.5;
    }
    
    /**
     * Store health metrics
     */
    public function storeMetrics(array $metrics): void
    {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO health_metrics 
                 (cpu_load, memory_percent, disk_free_percent, error_rate, active_users, created_at)
                 VALUES (?, ?, ?, ?, ?, NOW())"
            );
            
            $stmt->execute([
                $metrics['system']['cpu_load_1min'],
                $metrics['system']['memory_percent'],
                $metrics['disk']['free_percent'],
                $metrics['application']['error_rate'],
                $metrics['application']['active_users']
            ]);
            
        } catch (\Exception $e) {
            // Silent fail - don't break health check
        }
    }
}
