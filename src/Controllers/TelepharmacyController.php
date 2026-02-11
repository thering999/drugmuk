<?php

namespace App\Controllers;

use App\Core\Config;
use App\Services\PatientService;

/**
 * Telepharmacy Controller (Phase 6)
 * 
 * Manages video consultation sessions between Pharmacists and Patients
 */
class TelepharmacyController extends BaseController
{
    private $patientService;

    public function __construct()
    {
        parent::__construct();
        $this->patientService = new PatientService();
    }

    /**
     * Start a consultation room
     */
    public function room($hn = null)
    {
        $this->requireAuth();
        
        $patient = null;
        if ($hn) {
            $patient = $this->patientService->getPatientByHN($hn);
        }

        // Generate a secure, unique room name
        // Pattern: drugmuk-consult-[HASH]
        $roomName = "drugmuk-consult-" . md5(($hn ?: 'guest') . date('Y-m-d') . Config::get('APP_KEY'));

        return $this->view('telepharmacy/room', [
            'title' => 'Tele-pharmacy Consultation',
            'roomName' => $roomName,
            'patient' => $patient,
            'isPharmacist' => $_SESSION['role'] === 'admin' || $_SESSION['role'] === 'pharmacist'
        ]);
    }

    /**
     * List active or scheduled consultations (Dashboard)
     */
    public function dashboard()
    {
        $this->requireAuth();
        
        $intelService = new \App\Services\IntelligenceService();
        
        // Fetch prioritized patients (those with high risk or chronic)
        $recentPatients = $this->patientService->getRecentPatients(20);
        
        // Enrich patients with AI Risk Scores
        foreach ($recentPatients as &$p) {
            $insight = $intelService->getPatientInsight($p['hn']);
            if ($insight) {
                $p['ai_risk_score'] = $insight['score'];
                $p['ai_risk_level'] = ($insight['score'] > 50) ? 'Critical' : (($insight['score'] > 30) ? 'High' : 'Low');
                $p['ai_summary'] = $insight['summary'];
                $p['ai_alerts_count'] = count($insight['alerts']);
            } else {
                $p['ai_risk_score'] = 0;
                $p['ai_risk_level'] = 'Low';
                $p['ai_alerts_count'] = 0;
            }
            
            // Get last SOAP summary if available
            $stmt = $this->db->prepare("SELECT notes FROM patient_clinical_notes WHERE hn = ? AND note_type = 'telepharmacy' ORDER BY created_at DESC LIMIT 1");
            $stmt->execute([$p['hn']]);
            $lastNote = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($lastNote && strpos($lastNote['notes'], '[S]:') !== false) {
                $p['last_soap'] = $lastNote['notes'];
            }
        }

        // Sort by risk (Critical first)
        usort($recentPatients, function($a, $b) {
            return $b['ai_risk_score'] <=> $a['ai_risk_score'];
        });

        // Get missing lab alerts for priority list
        $missingLabAlerts = [];
        foreach ($recentPatients as $p) {
            $labs = $intelService->getClinicalMonitoringAdvisor($p['hn']);
            foreach ($labs as $l) {
                if ($l['status'] === 'Missing') {
                    $missingLabAlerts[] = [
                        'hn' => $p['hn'],
                        'name' => $p['first_name'] . ' ' . $p['last_name'],
                        'lab' => $l['lab'],
                        'drug' => $l['drug']
                    ];
                }
            }
            if (count($missingLabAlerts) >= 5) break;
        }

        return $this->view('telepharmacy/index', [
            'title' => 'AI Tele-pharmacy Dashboard',
            'patients' => $recentPatients,
            'missingLabs' => $missingLabAlerts,
            'stats' => $intelService->getExtendedDashboardStats()
        ]);
    }


