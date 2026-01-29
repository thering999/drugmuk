<?php

namespace App\Services\AI;

use App\Core\Database;
use PDO;

/**
 * Forecasting Engine (Phase 6)
 * 
 * Orchestrates multiple forecasting algorithms (EMA, Hybrid ML)
 */
class ForecastingEngine
{
    private $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Get Forecast using the best available model
     */
    public function predict($drugId, $targetDate = null)
    {
        if (!$targetDate) $targetDate = date('Y-m-01', strtotime('+1 month'));

        // 1. Check if we have a pre-calculated ML result (e.g. from Python script/Prophet)
        $mlForecast = $this->getMLForecast($drugId, $targetDate);
        if ($mlForecast) {
            return [
                'value' => $mlForecast['forecast_quantity'],
                'method' => $mlForecast['calculation_method'],
                'confidence' => $mlForecast['confidence_score'] ?? 0.8
            ];
        }

        // 2. Fallback to enhanced EMA with seasonal factors
        return [
            'value' => $this->calculateEnhancedEMA($drugId),
            'method' => 'ENHANCED_EMA',
            'confidence' => 0.6
        ];
    }

    private function getMLForecast($drugId, $date)
    {
        $stmt = $this->db->prepare("
            SELECT * FROM analytics_demand_forecast 
            WHERE drug_id = ? AND forecast_date = ? AND calculation_method IN ('PROPHET', 'LSTM')
            ORDER BY created_at DESC LIMIT 1
        ");
        $stmt->execute([$drugId, $date]);
        return $stmt->fetch();
    }

    private function calculateEnhancedEMA($drugId)
    {
        // Fetch historical monthly usage (last 24 months for better seasonality)
        $sql = "
            SELECT DATE_FORMAT(dispense_date, '%Y-%m') as month, SUM(quantity) as qty
            FROM dispensing_items di
            JOIN dispensing d ON di.dispensing_id = d.id
            WHERE di.drug_id = ? AND d.dispense_date >= DATE_SUB(NOW(), INTERVAL 24 MONTH)
            GROUP BY month ORDER BY month ASC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$drugId]);
        $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($history) < 3) return 0;

        $data = array_column($history, 'qty');
        
        // Simple EMA calculation
        $alpha = 0.4;
        $ema = $data[0];
        foreach ($data as $val) {
            $ema = ($alpha * $val) + ((1 - $alpha) * $ema);
        }

        // Apply Seasonality (Month-specific factor)
        $nextMonth = (int)date('m', strtotime('+1 month'));
        $stmt = $this->db->prepare("SELECT usage_factor FROM analytics_seasonal_patterns WHERE drug_id = ? AND month_index = ?");
        $stmt->execute([$drugId, $nextMonth]);
        $factor = $stmt->fetchColumn() ?: 1.0;

        return $ema * $factor;
    }

    /**
     * Export data for Python ML training
     */
    public function exportTrainingData($drugId)
    {
        $sql = "
            SELECT d.dispense_date as ds, di.quantity as y
            FROM dispensing_items di
            JOIN dispensing d ON di.dispensing_id = d.id
            WHERE di.drug_id = ?
            ORDER BY d.dispense_date ASC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$drugId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
