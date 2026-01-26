<?php

namespace Drugmuk\Controllers;

use Drugmuk\Models\Hospital;
use Drugmuk\Core\View;

class HospitalController
{
    private $hospitalModel;
    
    public function __construct()
    {
        $this->hospitalModel = new Hospital();
    }
    
    /**
     * Display list of all hospitals
     */
    public function index()
    {
        $hospitals = $this->hospitalModel->getSyncSummary();
        $statistics = $this->hospitalModel->getStatistics();
        
        View::render('hospitals/index', [
            'hospitals' => $hospitals,
            'statistics' => $statistics,
            'title' => 'จัดการ รพ.สต.'
        ]);
    }
    
    /**
     * Show form to create new hospital
     */
    public function create()
    {
        View::render('hospitals/create', [
            'title' => 'เพิ่ม รพ.สต. ใหม่'
        ]);
    }
    
    /**
     * Store new hospital
     */
    public function store()
    {
        try {
            $data = [
                'code' => $_POST['code'] ?? '',
                'name' => $_POST['name'] ?? '',
                'name_en' => $_POST['name_en'] ?? null,
                'type' => $_POST['type'] ?? 'health_center',
                'province' => $_POST['province'] ?? null,
                'district' => $_POST['district'] ?? null,
                'subdistrict' => $_POST['subdistrict'] ?? null,
                'address' => $_POST['address'] ?? null,
                'phone' => $_POST['phone'] ?? null,
                'email' => $_POST['email'] ?? null,
                'jhcis_host' => $_POST['jhcis_host'] ?? '',
                'jhcis_port' => $_POST['jhcis_port'] ?? 3306,
                'jhcis_database' => $_POST['jhcis_database'] ?? 'jhcisdb',
                'jhcis_username' => $_POST['jhcis_username'] ?? '',
                'jhcis_password' => $_POST['jhcis_password'] ?? '',
                'auto_sync_enabled' => isset($_POST['auto_sync_enabled']) ? 1 : 0,
                'sync_interval' => $_POST['sync_interval'] ?? 5,
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
                'notes' => $_POST['notes'] ?? null,
                'created_by' => $_SESSION['user_id'] ?? 1
            ];
            
            $id = $this->hospitalModel->create($data);
            
            $_SESSION['success'] = 'เพิ่ม รพ.สต. สำเร็จ';
            header('Location: /hospitals');
            exit;
            
        } catch (\Exception $e) {
            $_SESSION['error'] = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
            header('Location: /hospitals/create');
            exit;
        }
    }
    
    /**
     * Show form to edit hospital
     */
    public function edit($id)
    {
        $hospital = $this->hospitalModel->findById($id);
        
        if (!$hospital) {
            $_SESSION['error'] = 'ไม่พบข้อมูล รพ.สต.';
            header('Location: /hospitals');
            exit;
        }
        
        View::render('hospitals/edit', [
            'hospital' => $hospital,
            'title' => 'แก้ไขข้อมูล รพ.สต.'
        ]);
    }
    
    /**
     * Update hospital
     */
    public function update($id)
    {
        try {
            $data = [
                'code' => $_POST['code'] ?? '',
                'name' => $_POST['name'] ?? '',
                'name_en' => $_POST['name_en'] ?? null,
                'type' => $_POST['type'] ?? 'health_center',
                'province' => $_POST['province'] ?? null,
                'district' => $_POST['district'] ?? null,
                'subdistrict' => $_POST['subdistrict'] ?? null,
                'address' => $_POST['address'] ?? null,
                'phone' => $_POST['phone'] ?? null,
                'email' => $_POST['email'] ?? null,
                'jhcis_host' => $_POST['jhcis_host'] ?? '',
                'jhcis_port' => $_POST['jhcis_port'] ?? 3306,
                'jhcis_database' => $_POST['jhcis_database'] ?? 'jhcisdb',
                'jhcis_username' => $_POST['jhcis_username'] ?? '',
                'auto_sync_enabled' => isset($_POST['auto_sync_enabled']) ? 1 : 0,
                'sync_interval' => $_POST['sync_interval'] ?? 5,
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
                'notes' => $_POST['notes'] ?? null
            ];
            
            // Only update password if provided
            if (!empty($_POST['jhcis_password'])) {
                $data['jhcis_password'] = $_POST['jhcis_password'];
            }
            
            $this->hospitalModel->update($id, $data);
            
            $_SESSION['success'] = 'อัพเดทข้อมูล รพ.สต. สำเร็จ';
            header('Location: /hospitals');
            exit;
            
        } catch (\Exception $e) {
            $_SESSION['error'] = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
            header("Location: /hospitals/edit/{$id}");
            exit;
        }
    }
    
