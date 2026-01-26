<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\PatientService;
use App\Services\DrugAllergyService;

/**
 * Patient Controller
 * 
 * Handle patient profile and related features
 */
class PatientController extends Controller
{
    private $patientService;
    private $allergyService;
    
    public function __construct()
    {
        $this->patientService = new PatientService();
        $this->allergyService = new DrugAllergyService();
    }
    
    /**
     * Search patients (AJAX)
     * 
     * GET /api/patient/search?q=keyword
     */
    public function search()
    {
        header('Content-Type: application/json');
        
        try {
            $keyword = $_GET['q'] ?? '';
            
            if (empty($keyword)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'กรุณาระบุคำค้นหา'
                ]);
                return;
            }
            
            $patients = $this->patientService->searchPatients($keyword, 20);
            
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
     * Get patient profile (AJAX)
     * 
     * GET /api/patient/{hn}
     */
    public function getProfile($hn)
    {
        header('Content-Type: application/json');
        
        try {
            $profile = $this->patientService->getProfileWithCache($hn);
            
            if ($profile) {
                echo json_encode([
                    'success' => true,
                    'patient' => $profile
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'ไม่พบข้อมูลผู้ป่วย'
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
     * Get patient chronic diseases (AJAX)
     * 
     * GET /api/patient/{hn}/chronic
     */
    public function getChronicDiseases($hn)
    {
        header('Content-Type: application/json');
        
        try {
            $diseases = $this->patientService->getPatientChronicDiseases($hn);
            
            echo json_encode([
                'success' => true,
                'diseases' => $diseases,
                'count' => count($diseases)
            ]);
            
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Get patient recent visits (AJAX)
     * 
     * GET /api/patient/{hn}/visits
     */
    public function getRecentVisits($hn)
    {
        header('Content-Type: application/json');
        
        try {
            $limit = $_GET['limit'] ?? 10;
            $visits = $this->patientService->getRecentVisits($hn, $limit);
            
            echo json_encode([
                'success' => true,
                'visits' => $visits,
                'count' => count($visits)
            ]);
            
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Get current medications (AJAX)
     * 
     * GET /api/patient/{hn}/medications
     */
    public function getCurrentMedications($hn)
    {
        header('Content-Type: application/json');
        
        try {
            $medications = $this->patientService->getCurrentMedications($hn);
            
            echo json_encode([
                'success' => true,
                'medications' => $medications,
                'count' => count($medications)
            ]);
            
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Get vital signs trends (AJAX)
     * 
     * GET /api/patient/{hn}/vitals
     */
    public function getVitalSigns($hn)
    {
        header('Content-Type: application/json');
        
        try {
            $months = $_GET['months'] ?? 6;
            $vitals = $this->patientService->getVitalSignsTrends($hn, $months);
            
            echo json_encode([
                'success' => true,
                'vitals' => $vitals,
                'count' => count($vitals)
            ]);
            
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get vaccination history (AJAX)
     * 
     * GET /api/patient/{hn}/vaccines
     */
    public function getVaccines($hn)
    {
        header('Content-Type: application/json');
        try {
            $db = \App\Core\Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT * FROM patient_vaccines_cache WHERE hn = ? ORDER BY vstdate DESC");
            $stmt->execute([$hn]);
            $vaccines = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'vaccines' => $vaccines]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Get screening history (AJAX)
     * 
     * GET /api/patient/{hn}/screening
     */
    public function getScreening($hn)
    {
        header('Content-Type: application/json');
        try {
            $db = \App\Core\Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT * FROM patient_screening_cache WHERE hn = ? ORDER BY vstdate DESC LIMIT 20");
            $stmt->execute([$hn]);
            $screening = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'screening' => $screening]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    /**
     * Sync patient profile from JHCIS (AJAX)
     * 
     * POST /api/patient/{hn}/sync
     */
    public function syncProfile($hn)
    {
        header('Content-Type: application/json');
        
        try {
            $success = $this->patientService->cachePatientProfile($hn);
            
            if ($success) {
                echo json_encode([
                    'success' => true,
                    'message' => 'ซิงค์ข้อมูลสำเร็จ'
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
     * Get patient adherence history (AJAX)
     * 
     * GET /api/patient/{hn}/adherence
     */
    public function getAdherenceHistory($hn)
    {
        header('Content-Type: application/json');
        try {
            $db = \App\Core\Database::getInstance()->getConnection();
            $stmt = $db->prepare("
                SELECT dispense_id, status, taken_at, notes, created_at 
                FROM patient_adherence_logs 
                WHERE hn = ? 
                ORDER BY created_at DESC 
                LIMIT 50
            ");
            $stmt->execute([$hn]);
            $logs = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'logs' => $logs
            ]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Patient dashboard page
     * 
     * GET /patient/{hn}
     */
    public function dashboard($hn)
    {
        $profile = $this->patientService->getProfileWithCache($hn);
        
        if (!$profile) {
            $_SESSION['error'] = 'ไม่พบข้อมูลผู้ป่วย';
            header('Location: /');
            exit;
        }
        
        $this->view('patient/dashboard', [
            'patient' => $profile,
            'hn' => $hn
        ]);
    }
    
    /**
     * Patient search page
     * 
     * GET /patient/search
     */
    public function searchPage()
    {
        $this->view('patient/search');
    }
}
