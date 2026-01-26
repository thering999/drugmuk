<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\IntelligenceService;

/**
 * Intelligence Controller (Phase 2)
 * 
 * API Endpoints for Analytics & Predictive Insights
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
            $patients = $db->query("SELECT * FROM v_high_risk_patients_summary LIMIT 50")->fetchAll(\PDO::FETCH_ASSOC);
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
            
            // Phase 4: Clinical Alerts (Global)
            $criticalLabsCount = $db->query("
                SELECT COUNT(*) 
                FROM patient_lab_results 
                WHERE (lab_name = 'eGFR' AND lab_value < 30) 
                   OR (lab_name = 'Potassium' AND (lab_value > 5.0 OR lab_value < 3.5))
            ")->fetchColumn();
            
            // Recent DDI check (last 24 hours simulation)
            $recentInteractions = $db->query("
                SELECT d.hn, d.patient_name, di.drug_id, dr.name as drug_name, di.dispense_date
                FROM dispensing d
                JOIN dispensing_items di ON d.id = di.dispense_id
                JOIN drugs dr ON di.drug_id = dr.id
                WHERE di.created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
                LIMIT 5
            ")->fetchAll(\PDO::FETCH_ASSOC);

            // Predictive Out-of-Stock (7 days)
            $predictiveShortages = $this->intelService->getPredictiveShortages();

            echo json_encode([
                'success' => true, 
                'risk_stats' => $riskStats,
                'critical_labs_count' => (int)$criticalLabsCount,
                'recent_interactions' => $recentInteractions,
                'predictive_shortages' => $predictiveShortages,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
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
}
