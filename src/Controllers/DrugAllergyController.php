<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\DrugAllergyService;

/**
 * Drug Allergy Controller
 * 
 * Handle drug allergy checking and patient safety features
 */
class DrugAllergyController extends Controller
{
    private $allergyService;
    
    public function __construct()
    {
        $this->allergyService = new DrugAllergyService();
    }
    
    /**
     * Check drug allergy for patient (AJAX)
     * 
     * POST /api/allergy/check
     * Body: { "hn": "0000001", "drug_name": "Amoxicillin" }
     */
    public function check()
    {
        header('Content-Type: application/json');
        
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            $hn = $data['hn'] ?? '';
            $drugName = $data['drug_name'] ?? '';
            
            if (empty($hn) || empty($drugName)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'กรุณาระบุ HN และชื่อยา'
                ]);
                return;
            }
            
            // Check allergy
            $allergy = $this->allergyService->checkDrugAllergy($hn, $drugName);
            
            // Log the check
            $userId = $_SESSION['user_id'] ?? 1;
            $this->allergyService->logAllergyCheck($hn, $drugName, $allergy !== null, $userId);
            
            if ($allergy) {
                $severity = $this->allergyService->getSeverityInfo($allergy['severity'] ?? '');
                
                echo json_encode([
                    'success' => true,
                    'has_allergy' => true,
                    'allergy' => [
                        'name' => $allergy['allergy_name'],
                        'symptom' => $allergy['symptom'] ?? '',
                        'severity' => $severity,
                        'date_recorded' => $allergy['daterecord'] ?? ''
                    ],
                    'message' => '⚠️ ผู้ป่วยแพ้ยานี้!'
                ]);
            } else {
                echo json_encode([
                    'success' => true,
                    'has_allergy' => false,
                    'message' => 'ไม่พบประวัติแพ้ยา'
                ]);
            }
            
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Check multiple drugs for allergies (AJAX)
     * 
     * POST /api/allergy/check-multiple
     * Body: { "hn": "0000001", "drugs": ["Amoxicillin", "Paracetamol"] }
     */
    public function checkMultiple()
    {
        header('Content-Type: application/json');
        
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            $hn = $data['hn'] ?? '';
            $drugs = $data['drugs'] ?? [];
            
            if (empty($hn) || empty($drugs)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'กรุณาระบุ HN และรายการยา'
                ]);
                return;
            }
            
            // Check allergies
            $allergies = $this->allergyService->checkMultipleDrugs($hn, $drugs);
            
            $results = [];
            foreach ($allergies as $item) {
                $severity = $this->allergyService->getSeverityInfo($item['allergy']['severity'] ?? '');
                $results[] = [
                    'drug' => $item['drug'],
                    'allergy_name' => $item['allergy']['allergy_name'],
                    'symptom' => $item['allergy']['symptom'] ?? '',
                    'severity' => $severity
                ];
            }
            
            echo json_encode([
                'success' => true,
                'has_allergy' => !empty($allergies),
                'allergies' => $results,
                'count' => count($results)
            ]);
            
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Get patient allergies (AJAX)
     * 
     * GET /api/allergy/patient/{hn}
     */
    public function getPatientAllergies($hn)
    {
        header('Content-Type: application/json');
        
        try {
            // Get allergies with cache
            $allergies = $this->allergyService->getAllergiesWithCache($hn);
            
            $results = [];
            foreach ($allergies as $allergy) {
                $severity = $this->allergyService->getSeverityInfo($allergy['severity'] ?? '');
                $results[] = [
                    'allergy_name' => $allergy['allergy_name'],
                    'symptom' => $allergy['symptom'] ?? '',
                    'severity' => $severity,
                    'date_recorded' => $allergy['date_recorded'] ?? ''
                ];
            }
            
            echo json_encode([
                'success' => true,
                'allergies' => $results,
                'count' => count($results)
            ]);
            
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Sync patient allergies from JHCIS (AJAX)
     * 
     * POST /api/allergy/sync
     * Body: { "hn": "0000001" }
     */
    public function sync()
    {
        header('Content-Type: application/json');
        
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $hn = $data['hn'] ?? '';
            
            if (empty($hn)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'กรุณาระบุ HN'
                ]);
                return;
            }
            
            $success = $this->allergyService->syncAllergies($hn);
            
            if ($success) {
                $allergies = $this->allergyService->getCachedAllergies($hn, 1);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'ซิงค์ข้อมูลสำเร็จ',
                    'count' => count($allergies ?? [])
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'ไม่สามารถซิงค์ข้อมูลได้'
                ]);
            }
            
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Get allergy check statistics
     * 
     * GET /api/allergy/statistics
     */
    public function statistics()
    {
        header('Content-Type: application/json');
        
        try {
            $startDate = $_GET['start_date'] ?? date('Y-m-01');
            $endDate = $_GET['end_date'] ?? date('Y-m-d');
            
            $stats = $this->allergyService->getStatistics($startDate, $endDate);
            
            echo json_encode([
                'success' => true,
                'statistics' => $stats,
                'period' => [
                    'start' => $startDate,
                    'end' => $endDate
                ]
            ]);
            
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Allergy management page
     * 
     * GET /allergy/manage
     */
    public function manage()
    {
        $this->view('allergy/manage');
    }
    
    /**
     * Allergy statistics page
     * 
     * GET /allergy/stats
     */
    public function stats()
    {
        $startDate = $_GET['start_date'] ?? date('Y-m-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-d');
        
        $statistics = $this->allergyService->getStatistics($startDate, $endDate);
        
        $this->view('allergy/statistics', [
            'statistics' => $statistics,
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);
    }
}
