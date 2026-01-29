<?php

namespace App\Services;

use App\Core\Database;
use App\Services\LineNotificationService;
use PDO;

/**
 * Intelligence Service (Phase 2 Enhanced)
 * 
 * Provides predictive analytics, forecasting, pattern detection
 * with JHCIS Integration
 */
class IntelligenceService
{
    private $db;
    private $lineService;
    private $jhcisDb = null;
    
    private $jhcisError = null;
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->lineService = new LineNotificationService();
        $this->initJHCISConnection();
    }
    
    /**
     * Initialize JHCIS Connection if available
     */
    private function initJHCISConnection()
    {
        try {
            $host = getenv('JHCIS_DB_HOST') ?: 'localhost';
            $port = getenv('JHCIS_DB_PORT') ?: '3306';
            $dbname = getenv('JHCIS_DB_NAME') ?: 'jhcisdb';
            $user = getenv('JHCIS_DB_USER') ?: 'root';
            $pass = getenv('JHCIS_DB_PASS') ?: '';

            // Try to load from jhcis_config.json if exists
            $configFile = __DIR__ . '/../../config/jhcis_config.json';
            if (file_exists($configFile)) {
                $config = json_decode(file_get_contents($configFile), true);
                if ($config) {
                    $host = $config['host'] ?? $host;
                    $port = $config['port'] ?? $port;
                    $dbname = $config['dbname'] ?? $dbname;
                    $user = $config['user'] ?? $user;
                    $pass = $config['pass'] ?? $pass;
                }
            }

            $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
            $this->jhcisDb = new \PDO($dsn, $user, $pass);
            $this->jhcisDb->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        } catch (\Exception $e) {
            $this->jhcisDb = null;
            $this->jhcisError = $e->getMessage();
        }
    }

    public function getJHCISSummary()
    {
        if (!$this->jhcisDb) {
            return [
                'connected' => false, 
                'error' => $this->jhcisError ?: 'Unknown connection error'
            ];
        }
        try {
            $summary = ['connected' => true, 'patients_today' => 0, 'dispensing_today' => 0, 'top_diagnoses' => [], 'top_drugs' => []];
            $summary['patients_today'] = (int)$this->jhcisDb->query("SELECT COUNT(DISTINCT hn) FROM ovst WHERE vstdate = CURDATE()")->fetchColumn();
            $summary['dispensing_today'] = (int)$this->jhcisDb->query("SELECT COUNT(*) FROM opitemrece WHERE vstdate = CURDATE()")->fetchColumn();
            $summary['top_diagnoses'] = $this->jhcisDb->query("SELECT icd10, COUNT(*) as cnt FROM ovstdiag WHERE vstdate = CURDATE() GROUP BY icd10 ORDER BY cnt DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
            $summary['top_drugs'] = $this->jhcisDb->query("SELECT drugname, SUM(qty) as total_qty FROM opitemrece WHERE vstdate = CURDATE() GROUP BY drugname ORDER BY total_qty DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
            return $summary;
        } catch (\Exception $e) {
            return ['connected' => true, 'error' => $e->getMessage()];
        }
    }    
    /**
     * Generate Demand Forecast for a drug
     */
    public function calculateDemandForecast($drugId)
    {
        $history = $this->getHistoricalUsage($drugId, 12);
        if (empty($history)) return 0;
        
        $ema = $this->calculateEMA($history);
        $month = (int)date('m', strtotime('+1 month'));
        $seasonalFactor = $this->getSeasonalFactor($drugId, $month);
        $forecast = $ema * $seasonalFactor;
        
        try {
            $stmt = $this->db->prepare("
                INSERT INTO analytics_demand_forecast 
                (drug_id, forecast_date, forecast_quantity, avg_usage_monthly, seasonal_factor, calculation_method)
                VALUES (?, ?, ?, ?, ?, 'EMA')
                ON DUPLICATE KEY UPDATE forecast_quantity = VALUES(forecast_quantity)
            ");
            $forecastDate = date('Y-m-01', strtotime('+1 month'));
            $stmt->execute([$drugId, $forecastDate, $forecast, $ema, $seasonalFactor]);
        } catch (\Exception $e) {}
        
        return $forecast;
    }
    
    /**
     * Analyze Prescribing Patterns
     */
    public function analyzePrescribingPatterns($periodInMonths = 3)
    {
        try {
            $startDate = date('Y-m-01', strtotime("-$periodInMonths months"));
            $stmt = $this->db->prepare("
                SELECT drugname, COUNT(*) as total_rx, SUM(qty) as total_qty, AVG(qty) as avg_per_rx
                FROM patient_medication_history WHERE vstdate >= ?
                GROUP BY drugname ORDER BY total_rx DESC
            ");
            $stmt->execute([$startDate]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            return [];
        }
    }
    
    /**
     * Update Patient Risk Scores
     */
    public function updatePatientRiskScores()
    {
        try {
            $hns = $this->db->query("SELECT DISTINCT hn FROM patient_chronic_diseases WHERE status = 'active'")->fetchAll(PDO::FETCH_COLUMN);
            $count = 0;
            foreach ($hns as $hn) {
                try {
                    $this->db->prepare("CALL sp_calculate_patient_risk(?)")->execute([$hn]);
                    $count++;
                } catch (\Exception $e) {}
            }
            return $count;
        } catch (\Exception $e) {
            return 0;
        }
    }
    
    public function getRiskStatistics()
    {
        try {
            return $this->db->query("
                SELECT risk_level, COUNT(*) as count, AVG(risk_score) as avg_score
                FROM analytics_patient_risk GROUP BY risk_level
            ")->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            return [];
        }
    }

    public function getAntibioticUsage()
    {
        try {
            return $this->db->query("SELECT * FROM v_rdu_antibiotic_usage")->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            return [];
        }
    }

    public function getHighCostMedications($limit = 10)
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM v_high_cost_medications LIMIT ?");
            $stmt->bindValue(1, $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            return [];
        }
    }

    public function getPolypharmacyPatients()
    {
        try {
            return $this->db->query("
                SELECT hn, risk_score, risk_level, active_drugs_count 
                FROM analytics_patient_risk WHERE polypharmacy_detected = 1
                ORDER BY active_drugs_count DESC LIMIT 20
            ")->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            return [];
        }
    }

    public function autoAdjustInventoryPoints()
    {
        try {
            $forecasts = $this->db->query("
                SELECT drug_id, forecast_quantity FROM analytics_demand_forecast 
                WHERE forecast_date = DATE_FORMAT(DATE_ADD(NOW(), INTERVAL 1 MONTH), '%Y-%m-01')
            ")->fetchAll(PDO::FETCH_ASSOC);
            
            $updated = 0;
            foreach ($forecasts as $f) {
                $newMinStock = ceil($f['forecast_quantity'] * 1.2);
                $this->db->prepare("UPDATE drugs SET min_stock = ? WHERE id = ?")->execute([$newMinStock, $f['drug_id']]);
                $updated++;
            }
            return $updated;
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function getHistoricalUsage($drugId, $months)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT MONTH(dispense_date) as m, SUM(quantity) as qty
                FROM dispensing_items di JOIN dispensing d ON di.dispensing_id = d.id
                WHERE di.drug_id = ? AND d.dispense_date >= DATE_SUB(NOW(), INTERVAL ? MONTH)
                GROUP BY m
            ");
            $stmt->execute([$drugId, $months]);
            return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        } catch (\Exception $e) {
            return [];
        }
    }
    
    private function calculateEMA($history, $alpha = 0.3)
    {
        $data = array_values($history);
        if (empty($data)) return 0;
        $ema = $data[0];
        for ($i = 1; $i < count($data); $i++) {
            $ema = ($alpha * $data[$i]) + ((1 - $alpha) * $ema);
        }
        return $ema;
    }
    
    private function getSeasonalFactor($drugId, $month)
    {
        try {
            $stmt = $this->db->prepare("SELECT usage_factor FROM analytics_seasonal_patterns WHERE drug_id = ? AND month_index = ?");
            $stmt->execute([$drugId, $month]);
            return $stmt->fetchColumn() ?: 1.0;
        } catch (\Exception $e) {
            return 1.0;
        }
    }

    public function getPredictiveShortages()
    {
        try {
            return $this->db->query("
                SELECT d.id, d.name, d.code,
                    COALESCE(SUM(i.quantity), 0) as current_stock,
                    COALESCE(f.forecast_quantity, 0) / 30 as avg_daily_usage,
                    CASE WHEN (COALESCE(f.forecast_quantity, 0) / 30) > 0 
                        THEN COALESCE(SUM(i.quantity), 0) / (COALESCE(f.forecast_quantity, 0) / 30)
                        ELSE 999 END as days_remaining
                FROM drugs d
                LEFT JOIN inventory i ON d.id = i.drug_id
                LEFT JOIN analytics_demand_forecast f ON d.id = f.drug_id 
                    AND f.forecast_date = DATE_FORMAT(NOW(), '%Y-%m-01')
                WHERE d.is_active = 1 GROUP BY d.id HAVING days_remaining <= 7
                ORDER BY days_remaining ASC
            ")->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            return [];
        }
    }

    // ============================================
    // NEW: Enhanced Analytics + JHCIS Integration
    // ============================================

    /**
     * Get Extended Dashboard Statistics
     */
    public function getExtendedDashboardStats()
    {
        $stats = [
            'polypharmacy_count' => 0,
            'total_inventory_value' => 0,
            'allergy_alerts_today' => 0,
            'jhcis_patients_synced' => 0,
            'jhcis_dispensing_today' => 0,
            'forecast_accuracy' => 85,
            'cost_trend' => [],
            'seasonal_data' => []
        ];

        try {
            $stmt = $this->db->query("SELECT COUNT(*) FROM analytics_patient_risk WHERE polypharmacy_detected = 1");
            $stats['polypharmacy_count'] = (int)$stmt->fetchColumn();
        } catch (\Exception $e) {}

        try {
            $stmt = $this->db->query("SELECT SUM(i.quantity * d.cost_price) FROM inventory i JOIN drugs d ON i.drug_id = d.id");
            $stats['total_inventory_value'] = (float)$stmt->fetchColumn() ?: 0;
        } catch (\Exception $e) {}

        try {
            $stmt = $this->db->query("SELECT COUNT(*) FROM patient_allergies WHERE DATE(created_at) = CURDATE()");
            $stats['allergy_alerts_today'] = (int)$stmt->fetchColumn();
        } catch (\Exception $e) {}

        try {
            $stmt = $this->db->query("SELECT COUNT(DISTINCT hn) FROM patient_profiles");
            $stats['jhcis_patients_synced'] = (int)$stmt->fetchColumn();
        } catch (\Exception $e) {}

        try {
            $stmt = $this->db->query("SELECT COUNT(*) FROM sync_changes WHERE DATE(created_at) = CURDATE() AND change_type = 'dispensing'");
            $stats['jhcis_dispensing_today'] = (int)$stmt->fetchColumn();
        } catch (\Exception $e) {}

        $stats['cost_trend'] = $this->getCostTrend(6);
        $stats['forecast_accuracy'] = $this->calculateForecastAccuracy();
        $stats['seasonal_data'] = $this->getDynamicSeasonalData();

        return $stats;
    }

    /**
     * Get Monthly Cost Trend
     */
    public function getCostTrend($months = 6)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT DATE_FORMAT(d.dispense_date, '%Y-%m') as month,
                    SUM(di.quantity * dr.cost_price) as total_cost,
                    SUM(di.quantity * dr.selling_price) as total_revenue
                FROM dispensing d
                JOIN dispensing_items di ON d.id = di.dispense_id
                JOIN drugs dr ON di.drug_id = dr.id
                WHERE d.dispense_date >= DATE_SUB(NOW(), INTERVAL ? MONTH)
                GROUP BY DATE_FORMAT(d.dispense_date, '%Y-%m') ORDER BY month ASC
            ");
            $stmt->execute([$months]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Calculate Forecast Accuracy
     */
    public function calculateForecastAccuracy()
    {
        try {
            $stmt = $this->db->query("
                SELECT f.drug_id, f.forecast_quantity, COALESCE(SUM(di.quantity), 0) as actual_usage
                FROM analytics_demand_forecast f
                LEFT JOIN dispensing_items di ON f.drug_id = di.drug_id
                LEFT JOIN dispensing d ON di.dispense_id = d.id 
                    AND DATE_FORMAT(d.dispense_date, '%Y-%m') = DATE_FORMAT(f.forecast_date, '%Y-%m')
                WHERE f.forecast_date >= DATE_SUB(NOW(), INTERVAL 3 MONTH)
                    AND f.forecast_date < DATE_FORMAT(NOW(), '%Y-%m-01')
                GROUP BY f.drug_id, f.forecast_date
            ");
            
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (empty($rows)) return 85;
            
            $totalError = 0; $count = 0;
            foreach ($rows as $row) {
                if ($row['actual_usage'] > 0) {
                    $totalError += abs($row['forecast_quantity'] - $row['actual_usage']) / $row['actual_usage'];
                    $count++;
                }
            }
            if ($count == 0) return 85;
            return round(max(0, min(100, 100 - ($totalError / $count) * 100)), 1);
        } catch (\Exception $e) {
            return 85;
        }
    }

    /**
     * Get Dynamic Seasonal Analysis
     */
    public function getDynamicSeasonalData()
    {
        try {
            $stmt = $this->db->query("
                SELECT MONTH(d.dispense_date) as month_num, SUM(di.quantity) as total_qty, COUNT(DISTINCT d.id) as tx_count
                FROM dispensing d JOIN dispensing_items di ON d.id = di.dispense_id
                WHERE d.dispense_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                GROUP BY MONTH(d.dispense_date) ORDER BY month_num
            ");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (empty($data)) return $this->getMockSeasonalData();
            
            $avg = array_sum(array_column($data, 'total_qty')) / max(1, count($data));
            $result = [];
            foreach ($data as $row) {
                $ratio = $row['total_qty'] / max(1, $avg);
                $intensity = $ratio > 1.3 ? 'danger' : ($ratio > 1.1 ? 'warning' : ($ratio > 0.9 ? 'active' : 'normal'));
                $result[$row['month_num']] = ['qty' => (int)$row['total_qty'], 'tx_count' => (int)$row['tx_count'], 'intensity' => $intensity, 'ratio' => round($ratio, 2)];
            }
            for ($i = 1; $i <= 12; $i++) {
                if (!isset($result[$i])) $result[$i] = ['qty' => 0, 'tx_count' => 0, 'intensity' => 'normal', 'ratio' => 0];
            }
            ksort($result);
            return $result;
        } catch (\Exception $e) {
            return $this->getMockSeasonalData();
        }
    }
    
    private function getMockSeasonalData()
    {
        $months = []; $intensities = ['normal', 'active', 'normal', 'danger', 'normal', 'normal', 'active', 'normal', 'warning', 'danger', 'active', 'active'];
        for ($i = 1; $i <= 12; $i++) {
            $months[$i] = ['qty' => rand(100, 500), 'tx_count' => rand(10, 50), 'intensity' => $intensities[$i-1], 'ratio' => rand(8, 15) / 10];
        }
        return $months;
    }

    /**
     * Get JHCIS Summary
     */
    public function getJHCISSummary()
    {
        if (!$this->jhcisDb) return ['connected' => false];
        try {
            $summary = ['connected' => true, 'patients_today' => 0, 'dispensing_today' => 0, 'top_diagnoses' => [], 'top_drugs' => []];
            $summary['patients_today'] = (int)$this->jhcisDb->query("SELECT COUNT(DISTINCT hn) FROM ovst WHERE vstdate = CURDATE()")->fetchColumn();
            $summary['dispensing_today'] = (int)$this->jhcisDb->query("SELECT COUNT(*) FROM opitemrece WHERE vstdate = CURDATE()")->fetchColumn();
            $summary['top_diagnoses'] = $this->jhcisDb->query("SELECT icd10, COUNT(*) as cnt FROM ovstdiag WHERE vstdate = CURDATE() GROUP BY icd10 ORDER BY cnt DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
            $summary['top_drugs'] = $this->jhcisDb->query("SELECT drugname, SUM(qty) as total_qty FROM opitemrece WHERE vstdate = CURDATE() GROUP BY drugname ORDER BY total_qty DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
            return $summary;
        } catch (\Exception $e) {
            return ['connected' => true, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send Critical Alert to Discord/Telegram
     */
    public function sendCriticalAlert($type, $data)
    {
        try {
            $configFile = __DIR__ . '/../../config/notifications.json';
            if (!file_exists($configFile)) return false;
            $config = json_decode(file_get_contents($configFile), true);
            $message = $this->formatAlertMessage($type, $data);
            
            if (!empty($config['discord_webhook'])) {
                $this->sendDiscordAlert($config['discord_webhook'], $type, $message);
                return true;
            }
            if (!empty($config['telegram_bot_token']) && !empty($config['telegram_chat_id'])) {
                $this->sendTelegramAlert($config['telegram_bot_token'], $config['telegram_chat_id'], $message);
                return true;
            }
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    private function formatAlertMessage($type, $data)
    {
        switch ($type) {
            case 'critical_risk': return "🚨 <b>Critical Patient Risk</b>\nHN: {$data['hn']}\nScore: {$data['score']}";
            case 'shortage': return "📦 <b>Stock Shortage</b>\nDrug: {$data['drug']}\nDays Left: {$data['days']}";
            case 'allergy': return "⚠️ <b>Allergy Alert</b>\nPatient: {$data['patient']}\nDrug: {$data['drug']}";
            default: return json_encode($data);
        }
    }
    
    private function sendDiscordAlert($webhook, $type, $message)
    {
        $payload = json_encode(['embeds' => [['title' => '🚨 Drugmuk Alert', 'description' => strip_tags($message), 'color' => 15158332, 'timestamp' => date('c')]]]);
        $ch = curl_init($webhook);
        curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $payload, CURLOPT_HTTPHEADER => ['Content-Type: application/json'], CURLOPT_RETURNTRANSFER => true]);
        curl_exec($ch); curl_close($ch);
    }
    
    private function sendTelegramAlert($token, $chatId, $message)
    {
        $ch = curl_init("https://api.telegram.org/bot{$token}/sendMessage");
        curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query(['chat_id' => $chatId, 'text' => $message, 'parse_mode' => 'HTML']), CURLOPT_RETURNTRANSFER => true]);
        curl_exec($ch); curl_close($ch);
    }
}
