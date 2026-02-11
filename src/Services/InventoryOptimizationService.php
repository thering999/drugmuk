<?php
/**
 * Inventory Optimization Service
 * AI-powered inventory optimization and forecasting
 */

namespace App\Services;

class InventoryOptimizationService
{
    private $db;
    
    public function __construct($db)
    {
        $this->db = $db;
    }
    
    /**
     * Calculate optimal order quantity (Economic Order Quantity - EOQ)
     */
    public function calculateEOQ(int $drugId): array
    {
        // Get drug data
        $stmt = $this->db->prepare("
            SELECT 
                dr.name,
                dr.price,
                AVG(di.quantity) as avg_daily_usage
            FROM drugs dr
            LEFT JOIN dispensing_items di ON dr.id = di.drug_id
            WHERE dr.id = :drug_id
                AND di.created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)
            GROUP BY dr.id
        ");
        $stmt->execute(['drug_id' => $drugId]);
        $drug = $stmt->fetch();
        
        if (!$drug) {
            return ['error' => 'Drug not found'];
        }
        
        // EOQ Formula: √(2 × D × S / H)
        // D = Annual demand
        // S = Ordering cost per order (assumed 100 baht)
        // H = Holding cost per unit per year (assumed 20% of unit cost)
        
        $annualDemand = $drug['avg_daily_usage'] * 365;
        $orderingCost = 100; // baht per order
        $holdingCostRate = 0.20; // 20% of unit cost
        $holdingCost = $drug['price'] * $holdingCostRate;
        
        $eoq = sqrt((2 * $annualDemand * $orderingCost) / $holdingCost);
        
        // Reorder point = Lead time demand + Safety stock
        $leadTimeDays = 7; // assumed 7 days
        $safetyStockDays = 14; // 2 weeks safety stock
        $reorderPoint = ($drug['avg_daily_usage'] * $leadTimeDays) + 
                       ($drug['avg_daily_usage'] * $safetyStockDays);
        
        return [
            'drug_name' => $drug['name'],
            'avg_daily_usage' => round($drug['avg_daily_usage'], 2),
            'annual_demand' => round($annualDemand, 2),
            'economic_order_quantity' => round($eoq, 0),
            'reorder_point' => round($reorderPoint, 0),
            'orders_per_year' => round($annualDemand / $eoq, 1),
            'days_between_orders' => round(365 / ($annualDemand / $eoq), 0),
        ];
    }
    
