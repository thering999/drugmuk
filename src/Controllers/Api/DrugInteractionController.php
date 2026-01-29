<?php
/**
 * Drug Interaction Controller
 * API สำหรับตรวจสอบ Drug Interactions
 * 
 * @package Drugmuk
 * @version 3.4.0
 */

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Services\DrugInteractionService;

class DrugInteractionController extends Controller
{
    private DrugInteractionService $interactionService;
    
    public function __construct()
    {
        $this->interactionService = new DrugInteractionService();
    }
    
    /**
     * ตรวจสอบ Drug Interactions
     * POST /api/drug-interaction/check
     * Body: { "drug_ids": [1, 2, 3] }
     */
    public function check()
    {
        header('Content-Type: application/json');
        
        $input = json_decode(file_get_contents('php://input'), true);
        $drugIds = $input['drug_ids'] ?? [];
        
        if (empty($drugIds) || !is_array($drugIds)) {
            echo json_encode([
                'success' => false,
                'error' => 'drug_ids is required and must be an array'
            ]);
            return;
        }
        
        $interactions = $this->interactionService->checkInteractions($drugIds);
        
        // Format response
        $formatted = array_map(function($interaction) {
            $severity = DrugInteractionService::formatSeverity($interaction['severity']);
            return [
                'drug1' => [
                    'id' => $interaction['drug1_id'],
                    'name' => $interaction['drug1_name']
                ],
                'drug2' => [
                    'id' => $interaction['drug2_id'],
                    'name' => $interaction['drug2_name']
                ],
                'severity' => $interaction['severity'],
                'severity_label' => $severity['label'],
                'severity_color' => $severity['color'],
                'severity_icon' => $severity['icon'],
                'effect' => $interaction['effect'],
                'recommendation' => $interaction['recommendation']
            ];
        }, $interactions);
        
        echo json_encode([
            'success' => true,
            'has_interactions' => !empty($interactions),
            'interaction_count' => count($interactions),
            'has_severe' => $this->hasSevereInteraction($interactions),
            'interactions' => $formatted
        ]);
    }
    
    /**
     * ตรวจสอบ Drug Interactions สำหรับผู้ป่วย
     * POST /api/drug-interaction/check-patient
     * Body: { "patient_hn": "12345", "drug_ids": [1, 2, 3] }
     */
    public function checkPatient()
    {
        header('Content-Type: application/json');
        
        $input = json_decode(file_get_contents('php://input'), true);
        $patientHN = $input['patient_hn'] ?? '';
        $drugIds = $input['drug_ids'] ?? [];
        
        if (empty($patientHN)) {
            echo json_encode(['success' => false, 'error' => 'patient_hn is required']);
            return;
        }
        
        if (empty($drugIds) || !is_array($drugIds)) {
            echo json_encode(['success' => false, 'error' => 'drug_ids is required']);
            return;
        }
        
        // Check drug-drug interactions
        $interactions = $this->interactionService->checkPatientInteractions($patientHN, $drugIds);
        
        // Check allergy interactions
        $allergyWarnings = $this->interactionService->checkAllergyInteraction($patientHN, $drugIds);
        
        // Combine and format
        $allWarnings = [];
        
        foreach ($allergyWarnings as $warning) {
            $allWarnings[] = [
                'type' => 'allergy',
                'severity' => 'contraindicated',
                'severity_label' => 'แพ้ยา',
                'severity_icon' => '🚨',
                'severity_color' => '#991b1b',
                'drug_name' => $warning['drug_name'],
                'message' => "ผู้ป่วยมีประวัติแพ้ยา {$warning['allergy_drug']}",
                'reaction' => $warning['reaction'],
                'recommendation' => '❌ ห้ามใช้ยานี้ - เลือกยาอื่นแทน'
            ];
        }
        
        foreach ($interactions as $interaction) {
            $severity = DrugInteractionService::formatSeverity($interaction['severity']);
            $allWarnings[] = [
                'type' => 'interaction',
                'severity' => $interaction['severity'],
                'severity_label' => $severity['label'],
                'severity_icon' => $severity['icon'],
                'severity_color' => $severity['color'],
                'drug1_name' => $interaction['drug1_name'],
                'drug2_name' => $interaction['drug2_name'],
                'message' => "{$interaction['drug1_name']} + {$interaction['drug2_name']}: {$interaction['effect']}",
                'effect' => $interaction['effect'],
                'recommendation' => $interaction['recommendation']
            ];
        }
        
        // Sort by severity
        usort($allWarnings, function($a, $b) {
            $order = ['contraindicated' => 4, 'major' => 3, 'moderate' => 2, 'minor' => 1];
            return ($order[$b['severity']] ?? 0) - ($order[$a['severity']] ?? 0);
        });
        
        $hasSevere = $this->hasSevereInteraction($interactions) || !empty($allergyWarnings);
        
        echo json_encode([
            'success' => true,
            'patient_hn' => $patientHN,
            'has_warnings' => !empty($allWarnings),
            'warning_count' => count($allWarnings),
            'has_severe' => $hasSevere,
            'has_allergy' => !empty($allergyWarnings),
            'warnings' => $allWarnings
        ]);
    }
    
