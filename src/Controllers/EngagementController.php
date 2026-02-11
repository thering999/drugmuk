<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\EngagementService;
use App\Services\PatientService;
use App\Services\TelehealthService;

/**
 * Engagement Controller (Phase 3)
 */
class EngagementController extends Controller
{
    private $engagementService;
    private $patientService;
    private $telehealthService;
    
    public function __construct()
    {
        $this->engagementService = new EngagementService();
        $this->patientService = new PatientService();
        $this->telehealthService = new TelehealthService();
    }
    
    /**
     * API: Start Tele-Consultation
     * POST /api/engagement/teleconsult/start
     */
    public function startTeleconsult()
    {
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents('php://input'), true);
        
        $token = $data['token'] ?? '';
        
        // Verify Token
        $hn = $this->engagementService->verifyAccessToken($token);
        if (!$hn) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Invalid Access']);
            return;
        }

        $room = $this->telehealthService->createConsultationRoom($hn);
        
        echo json_encode([
            'success' => true, 
            'url' => $room['url'],
            'room_name' => $room['room_name']
        ]);
    }

    /**
     * View: Engagement Dashboard (Pharmacist View)
     */
    public function index()
    {
        $lowAdherencePatients = $this->engagementService->getLowAdherencePatients();
        $stats = $this->engagementService->getActiveMonitoringStats();
        
        $this->view('engagement/index', [
            'lowAdherencePatients' => $lowAdherencePatients,
            'stats' => $stats
        ]);
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
        
        $token = $data['token'] ?? '';
        $hn = $data['hn'] ?? '';
        
        // Secure: Verify token first
        $verifiedHn = $this->engagementService->verifyAccessToken($token);
        if (!$verifiedHn || $verifiedHn !== $hn) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Invalid or expired token']);
            return;
        }

        $success = $this->engagementService->recordAdherence(
            $hn, $data['drugId'], $data['status'] ?? 'taken', $data['notes'] ?? ''
        );
        echo json_encode(['success' => $success]);
    }

    /**
     * API: Get AI General Health Advice
     * GET /api/engagement/ai-advice
     */
    public function getAIAdvice()
    {
        header('Content-Type: application/json');
        $token = $_GET['token'] ?? '';
        
        $hn = $this->engagementService->verifyAccessToken($token);
        if (!$hn) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }

        $intelService = new \App\Services\IntelligenceService();
        $advice = $intelService->getAIAdvice($hn);
        
        echo json_encode(['success' => true, 'advice' => $advice]);
    }

    /**
     * API: Get AI Patient Safety Insights (for Portal)
     * GET /api/engagement/safety-report
     */
    public function getSafetyReport()
    {
        header('Content-Type: application/json');
        $token = $_GET['token'] ?? '';
        
        $hn = $this->engagementService->verifyAccessToken($token);
        if (!$hn) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }

        $intelService = new \App\Services\IntelligenceService();
        $report = $intelService->generatePatientSafetyReport($hn);
        
        echo json_encode(['success' => true, 'report' => $report]);
    }

    /**
     * View: Patient Mobile Link (Mockup)
     * GET /patient/v/{token}
     */
    /**
     * View: Patient Mobile Link (Secure)
     * GET /patient/v/{token}
     */
    public function patientPortal($token)
    {
        $hn = $this->engagementService->verifyAccessToken($token);
        
        if (!$hn) {
            http_response_code(403);
            die("<h1>Invalid or Expired Link</h1><p>Please request a new link from the hospital.</p>");
        }

        $patient = $this->patientService->getProfileWithCache($hn);
        
        if (!$patient) {
             $patient = ['full_name' => 'ผู้ป่วยทั่วไป', 'hn' => $hn]; 
        }

        $instructions = $this->engagementService->getPatientInstructions($hn);
        $adherenceStats = $this->engagementService->getAdherenceStats($hn);
        
        // Real appointment from JHCIS
        $nextAppointment = $this->patientService->getNextAppointment($hn);
        
        // Fallback if no appointment found
        if (!$nextAppointment) {
            $nextAppointment = null;
        }

        // Get AI Insights
        $intelService = new \App\Services\IntelligenceService();
        $safetyReport = $intelService->generatePatientSafetyReport($hn);
        $visitSummary = $intelService->generateVisitSummary($hn);
        $mpr = $intelService->calculateMPR($hn);
        $aiAdvice = $intelService->getAIAdvice($hn);
        $dietAdvice = $intelService->getThaiDietAdvice($hn);
        
        $telehealthHistory = $this->engagementService->getTelehealthHistory($hn);
        
        $this->view('engagement/portal', [
            'patient' => $patient,
            'instructions' => $instructions,
            'adherenceStats' => $adherenceStats,
            'nextAppointment' => $nextAppointment,
            'safetyReport' => $safetyReport,
            'visitSummary' => $visitSummary,
            'mpr' => $mpr,
            'aiAdvice' => $aiAdvice,
            'dietAdvice' => $dietAdvice,
            'telehealthHistory' => $telehealthHistory,
            'token' => $token
        ]);
    }

    /**
     * API: Send Patient Portal Link via LINE
     * POST /api/engagement/send-portal-link
     */
    public function sendPortalLink()
    {
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents('php://input'), true);
        $hn = $data['hn'] ?? null;

        if (!$hn) {
            echo json_encode(['success' => false, 'message' => 'Missing HN']);
            return;
        }

        // Get Patient
        $patient = $this->patientService->getPatientByHN($hn); 
        
        // Mock LINE ID for demo if not in DB
        $lineUserId = $patient['line_user_id'] ?? \App\Core\Config::get('LINE_ADMIN_USER_ID');

        if (!$lineUserId) {
            echo json_encode(['success' => false, 'message' => 'Line ID not found']);
            return;
        }

        // Generate Secure Link
        $link = $this->engagementService->generateAccessLink($hn);
        
        // Construct Flex Message
        $flexContent = [
            "type" => "bubble",
            "hero" => [
                "type" => "image",
                "url" => "https://img.freepik.com/free-vector/telemedicine-concept-isometric-illustration_1284-24545.jpg",
                "size" => "full",
                "aspectRatio" => "20:13",
                "aspectMode" => "cover"
            ],
            "body" => [
                "type" => "box",
                "layout" => "vertical",
                "contents" => [
                    [
                        "type" => "text",
                        "text" => "My Health Portal",
                        "weight" => "bold",
                        "size" => "xl"
                    ],
                    [
                        "type" => "text",
                        "text" => "ข้อมูลสุขภาพของคุณ " . ($patient['first_name'] ?? 'ผู้ป่วย'),
                        "size" => "sm",
                        "color" => "#aaaaaa",
                        "wrap" => true
                    ],
                    [
                        "type" => "separator",
                        "margin" => "md"
                    ],
                    [
                        "type" => "text",
                        "text" => "เข้าถึงประวัติการรักษา รายการยา และปรึกษาเภสัชกรได้ตลอด 24 ชม. (ลิงก์มีอายุ 24 ชม.)",
                        "wrap" => true,
                        "margin" => "md",
                        "size" => "sm"
                    ]
                ]
            ],
            "footer" => [
                "type" => "box",
                "layout" => "vertical",
                "spacing" => "sm",
                "contents" => [
                    [
                        "type" => "button",
                        "style" => "primary",
                        "height" => "sm",
                        "action" => [
                            "type" => "uri",
                            "label" => "เข้าสู่ระบบ",
                            "uri" => $link
                        ]
                    ]
                ],
                "flex" => 0
            ]
        ];

        $lineService = new \App\Services\LineNotificationService();
        $sent = $lineService->sendFlex($lineUserId, "Patient Portal Access", $flexContent);

        echo json_encode(['success' => $sent]);
    }
}
