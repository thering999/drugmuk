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
        
        // In a real system, we'd fetch from a `consultations` table
        // For now, we list patients with recent chronic visit history
        $recentPatients = $this->patientService->getRecentPatients(10);

        return $this->view('telepharmacy/index', [
            'title' => 'Tele-pharmacy Dashboard',
            'patients' => $recentPatients
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
}