    /**
     * API: Save clinical notes from a video session
     */
    public function saveSessionNotes()
    {
        $this->requireAuth();
        
        $data = json_decode(file_get_contents('php://input'), true);
        $hn = $data['hn'] ?? null;
        $notes = $data['notes'] ?? '';

        if (!$hn) {
            return $this->json(['success' => false, 'message' => 'HN is required']);
        }

        // Save to patient_clinical_notes (Part of Safety/Intelligence)
        $stmt = $this->db->prepare("
            INSERT INTO patient_clinical_notes (hn, staff_id, note_type, notes, created_at)
            VALUES (?, ?, 'telepharmacy', ?, NOW())
        ");
        
        $success = $stmt->execute([$hn, $_SESSION['user_id'], $notes]);

        return $this->json(['success' => $success]);
    }
    /**
     * API: Send invitation link via LINE
     */
    public function sendInvite()
    {
        $this->requireAuth();
        
        $data = json_decode(file_get_contents('php://input'), true);
        $hn = $data['hn'] ?? null;
        $roomName = $data['roomName'] ?? null;
        
        if (!$hn || !$roomName) {
            return $this->json(['success' => false, 'message' => 'Missing HN or Room Name']);
        }

        try {
            // In a real scenario, we would look up the patient's LINE User ID
            // $patientLineId = $this->patientService->getLineId($hn);
            
            // For Demo: Send to Admin/Doctor LINE
            $lineService = new \App\Services\LineNotificationService();
            $inviteLink = Config::get('APP_URL') . "/tele-pharmacy/room/" . $hn;
            
            $message = "🏥 *Digital Pharmacy Invitation*\n";
            $message .= "เภสัชกรได้เชิญคุณเข้ารับคำปรึกษาออนไลน์\n";
            $message .= "ผู้ป่วย: " . $hn . "\n";
            $message .= "-----------------------\n";
            $message .= "คลิกเพื่อเข้าร่วม: " . $inviteLink;
            
            // Assuming we send to the configured Admin ID for the demo
            $adminId = Config::get('LINE_ADMIN_USER_ID');
            if ($adminId) {
                $lineService->sendPush($adminId, $message);
                return $this->json(['success' => true, 'message' => 'Sent to LINE']);
            } else {
                return $this->json(['success' => false, 'message' => 'LINE config missing']);
            }
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    /**
     * API: Analyze clinical notes with AI
     */
    public function analyzeNote()
    {
        $this->requireAuth();
        
        $data = json_decode(file_get_contents('php://input'), true);
        $notes = $data['notes'] ?? '';
        $hn = $data['hn'] ?? null; // Added HN

        if (empty($notes)) {
            return $this->json(['success' => false, 'message' => 'No notes provided']);
        }

        $intelService = new \App\Services\IntelligenceService();
        $analysis = $intelService->analyzeClinicalNote($notes, $hn); // Pass HN

        return $this->json(['success' => true, 'analysis' => $analysis]);
    }

    /**
     * API: Log Clinical Intervention (AI Verified)
     */
    public function logIntervention()
    {
        $this->requireAuth();
        
        $data = json_decode(file_get_contents('php://input'), true);
        $hn = $data['hn'] ?? null;
        $type = $data['type'] ?? 'General Intervention';
        $details = $data['details'] ?? '';
        $severity = $data['severity'] ?? 'Moderate';
        
        if (!$hn) {
            return $this->json(['success' => false, 'message' => 'HN is required']);
        }

        // Ensure table exists (Safe for demo/prototyping)
        try {
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS clinical_interventions (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    hn VARCHAR(50) NOT NULL,
                    staff_id INT NOT NULL,
                    intervention_type VARCHAR(100),
                    details TEXT,
                    severity VARCHAR(20),
                    status VARCHAR(20) DEFAULT 'Logged',
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )
            ");
            
            $stmt = $this->db->prepare("
                INSERT INTO clinical_interventions (hn, staff_id, intervention_type, details, severity, status, created_at)
                VALUES (?, ?, ?, ?, ?, 'Logged', NOW())
            ");
            
            $success = $stmt->execute([$hn, $_SESSION['user_id'], $type, $details, $severity]);
            
            // NEW: Send LINE notification for Major/Critical severity
            if ($success && (strtolower($severity) === 'major' || strtolower($severity) === 'critical')) {
                $lineService = new \App\Services\LineNotificationService();
                $patientService = new \App\Services\PatientService();
                $patient = $patientService->getPatientByHN($hn);
                $patientName = $patient ? ($patient['first_name'] . ' ' . $patient['last_name']) : $hn;
                
                $lineService->sendClinicalAlert($patientName, $type, $details);
            }
            
            return $this->json(['success' => $success]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
