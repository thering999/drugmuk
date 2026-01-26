<?php

namespace App\Controllers;

use App\Services\JHCIS\JHCISSyncService;

/**
 * Multi-JHCIS Hospital Management Controller
 */
class MultiJHCISController
{
    private $db;
    
    public function __construct()
    {
        $this->db = \App\Core\Database::getInstance()->getConnection();
    }
    
    /**
     * Hospital Management Page
     */
    public function index()
    {
        // Get all hospitals
        $stmt = $this->db->query("SELECT * FROM jhcis_hospitals ORDER BY name");
        $hospitals = $stmt->fetchAll();
        
        include __DIR__ . '/../Views/jhcis/hospitals.php';
    }
    
    /**
     * Add Hospital (API)
     */
    public function addHospital()
    {
        header('Content-Type: application/json');
        
        try {
            $code = $_POST['code'] ?? '';
            $name = $_POST['name'] ?? '';
            $dbHost = $_POST['db_host'] ?? 'localhost';
            $dbPort = $_POST['db_port'] ?? 3306;
            $dbName = $_POST['db_name'] ?? '';
            $dbUser = $_POST['db_user'] ?? '';
            $dbPass = $_POST['db_pass'] ?? '';
            
            if (empty($code) || empty($name) || empty($dbName)) {
                throw new \Exception('กรุณากรอกข้อมูลให้ครบถ้วน');
            }
            
            $stmt = $this->db->prepare(
                "INSERT INTO jhcis_hospitals 
                 (code, name, db_host, db_port, db_name, db_user, db_pass, is_active)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 1)"
            );
            
            $stmt->execute([$code, $name, $dbHost, $dbPort, $dbName, $dbUser, $dbPass]);
            
            echo json_encode([
                'success' => true,
                'message' => 'เพิ่มโรงพยาบาลสำเร็จ'
            ]);
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }
    
    /**
     * Update Hospital (API)
     */
    public function updateHospital()
    {
        header('Content-Type: application/json');
        
        try {
            $id = $_POST['id'] ?? 0;
            $code = $_POST['code'] ?? '';
            $name = $_POST['name'] ?? '';
            $dbHost = $_POST['db_host'] ?? 'localhost';
            $dbPort = $_POST['db_port'] ?? 3306;
            $dbName = $_POST['db_name'] ?? '';
            $dbUser = $_POST['db_user'] ?? '';
            $dbPass = $_POST['db_pass'] ?? '';
            $isActive = $_POST['is_active'] ?? 1;
            
            $stmt = $this->db->prepare(
                "UPDATE jhcis_hospitals 
                 SET code = ?, name = ?, db_host = ?, db_port = ?, 
                     db_name = ?, db_user = ?, db_pass = ?, is_active = ?
                 WHERE id = ?"
            );
            
            $stmt->execute([$code, $name, $dbHost, $dbPort, $dbName, $dbUser, $dbPass, $isActive, $id]);
            
            echo json_encode([
                'success' => true,
                'message' => 'อัพเดทสำเร็จ'
            ]);
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }
    
    /**
     * Delete Hospital (API)
     */
    public function deleteHospital()
    {
        header('Content-Type: application/json');
        
        try {
            $id = $_POST['id'] ?? 0;
            
            $stmt = $this->db->prepare("DELETE FROM jhcis_hospitals WHERE id = ?");
            $stmt->execute([$id]);
            
            echo json_encode([
                'success' => true,
                'message' => 'ลบสำเร็จ'
            ]);
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }
    
    /**
     * Sync All Hospitals
     */
    public function syncAll()
    {
        header('Content-Type: application/json');
        
        try {
            $syncService = new JHCISSyncService();
            
            $fromDate = date('Y-m-d', strtotime('-30 days'));
            $toDate = date('Y-m-d');
            
            $results = $syncService->syncAllHospitals($fromDate, $toDate);
            
            echo json_encode([
                'success' => true,
                'results' => $results
            ]);
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }
}
