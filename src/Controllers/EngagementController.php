<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\EngagementService;
use App\Services\PatientService;

/**
 * Engagement Controller (Phase 3)
 */
class EngagementController extends Controller
{
    private $engagementService;
    private $patientService;
    
    public function __construct()
    {
        $this->engagementService = new EngagementService();
        $this->patientService = new PatientService();
    }
    
    /**
     * API: Send Reminder
     * POST /api/engagement/send-reminder
     */
    public function sendReminder()
    {
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['hn'], $data['drug_name'], $data['instruction'])) {
            echo json_encode(['success' => false, 'message' => 'Missing required data']);
            return;
        }
        
        $success = $this->engagementService->sendMedicationReminder($data['hn'], $data['drug_name'], $data['instruction']);
        echo json_encode(['success' => $success]);
    }
    
    /**
     * API: Generate Easy Instructions
     * POST /api/engagement/generate-instruction
     */
    public function generateInstruction()
    {
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents('php://input'), true);
        
        $drugName = $data['drug_name'] ?? '';
        $raw = $data['raw_instruction'] ?? '';
        
        $easy = $this->engagementService->generateEasyInstruction($drugName, $raw);
        echo json_encode(['success' => true, 'instruction' => $easy]);
    }
    
    /**
     * API: Save Instructions
     * POST /api/engagement/save-instruction
     */
    public function saveInstruction()
    {
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents('php://input'), true);
        
        $success = $this->engagementService->savePersonalizedInstruction(
            $data['hn'], $data['drug_id'], $data['drug_name'], $data['instruction']
        );
        echo json_encode(['success' => $success]);
    }
    
    /**
     * API: Get Patient Adherence Stats
     * GET /api/engagement/adherence/{hn}
     */
    public function getAdherence($hn)
    {
        header('Content-Type: application/json');
        $stats = $this->engagementService->getAdherenceStats($hn);
        echo json_encode(['success' => true, 'stats' => $stats]);
    }

    /**
     * API: Record Adherence (Self-Report)
     * POST /api/engagement/record-adherence
     */
    public function recordAdherence()
    {
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents('php://input'), true);
        
        $success = $this->engagementService->recordAdherence(
            $data['hn'], $data['drugId'], $data['status'] ?? 'taken', $data['notes'] ?? ''
        );
        echo json_encode(['success' => $success]);
    }

    /**
     * View: Patient Mobile Link (Mockup)
     * GET /patient/v/{token}
     */
    public function patientPortal($token)
    {
        // Simple mockup of a patient portal view
        // In reality, token would decode to an HN
        $hn = '0000001'; 
        $patient = $this->patientService->getPatientProfile($hn);
        $instructions = $this->engagementService->getPatientInstructions($hn);
        
        $this->view('engagement/portal', [
            'patient' => $patient,
            'instructions' => $instructions
        ]);
    }
}
