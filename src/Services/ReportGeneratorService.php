<?php
/**
 * Report Generator Service
 * Generate various reports with charts and export capabilities
 */

namespace App\Services;

class ReportGeneratorService
{
    private $db;
    private $exporter;
    
    public function __construct($db)
    {
        $this->db = $db;
        $this->exporter = new DataExportService();
    }
    
    /**
     * Generate Inventory Summary Report
     */
    public function generateInventorySummary(array $filters = []): array
    {
        $where = ['1=1'];
        $params = [];
        
        if (!empty($filters['category'])) {
            $where[] = 'dr.category = :category';
            $params['category'] = $filters['category'];
        }
        
        $sql = "
            SELECT 
                dr.code,
                dr.name,
                dr.category,
                dr.unit,
                dr.price,
                COALESCE(SUM(i.quantity), 0) as total_quantity,
                COALESCE(SUM(i.quantity * dr.price), 0) as total_value,
                dr.min_level,
                dr.max_level,
                CASE 
                    WHEN COALESCE(SUM(i.quantity), 0) < dr.min_level THEN 'ต่ำ'
                    WHEN COALESCE(SUM(i.quantity), 0) > dr.max_level THEN 'สูง'
                    ELSE 'ปกติ'
                END as stock_status
            FROM drugs dr
            LEFT JOIN inventory i ON dr.id = i.drug_id
            WHERE " . implode(' AND ', $where) . "
            GROUP BY dr.id
            ORDER BY total_value DESC
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Generate Drug Usage Report
     */
    public function generateDrugUsageReport(string $startDate, string $endDate): array
    {
        $stmt = $this->db->prepare("
            SELECT 
                dr.code,
                dr.name,
                dr.unit,
                COUNT(DISTINCT d.id) as dispensing_count,
                SUM(di.quantity) as total_quantity,
                AVG(di.quantity) as avg_quantity_per_dispensing,
                SUM(di.quantity * dr.price) as total_value
            FROM dispensing d
            JOIN dispensing_items di ON d.id = di.dispense_id
            JOIN drugs dr ON di.drug_id = dr.id
            WHERE d.dispense_date BETWEEN :start_date AND :end_date
            GROUP BY dr.id
            ORDER BY total_quantity DESC
        ");
        
        $stmt->execute([
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Generate Expiry Report
     */
    public function generateExpiryReport(int $months = 6): array
    {
        $stmt = $this->db->prepare("
            SELECT 
                dr.code,
                dr.name,
                i.lot_no,
                i.expire_date,
                i.quantity,
                dr.unit,
                dr.price,
                (i.quantity * dr.price) as value,
                DATEDIFF(i.expire_date, NOW()) as days_until_expiry,
                CASE 
                    WHEN i.expire_date < NOW() THEN 'หมดอายุแล้ว'
                    WHEN DATEDIFF(i.expire_date, NOW()) <= 30 THEN 'เร่งด่วน'
                    WHEN DATEDIFF(i.expire_date, NOW()) <= 90 THEN 'ใกล้หมดอายุ'
                    ELSE 'ปกติ'
                END as urgency
            FROM inventory i
            JOIN drugs dr ON i.drug_id = dr.id
            WHERE i.expire_date <= DATE_ADD(NOW(), INTERVAL :months MONTH)
                AND i.quantity > 0
            ORDER BY i.expire_date ASC
        ");
        
        $stmt->execute(['months' => $months]);
        return $stmt->fetchAll();
    }
    
    /**
     * Generate Financial Report
     */
    public function generateFinancialReport(string $startDate, string $endDate): array
    {
        // Orders
        $orders = $this->db->prepare("
            SELECT 
                DATE_FORMAT(order_date, '%Y-%m') as month,
                COUNT(*) as order_count,
                SUM(oi.quantity * oi.unit_price) as total_amount
            FROM orders o
            JOIN order_items oi ON o.id = oi.order_id
            WHERE o.order_date BETWEEN :start_date AND :end_date
            GROUP BY DATE_FORMAT(order_date, '%Y-%m')
            ORDER BY month
        ");
        $orders->execute(['start_date' => $startDate, 'end_date' => $endDate]);
        
        // Dispensing
        $dispensing = $this->db->prepare("
            SELECT 
                DATE_FORMAT(dispense_date, '%Y-%m') as month,
                COUNT(*) as dispensing_count,
                SUM(di.quantity * dr.price) as total_value
            FROM dispensing d
            JOIN dispensing_items di ON d.id = di.dispense_id
            JOIN drugs dr ON di.drug_id = dr.id
            WHERE d.dispense_date BETWEEN :start_date AND :end_date
            GROUP BY DATE_FORMAT(dispense_date, '%Y-%m')
            ORDER BY month
        ");
        $dispensing->execute(['start_date' => $startDate, 'end_date' => $endDate]);
        
        return [
            'orders' => $orders->fetchAll(),
            'dispensing' => $dispensing->fetchAll(),
        ];
    }
    
    /**
     * Generate ABC/VEN Analysis Report
     */
    public function generateABCVENReport(int $fiscalYear): array
    {
        $stmt = $this->db->prepare("
            SELECT 
                dr.code,
                dr.name,
                pp.abc_class,
                pp.ven_class,
                pp.average_usage,
                pp.planned_quantity,
                pp.planned_budget,
                CASE 
                    WHEN pp.abc_class = 'A' AND pp.ven_class = 'V' THEN 'สำคัญมาก'
                    WHEN pp.abc_class = 'A' OR pp.ven_class = 'V' THEN 'สำคัญ'
                    ELSE 'ปกติ'
                END as priority
            FROM purchasing_plans pp
            JOIN drugs dr ON pp.drug_id = dr.id
            WHERE pp.fiscal_year = :fiscal_year
            ORDER BY pp.abc_class, pp.ven_class, pp.planned_budget DESC
        ");
        
        $stmt->execute(['fiscal_year' => $fiscalYear]);
        return $stmt->fetchAll();
    }
    
    /**
     * Generate Supplier Performance Report
     */
    public function generateSupplierReport(string $startDate, string $endDate): array
    {
        $stmt = $this->db->prepare("
            SELECT 
                s.code,
                s.name,
                COUNT(DISTINCT o.id) as order_count,
                SUM(oi.quantity * oi.unit_price) as total_amount,
                AVG(DATEDIFF(or.receive_date, o.order_date)) as avg_delivery_days,
                SUM(CASE WHEN o.status = 'received' THEN 1 ELSE 0 END) as completed_orders
            FROM suppliers s
            JOIN orders o ON s.id = o.supplier_id
            LEFT JOIN order_items oi ON o.id = oi.order_id
            LEFT JOIN order_receives or ON o.id = or.order_id
            WHERE o.order_date BETWEEN :start_date AND :end_date
            GROUP BY s.id
            ORDER BY total_amount DESC
        ");
        
        $stmt->execute([
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Export report to file
     */
    public function exportReport(string $reportType, array $data, string $format = 'excel'): void
    {
        $filename = $reportType . '_' . date('Ymd_His');
        
        switch ($format) {
            case 'csv':
                $this->exporter->downloadCSV($data, $filename . '.csv');
                break;
            case 'json':
                $this->exporter->downloadJSON($data, $filename . '.json');
                break;
            case 'excel':
            default:
                $this->exporter->downloadExcel($data, $filename . '.xls', [], $reportType);
                break;
        }
    }
    
    /**
     * Generate chart data for dashboard
     */
    public function generateChartData(string $chartType, array $params = []): array
    {
        switch ($chartType) {
            case 'monthly_usage':
                return $this->getMonthlyUsageChartData($params['months'] ?? 6);
            
            case 'abc_distribution':
                return $this->getABCDistributionChartData($params['fiscal_year'] ?? date('Y') + 543);
            
            case 'category_value':
                return $this->getCategoryValueChartData();
            
            default:
                return [];
        }
    }
    
    private function getMonthlyUsageChartData(int $months): array
    {
        $stmt = $this->db->prepare("
            SELECT 
                DATE_FORMAT(dispense_date, '%Y-%m') as label,
                SUM(di.quantity) as value
            FROM dispensing d
            JOIN dispensing_items di ON d.id = di.dispense_id
            WHERE dispense_date >= DATE_SUB(NOW(), INTERVAL :months MONTH)
            GROUP BY DATE_FORMAT(dispense_date, '%Y-%m')
            ORDER BY label
        ");
        
        $stmt->execute(['months' => $months]);
        return $stmt->fetchAll();
    }
    
    private function getABCDistributionChartData(int $fiscalYear): array
    {
        $stmt = $this->db->prepare("
            SELECT 
                abc_class as label,
                COUNT(*) as value,
                SUM(planned_budget) as budget
            FROM purchasing_plans
            WHERE fiscal_year = :fiscal_year
            GROUP BY abc_class
            ORDER BY abc_class
        ");
        
        $stmt->execute(['fiscal_year' => $fiscalYear]);
        return $stmt->fetchAll();
    }
    
    private function getCategoryValueChartData(): array
    {
        $stmt = $this->db->query("
            SELECT 
                COALESCE(dr.category, 'อื่นๆ') as label,
                SUM(i.quantity * dr.price) as value
            FROM inventory i
            JOIN drugs dr ON i.drug_id = dr.id
            WHERE i.quantity > 0
            GROUP BY dr.category
            ORDER BY value DESC
            LIMIT 10
        ");
        
        return $stmt->fetchAll();
    }
}
