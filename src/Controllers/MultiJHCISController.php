<?php

namespace App\Controllers;

use App\Services\JHCIS\JHCISSyncService;
use App\Models\Hospital;

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
            $pcucode = $_POST['pcucode'] ?? null;
            
            if (empty($code) || empty($name) || empty($dbName)) {
                throw new \Exception('กรุณากรอกข้อมูลให้ครบถ้วน');
            }
            
            $hospitalModel = new \App\Models\Hospital();
            $hospitalModel->create([
                'code' => $code,
                'name' => $name,
                'db_host' => $dbHost,
                'db_port' => $dbPort,
                'db_name' => $dbName,
                'db_user' => $dbUser,
                'db_pass' => $dbPass,
                'pcucode' => $pcucode,
                'is_active' => 1
            ]);
            
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
            $pcucode = $_POST['pcucode'] ?? null;
            $isActive = $_POST['is_active'] ?? 1;
            
            $data = [
                'code' => $code,
                'name' => $name,
                'db_host' => $dbHost,
                'db_port' => $dbPort,
                'db_name' => $dbName,
                'db_user' => $dbUser,
                'pcucode' => $pcucode,
                'is_active' => $isActive
            ];
            
            // Only update password if provided
            if (!empty($dbPass)) {
                $data['db_pass'] = $dbPass;
            }
            
            $hospitalModel = new \App\Models\Hospital();
            $hospitalModel->update($id, $data);
            
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
    
    /**
     * Reports Page
     */
    public function reports()
    {
        try {
            // Get all hospitals for selection (same as hospital management page)
            $stmt = $this->db->query("SELECT id, code, name, pcucode, is_active FROM jhcis_hospitals ORDER BY name");
            $hospitals = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            // Debug: check if we have hospitals
            if (empty($hospitals)) {
                error_log("WARNING: No hospitals found in jhcis_hospitals table");
                // Show visible error for debugging
                $debugError = "⚠️ DEBUG: No hospitals found in database. Please check if jhcis_hospitals table has data.";
                $hospitals = []; // Ensure it's an array
            } else {
                error_log("DEBUG: Found " . count($hospitals) . " hospitals");
                foreach ($hospitals as $h) {
                    error_log("DEBUG Hospital: ID={$h['id']}, Name={$h['name']}, Code={$h['code']}, PCU={$h['pcucode']}, Active={$h['is_active']}");
                }
                $debugError = null;
            }
            
            $hospitalId = $_GET['hospital_id'] ?? null;
            
            include __DIR__ . '/../Views/jhcis/reports.php';
            
        } catch (\PDOException $e) {
            error_log("Database error in reports(): " . $e->getMessage());
            echo "<h1>Database Error</h1>";
            echo "<p style='color: red;'>Failed to load hospitals: " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<p>Error Code: " . $e->getCode() . "</p>";
            echo "<p><a href='/admin/jhcis/hospitals'>Go to Hospital Management</a></p>";
        } catch (\Exception $e) {
            error_log("Error loading reports page: " . $e->getMessage());
            echo "<h1>Error</h1>";
            echo "<p style='color: red;'>Failed to load reports page: " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<p><a href='/admin/jhcis/hospitals'>Go to Hospital Management</a> to add hospitals first.</p>";
        }
    }
    
    /**
     * API: Get hospitals list (Fallback for frontend)
     */
    public function getHospitalsAPI()
    {
        header('Content-Type: application/json');
        try {
            // Using the manual connection logic (duplicated for safety) or simpler query
            $stmt = $this->db->query("SELECT id, code, name, pcucode, is_active FROM jhcis_hospitals ORDER BY name");
            $hospitals = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $hospitals]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }
    
    /**
     * API Debug Page
     */
    public function apiDebug()
    {
        include __DIR__ . '/../Views/jhcis/api-debug.php';
    }
}