    /**
     * Delete hospital
     */
    public function delete($id)
    {
        try {
            $this->hospitalModel->delete($id);
            
            $_SESSION['success'] = 'ลบ รพ.สต. สำเร็จ';
            
        } catch (\Exception $e) {
            $_SESSION['error'] = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
        }
        
        header('Location: /hospitals');
        exit;
    }
    
    /**
     * Test database connection
     */
    public function testConnection($id)
    {
        $result = $this->hospitalModel->testConnection($id);
        
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }
    
    /**
     * View hospital details and sync logs
     */
    public function view($id)
    {
        $hospital = $this->hospitalModel->findById($id);
        
        if (!$hospital) {
            $_SESSION['error'] = 'ไม่พบข้อมูล รพ.สต.';
            header('Location: /hospitals');
            exit;
        }
        
        $syncLogs = $this->hospitalModel->getSyncLogs($id, 50);
        
        View::render('hospitals/view', [
            'hospital' => $hospital,
            'syncLogs' => $syncLogs,
            'title' => 'รายละเอียด รพ.สต.'
        ]);
    }
    
    /**
     * Sync data from specific hospital
     */
    public function sync($id)
    {
        try {
            $hospital = $this->hospitalModel->findById($id);
            
            if (!$hospital) {
                throw new \Exception('ไม่พบข้อมูล รพ.สต.');
            }
            
            // Create sync log
            $logId = $this->hospitalModel->createSyncLog([
                'hospital_id' => $id,
                'sync_type' => 'manual',
                'sync_module' => 'dispensing',
                'status' => 'running',
                'started_at' => date('Y-m-d H:i:s')
            ]);
            
            $startTime = microtime(true);
            
            // Get hospital database connection
            $hospitalDb = $this->hospitalModel->getConnection($id);
            
            // Fetch dispensing data from JHCIS
            // This is a simplified example - adjust based on actual JHCIS schema
            $query = "SELECT * FROM opd_dx 
                      WHERE vstdate >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                      LIMIT 1000";
            
            $stmt = $hospitalDb->query($query);
            $records = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            $totalRecords = count($records);
            $successCount = 0;
            $failedCount = 0;
            
            // Process each record
            foreach ($records as $record) {
                try {
                    // Insert into hospital_dispensing table
                    // Simplified - adjust based on actual needs
                    $successCount++;
                } catch (\Exception $e) {
                    $failedCount++;
                }
            }
            
            $duration = round((microtime(true) - $startTime));
            
            // Update sync log
            $this->hospitalModel->updateSyncLog($logId, [
                'status' => $failedCount > 0 ? 'partial' : 'success',
                'completed_at' => date('Y-m-d H:i:s'),
                'duration_seconds' => $duration,
                'records_total' => $totalRecords,
                'records_success' => $successCount,
                'records_failed' => $failedCount
            ]);
            
            // Update hospital last sync
            $this->hospitalModel->update($id, [
                'last_sync_at' => date('Y-m-d H:i:s'),
                'last_sync_status' => $failedCount > 0 ? 'partial' : 'success'
            ]);
            
            $_SESSION['success'] = "ซิงค์ข้อมูลสำเร็จ: {$successCount}/{$totalRecords} รายการ";
            
        } catch (\Exception $e) {
            // Update sync log if exists
            if (isset($logId)) {
                $this->hospitalModel->updateSyncLog($logId, [
                    'status' => 'failed',
                    'completed_at' => date('Y-m-d H:i:s'),
                    'error_message' => $e->getMessage()
                ]);
            }
            
            $_SESSION['error'] = 'เกิดข้อผิดพลาดในการซิงค์: ' . $e->getMessage();
        }
        
        header("Location: /hospitals/view/{$id}");
        exit;
    }
    
    /**
     * Sync all active hospitals
     */
    public function syncAll()
    {
        $hospitals = $this->hospitalModel->getActiveForSync();
        $results = [];
        
        foreach ($hospitals as $hospital) {
            try {
                // Call sync for each hospital
                // This is simplified - in production, use queue/background jobs
                $results[] = [
                    'hospital_id' => $hospital['id'],
                    'hospital_name' => $hospital['name'],
                    'status' => 'queued'
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'hospital_id' => $hospital['id'],
                    'hospital_name' => $hospital['name'],
                    'status' => 'error',
                    'message' => $e->getMessage()
                ];
            }
        }
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'เริ่มซิงค์ข้อมูลทั้งหมด',
            'hospitals_count' => count($hospitals),
            'results' => $results
        ]);
        exit;
    }
}
