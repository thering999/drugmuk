<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\ChronicDiseaseService;

/**
 * Chronic Disease Controller
 * 
 * Manage chronic patients and refills
 */
class ChronicDiseaseController extends Controller
{
    private $chronicService;
    
    public function __construct()
    {
        $this->chronicService = new ChronicDiseaseService();
    }
    
    /**
     * Get chronic patients list (AJAX)
     * 
     * GET /api/chronic/patients
     */
    public function getPatients()
    {
        header('Content-Type: application/json');
        
        try {
            $filters = [
                'disease' => $_GET['disease'] ?? null,
                'search' => $_GET['search'] ?? null
            ];
            
            $patients = $this->chronicService->getChronicPatients($filters);
            
            echo json_encode([
                'success' => true,
                'patients' => $patients,
                'count' => count($patients)
            ]);
            
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Get refill schedule for a patient (AJAX)
     * 
     * GET /api/chronic/{hn}/refills
     */
    public function getRefillSchedule($hn)
    {
        header('Content-Type: application/json');
        
        try {
            $schedule = $this->chronicService->getRefillSchedule($hn);
            
            echo json_encode([
                'success' => true,
                'schedule' => $schedule,
                'count' => count($schedule)
            ]);
            
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Get patients due for refill (AJAX)
     * 
     * GET /api/chronic/due-refills
     */
    public function getDueRefills()
    {
        header('Content-Type: application/json');
        
        try {
            $days = $_GET['days'] ?? 7;
            $patients = $this->chronicService->getPatientsDueForRefill($days);
            
            echo json_encode([
                'success' => true,
                'patients' => $patients,
                'count' => count($patients)
            ]);
            
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Get overdue patients (AJAX)
     * 
     * GET /api/chronic/overdue
     */
    public function getOverdue()
    {
        header('Content-Type: application/json');
        
        try {
            $patients = $this->chronicService->getOverduePatients();
            
            echo json_encode([
                'success' => true,
                'patients' => $patients,
                'count' => count($patients)
            ]);
            
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Send refill reminder (AJAX)
     * 
     * POST /api/chronic/send-reminder
     */
    public function sendReminder()
    {
        header('Content-Type: application/json');
        
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            $hn = $data['hn'] ?? null;
            $drugName = $data['drug_name'] ?? null;
            $nextRefillDate = $data['next_refill_date'] ?? null;
            $channel = $data['channel'] ?? 'sms';
            
            if (!$hn || !$drugName || !$nextRefillDate) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ข้อมูลไม่ครบถ้วน'
                ]);
                return;
            }
            
            $success = $this->chronicService->sendRefillReminder($hn, $drugName, $nextRefillDate, $channel);
            
            if ($success) {
                echo json_encode([
                    'success' => true,
                    'message' => 'ส่งการแจ้งเตือนสำเร็จ'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'ไม่สามารถส่งการแจ้งเตือนได้'
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
     * Get chronic statistics (AJAX)
     * 
     * GET /api/chronic/statistics
     */
    public function getStatistics()
    {
        header('Content-Type: application/json');
        
        try {
            $stats = $this->chronicService->getStatistics();
            
            echo json_encode([
                'success' => true,
                'statistics' => $stats
            ]);
            
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Chronic Disease Dashboard page
     * 
     * GET /chronic/dashboard
     */
    public function dashboard()
    {
        $this->view('chronic/dashboard');
    }
}
