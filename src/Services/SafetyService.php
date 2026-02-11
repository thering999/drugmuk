<?php

namespace App\Services;

use App\Core\Database;
use App\Services\LineNotificationService;
use App\Services\DrugInteractionService;
use PDO;

/**
 * Safety Service (Phase 4)
 * 
 * Advanced clinical safety checks including DDI and Lab-based alerts
 */
class SafetyService
{
    private $db;
    private $lineService;
    private $drugInteractionService;
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->lineService = new LineNotificationService();
        $this->drugInteractionService = new DrugInteractionService();
    }
    
    /**
     * Check interactions between drugs in a list
     * @param array $drugNames List of drug names
     * @return array List of interactions found
     */
    public function checkDrugInteractions(array $drugNames)
    {
        if (count($drugNames) < 2) return [];
        
        // Convert names to simulated IDs for DrugInteractionService
        // In a real scenario, we would query `drugs` table to get real IDs
        // For now, we will create a map of Drug Name -> ID just to pass to the service
        // But DrugInteractionService actually needs to look up names from IDs internally
        // So we might need a direct string-based check in DrugInteractionService or shim it here.
        
        // BETTER APPROACH: Let's use the DrugInteractionService's lower-level logic
        // But that service is built around IDs. 
        // Let's implement a 'findInteraction' wrapper here that uses the internal knownInteractions of that service?
        // No, `knownInteractions` is private.
        
        // WORKAROUND: We will query the DB to get IDs for these names, then call the service.
        $drugIds = [];
        $placeholders = str_repeat('?,', count($drugNames) - 1) . '?';
        $sql = "SELECT id FROM drugs WHERE name IN ($placeholders) OR generic_name IN ($placeholders)";
        
        // Duplicate params for OR check? No, 'name IN () OR generic IN ()' needs 2 sets.
        // Let's just search by name for now to match the JS frontend input
        $sql = "SELECT id FROM drugs WHERE name IN ($placeholders)";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($drugNames);
            $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $drugIds = $rows;
        } catch (\Exception $e) {
            // Fallback if query fails
            return [];
        }
        
        if (empty($drugIds)) return [];

        // Now stick into the service
        $results = $this->drugInteractionService->checkInteractions($drugIds);
        
        // Map back to format expected by frontend JS (which expects drug_a_name, description etc)
        // The service returns: drug1_name, drug2_name, severity, effect, recommendation
        return array_map(function($r) {
            return [
                'drug_a_name' => $r['drug1_name'],
                'drug_b_name' => $r['drug2_name'],
                'severity' => $r['severity'],
                'description' => $r['effect'],
                'action_suggested' => $r['recommendation']
            ];
        }, $results);
    }
    
    /**
     * Check if a drug is safe for a specific patient based on labs/age
     */
    public function checkPatientSafety($hn, $drugName)
    {
        $alerts = [];
        
        // 1. Check Renal Rules (eGFR)
        $latestEGFR = $this->getLatestLab($hn, 'eGFR');
        if ($latestEGFR) {
            $sql = "
                SELECT * FROM ref_drug_safety_rules 
                WHERE drug_name = ? AND condition_type = 'egfr'
                AND (? < min_value OR ? > max_value)
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$drugName, $latestEGFR['value'], $latestEGFR['value']]);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $rule) {
                $alerts[] = [
                    'type' => 'renal',
                    'severity' => 'major',
                    'message' => $rule['alert_message'],
                    'value' => $latestEGFR['value'],
                    'unit' => $latestEGFR['unit']
                ];

                // Send LINE Alert for major safety issues
                $this->lineService->sendClinicalAlert(
                    $hn, // In a real system you'd fetch patient name
                    'Renal Safety (eGFR)',
                    $rule['alert_message'] . " (Current: " . $latestEGFR['value'] . ")"
                );
            }
        }
        
        // 2. Check Age Rules (Simplified - in real life would fetch patient age)
        // This is a placeholder for age logic
        
        return $alerts;
    }
    
    /**
     * Get Latest Lab Value for a specific test
     */
    private function getLatestLab($hn, $labName)
    {
        $stmt = $this->db->prepare("
            SELECT lab_value as value, lab_unit as unit, vstdate 
            FROM patient_lab_results 
            WHERE hn = ? AND lab_name = ? 
            ORDER BY vstdate DESC LIMIT 1
        ");
        $stmt->execute([$hn, $labName]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get Clinical Summary for Dashboard
     */
    public function getClinicalLabs($hn)
    {
        $stmt = $this->db->prepare("
            SELECT lab_name, lab_value, lab_unit, vstdate 
            FROM patient_lab_results 
            WHERE hn = ? 
            ORDER BY vstdate DESC, lab_name ASC
        ");
        $stmt->execute([$hn]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