    /**
     * ดึงรายการยาปัจจุบันของผู้ป่วย
     * GET /api/drug-interaction/patient-drugs/{hn}
     */
    public function getPatientDrugs($hn)
    {
        header('Content-Type: application/json');
        
        $db = \App\Core\Database::getInstance()->getConnection();
        
        $sql = "SELECT DISTINCT d.id, d.code, d.name, d.generic_name,
                       MAX(di.created_at) as last_dispensed
                FROM dispensing ds
                JOIN dispensing_items di ON ds.id = di.dispensing_id
                JOIN drugs d ON di.drug_id = d.id
                WHERE ds.patient_hn = :hn 
                AND ds.dispense_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                GROUP BY d.id, d.code, d.name, d.generic_name
                ORDER BY last_dispensed DESC";
        
        $stmt = $db->prepare($sql);
        $stmt->execute(['hn' => $hn]);
        
        echo json_encode([
            'success' => true,
            'patient_hn' => $hn,
            'drugs' => $stmt->fetchAll()
        ]);
    }
    
    /**
     * ดึงประวัติแพ้ยาของผู้ป่วย
     * GET /api/drug-interaction/patient-allergies/{hn}
     */
    public function getPatientAllergies($hn)
    {
        header('Content-Type: application/json');
        
        $db = \App\Core\Database::getInstance()->getConnection();
        
        $sql = "SELECT * FROM patient_allergies WHERE patient_hn = :hn ORDER BY created_at DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute(['hn' => $hn]);
        
        echo json_encode([
            'success' => true,
            'patient_hn' => $hn,
            'allergies' => $stmt->fetchAll()
        ]);
    }
    
    /**
     * ค้นหา Drug Interactions
     * GET /api/drug-interaction/search?drug=warfarin
     */
    public function search()
    {
        header('Content-Type: application/json');
        
        $drugName = $_GET['drug'] ?? '';
        
        if (empty($drugName)) {
            echo json_encode(['success' => false, 'error' => 'drug parameter is required']);
            return;
        }
        
        // Search in known interactions
        $knownInteractions = [
            'warfarin' => ['aspirin', 'ibuprofen', 'naproxen', 'fluconazole', 'metronidazole'],
            'metformin' => ['contrast media', 'alcohol'],
            'simvastatin' => ['erythromycin', 'clarithromycin', 'itraconazole', 'amlodipine'],
            'digoxin' => ['amiodarone', 'verapamil', 'quinidine'],
            'lithium' => ['ibuprofen', 'enalapril', 'hydrochlorothiazide'],
            'clopidogrel' => ['omeprazole', 'esomeprazole']
        ];
        
        $drugLower = strtolower($drugName);
        $results = [];
        
        foreach ($knownInteractions as $drug => $interactions) {
            if (strpos($drug, $drugLower) !== false || strpos($drugLower, $drug) !== false) {
                foreach ($interactions as $interacting) {
                    $results[] = [
                        'drug1' => $drug,
                        'drug2' => $interacting,
                        'severity' => 'major'
                    ];
                }
            }
            
            foreach ($interactions as $interacting) {
                if (strpos($interacting, $drugLower) !== false || strpos($drugLower, $interacting) !== false) {
                    $results[] = [
                        'drug1' => $drug,
                        'drug2' => $interacting,
                        'severity' => 'major'
                    ];
                }
            }
        }
        
        echo json_encode([
            'success' => true,
            'query' => $drugName,
            'count' => count($results),
            'interactions' => $results
        ]);
    }
    
    /**
     * ตรวจสอบว่ามี severe interaction หรือไม่
     */
    private function hasSevereInteraction(array $interactions): bool
    {
        foreach ($interactions as $interaction) {
            if (in_array($interaction['severity'], ['major', 'contraindicated'])) {
                return true;
            }
        }
        return false;
    }
}
