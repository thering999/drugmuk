<?php

namespace App\Services;

use App\Core\Database;
use PDO;

/**
 * Intelligence Service (Phase 2)
 * 
 * Provides predictive analytics, forecasting, and pattern detection
 */
class IntelligenceService
{
    private $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Generate Demand Forecast for a drug
     * Uses Exponential Moving Average (EMA) and Seasonal Factors
     */
    public function calculateDemandForecast($drugId)
    {
        // 1. Get historical usage (local dispensing + JHCIS synced data)
        $history = $this->getHistoricalUsage($drugId, 12); // Last 12 months
        
        if (empty($history)) return 0;
        
        // 2. Calculate EMA (Smoothing)
        $ema = $this->calculateEMA($history);
        
        // 3. Apply Seasonal Factor
        $month = (int)date('m', strtotime('+1 month'));
        $seasonalFactor = $this->getSeasonalFactor($drugId, $month);
        
        $forecast = $ema * $seasonalFactor;
        
        // 4. Save to analytics table
        $stmt = $this->db->prepare("
            INSERT INTO analytics_demand_forecast 
            (drug_id, forecast_date, forecast_quantity, avg_usage_monthly, seasonal_factor, calculation_method)
            VALUES (?, ?, ?, ?, ?, 'EMA')
        ");
        
        $forecastDate = date('Y-m-01', strtotime('+1 month'));
        $stmt->execute([$drugId, $forecastDate, $forecast, $ema, $seasonalFactor]);
        
        return $forecast;
    }
    
    /**
     * Analyze Prescribing Patterns for Doctors
     */
    public function analyzePrescribingPatterns($periodInMonths = 3)
    {
        $startDate = date('Y-m-01', strtotime("-$periodInMonths months"));
        
        // Query from patient_medication_history (synced from JHCIS)
        // Note: Real usage might need doctor data from JHCIS
        $sql = "
            SELECT 
                med.drugname,
                COUNT(*) as total_rx,
                SUM(med.qty) as total_qty,
                AVG(med.qty) as avg_per_rx
            FROM patient_medication_history med
            WHERE med.vstdate >= ?
            GROUP BY med.drugname
            ORDER BY total_rx DESC
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$startDate]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Identify High-Risk Patients
     */
    public function updatePatientRiskScores()
    {
        // Trigger stored procedure for all active chronic patients
        $sql = "
            SELECT DISTINCT hn 
            FROM patient_chronic_diseases 
            WHERE status = 'active'
        ";
        
        $hns = $this->db->query($sql)->fetchAll(PDO::FETCH_COLUMN);
        
        $count = 0;
        foreach ($hns as $hn) {
            $stmt = $this->db->prepare("CALL sp_calculate_patient_risk(?)");
            $stmt->execute([$hn]);
            $count++;
        }
        
        return $count;
    }
    
    /**
     * Get High Risk Summary Statistics
     */
    public function getRiskStatistics()
    {
        return $this->db->query("
            SELECT 
                risk_level, 
                COUNT(*) as count,
                AVG(risk_score) as avg_score
            FROM analytics_patient_risk
            GROUP BY risk_level
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get Antibiotic Usage (RDU)
     */
    public function getAntibioticUsage()
    {
        return $this->db->query("SELECT * FROM v_rdu_antibiotic_usage")->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get High Cost Medications
     */
    public function getHighCostMedications($limit = 10)
    {
        $stmt = $this->db->prepare("SELECT * FROM v_high_cost_medications LIMIT ?");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get Polypharmacy Detection (Patients with 5+ active medications)
     */
    public function getPolypharmacyPatients()
    {
        return $this->db->query("
            SELECT hn, risk_score, risk_level, active_drugs_count 
            FROM analytics_patient_risk 
            WHERE polypharmacy_detected = 1
            ORDER BY active_drugs_count DESC
            LIMIT 20
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Internal Helpers
     */
    
    /**
     * Automatically adjust inventory reorder points based on forecast
     */
    public function autoAdjustInventoryPoints()
    {
        $sql = "SELECT drug_id, forecast_quantity FROM analytics_demand_forecast 
                WHERE forecast_date = DATE_FORMAT(DATE_ADD(NOW(), INTERVAL 1 MONTH), '%Y-%m-01')";
        $forecasts = $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        
        $updated = 0;
        foreach ($forecasts as $f) {
            // Set min_stock to forecast + 20% buffer
            $newMinStock = ceil($f['forecast_quantity'] * 1.2);
            
            $stmt = $this->db->prepare("UPDATE drugs SET min_stock = ? WHERE id = ?");
            $stmt->execute([$newMinStock, $f['drug_id']]);
            $updated++;
        }
        
        return $updated;
    }

    private function getHistoricalUsage($drugId, $months)
    {
        // Fetch from dispensing items + medication history
        $sql = "
            SELECT MONTH(dispense_date) as m, SUM(quantity) as qty
            FROM dispensing_items di
            JOIN dispensing d ON di.dispensing_id = d.id
            WHERE di.drug_id = ? AND d.dispense_date >= DATE_SUB(NOW(), INTERVAL ? MONTH)
            GROUP BY m
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$drugId, $months]);
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }
    
    private function calculateEMA($history, $alpha = 0.3)
    {
        $data = array_values($history);
        $ema = $data[0];
        for ($i = 1; $i < count($data); $i++) {
            $ema = ($alpha * $data[$i]) + ((1 - $alpha) * $ema);
        }
        return $ema;
    }
    
    private function getSeasonalFactor($drugId, $month)
    {
        $stmt = $this->db->prepare("SELECT usage_factor FROM analytics_seasonal_patterns WHERE drug_id = ? AND month_index = ?");
        $stmt->execute([$drugId, $month]);
        return $stmt->fetchColumn() ?: 1.0;
    }

    /**
     * Predictive Out-of-Stock (7 Day outlook)
     */
    public function getPredictiveShortages()
    {
        return $this->db->query("
            SELECT 
                d.id, d.name, d.code,
                COALESCE(SUM(i.quantity), 0) as current_stock,
                COALESCE(f.forecast_quantity, 0) / 30 as avg_daily_usage,
                CASE 
                    WHEN (COALESCE(f.forecast_quantity, 0) / 30) > 0 
                    THEN COALESCE(SUM(i.quantity), 0) / (COALESCE(f.forecast_quantity, 0) / 30)
                    ELSE 999
                END as days_remaining
            FROM drugs d
            LEFT JOIN inventory i ON d.id = i.drug_id
            LEFT JOIN analytics_demand_forecast f ON d.id = f.drug_id 
                AND f.forecast_date = DATE_FORMAT(NOW(), '%Y-%m-01')
            WHERE d.is_active = 1
            GROUP BY d.id
            HAVING days_remaining <= 7
            ORDER BY days_remaining ASC
        ")->fetchAll(PDO::FETCH_ASSOC);
    }
}
