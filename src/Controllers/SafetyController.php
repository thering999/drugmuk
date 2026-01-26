<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\SafetyService;

/**
 * Safety Controller (Phase 4)
 */
class SafetyController extends Controller
{
    private $safetyService;
    
    public function __construct()
    {
        $this->safetyService = new SafetyService();
    }
    
    /**
     * API: Check Multiple Drug Interactions
     * POST /api/safety/check-ddi
     */
    public function checkDDI()
    {
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents('php://input'), true);
        
        $drugNames = $data['drugs'] ?? [];
        if (empty($drugNames)) {
            echo json_encode(['success' => true, 'interactions' => []]);
            return;
        }
        
        $interactions = $this->safetyService->checkDrugInteractions($drugNames);
        echo json_encode(['success' => true, 'interactions' => $interactions]);
    }
    
    /**
     * API: Check Patient safety for specific drug
     * GET /api/safety/check-patient?hn={hn}&drug={drug}
     */
    public function checkPatient()
    {
        header('Content-Type: application/json');
        $hn = $_GET['hn'] ?? '';
        $drug = $_GET['drug'] ?? '';
        
        if (!$hn || !$drug) {
            echo json_encode(['success' => false, 'message' => 'Missing HN or Drug name']);
            return;
        }
        
        $alerts = $this->safetyService->checkPatientSafety($hn, $drug);
        echo json_encode(['success' => true, 'alerts' => $alerts]);
    }
    
    /**
     * API: Get Lab Summary
     * GET /api/safety/labs/{hn}
     */
    public function getLabs($hn)
    {
        header('Content-Type: application/json');
        $labs = $this->safetyService->getClinicalLabs($hn);
        echo json_encode(['success' => true, 'labs' => $labs]);
    }
}
