<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\DrugService;
use App\Services\PatientService;

/**
 * Label Controller (Phase 4)
 * Generates printer-friendly medication labels with QR codes
 */
class LabelController extends Controller
{
    private $drugService;
    private $patientService;
    
    public function __construct()
    {
        $this->drugService = new DrugService();
        $this->patientService = new PatientService();
    }
    
    /**
     * View: Generate and Display Label
     * GET /label/print/{dispense_id}/{item_id}
     */
    public function printLabel($dispenseId, $itemId)
    {
        // Fetch dispensing details
        // (Assuming a method to get specific dispensed item details)
        $db = \App\Core\Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT d.hn, d.patient_name, di.drug_id, dr.name as drug_name, 
                   dr.unit, di.quantity, dr.video_url, dr.storage_advice
            FROM dispensing d
            JOIN dispensing_items di ON d.id = di.dispense_id
            JOIN drugs dr ON di.drug_id = dr.id
            WHERE d.id = ? AND di.id = ?
        ");
        $stmt->execute([$dispenseId, $itemId]);
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$data) {
            die('Data not found');
        }
        
        // Generate Token for Patient Portal (Simulated)
        $token = base64_encode($data['hn']);
        $portalUrl = "http://localhost:8080/patient/v/" . $token;
        
        // QR Code Generation (Using a public API for simplicity in this demo)
        $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($portalUrl);
        
        $this->view('label/medication', [
            'data' => $data,
            'qr_url' => $qrUrl,
            'portal_url' => $portalUrl
        ]);
    }
}