    /**
     * Forecast demand using moving average
     */
    public function forecastDemand(int $drugId, int $months = 3): array
    {
        // Get historical data (last 12 months)
        $stmt = $this->db->prepare("
            SELECT 
                DATE_FORMAT(d.dispense_date, '%Y-%m') as month,
                SUM(di.quantity) as total_quantity
            FROM dispensing d
            JOIN dispensing_items di ON d.id = di.dispense_id
            WHERE di.drug_id = :drug_id
                AND d.dispense_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(d.dispense_date, '%Y-%m')
            ORDER BY month ASC
        ");
        $stmt->execute(['drug_id' => $drugId]);
        $historical = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        if (count($historical) < 3) {
            return ['error' => 'Insufficient historical data'];
        }
        
        // Calculate moving average (3-month)
        $quantities = array_column($historical, 'total_quantity');
        $movingAvg = $this->calculateMovingAverage($quantities, 3);
        
        // Forecast next months
        $lastAvg = end($movingAvg);
        $forecast = [];
        
        for ($i = 1; $i <= $months; $i++) {
            $forecastMonth = date('Y-m', strtotime("+{$i} month"));
            $forecast[] = [
                'month' => $forecastMonth,
                'forecast_quantity' => round($lastAvg, 0),
                'confidence' => 'medium'
            ];
        }
        
        // Calculate Standard Deviation of Demand (for Safety Stock)
        $stdDev = $this->calculateStandardDeviation($quantities);
        
        // Calculate Safety Stock (Z-score 1.65 for 95% Service Level * StdDev * Sqrt(LeadTime))
        // Assuming Lead Time = 1 month (or sum of lead time variance)
        $serviceLevelZ = 1.65;
        $leadTimeMonths = 1;
        $safetyStock = $serviceLevelZ * $stdDev * sqrt($leadTimeMonths);
        
        // Calculate Reorder Point = (Avg Demand * Lead Time) + Safety Stock
        $reorderPoint = ($lastAvg * $leadTimeMonths) + $safetyStock; 
        
        return [
            'historical' => $historical,
            'moving_average' => $movingAvg,
            'forecast' => $forecast,
            'metrics' => [
                'avg_monthly_usage' => round($lastAvg, 2),
                'std_dev' => round($stdDev, 2),
                'safety_stock' => round($safetyStock, 2),
                'reorder_point' => round($reorderPoint, 2),
                'service_level' => '95%'
            ]
        ];
    }
    
    /**
     * Private helper: Calculate Standard Deviation
     */
    private function calculateStandardDeviation(array $data): float
    {
        if (count($data) < 2) return 0.0;
        
        $mean = array_sum($data) / count($data);
        $variance = 0.0;
        
        foreach ($data as $val) {
            $variance += pow($val - $mean, 2);
        }
        
        return (float)sqrt($variance / (count($data) - 1));
    }
    
    /**
     * Identify slow-moving and dead stock
     */
    public function identifySlowMovingStock(int $days = 90): array
    {
        $stmt = $this->db->prepare("
            SELECT 
                dr.id,
                dr.code,
                dr.name,
                SUM(i.quantity) as current_stock,
                dr.unit,
                SUM(i.quantity * dr.price) as stock_value,
                COALESCE(usage.total_used, 0) as usage_last_90_days,
                CASE 
                    WHEN COALESCE(usage.total_used, 0) = 0 THEN 'dead'
                    WHEN COALESCE(usage.total_used, 0) < 10 THEN 'very_slow'
                    WHEN COALESCE(usage.total_used, 0) < 50 THEN 'slow'
                    ELSE 'normal'
                END as movement_status
            FROM drugs dr
            LEFT JOIN inventory i ON dr.id = i.drug_id
            LEFT JOIN (
                SELECT 
                    di.drug_id,
                    SUM(di.quantity) as total_used
                FROM dispensing_items di
                JOIN dispensing d ON di.dispense_id = d.id
                WHERE d.dispense_date >= DATE_SUB(NOW(), INTERVAL :days DAY)
                GROUP BY di.drug_id
            ) usage ON dr.id = usage.drug_id
            WHERE dr.is_active = 1
            GROUP BY dr.id
            HAVING current_stock > 0 AND movement_status IN ('dead', 'very_slow', 'slow')
            ORDER BY stock_value DESC
        ");
        
        $stmt->execute(['days' => $days]);
        return $stmt->fetchAll();
    }
    
    /**
     * Suggest stock adjustments
     */
    public function suggestStockAdjustments(): array
    {
        $stmt = $this->db->query("
            SELECT 
                dr.id,
                dr.code,
                dr.name,
                COALESCE(SUM(i.quantity), 0) as current_stock,
                dr.min_level,
                dr.max_level,
                dr.unit,
                CASE 
                    WHEN COALESCE(SUM(i.quantity), 0) < dr.min_level 
                        THEN dr.max_level - COALESCE(SUM(i.quantity), 0)
                    WHEN COALESCE(SUM(i.quantity), 0) > dr.max_level 
                        THEN COALESCE(SUM(i.quantity), 0) - dr.max_level
                    ELSE 0
                END as adjustment_quantity,
                CASE 
                    WHEN COALESCE(SUM(i.quantity), 0) < dr.min_level THEN 'order'
                    WHEN COALESCE(SUM(i.quantity), 0) > dr.max_level THEN 'reduce'
                    ELSE 'ok'
                END as action
            FROM drugs dr
            LEFT JOIN inventory i ON dr.id = i.drug_id
            WHERE dr.is_active = 1
            GROUP BY dr.id
            HAVING action != 'ok'
            ORDER BY adjustment_quantity DESC
            LIMIT 50
        ");
        
        return $stmt->fetchAll();
    }
    
    /**
     * Calculate inventory turnover ratio
     */
    public function calculateTurnoverRatio(int $drugId = null): array
    {
        $where = $drugId ? "WHERE dr.id = :drug_id" : "";
        $params = $drugId ? ['drug_id' => $drugId] : [];
        
        $stmt = $this->db->prepare("
            SELECT 
                dr.id,
                dr.code,
                dr.name,
                AVG(i.quantity * dr.price) as avg_inventory_value,
                COALESCE(SUM(di.quantity * dr.price), 0) as cost_of_goods_sold,
                CASE 
                    WHEN AVG(i.quantity * dr.price) > 0 
                    THEN COALESCE(SUM(di.quantity * dr.price), 0) / AVG(i.quantity * dr.price)
                    ELSE 0
                END as turnover_ratio,
                CASE 
                    WHEN AVG(i.quantity * dr.price) > 0 
                    THEN 365 / (COALESCE(SUM(di.quantity * dr.price), 0) / AVG(i.quantity * dr.price))
                    ELSE 0
                END as days_in_inventory
            FROM drugs dr
            LEFT JOIN inventory i ON dr.id = i.drug_id
            LEFT JOIN (
                SELECT 
                    di.drug_id,
                    di.quantity,
                    dr2.price
                FROM dispensing_items di
                JOIN dispensing d ON di.dispense_id = d.id
                JOIN drugs dr2 ON di.drug_id = dr2.id
                WHERE d.dispense_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            ) di ON dr.id = di.drug_id
            {$where}
            GROUP BY dr.id
            ORDER BY turnover_ratio DESC
        ");
        
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Private helper: Calculate moving average
     */
    private function calculateMovingAverage(array $data, int $period): array
    {
        $result = [];
        $count = count($data);
        
        for ($i = $period - 1; $i < $count; $i++) {
            $sum = 0;
            for ($j = 0; $j < $period; $j++) {
                $sum += $data[$i - $j];
            }
            $result[] = $sum / $period;
        }
        
        return $result;
    }
    
    /**
     * Generate optimization report
     */
    public function generateOptimizationReport(): array
    {
        return [
            'slow_moving' => $this->identifySlowMovingStock(90),
            'stock_adjustments' => $this->suggestStockAdjustments(),
            'turnover_analysis' => $this->calculateTurnoverRatio(),
            'generated_at' => date('Y-m-d H:i:s')
        ];
    }
}
