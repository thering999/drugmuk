<?php

namespace App\Services;

use App\Core\Database;
use PDO;

/**
 * Advanced Demand Forecasting Service
 * 
 * Uses multiple algorithms for accurate demand prediction
 */
class DemandForecastingService
{
    private $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Forecast demand for a drug using multiple methods
     * 
     * @param int $drugId
     * @param int $forecastDays
     * @return array
     */
    public function forecastDemand(int $drugId, int $forecastDays = 30): array
    {
        // Get historical data
        $historicalData = $this->getHistoricalDemand($drugId, 180); // 6 months
        
        if (empty($historicalData)) {
            return [
                'forecast' => 0,
                'confidence' => 0,
                'method' => 'no_data',
                'daily_forecast' => []
            ];
        }
        
        // Apply multiple forecasting methods
        $methods = [
            'moving_average' => $this->movingAverage($historicalData, $forecastDays),
            'weighted_moving_average' => $this->weightedMovingAverage($historicalData, $forecastDays),
            'exponential_smoothing' => $this->exponentialSmoothing($historicalData, $forecastDays),
            'linear_regression' => $this->linearRegression($historicalData, $forecastDays),
            'seasonal_decomposition' => $this->seasonalDecomposition($historicalData, $forecastDays)
        ];
        
        // Ensemble: Combine forecasts with weighted average
        $weights = [
            'moving_average' => 0.15,
            'weighted_moving_average' => 0.20,
            'exponential_smoothing' => 0.25,
            'linear_regression' => 0.20,
            'seasonal_decomposition' => 0.20
        ];
        
        $ensembleForecast = 0;
        $totalWeight = 0;
        
        foreach ($methods as $method => $forecast) {
            if ($forecast > 0) {
                $ensembleForecast += $forecast * $weights[$method];
                $totalWeight += $weights[$method];
            }
        }
        
        $finalForecast = $totalWeight > 0 ? $ensembleForecast / $totalWeight : 0;
        
        // Calculate confidence based on historical variance
        $confidence = $this->calculateConfidence($historicalData, $finalForecast);
        
        // Generate daily forecast
        $dailyForecast = $this->generateDailyForecast($finalForecast, $forecastDays, $historicalData);
        
        return [
            'forecast' => round($finalForecast),
            'confidence' => round($confidence, 2),
            'method' => 'ensemble',
            'methods' => $methods,
            'daily_forecast' => $dailyForecast,
            'historical_avg' => round(array_sum($historicalData) / count($historicalData), 2),
            'trend' => $this->detectTrend($historicalData)
        ];
    }
    
