<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\IntelligenceService;

/**
 * Intelligence Controller (Phase 2 Enhanced)
 * 
 * API Endpoints for Analytics & Predictive Insights + JHCIS
 */
class IntelligenceController extends Controller
{
    private $intelService;
    
    public function __construct()
    {
        $this->intelService = new IntelligenceService();
    }
    
    /**
     * GET /api/intelligence/forecast/{drug_id}
     */
    public function getForecast($drugId)
    {
        header('Content-Type: application/json');
        try {
            $forecast = $this->intelService->calculateDemandForecast($drugId);
            echo json_encode(['success' => true, 'forecast' => $forecast]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    /**
     * GET /api/intelligence/high-risk-patients
     */
    public function getHighRiskPatients()
    {
        header('Content-Type: application/json');
        try {
            $db = \App\Core\Database::getInstance()->getConnection();
            $patients = [];
            try {
                $patients = $db->query("SELECT * FROM v_high_risk_patients_summary LIMIT 50")->fetchAll(\PDO::FETCH_ASSOC);
            } catch (\Exception $e) {
                // View might not exist
            }
            echo json_encode(['success' => true, 'patients' => $patients]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    /**
     * POST /api/intelligence/recalculate-risk
     */
    public function recalculateRisk()
    {
        header('Content-Type: application/json');
        try {
            $count = $this->intelService->updatePatientRiskScores();
            echo json_encode(['success' => true, 'updated_count' => $count]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    /**
     * GET /api/intelligence/dashboard-stats
     */
    public function getDashboardStats()
    {
        header('Content-Type: application/json');
        try {
            $db = \App\Core\Database::getInstance()->getConnection();
            $riskStats = $this->intelService->getRiskStatistics();
            
            // Clinical Alerts
            $criticalLabsCount = 0;
            try {
                $criticalLabsCount = $db->query("
                    SELECT COUNT(*) FROM patient_lab_results 
                    WHERE (lab_name = 'eGFR' AND lab_value < 30) 
                       OR (lab_name = 'Potassium' AND (lab_value > 5.0 OR lab_value < 3.5))
                ")->fetchColumn();
            } catch (\Exception $e) {}
            
            // Recent DDI
            $recentInteractions = [];
            try {
                $recentInteractions = $db->query("
                    SELECT d.hn, d.patient_name, di.drug_id, dr.name as drug_name, di.dispense_date
                    FROM dispensing d
                    JOIN dispensing_items di ON d.id = di.dispense_id
                    JOIN drugs dr ON di.drug_id = dr.id
                    WHERE di.created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
                    LIMIT 5
                ")->fetchAll(\PDO::FETCH_ASSOC);
            } catch (\Exception $e) {}

            // Predictive Shortages
            $predictiveShortages = $this->intelService->getPredictiveShortages();
            
            // Extended Stats
            $extendedStats = $this->intelService->getExtendedDashboardStats();

            echo json_encode([
                'success' => true, 
                'risk_stats' => $riskStats,
                'critical_labs_count' => (int)$criticalLabsCount,
                'recent_interactions' => $recentInteractions,
                'predictive_shortages' => $predictiveShortages,
                'extended' => $extendedStats,
                'timestamp' => date('Y-m-d H:i:s')
            ], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * GET /api/intelligence/rdu-analysis
     */
    public function getRDUAnalysis()
    {
        header('Content-Type: application/json');
        try {
            $analysis = $this->intelService->getAntibioticUsage();
            echo json_encode(['success' => true, 'analysis' => $analysis]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * GET /api/intelligence/high-cost-analysis
     */
    public function getHighCostAnalysis()
    {
        header('Content-Type: application/json');
        try {
            $analysis = $this->intelService->getHighCostMedications();
            echo json_encode(['success' => true, 'analysis' => $analysis]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * GET /api/intelligence/polypharmacy
     */
    public function getPolypharmacy()
    {
        header('Content-Type: application/json');
        try {
            $patients = $this->intelService->getPolypharmacyPatients();
            echo json_encode(['success' => true, 'patients' => $patients]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Intelligence Dashboard View
     * GET /admin/intelligence
     */
    public function dashboard()
    {
        $this->view('intelligence/dashboard');
    }

    /**
     * POST /api/intelligence/auto-adjust-inventory
     */
    public function autoAdjustInventory()
    {
        header('Content-Type: application/json');
        try {
            $updated = $this->intelService->autoAdjustInventoryPoints();
            echo json_encode(['success' => true, 'updated_count' => $updated]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * GET /api/intelligence/jhcis-summary
     */
    public function getJHCISSummary()
    {
        header('Content-Type: application/json');
        try {
            $summary = $this->intelService->getJHCISSummary();
            echo json_encode(['success' => true, 'summary' => $summary], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * GET /api/intelligence/cost-trend
     */
    public function getCostTrend()
    {
        header('Content-Type: application/json');
        try {
            $months = $_GET['months'] ?? 6;
            $trend = $this->intelService->getCostTrend((int)$months);
            echo json_encode(['success' => true, 'trend' => $trend]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * POST /api/intelligence/send-alert
     */
    public function sendAlert()
    {
        header('Content-Type: application/json');
        try {
            $type = $_POST['type'] ?? 'general';
            $data = json_decode($_POST['data'] ?? '{}', true);
            
            $sent = $this->intelService->sendCriticalAlert($type, $data);
            echo json_encode(['success' => $sent, 'message' => $sent ? 'Alert sent!' : 'No notification channel configured']);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * GET /api/intelligence/export-pdf
     */
    public function exportPDF()
    {
        // Generate PDF report data (HTML-based for now)
        try {
            $stats = $this->intelService->getExtendedDashboardStats();
            $riskStats = $this->intelService->getRiskStatistics();
            $shortages = $this->intelService->getPredictiveShortages();
            
            $html = $this->generatePDFHTML($stats, $riskStats, $shortages);
            
            header('Content-Type: text/html; charset=utf-8');
            header('Content-Disposition: attachment; filename="intelligence_report_' . date('Y-m-d') . '.html"');
            echo $html;
        } catch (\Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
    
    private function generatePDFHTML($stats, $riskStats, $shortages)
    {
        $date = date('d/m/Y H:i:s');
        $criticalCount = 0;
        $highCount = 0;
        foreach ($riskStats as $r) {
            if ($r['risk_level'] === 'critical') $criticalCount = $r['count'];
            if ($r['risk_level'] === 'high') $highCount = $r['count'];
        }
        
        $shortageRows = '';
        foreach ($shortages as $s) {
            $shortageRows .= "<tr><td>{$s['name']}</td><td>{$s['current_stock']}</td><td>" . round($s['days_remaining'], 1) . " วัน</td></tr>";
        }
        if (empty($shortageRows)) {
            $shortageRows = '<tr><td colspan="3" style="text-align:center">✅ ไม่มียาที่เสี่ยงขาดสต็อกใน 7 วัน</td></tr>';
        }

        $formattedInventoryValue = number_format($stats['total_inventory_value'], 2);
        
        return <<<HTML
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Intelligence Report - {$date}</title>
    <style>
        body { font-family: 'Sarabun', sans-serif; padding: 40px; background: #f8fafc; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 3px solid #4299e1; padding-bottom: 20px; }
        .header h1 { color: #2d3748; margin: 0; }
        .header p { color: #718096; margin: 5px 0 0 0; }
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); text-align: center; }
        .stat-card .value { font-size: 32px; font-weight: bold; color: #4299e1; }
        .stat-card .label { font-size: 14px; color: #718096; }
        .section { background: white; padding: 25px; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .section h2 { margin: 0 0 15px 0; color: #2d3748; font-size: 18px; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #e2e8f0; }
        th { background: #f7fafc; font-weight: 600; }
        .footer { text-align: center; color: #a0aec0; font-size: 12px; margin-top: 30px; }
        @media print { body { background: white; } }
    </style>
</head>
<body>
    <div class="header">
        <h1>🧠 Intelligence Report</h1>
        <p>Generated: {$date}</p>
    </div>
    
    <div class="stats-grid">
        <div class="stat-card">
            <div class="value">{$criticalCount}</div>
            <div class="label">Critical Risk Patients</div>
        </div>
        <div class="stat-card">
            <div class="value">{$highCount}</div>
            <div class="label">High Risk Patients</div>
        </div>
        <div class="stat-card">
            <div class="value">{$stats['polypharmacy_count']}</div>
            <div class="label">Polypharmacy Cases</div>
        </div>
        <div class="stat-card">
            <div class="value">{$stats['forecast_accuracy']}%</div>
            <div class="label">Forecast Accuracy</div>
        </div>
    </div>
    
    <div class="section">
        <h2>📦 Predictive Shortages (7 days)</h2>
        <table>
            <thead><tr><th>Drug Name</th><th>Current Stock</th><th>Days Remaining</th></tr></thead>
            <tbody>{$shortageRows}</tbody>
        </table>
    </div>
    
    <div class="section">
        <h2>💰 Inventory Statistics</h2>
        <p><strong>Total Inventory Value:</strong> ฿ {$formattedInventoryValue}</p>
        <p><strong>JHCIS Patients Synced:</strong> {$stats['jhcis_patients_synced']}</p>
        <p><strong>Allergy Alerts Today:</strong> {$stats['allergy_alerts_today']}</p>
    </div>
    
    <div class="footer">
        <p>Drugmuk Intelligence System | Auto-generated Report</p>
    </div>
</body>
</html>
HTML;
    }
}
