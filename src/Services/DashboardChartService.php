<?php
/**
 * Dashboard Analytics Service
 * สร้างข้อมูลสำหรับ Charts และ Graphs
 * 
 * @package Drugmuk
 * @version 3.4.0
 */

namespace App\Services;

use App\Core\Database;

class DashboardChartService
{
    private \PDO $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * ข้อมูลการจ่ายยา 7 วันย้อนหลัง
     */
    public function getDispensingTrend(int $days = 7): array
    {
        $sql = "SELECT 
                    DATE(dispense_date) as date,
                    COUNT(*) as count,
                    COALESCE(SUM(di.quantity), 0) as total_quantity
                FROM dispensing d
                LEFT JOIN dispensing_items di ON d.id = di.dispensing_id
                WHERE dispense_date >= DATE_SUB(CURDATE(), INTERVAL :days DAY)
                GROUP BY DATE(dispense_date)
                ORDER BY date ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['days' => $days]);
        $results = $stmt->fetchAll();
        
        // Fill missing dates
        $data = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $data[$date] = ['date' => $date, 'count' => 0, 'total_quantity' => 0];
        }
        
        foreach ($results as $row) {
            $data[$row['date']] = $row;
        }
        
        return [
            'labels' => array_map(fn($d) => date('d/m', strtotime($d)), array_keys($data)),
            'counts' => array_column(array_values($data), 'count'),
            'quantities' => array_column(array_values($data), 'total_quantity')
        ];
    }
    
    /**
     * ข้อมูลสต็อกแยกตามหมวดหมู่
     */
    public function getStockByCategory(): array
    {
        $sql = "SELECT 
                    COALESCE(d.category, 'ไม่ระบุ') as category,
                    COUNT(DISTINCT d.id) as drug_count,
                    COALESCE(SUM(i.quantity), 0) as total_quantity,
                    COALESCE(SUM(i.quantity * i.cost_price), 0) as total_value
                FROM drugs d
                LEFT JOIN inventory i ON d.id = i.drug_id
                WHERE d.is_active = 1
                GROUP BY d.category
                ORDER BY total_value DESC";
        
        $results = $this->db->query($sql)->fetchAll();
        
        return [
            'labels' => array_column($results, 'category'),
            'values' => array_column($results, 'total_value'),
            'counts' => array_column($results, 'drug_count')
        ];
    }
    
    /**
     * ข้อมูล ABC Analysis (Pareto)
     */
    public function getABCAnalysis(): array
    {
        $sql = "SELECT 
                    d.name,
                    COALESCE(SUM(di.quantity * di.unit_price), 0) as total_value
                FROM dispensing_items di
                JOIN drugs d ON di.drug_id = d.id
                WHERE di.created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                GROUP BY d.id, d.name
                ORDER BY total_value DESC
                LIMIT 20";
        
        $results = $this->db->query($sql)->fetchAll();
        
        $totalValue = array_sum(array_column($results, 'total_value'));
        $cumulative = 0;
        $cumulativePercentages = [];
        
        foreach ($results as $row) {
            $cumulative += $row['total_value'];
            $cumulativePercentages[] = $totalValue > 0 ? round(($cumulative / $totalValue) * 100, 1) : 0;
        }
        
        return [
            'labels' => array_column($results, 'name'),
            'values' => array_column($results, 'total_value'),
            'cumulative' => $cumulativePercentages
        ];
    }
    
    /**
     * ข้อมูลยาใกล้หมดอายุ แยกตามช่วงเวลา
     */
    public function getExpiringByPeriod(): array
    {
        $periods = [
            ['label' => '0-30 วัน', 'min' => 0, 'max' => 30, 'color' => '#dc3545'],
            ['label' => '31-60 วัน', 'min' => 31, 'max' => 60, 'color' => '#fd7e14'],
            ['label' => '61-90 วัน', 'min' => 61, 'max' => 90, 'color' => '#ffc107'],
            ['label' => '91-180 วัน', 'min' => 91, 'max' => 180, 'color' => '#28a745']
        ];
        
        $data = [];
        foreach ($periods as $period) {
            $sql = "SELECT COUNT(*) as count, COALESCE(SUM(quantity), 0) as quantity
                    FROM inventory
                    WHERE expire_date BETWEEN DATE_ADD(CURDATE(), INTERVAL :min DAY) 
                          AND DATE_ADD(CURDATE(), INTERVAL :max DAY)
                    AND quantity > 0";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['min' => $period['min'], 'max' => $period['max']]);
            $result = $stmt->fetch();
            
            $data[] = [
                'label' => $period['label'],
                'count' => (int)$result['count'],
                'quantity' => (int)$result['quantity'],
                'color' => $period['color']
            ];
        }
        
        return [
            'labels' => array_column($data, 'label'),
            'counts' => array_column($data, 'count'),
            'quantities' => array_column($data, 'quantity'),
            'colors' => array_column($data, 'color')
        ];
    }
    
    /**
     * ข้อมูลการสั่งซื้อ 6 เดือนย้อนหลัง
     */
    public function getOrderTrend(int $months = 6): array
    {
        $sql = "SELECT 
                    DATE_FORMAT(order_date, '%Y-%m') as month,
                    COUNT(*) as order_count,
                    COALESCE(SUM(total_amount), 0) as total_amount
                FROM orders
                WHERE order_date >= DATE_SUB(CURDATE(), INTERVAL :months MONTH)
                GROUP BY DATE_FORMAT(order_date, '%Y-%m')
                ORDER BY month ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['months' => $months]);
        $results = $stmt->fetchAll();
        
        // Fill missing months
        $data = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $month = date('Y-m', strtotime("-$i months"));
            $data[$month] = ['month' => $month, 'order_count' => 0, 'total_amount' => 0];
        }
        
        foreach ($results as $row) {
            $data[$row['month']] = $row;
        }
        
        return [
            'labels' => array_map(fn($m) => date('M Y', strtotime($m . '-01')), array_keys($data)),
            'counts' => array_column(array_values($data), 'order_count'),
            'amounts' => array_column(array_values($data), 'total_amount')
        ];
    }
    
    /**
     * ข้อมูล Top 10 ยาที่ใช้มากที่สุด
     */
    public function getTopDrugs(int $limit = 10): array
    {
        $sql = "SELECT 
                    d.code,
                    d.name,
                    d.unit,
                    COALESCE(SUM(di.quantity), 0) as total_quantity,
                    COUNT(DISTINCT di.dispensing_id) as dispense_count
                FROM dispensing_items di
                JOIN drugs d ON di.drug_id = d.id
                WHERE di.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                GROUP BY d.id, d.code, d.name, d.unit
                ORDER BY total_quantity DESC
                LIMIT :limit";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * ข้อมูลมูลค่าคลัง แยกตาม VEN Class
     */
    public function getStockByVEN(): array
    {
        $sql = "SELECT 
                    COALESCE(d.ven_class, 'N') as ven_class,
                    COUNT(DISTINCT d.id) as drug_count,
                    COALESCE(SUM(i.quantity * i.cost_price), 0) as total_value
                FROM drugs d
                LEFT JOIN inventory i ON d.id = i.drug_id
                WHERE d.is_active = 1
                GROUP BY d.ven_class";
        
        $results = $this->db->query($sql)->fetchAll();
        
        $venLabels = ['V' => 'Vital', 'E' => 'Essential', 'N' => 'Non-essential'];
        $venColors = ['V' => '#dc3545', 'E' => '#ffc107', 'N' => '#28a745'];
        
        $formatted = [];
        foreach ($results as $row) {
            $class = $row['ven_class'] ?: 'N';
            $formatted[] = [
                'class' => $class,
                'label' => $venLabels[$class] ?? 'Other',
                'count' => (int)$row['drug_count'],
                'value' => (float)$row['total_value'],
                'color' => $venColors[$class] ?? '#6c757d'
            ];
        }
        
        return [
            'labels' => array_column($formatted, 'label'),
            'values' => array_column($formatted, 'value'),
            'counts' => array_column($formatted, 'count'),
            'colors' => array_column($formatted, 'color')
        ];
    }
    
    /**
     * สรุปข้อมูลทั้งหมดสำหรับ Dashboard
     */
    public function getDashboardData(): array
    {
        return [
            'dispensing_trend' => $this->getDispensingTrend(7),
            'stock_by_category' => $this->getStockByCategory(),
            'expiring_by_period' => $this->getExpiringByPeriod(),
            'order_trend' => $this->getOrderTrend(6),
            'top_drugs' => $this->getTopDrugs(10),
            'stock_by_ven' => $this->getStockByVEN()
        ];
    }
    
    /**
     * ข้อมูล Real-time Metrics
     */
    public function getRealTimeMetrics(): array
    {
        // Today's dispensing
        $sql1 = "SELECT COUNT(*) as count FROM dispensing WHERE DATE(dispense_date) = CURDATE()";
        $todayDispensing = $this->db->query($sql1)->fetch()['count'] ?? 0;
        
        // Today's receiving
        $sql2 = "SELECT COUNT(*) as count FROM transactions WHERE transaction_type = 'receive' AND DATE(transaction_date) = CURDATE()";
        $todayReceiving = $this->db->query($sql2)->fetch()['count'] ?? 0;
        
        // Pending orders
        $sql3 = "SELECT COUNT(*) as count FROM orders WHERE status IN ('pending', 'approved', 'partial')";
        $pendingOrders = $this->db->query($sql3)->fetch()['count'] ?? 0;
        
        // Low stock count
        $sql4 = "SELECT COUNT(*) as count FROM (
                    SELECT d.id FROM drugs d
                    LEFT JOIN inventory i ON d.id = i.drug_id
                    WHERE d.is_active = 1
                    GROUP BY d.id, d.min_stock
                    HAVING COALESCE(SUM(i.quantity), 0) < d.min_stock
                ) as low_stock";
        $lowStock = $this->db->query($sql4)->fetch()['count'] ?? 0;
        
        // Expiring soon (90 days)
        $sql5 = "SELECT COUNT(*) as count FROM inventory WHERE expire_date <= DATE_ADD(CURDATE(), INTERVAL 90 DAY) AND quantity > 0";
        $expiringSoon = $this->db->query($sql5)->fetch()['count'] ?? 0;
        
        // Total inventory value
        $sql6 = "SELECT COALESCE(SUM(quantity * cost_price), 0) as total FROM inventory WHERE quantity > 0";
        $totalValue = $this->db->query($sql6)->fetch()['total'] ?? 0;
        
        return [
            'today_dispensing' => (int)$todayDispensing,
            'today_receiving' => (int)$todayReceiving,
            'pending_orders' => (int)$pendingOrders,
            'low_stock' => (int)$lowStock,
            'expiring_soon' => (int)$expiringSoon,
            'total_value' => (float)$totalValue,
            'updated_at' => date('Y-m-d H:i:s')
        ];
    }
}
