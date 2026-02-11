<?php

namespace App\Controllers;

use App\Core\Database;
use PDO;

/**
 * Analytics Controller
 * Advanced analytics and reporting dashboard
 * 
 * @package Drugmuk
 * @version 3.6.0
 */
class AnalyticsController
{
    private $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Analytics Dashboard Page
     */
    public function index()
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
        
        require_once __DIR__ . '/../Views/analytics/dashboard.php';
    }
    
    /**
     * GET /api/analytics/dashboard
     * Get dashboard analytics data
     */
    public function getDashboardData()
    {
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }
        
        try {
            $period = $_GET['period'] ?? 'week';
            $dateRange = $this->getDateRange($period);
            
            $data = [
                'success' => true,
                'kpis' => $this->getKPIs($dateRange),
                'charts' => $this->getChartsData($dateRange),
                'tables' => $this->getTablesData($dateRange)
            ];
            
            echo json_encode($data);
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        
        exit;
    }

    /**
     * Export Analytics Report
     */
    public function exportReport()
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $period = $_GET['period'] ?? 'week';
        $dateRange = $this->getDateRange($period);

        // Get Data
        $kpis = $this->getKPIs($dateRange);
        $drugSummary = $this->getDrugSummary($dateRange);

        // Set Headers for CSV Download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=analytics_report_' . $period . '_' . date('Y-m-d') . '.csv');

        // Create File Pointer
        $output = fopen('php://output', 'w');
        
        // Add BOM for Excel UTF-8 compatibility
        fwrite($output, "\xEF\xBB\xBF");

        // 1. Report Header
        fputcsv($output, ['Drugmuk Analytics Report']);
        fputcsv($output, ['Period', ucfirst($period) . ' (' . $dateRange['start'] . ' to ' . $dateRange['end'] . ')']);
        fputcsv($output, ['Generated At', date('Y-m-d H:i:s')]);
        fputcsv($output, []); // Empty line

        // 2. KPI Summary
        fputcsv($output, ['KPI Metrics']);
        fputcsv($output, ['Metric', 'Value', 'Change %']);
        fputcsv($output, ['Dispensing Count', $kpis['dispensing']['value'], $kpis['dispensing']['change'] . '%']);
        fputcsv($output, ['Inventory Value', number_format($kpis['inventory']['value'], 2), $kpis['inventory']['change'] . '%']);
        fputcsv($output, ['Orders Count', $kpis['orders']['value'], $kpis['orders']['change'] . '%']);
        fputcsv($output, ['Low Stock Items', $kpis['lowStock']['value'], $kpis['lowStock']['change'] . '%']);
        fputcsv($output, []); // Empty line

        // 3. Drug Summary Details
        fputcsv($output, ['Drug Summary Details']);
        fputcsv($output, ['Drug Name', 'Dispensed Quantity', 'Total Value (Baht)', 'Current Stock', 'Status']);

        foreach ($drugSummary as $row) {
            fputcsv($output, [
                $row['name'],
                $row['dispensed'],
                number_format($row['value'], 2),
                $row['stock'],
                $row['statusText']
            ]);
        }

        fclose($output);
        exit;
    }
    
    /**
     * Get KPI metrics
     */
    private function getKPIs($dateRange)
    {
        // Dispensing KPI
        $dispensingCurrent = $this->getDispensingCount($dateRange['start'], $dateRange['end']);
        $dispensingPrevious = $this->getDispensingCount($dateRange['prev_start'], $dateRange['prev_end']);
        $dispensingChange = $this->calculateChange($dispensingCurrent, $dispensingPrevious);
        
        // Inventory Value KPI
        // Note: Can't compare with previous period as inventory table has no timestamps
        $inventoryValue = $this->getInventoryValue();
        $inventoryChange = 0; // No historical data available
        
        // Orders KPI
        $ordersCurrent = $this->getOrdersCount($dateRange['start'], $dateRange['end']);
        $ordersPrevious = $this->getOrdersCount($dateRange['prev_start'], $dateRange['prev_end']);
        $ordersChange = $this->calculateChange($ordersCurrent, $ordersPrevious);
        
        // Low Stock KPI
        // Note: Can't compare with previous period as inventory table has no timestamps
        $lowStockCurrent = $this->getLowStockCount();
        $lowStockChange = 0; // No historical data available
        
        return [
            'dispensing' => [
                'value' => $dispensingCurrent,
                'change' => $dispensingChange
            ],
            'inventory' => [
                'value' => $inventoryValue,
                'change' => $inventoryChange
            ],
            'orders' => [
                'value' => $ordersCurrent,
                'change' => $ordersChange
            ],
            'lowStock' => [
                'value' => $lowStockCurrent,
                'change' => $lowStockChange
            ]
        ];
    }
    
    /**
     * Get charts data
     */
    private function getChartsData($dateRange)
    {
        return [
            'dispensingTrend' => $this->getDispensingTrend($dateRange),
            'topDrugs' => $this->getTopDrugs($dateRange),
            'inventoryValue' => $this->getInventoryByCategory(),
            'expiringDrugs' => $this->getExpiringDrugsChart()
        ];
    }
    
    /**
     * Get tables data
     */
    private function getTablesData($dateRange)
    {
        return [
            'drugSummary' => $this->getDrugSummary($dateRange)
        ];
    }
    
    /**
     * Get dispensing trend data
     */
    private function getDispensingTrend($dateRange)
    {
        $days = $this->getDaysBetween($dateRange['start'], $dateRange['end']);
        $labels = [];
        $data = [];
        
        foreach ($days as $day) {
            $labels[] = date('d/m', strtotime($day));
            
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as count
                FROM dispensing
                WHERE DATE(dispense_date) = ?
            ");
            $stmt->execute([$day]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $data[] = (int)$result['count'];
        }
        
        return [
            'labels' => $labels,
            'data' => $data
        ];
    }
    
    /**
     * Get top drugs
     */
    private function getTopDrugs($dateRange)
    {
        $stmt = $this->db->prepare("
            SELECT 
                d.name,
                SUM(di.quantity) as total_quantity
            FROM dispensing_items di
            INNER JOIN dispensing disp ON di.dispense_id = disp.id
            INNER JOIN drugs d ON di.drug_id = d.id
            WHERE disp.dispense_date BETWEEN ? AND ?
            GROUP BY d.id, d.name
            ORDER BY total_quantity DESC
            LIMIT 10
        ");
        $stmt->execute([$dateRange['start'], $dateRange['end']]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $labels = [];
        $data = [];
        
        foreach ($results as $row) {
            $labels[] = $row['name'];
            $data[] = (float)$row['total_quantity'];
        }
        
        return [
            'labels' => $labels,
            'data' => $data
        ];
    }
    
    /**
     * Get inventory value by category
     */
    private function getInventoryByCategory()
    {
        $stmt = $this->db->query("
            SELECT 
                COALESCE(d.category, 'อื่นๆ') as category,
                SUM(i.quantity * i.cost_price) as total_value
            FROM inventory i
            INNER JOIN drugs d ON i.drug_id = d.id
            WHERE i.quantity > 0
            GROUP BY d.category
            ORDER BY total_value DESC
            LIMIT 5
        ");
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $labels = [];
        $data = [];
        
        foreach ($results as $row) {
            $labels[] = $row['category'];
            $data[] = (float)$row['total_value'];
        }
        
        return [
            'labels' => $labels,
            'data' => $data
        ];
    }
    
    /**
     * Get expiring drugs chart
     */
    private function getExpiringDrugsChart()
    {
        $labels = ['หมดอายุแล้ว', 'ใน 30 วัน', 'ใน 90 วัน'];
        $data = [];
        
        // Expired
        $stmt = $this->db->query("
            SELECT COUNT(*) as count
            FROM inventory
            WHERE expire_date < CURDATE()
            AND quantity > 0
        ");
        $data[] = (int)$stmt->fetchColumn();
        
        // Expiring in 30 days
        $stmt = $this->db->query("
            SELECT COUNT(*) as count
            FROM inventory
            WHERE expire_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
            AND quantity > 0
        ");
        $data[] = (int)$stmt->fetchColumn();
        
        // Expiring in 90 days
        $stmt = $this->db->query("
            SELECT COUNT(*) as count
            FROM inventory
            WHERE expire_date BETWEEN DATE_ADD(CURDATE(), INTERVAL 31 DAY) AND DATE_ADD(CURDATE(), INTERVAL 90 DAY)
            AND quantity > 0
        ");
        $data[] = (int)$stmt->fetchColumn();
        
        return [
            'labels' => $labels,
            'data' => $data
        ];
    }
    
    /**
     * Get drug summary table
     */
    private function getDrugSummary($dateRange)
    {
        $stmt = $this->db->prepare("
            SELECT 
                d.name,
                COALESCE(SUM(di.quantity), 0) as dispensed,
                COALESCE(SUM(di.quantity * i.cost_price), 0) as value,
                COALESCE(i.quantity, 0) as stock,
                CASE 
                    WHEN i.quantity <= d.min_stock THEN 'low'
                    WHEN i.expire_date < DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 'expiring'
                    ELSE 'normal'
                END as status
            FROM drugs d
            LEFT JOIN inventory i ON d.id = i.drug_id
            LEFT JOIN dispensing_items di ON d.id = di.drug_id
            LEFT JOIN dispensing disp ON di.dispense_id = disp.id 
                AND disp.dispense_date BETWEEN ? AND ?
            GROUP BY d.id, d.name, i.quantity, d.min_stock, i.expire_date
            ORDER BY dispensed DESC
            LIMIT 20
        ");
        $stmt->execute([$dateRange['start'], $dateRange['end']]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $summary = [];
        foreach ($results as $row) {
            $statusText = 'ปกติ';
            if ($row['status'] === 'low') {
                $statusText = 'สต็อกต่ำ';
            } elseif ($row['status'] === 'expiring') {
                $statusText = 'ใกล้หมดอายุ';
            }
            
            $summary[] = [
                'name' => $row['name'],
                'dispensed' => (float)$row['dispensed'],
                'value' => (float)$row['value'],
                'stock' => (float)$row['stock'],
                'status' => $row['status'],
                'statusText' => $statusText
            ];
        }
        
        return $summary;
    }
    
    /**
     * Helper: Get date range based on period
     */
    private function getDateRange($period)
    {
        $end = date('Y-m-d');
        
        switch ($period) {
            case 'today':
                $start = date('Y-m-d');
                $prevStart = date('Y-m-d', strtotime('-1 day'));
                $prevEnd = date('Y-m-d', strtotime('-1 day'));
                break;
            case 'week':
                $start = date('Y-m-d', strtotime('-6 days'));
                $prevStart = date('Y-m-d', strtotime('-13 days'));
                $prevEnd = date('Y-m-d', strtotime('-7 days'));
                break;
            case 'month':
                $start = date('Y-m-d', strtotime('-29 days'));
                $prevStart = date('Y-m-d', strtotime('-59 days'));
                $prevEnd = date('Y-m-d', strtotime('-30 days'));
                break;
            case 'quarter':
                $start = date('Y-m-d', strtotime('-89 days'));
                $prevStart = date('Y-m-d', strtotime('-179 days'));
                $prevEnd = date('Y-m-d', strtotime('-90 days'));
                break;
            case 'year':
                $start = date('Y-m-d', strtotime('-364 days'));
                $prevStart = date('Y-m-d', strtotime('-729 days'));
                $prevEnd = date('Y-m-d', strtotime('-365 days'));
                break;
            default:
                $start = date('Y-m-d', strtotime('-6 days'));
                $prevStart = date('Y-m-d', strtotime('-13 days'));
                $prevEnd = date('Y-m-d', strtotime('-7 days'));
        }
        
        return [
            'start' => $start,
            'end' => $end,
            'prev_start' => $prevStart,
            'prev_end' => $prevEnd
        ];
    }
    
    /**
     * Helper: Get days between dates
     */
    private function getDaysBetween($start, $end)
    {
        $days = [];
        $current = strtotime($start);
        $endTime = strtotime($end);
        
        while ($current <= $endTime) {
            $days[] = date('Y-m-d', $current);
            $current = strtotime('+1 day', $current);
        }
        
        return $days;
    }
    
    /**
     * Helper: Get dispensing count
     */
    private function getDispensingCount($start, $end)
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count
            FROM dispensing
            WHERE dispense_date BETWEEN ? AND ?
        ");
        $stmt->execute([$start, $end]);
        return (int)$stmt->fetchColumn();
    }
    
    /**
     * Helper: Get inventory value
     */
    private function getInventoryValue($asOfDate = null)
    {
        // Note: Inventory table doesn't have timestamp columns
        // So we can't filter by date - just return current value
        $sql = "
            SELECT COALESCE(SUM(quantity * cost_price), 0) as total
            FROM inventory
            WHERE quantity > 0
        ";
        
        $stmt = $this->db->query($sql);
        return (float)$stmt->fetchColumn();
    }
    
    /**
     * Helper: Get orders count
     */
    private function getOrdersCount($start, $end)
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count
            FROM orders
            WHERE order_date BETWEEN ? AND ?
        ");
        $stmt->execute([$start, $end]);
        return (int)$stmt->fetchColumn();
    }
    
    /**
     * Helper: Get low stock count
     */
    private function getLowStockCount($asOfDate = null)
    {
        // Note: Inventory table doesn't have timestamp columns
        // So we can't filter by date - just return current count
        $sql = "
            SELECT COUNT(*) as count
            FROM inventory i
            INNER JOIN drugs d ON i.drug_id = d.id
            WHERE i.quantity <= d.min_stock
            AND i.quantity > 0
        ";
        
        $stmt = $this->db->query($sql);
        return (int)$stmt->fetchColumn();
    }
    
    /**
     * Helper: Calculate percentage change
     */
    private function calculateChange($current, $previous)
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }
        
        return round((($current - $previous) / $previous) * 100, 1);
    }
}
