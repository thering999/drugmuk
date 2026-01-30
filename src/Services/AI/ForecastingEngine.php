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

    public function predict($drugId, $targetDate = null)
    {
        if (!$targetDate) $targetDate = date('Y-m-01', strtotime('+1 month'));

        // 1. Check if we have a pre-calculated ML result
        $mlForecast = $this->getMLForecast($drugId, $targetDate);
        if ($mlForecast) {
            return [
                'value' => $mlForecast['forecast_quantity'],
                'method' => $mlForecast['calculation_method'],
                'confidence' => $mlForecast['confidence_score'] ?? 0.8
            ];
        }

        // 2. If no pre-calculated, try running the Python script if data is sufficient
        $pythonResult = $this->runPythonForecast($drugId);
        if ($pythonResult && $pythonResult['success']) {
            return [
                'value' => $pythonResult['forecast_quantity'],
                'method' => $pythonResult['calculation_method'],
                'confidence' => $pythonResult['confidence_score'] ?? 0.8
            ];
        }

        // 3. Fallback to enhanced EMA with seasonal factors
        return [
            'value' => $this->calculateEnhancedEMA($drugId),
            'method' => 'ENHANCED_EMA',
            'confidence' => 0.6
        ];
    }

    private function runPythonForecast($drugId)
    {
        $data = $this->exportTrainingData($drugId);
        if (count($data) < 5) return null;

        $tmpFile = tempnam(sys_get_temp_dir(), 'forecast_');
        file_put_contents($tmpFile, json_encode($data));

        $scriptPath = __DIR__ . '/../../../scripts/ai/forecast_prophet.py';
        $command = "python3 " . escapeshellarg($scriptPath) . " " . escapeshellarg($tmpFile);
        
        exec($command, $output, $returnCode);
        unlink($tmpFile);

        if ($returnCode === 0 && !empty($output)) {
            $result = json_decode(implode('', $output), true);
            if ($result && isset($result['success']) && $result['success']) {
                // Save to DB for next time
                $this->saveForecast($drugId, $result);
                return $result;
            }
        }
        return null;
    }

    private function saveForecast($drugId, $data)
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO analytics_demand_forecast (drug_id, forecast_date, forecast_quantity, calculation_method, confidence_score)
                VALUES (?, ?, ?, ?, ?)
            ");
            $targetDate = date('Y-m-01', strtotime('+1 month'));
            $stmt->execute([
                $drugId,
                $targetDate,
                $data['forecast_quantity'],
                $data['calculation_method'],
                $data['confidence_score'] ?? 0.8
            ]);
        } catch (\Exception $e) {
            error_log("Failed to save forecast: " . $e->getMessage());
        }
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
            JOIN dispensing d ON di.dispense_id = d.id
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
    private function exportTrainingData($drugId)
    {
        $sql = "
            SELECT d.dispense_date as ds, di.quantity as y
            FROM dispensing_items di
            JOIN dispensing d ON di.dispense_id = d.id
            WHERE di.drug_id = ?
            ORDER BY d.dispense_date ASC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$drugId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Run forecasts for all active drugs
     */
    public function runAllForecasts()
    {
        $stmt = $this->db->query("SELECT id FROM drugs");
        $drugs = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $results = ['total' => count($drugs), 'processed' => 0, 'errors' => 0];
        foreach ($drugs as $drugId) {
            try {
                $this->predict($drugId);
                $results['processed']++;
            } catch (\Exception $e) {
                $results['errors']++;
                error_log("Forecast error for drug $drugId: " . $e->getMessage());
            }
        }
        return $results;
    }
}