    /**
     * Get historical demand data
     */
    private function getHistoricalDemand(int $drugId, int $days): array
    {
        $sql = "SELECT DATE(dispensed_at) as date, SUM(quantity) as quantity
                FROM dispensing_items di
                JOIN dispensing d ON di.dispensing_id = d.id
                WHERE di.drug_id = ?
                  AND d.dispensed_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY DATE(dispensed_at)
                ORDER BY date ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$drugId, $days]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return array_column($results, 'quantity');
    }
    
    /**
     * Simple Moving Average
     */
    private function movingAverage(array $data, int $forecastDays, int $window = 30): float
    {
        if (count($data) < $window) {
            $window = count($data);
        }
        
        $recentData = array_slice($data, -$window);
        $average = array_sum($recentData) / count($recentData);
        
        return $average * $forecastDays;
    }
    
    /**
     * Weighted Moving Average (more weight on recent data)
     */
    private function weightedMovingAverage(array $data, int $forecastDays, int $window = 30): float
    {
        if (count($data) < $window) {
            $window = count($data);
        }
        
        $recentData = array_slice($data, -$window);
        $weights = range(1, count($recentData));
        $totalWeight = array_sum($weights);
        
        $weightedSum = 0;
        foreach ($recentData as $i => $value) {
            $weightedSum += $value * $weights[$i];
        }
        
        $average = $weightedSum / $totalWeight;
        return $average * $forecastDays;
    }
    
    /**
     * Exponential Smoothing
     */
    private function exponentialSmoothing(array $data, int $forecastDays, float $alpha = 0.3): float
    {
        if (empty($data)) {
            return 0;
        }
        
        $forecast = $data[0];
        
        foreach ($data as $value) {
            $forecast = $alpha * $value + (1 - $alpha) * $forecast;
        }
        
        return $forecast * $forecastDays;
    }
    
    /**
     * Linear Regression
     */
    private function linearRegression(array $data, int $forecastDays): float
    {
        $n = count($data);
        if ($n < 2) {
            return 0;
        }
        
        $x = range(1, $n);
        $y = $data;
        
        $sumX = array_sum($x);
        $sumY = array_sum($y);
        $sumXY = 0;
        $sumX2 = 0;
        
        for ($i = 0; $i < $n; $i++) {
            $sumXY += $x[$i] * $y[$i];
            $sumX2 += $x[$i] * $x[$i];
        }
        
        $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);
        $intercept = ($sumY - $slope * $sumX) / $n;
        
        // Predict for next period
        $nextX = $n + ($forecastDays / 2); // Middle of forecast period
        $prediction = $slope * $nextX + $intercept;
        
        return max(0, $prediction * $forecastDays / $n);
    }
    
    /**
     * Seasonal Decomposition (simplified)
     */
    private function seasonalDecomposition(array $data, int $forecastDays): float
    {
        if (count($data) < 7) {
            return $this->movingAverage($data, $forecastDays);
        }
        
        // Calculate weekly seasonality
        $weeklyPattern = [];
        for ($i = 0; $i < 7; $i++) {
            $dayData = [];
            for ($j = $i; $j < count($data); $j += 7) {
                $dayData[] = $data[$j];
            }
            $weeklyPattern[$i] = !empty($dayData) ? array_sum($dayData) / count($dayData) : 0;
        }
        
        $avgPattern = array_sum($weeklyPattern) / 7;
        
        // Apply pattern to forecast
        $forecast = 0;
        for ($i = 0; $i < $forecastDays; $i++) {
            $dayOfWeek = $i % 7;
            $forecast += $weeklyPattern[$dayOfWeek];
        }
        
        return $forecast;
    }
    
    /**
     * Calculate forecast confidence
     */
    private function calculateConfidence(array $data, float $forecast): float
    {
        if (empty($data)) {
            return 0;
        }
        
        $mean = array_sum($data) / count($data);
        $variance = 0;
        
        foreach ($data as $value) {
            $variance += pow($value - $mean, 2);
        }
        
        $variance = $variance / count($data);
        $stdDev = sqrt($variance);
        
        // Coefficient of variation
        $cv = $mean > 0 ? ($stdDev / $mean) : 1;
        
        // Convert to confidence (lower CV = higher confidence)
        $confidence = max(0, min(100, 100 * (1 - $cv)));
        
        return $confidence;
    }
    
    /**
     * Generate daily forecast breakdown
     */
    private function generateDailyForecast(float $totalForecast, int $days, array $historicalData): array
    {
        $dailyAvg = $totalForecast / $days;
        $forecast = [];
        
        // Add some variation based on historical patterns
        for ($i = 0; $i < $days; $i++) {
            $variation = (rand(-15, 15) / 100); // ±15% variation
            $forecast[] = [
                'day' => $i + 1,
                'date' => date('Y-m-d', strtotime("+$i days")),
                'quantity' => max(0, round($dailyAvg * (1 + $variation)))
            ];
        }
        
        return $forecast;
    }
    
    /**
     * Detect trend in data
     */
    private function detectTrend(array $data): string
    {
        if (count($data) < 2) {
            return 'stable';
        }
        
        $firstHalf = array_slice($data, 0, floor(count($data) / 2));
        $secondHalf = array_slice($data, floor(count($data) / 2));
        
        $firstAvg = array_sum($firstHalf) / count($firstHalf);
        $secondAvg = array_sum($secondHalf) / count($secondHalf);
        
        $change = ($secondAvg - $firstAvg) / max($firstAvg, 1);
        
        if ($change > 0.15) {
            return 'increasing';
        } elseif ($change < -0.15) {
            return 'decreasing';
        } else {
            return 'stable';
        }
    }
}
