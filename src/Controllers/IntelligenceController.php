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
            $model = $_GET['model'] ?? 'EMA'; // Default to EMA
            $forecast = $this->intelService->calculateDemandForecast($drugId, $model);
            echo json_encode(['success' => true, 'forecast' => $forecast, 'model' => $model]);
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
     * GET /api/intelligence/intervention-advice
     */
    public function getInterventionAdvice()
    {
        header('Content-Type: application/json');
        try {
            $type = $_GET['type'] ?? '';
            $details = $_GET['details'] ?? '';
            $advice = $this->intelService->getInterventionAdvice($type, $details);
            echo json_encode(['success' => true, 'advice' => $advice]);
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
                'engagement_stats' => $this->intelService->getEngagementStats(),
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
     * GET /api/intelligence/budget-forecast
     */
    public function getBudgetForecast()
    {
        header('Content-Type: application/json');
        try {
            $forecast = $this->intelService->calculateBudgetForecast();
            echo json_encode(['success' => true, 'forecast' => $forecast]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * GET /api/intelligence/adherence/{hn}
     */
    public function getAdherenceRisk($hn)
    {
        header('Content-Type: application/json');
        try {
            $risk = $this->intelService->predictPatientAdherence($hn);
            echo json_encode(['success' => true, 'adherence' => $risk]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * GET /api/intelligence/dur-report
     */
    public function getDURReport()
    {
        header('Content-Type: application/json');
        try {
            $report = $this->intelService->getDrugUtilizationReport();
            echo json_encode(['success' => true, 'report' => $report]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * POST /api/intelligence/tele-summary
     */
    public function getTeleSummary()
    {
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents('php://input'), true);
        $notes = $data['notes'] ?? '';
        
        try {
            $summary = $this->intelService->summarizeTeleconsultation($notes);
            echo json_encode(['success' => true, 'summary' => $summary]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }


    /**
     * GET /api/intelligence/clinical-monitoring/{hn}
     */
    public function getClinicalMonitoring($hn)
    {
        header('Content-Type: application/json');
        try {
            $recommendations = $this->intelService->getClinicalMonitoringAdvisor($hn);
            echo json_encode(['success' => true, 'recommendations' => $recommendations]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }


    /**
     * GET /api/intelligence/safety-monitoring
     */
    public function getSafetyMonitoring()
    {
        header('Content-Type: application/json');
        try {
            $list = $this->intelService->getSafetyMonitoringList();
            echo json_encode(['success' => true, 'monitoring_list' => $list]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * GET /api/intelligence/patient-safety-report/{hn}
     */
    public function getPatientSafetyReport($hn)
    {
        header('Content-Type: application/json');
        try {
            $report = $this->intelService->generatePatientSafetyReport($hn);
            echo json_encode(['success' => true, 'report' => $report]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }


    /**
     * GET /api/intelligence/patient-insight/{hn}
     */
    public function getPatientInsight($hn)
    {
        header('Content-Type: application/json');
        try {
            $insight = $this->intelService->getPatientInsight($hn);
            if (!$insight) {
                echo json_encode(['success' => false, 'message' => 'Patient not found or no insight available']);
                return;
            }
            echo json_encode(['success' => true, 'insight' => $insight], JSON_UNESCAPED_UNICODE);
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
     * POST /api/intelligence/run-clinical-audit
     * Scans all active chronic patients for potential AI insights and risks
     */
    public function runGlobalClinicalAudit()
    {
        header('Content-Type: application/json');
        try {
            $db = \App\Core\Database::getInstance()->getConnection();
            $hns = $db->query("SELECT DISTINCT hn FROM patient_chronic_diseases_cache LIMIT 100")->fetchAll(\PDO::FETCH_COLUMN);
            
            $results = [
                'processed' => 0,
                'high_risk_found' => 0,
                'alerts_total' => 0
            ];

            // Ensure interventions table exists
            $db->exec("CREATE TABLE IF NOT EXISTS clinical_interventions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                hn VARCHAR(50) NOT NULL,
                staff_id INT NOT NULL,
                intervention_type VARCHAR(100),
                details TEXT,
                severity VARCHAR(20),
                status VARCHAR(20) DEFAULT 'Logged',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )");

            foreach ($hns as $hn) {
                $insight = $this->intelService->getPatientInsight($hn);
                if ($insight) {
                    $results['processed']++;
                    if ($insight['score'] > 40) {
                        $results['high_risk_found']++;
                        
                        // Check if we should log these as interventions
                        foreach ($insight['alerts'] as $alert) {
                            $stmt = $db->prepare("
                                INSERT INTO clinical_interventions (hn, staff_id, intervention_type, details, severity, status, created_at)
                                SELECT ?, 1, ?, ?, ?, 'Pending', NOW()
                                WHERE NOT EXISTS (SELECT 1 FROM clinical_interventions WHERE hn = ? AND details = ? AND status = 'Pending')
                            ");
                            $severity = ($insight['score'] > 50 ? 'Major' : 'Moderate');
                            $stmt->execute([$hn, $alert['title'], $alert['message'], $severity, $hn, $alert['message']]);

                            // Send LINE Alert for Major findings during Audit
                            if ($severity === 'Major') {
                                $lineService = new \App\Services\LineNotificationService();
                                $patientService = new \App\Services\PatientService();
                                $p = $patientService->getPatientByHN($hn);
                                $pName = $p ? ($p['first_name'] . ' ' . $p['last_name']) : $hn;
                                $lineService->sendClinicalAlert($pName, $alert['title'], $alert['message']);
                            }
                        }
                    }
                    $results['alerts_total'] += count($insight['alerts']);
                    
                    if ($insight['score'] > 60) {
                        $this->intelService->sendCriticalAlert('critical_risk', [
                            'hn' => $hn,
                            'score' => $insight['score'],
                            'summary' => $insight['summary']
                        ]);
                    }
                }
            }
            
            echo json_encode(['success' => true, 'results' => $results]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * GET /api/intelligence/interventions
     */
    public function getInterventions()
    {
        header('Content-Type: application/json');
        try {
            $db = \App\Core\Database::getInstance()->getConnection();
            
            // Ensure table exists
            $db->exec("CREATE TABLE IF NOT EXISTS clinical_interventions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                hn VARCHAR(50) NOT NULL,
                staff_id INT NOT NULL,
                intervention_type VARCHAR(100),
                details TEXT,
                severity VARCHAR(20),
                status VARCHAR(20) DEFAULT 'Logged',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )");

            $interventions = $db->query("
                SELECT i.*, p.first_name, p.last_name 
                FROM clinical_interventions i
                LEFT JOIN patient_profiles p ON i.hn = p.hn
                ORDER BY i.created_at DESC LIMIT 50
            ")->fetchAll(\PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'interventions' => $interventions]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * GET /api/intelligence/intervention-analytics
     */
    public function getInterventionAnalytics()
    {
        header('Content-Type: application/json');
        try {
            $analytics = $this->intelService->getInterventionAnalytics();
            echo json_encode(['success' => true, 'analytics' => $analytics]);
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
     * POST /api/intelligence/analyze-note
     * Analyze clinical note text for ADR and Interactions
     */
    public function analyzeClinicalNote()
    {
        header('Content-Type: application/json');
        try {
            // Get raw POST data
            $input = json_decode(file_get_contents('php://input'), true);
            $text = $input['text'] ?? '';
            $hn = $input['hn'] ?? null;
            
            if (empty($text)) {
                throw new \Exception('Text content is required');
            }
            
            $analysis = $this->intelService->analyzeClinicalNote($text, $hn);
            echo json_encode(['success' => true, 'analysis' => $analysis]);
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
            $response = [
                'success' => true,
                'timestamp' => date('Y-m-d H:i:s'),
                'risk_stats' => $this->intelService->getRiskStatistics(),
                'predictive_shortages' => $this->intelService->getPredictiveShortages(),
                'extended' => $this->intelService->getExtendedDashboardStats(),
                'engagement_stats' => $this->intelService->getEngagementStats()
            ];
            
            $stats = $response['extended'];
            $riskStats = $response['risk_stats'];
            $shortages = $response['predictive_shortages'];
            // $engagementStats = $response['engagement_stats']; // If generatePDFHTML needs it, uncomment and pass it

            $html = $this->generatePDFHTML($stats, $riskStats, $shortages); // Pass $engagementStats if generatePDFHTML is updated to accept it
            
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
        
        return <<<OUT
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
OUT;
    }

    /**
     * GET /api/intelligence/export-interventions
     */
    public function exportInterventions()
    {
        try {
            $db = \App\Core\Database::getInstance()->getConnection();

            // Ensure table exists
            $db->exec("CREATE TABLE IF NOT EXISTS clinical_interventions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                hn VARCHAR(50) NOT NULL,
                staff_id INT NOT NULL,
                intervention_type VARCHAR(100),
                details TEXT,
                severity VARCHAR(20),
                status VARCHAR(20) DEFAULT 'Logged',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )");

            $data = $db->query("
                SELECT i.id, i.created_at, i.hn, p.first_name, p.last_name, 
                       i.intervention_type, i.details, i.severity, i.status
                FROM clinical_interventions i
                LEFT JOIN patient_profiles p ON i.hn = p.hn
                ORDER BY i.created_at DESC
            ")->fetchAll(\PDO::FETCH_ASSOC);

            $export = new \App\Services\ExcelExportService();
            $export->setTitle('Clinical Interventions Report - Drugmuk')
                   ->setFilename('clinical_interventions_' . date('Y-m-d') . '.xls')
                   ->setHeaders([
                       'ID', 'Date Time', 'HN', 'First Name', 'Last Name',
                       'Type', 'Details', 'Severity', 'Status'
                   ])
                   ->setData($data)
                   ->exportExcelHTML();
        } catch (\Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    /**
     * GET /api/intelligence/shortage-substitutions
     */
    public function getShortageSubstitutions()
    {
        header('Content-Type: application/json');
        try {
            $substitutions = $this->intelService->getShortageSubstitutions();
            echo json_encode(['success' => true, 'substitutions' => $substitutions]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * GET /api/intelligence/medication-reconciliation/{hn}
     */
    public function getMedicationReconciliation($hn)
    {
        header('Content-Type: application/json');
        try {
            $reconciliation = $this->intelService->getMedicationReconciliation($hn);
            echo json_encode(['success' => true, 'reconciliation' => $reconciliation]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * GET /api/intelligence/clinical-burdens/{hn}
     */
    public function getClinicalBurdens($hn)
    {
        header('Content-Type: application/json');
        try {
            $burdens = $this->intelService->getClinicalBurdens($hn);
            echo json_encode(['success' => true, 'burdens' => $burdens]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * GET /api/intelligence/thai-diet-advice/{hn}
     */
    public function getThaiDietAdvice($hn)
    {
        header('Content-Type: application/json');
        try {
            $advice = $this->intelService->getThaiDietAdvice($hn);
            echo json_encode(['success' => true, 'advice' => $advice]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * POST /api/intelligence/check-interactions-batch
     */
    public function checkBatchInteractions()
    {
        header('Content-Type: application/json');
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $hn = $data['hn'] ?? '';
            $drugIds = $data['drug_ids'] ?? [];
            
            $interactions = $this->intelService->checkBatchInteractions($hn, $drugIds);
            echo json_encode(['success' => true, 'interactions' => $interactions]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * GET /api/intelligence/deprescribing/{hn}
     */
    public function getDeprescribing($hn)
    {
        header('Content-Type: application/json');
        try {
            $suggestions = $this->intelService->getDeprescribing($hn);
            echo json_encode(['success' => true, 'data' => $suggestions]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * GET /api/intelligence/renal-dose-risk/{hn}
     */
    public function getRenalDoseSuggestions($hn)
    {
        header('Content-Type: application/json');
        try {
            $suggestions = $this->intelService->getRenalDoseSuggestions($hn);
            echo json_encode(['success' => true, 'data' => $suggestions]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * GET /api/intelligence/org-insights
     */
    public function getOrgInsights()
    {
        header('Content-Type: application/json');
        try {
            $strategy = $this->intelService->getInventoryOptimizationStrategy();
            echo json_encode(['success' => true, 'data' => $strategy]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    /**
     * GET /api/intelligence/clinical-review/{hn}
     */
    public function getAIClinicalReview($hn)
    {
        header('Content-Type: application/json');
        try {
            $drugIds = $_GET['drug_ids'] ?? [];
            if (is_string($drugIds)) $drugIds = explode(',', $drugIds);
            
            $review = $this->intelService->getAIClinicalReview($hn, $drugIds);
            echo json_encode(['success' => true, 'data' => $review]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * POST /api/intelligence/ask
     */
    public function askAI()
    {
        header('Content-Type: application/json');
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $query = $input['query'] ?? '';
            $hn = $input['hn'] ?? null;
            
            $answer = $this->intelService->askClinicalAssistant($query, $hn);
            echo json_encode(['success' => true, 'data' => $answer]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * GET /api/intelligence/shortage-priority/{drug_id}
     */
    public function getShortagePriority($drugId)
    {
        header('Content-Type: application/json');
        try {
            $priority = $this->intelService->getShortagePatientPrioritization($drugId);
            echo json_encode(['success' => true, 'data' => $priority]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    /**
     * GET /api/intelligence/ckd-progression/{hn}
     */
    public function getCKDProgression($hn)
    {
        header('Content-Type: application/json');
        try {
            $prediction = $this->intelService->predictCKDProgression($hn);
            echo json_encode(['success' => true, 'data' => $prediction]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * GET /api/intelligence/adherence-coaching/{hn}
     */
    public function getAdherenceCoaching($hn)
    {
        header('Content-Type: application/json');
        try {
            $coaching = $this->intelService->generateAdherenceCoaching($hn);
            echo json_encode(['success' => true, 'data' => $coaching]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * GET /api/intelligence/optimization/{hn}
     */
    public function getPharmacotherapyOptimization($hn)
    {
        header('Content-Type: application/json');
        try {
            $optimization = $this->intelService->getPharmacotherapyOptimization($hn);
            echo json_encode(['success' => true, 'data' => $optimization]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    /**
     * POST /api/intelligence/scribe
     */
    public function generateScribe()
    {
        header('Content-Type: application/json');
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $hn = $input['hn'] ?? '';
            $findings = $input['findings'] ?? [];
            
            $scribe = $this->intelService->generateClinicalScribe($hn, $findings);
            echo json_encode(['success' => true, 'data' => $scribe]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * GET /api/intelligence/cost-optimization/{hn}
     */
    public function getCostOptimization($hn)
    {
        header('Content-Type: application/json');
        try {
            $optimization = $this->intelService->getCostEffectivenessOptimization($hn);
            echo json_encode(['success' => true, 'data' => $optimization]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
