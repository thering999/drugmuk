<?php

namespace App\Services;

use App\Core\Database;
use App\Services\LineNotificationService;
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
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->lineService = new LineNotificationService();
    }
    
    /**
     * Check interactions between drugs in a list
     * @param array $drugNames List of drug names
     * @return array List of interactions found
     */
    public function checkDrugInteractions(array $drugNames)
    {
        if (count($drugNames) < 2) return [];
        
        $interactions = [];
        $placeholders = str_repeat('?,', count($drugNames) - 1) . '?';
        
        // Find all interactions where both drug names are in our list
        $sql = "
            SELECT * FROM ref_drug_interactions 
            WHERE drug_a_name IN ($placeholders) 
            AND drug_b_name IN ($placeholders)
        ";
        
        $params = array_merge($drugNames, $drugNames);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
