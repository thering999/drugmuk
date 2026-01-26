<?php
/**
 * Dashboard Analytics Service
 * Real-time analytics and statistics for dashboard
 */

namespace App\Services;

class DashboardAnalyticsService
{
    private $db;
    private $cache;
    
    public function __construct($db)
    {
        $this->db = $db;
        $this->cache = new CacheService();
    }
    
    /**
     * Get dashboard overview statistics
     */
    public function getOverview(): array
    {
        return $this->cache->remember('dashboard_overview', function() {
            return [
                'total_drugs' => $this->getTotalDrugs(),
                'total_inventory_value' => $this->getTotalInventoryValue(),
                'expiring_soon' => $this->getExpiringSoonCount(),
                'low_stock' => $this->getLowStockCount(),
                'pending_orders' => $this->getPendingOrdersCount(),
                'today_dispensing' => $this->getTodayDispensingCount(),
            ];
        }, 300); // Cache 5 minutes
    }
    
    /**
     * Get monthly usage trends
     */
    public function getMonthlyTrends(int $months = 6): array
    {
        $stmt = $this->db->prepare("
            SELECT 
                DATE_FORMAT(dispense_date, '%Y-%m') as month,
                COUNT(*) as total_dispensing,
                SUM(di.quantity) as total_quantity
            FROM dispensing d
            JOIN dispensing_items di ON d.id = di.dispense_id
            WHERE dispense_date >= DATE_SUB(NOW(), INTERVAL :months MONTH)
            GROUP BY DATE_FORMAT(dispense_date, '%Y-%m')
            ORDER BY month ASC
        ");
        
        $stmt->execute(['months' => $months]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get top 10 most used drugs
     */
    public function getTopUsedDrugs(int $limit = 10): array
    {
        return $this->cache->remember('top_used_drugs', function() use ($limit) {
            $stmt = $this->db->prepare("
                SELECT 
                    dr.id,
                    dr.code,
                    dr.name,
                    SUM(di.quantity) as total_used,
                    dr.unit
                FROM dispensing_items di
                JOIN drugs dr ON di.drug_id = dr.id
                WHERE di.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY dr.id
                ORDER BY total_used DESC
                LIMIT :limit
            ");
            
            $stmt->execute(['limit' => $limit]);
            return $stmt->fetchAll();
        }, 3600); // Cache 1 hour
    }
    
    /**
     * Get ABC analysis summary
     */
    public function getABCAnalysis(): array
    {
        return $this->cache->remember('abc_analysis', function() {
            $stmt = $this->db->query("
                SELECT 
                    abc_class,
                    COUNT(*) as count,
                    SUM(planned_budget) as total_budget
                FROM purchasing_plans
                WHERE fiscal_year = YEAR(NOW()) + 543
                GROUP BY abc_class
                ORDER BY abc_class
            ");
            
            return $stmt->fetchAll();
        }, 3600);
    }
    
    /**
     * Get inventory value by category
     */
    public function getInventoryValueByCategory(): array
    {
        $stmt = $this->db->query("
            SELECT 
                COALESCE(dr.category, 'อื่นๆ') as category,
                COUNT(DISTINCT dr.id) as drug_count,
                SUM(i.quantity * dr.price) as total_value
            FROM inventory i
            JOIN drugs dr ON i.drug_id = dr.id
            WHERE i.quantity > 0
            GROUP BY dr.category
            ORDER BY total_value DESC
        ");
        
        return $stmt->fetchAll();
    }
    
    /**
     * Get expiring drugs alert
     */
    public function getExpiringDrugsAlert(int $days = 30): array
    {
        $stmt = $this->db->prepare("
            SELECT 
                dr.code,
                dr.name,
                i.lot_no,
                i.expire_date,
                i.quantity,
                dr.unit,
                DATEDIFF(i.expire_date, NOW()) as days_until_expiry
            FROM inventory i
            JOIN drugs dr ON i.drug_id = dr.id
            WHERE i.expire_date <= DATE_ADD(NOW(), INTERVAL :days DAY)
                AND i.expire_date > NOW()
                AND i.quantity > 0
            ORDER BY i.expire_date ASC
            LIMIT 20
        ");
        
        $stmt->execute(['days' => $days]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get low stock alerts
     */
    public function getLowStockAlerts(): array
    {
        $stmt = $this->db->query("
            SELECT 
                dr.code,
                dr.name,
                COALESCE(SUM(i.quantity), 0) as current_stock,
                dr.min_level,
                dr.unit,
                (dr.min_level - COALESCE(SUM(i.quantity), 0)) as shortage
            FROM drugs dr
            LEFT JOIN inventory i ON dr.id = i.drug_id
            WHERE dr.is_active = 1
            GROUP BY dr.id
            HAVING current_stock < dr.min_level
            ORDER BY shortage DESC
            LIMIT 20
        ");
        
        return $stmt->fetchAll();
    }
    
    /**
     * Get recent activities
     */
    public function getRecentActivities(int $limit = 10): array
    {
        $stmt = $this->db->prepare("
            SELECT 
                al.action,
                al.table_name,
                al.created_at,
                u.full_name as user_name,
                al.ip_address
            FROM audit_logs al
            LEFT JOIN users u ON al.user_id = u.id
            ORDER BY al.created_at DESC
            LIMIT :limit
        ");
        
        $stmt->execute(['limit' => $limit]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get system health metrics
     */
    public function getSystemHealth(): array
    {
        return [
            'database' => $this->checkDatabaseHealth(),
            'cache' => $this->checkCacheHealth(),
            'storage' => $this->checkStorageHealth(),
        ];
    }
    
    /**
     * Private helper methods
     */
    private function getTotalDrugs(): int
    {
        $stmt = $this->db->query("SELECT COUNT(*) FROM drugs WHERE is_active = 1");
        return (int) $stmt->fetchColumn();
    }
    
    private function getTotalInventoryValue(): float
    {
        $stmt = $this->db->query("
            SELECT SUM(i.quantity * dr.price) 
            FROM inventory i 
            JOIN drugs dr ON i.drug_id = dr.id
        ");
        return (float) $stmt->fetchColumn();
    }
    
    private function getExpiringSoonCount(): int
    {
        $stmt = $this->db->query("
            SELECT COUNT(*) 
            FROM inventory 
            WHERE expire_date <= DATE_ADD(NOW(), INTERVAL 30 DAY)
                AND expire_date > NOW()
                AND quantity > 0
        ");
        return (int) $stmt->fetchColumn();
    }
    
    private function getLowStockCount(): int
    {
        $stmt = $this->db->query("
            SELECT COUNT(*) FROM (
                SELECT dr.id
                FROM drugs dr
                LEFT JOIN inventory i ON dr.id = i.drug_id
                WHERE dr.is_active = 1
                GROUP BY dr.id
                HAVING COALESCE(SUM(i.quantity), 0) < dr.min_level
            ) as low_stock
        ");
        return (int) $stmt->fetchColumn();
    }
    
    private function getPendingOrdersCount(): int
    {
        $stmt = $this->db->query("
            SELECT COUNT(*) 
            FROM orders 
            WHERE status IN ('draft', 'pending')
        ");
        return (int) $stmt->fetchColumn();
    }
    
    private function getTodayDispensingCount(): int
    {
        $stmt = $this->db->query("
            SELECT COUNT(*) 
            FROM dispensing 
            WHERE DATE(dispense_date) = CURDATE()
        ");
        return (int) $stmt->fetchColumn();
    }
    
    private function checkDatabaseHealth(): array
    {
        try {
            $this->db->query("SELECT 1");
            return ['status' => 'healthy', 'message' => 'Connected'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    private function checkCacheHealth(): array
    {
        $stats = $this->cache->getStats();
        return [
            'status' => 'healthy',
            'driver' => $stats['driver'],
            'keys' => $stats['keys'] ?? 0,
        ];
    }
    
    private function checkStorageHealth(): array
    {
        $free = disk_free_space('/');
        $total = disk_total_space('/');
        $used_percent = (($total - $free) / $total) * 100;
        
        return [
            'status' => $used_percent < 90 ? 'healthy' : 'warning',
            'used_percent' => round($used_percent, 2),
            'free_space' => $this->formatBytes($free),
        ];
    }
    
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
