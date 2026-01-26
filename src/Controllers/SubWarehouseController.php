<?php

namespace App\Controllers;

use App\Models\Subwarehouse;
use App\Models\Requisition;
use App\Core\Database;

class SubwarehouseController {
    private $db;
    private $subwarehouseModel;
    private $requisitionModel;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->subwarehouseModel = new Subwarehouse($this->db);
        $this->requisitionModel = new Requisition($this->db);
    }
    
    // ========================================================================
    // Main Views
    // ========================================================================
    
    /**
     * หน้าแรกของคลังย่อย - แสดงรายการคลังย่อยทั้งหมด
     */
    public function index() {
        header('Content-Type: text/html; charset=UTF-8');
        $subwarehouses = $this->subwarehouseModel->getAll('active');
        require_once __DIR__ . '/../Views/subwarehouse/index.php';
    }
    
    /**
     * Dashboard ของคลังย่อยแต่ละคลัง
     */
    public function dashboard($code) {
        $subwarehouse = $this->subwarehouseModel->getByCode($code);
        
        if (!$subwarehouse) {
            header('Location: /subwarehouse');
            exit;
        }
        
        header('Content-Type: text/html; charset=UTF-8');
        $stats = $this->subwarehouseModel->getStatistics($subwarehouse['id']);
        $lowStockDrugs = $this->subwarehouseModel->getLowStockDrugs($subwarehouse['id']);
        $recentRequisitions = $this->requisitionModel->getAll($subwarehouse['id'], null);
        
        require_once __DIR__ . '/../Views/subwarehouse/dashboard.php';
    }
    
    /**
     * หน้าขอเบิกยา (Requisition)
     */
    public function requisition($code) {
        $subwarehouse = $this->subwarehouseModel->getByCode($code);
        
        if (!$subwarehouse) {
            header('Location: /subwarehouse');
            exit;
        }
        
        $lowStockDrugs = $this->subwarehouseModel->getLowStockDrugs($subwarehouse['id']);
        $pendingRequisitions = $this->requisitionModel->getAll($subwarehouse['id'], 'pending');
        
        require_once __DIR__ . '/../Views/subwarehouse/requisition.php';
    }
    
    /**
     * หน้าจ่ายยา (Dispense)
     */
    public function dispense($code) {
        $subwarehouse = $this->subwarehouseModel->getByCode($code);
        
        if (!$subwarehouse) {
            header('Location: /subwarehouse');
            exit;
        }
        
        header('Content-Type: text/html; charset=UTF-8');
        $inventory = $this->subwarehouseModel->getInventory($subwarehouse['id']);
        
        require_once __DIR__ . '/../Views/subwarehouse/dispense.php';
    }
    
    /**
     * หน้าตั้งค่าสูตรคำนวณ (Configure Formula)
     */
    public function configureFormula($code) {
        $subwarehouse = $this->subwarehouseModel->getByCode($code);
        
        if (!$subwarehouse) {
            header('Location: /subwarehouse');
            exit;
        }
        
        header('Content-Type: text/html; charset=UTF-8');
        require_once __DIR__ . '/../Views/subwarehouse/configure_formula.php';
    }
    
    // ========================================================================
    // API Endpoints - Subwarehouse Management
    // ========================================================================
    
    /**
     * API: ดึงรายการคลังย่อยทั้งหมด
     */
    public function apiGetAll() {
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            $subwarehouses = $this->subwarehouseModel->getAll();
            echo json_encode($subwarehouses, JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
    
    /**
     * API: สร้างคลังย่อยใหม่
     */
    public function apiCreate() {
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $id = $this->subwarehouseModel->create($data);
            
            echo json_encode([
                'success' => true,
                'id' => $id,
                'message' => 'สร้างคลังย่อยสำเร็จ'
            ], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
    
    // ========================================================================
    // API Endpoints - Inventory
    // ========================================================================
    
    /**
     * API: ดึงสต็อกยาในคลังย่อย
     */
    public function apiGetInventory($code) {
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            $subwarehouse = $this->subwarehouseModel->getByCode($code);
            
            if (!$subwarehouse) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'ไม่พบคลังย่อย'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            $inventory = $this->subwarehouseModel->getInventory($subwarehouse['id']);
            echo json_encode($inventory, JSON_UNESCAPED_UNICODE);
            
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
    
    /**
     * API: อัพเดทสต็อกยา
     */
    public function apiUpdateInventory($code) {
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            $subwarehouse = $this->subwarehouseModel->getByCode($code);
            
            if (!$subwarehouse) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'ไม่พบคลังย่อย'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            $this->subwarehouseModel->updateInventory(
                $subwarehouse['id'],
                $data['drug_id'],
                $data
            );
            
            echo json_encode([
                'success' => true,
                'message' => 'อัพเดทสต็อกสำเร็จ'
            ], JSON_UNESCAPED_UNICODE);
            
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
    
    // ========================================================================
    // API Endpoints - Requisition
    // ========================================================================
    
    /**
     * API: สร้างใบขอเบิกใหม่
     */
    public function apiCreateRequisition($code) {
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            $subwarehouse = $this->subwarehouseModel->getByCode($code);
            
            if (!$subwarehouse) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'ไม่พบคลังย่อย'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            $requisitionId = $this->requisitionModel->create([
                'subwarehouse_id' => $subwarehouse['id'],
                'requested_by' => $_SESSION['user_id'] ?? 1,
                'request_date' => date('Y-m-d'),
                'notes' => $input['notes'] ?? null
            ], $input['items']);
            
            echo json_encode([
                'success' => true,
                'requisition_id' => $requisitionId,
                'message' => 'สร้างใบขอเบิกสำเร็จ'
            ], JSON_UNESCAPED_UNICODE);
            
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
    
    /**
     * API: สร้างใบขอเบิกอัตโนมัติ
     */
    public function apiAutoRequisition($code) {
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            $subwarehouse = $this->subwarehouseModel->getByCode($code);
            
            if (!$subwarehouse) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'ไม่พบคลังย่อย'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            $requisitionId = $this->requisitionModel->autoGenerate(
                $subwarehouse['id'],
                $_SESSION['user_id'] ?? 1
            );
            
            if (!$requisitionId) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ไม่มียาที่ต้องเบิก'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            echo json_encode([
                'success' => true,
                'requisition_id' => $requisitionId,
                'message' => 'สร้างใบขอเบิกอัตโนมัติสำเร็จ'
            ], JSON_UNESCAPED_UNICODE);
            
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
    
    /**
     * API: ดึงรายการใบขอเบิก
     */
    public function apiGetRequisitions($code) {
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            $subwarehouse = $this->subwarehouseModel->getByCode($code);
            
            if (!$subwarehouse) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'ไม่พบคลังย่อย'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            $status = $_GET['status'] ?? null;
            $requisitions = $this->requisitionModel->getAll($subwarehouse['id'], $status);
            
            echo json_encode($requisitions, JSON_UNESCAPED_UNICODE);
            
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
    
    /**
     * API: ดูรายละเอียดใบขอเบิก
     */
    public function apiGetRequisitionDetail($id) {
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            $requisition = $this->requisitionModel->getById($id);
            
            if (!$requisition) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'ไม่พบใบขอเบิก'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            $items = $this->requisitionModel->getItems($id);
            
            echo json_encode([
                'success' => true,
                'requisition' => $requisition,
                'items' => $items
            ], JSON_UNESCAPED_UNICODE);
            
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
    
    // ========================================================================
    // API Endpoints - Dispensing
    // ========================================================================
    
    /**
     * API: จ่ายยาจากคลังย่อย
     */
    public function apiDispense($code) {
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            $subwarehouse = $this->subwarehouseModel->getByCode($code);
            
            if (!$subwarehouse) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'ไม่พบคลังย่อย'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            // บันทึกการจ่ายยา
            $stmt = $this->db->prepare("
                INSERT INTO subwarehouse_dispensing 
                (subwarehouse_id, drug_id, quantity, patient_name, patient_id, dispensed_by, dispense_date, notes)
                VALUES (:subwarehouse_id, :drug_id, :quantity, :patient_name, :patient_id, :dispensed_by, :dispense_date, :notes)
            ");
            
            $stmt->execute([
                'subwarehouse_id' => $subwarehouse['id'],
                'drug_id' => $input['drug_id'],
                'quantity' => $input['quantity'],
                'patient_name' => $input['patient_name'] ?? null,
                'patient_id' => $input['patient_id'] ?? null,
                'dispensed_by' => $_SESSION['user_id'] ?? 1,
                'dispense_date' => date('Y-m-d'),
                'notes' => $input['notes'] ?? null
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'จ่ายยาสำเร็จ'
            ], JSON_UNESCAPED_UNICODE);
            
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
    
    // ========================================================================
    // API Endpoints - Statistics
    // ========================================================================
    
    /**
     * API: บันทึกการตั้งค่าสูตรคำนวณ
     */
    public function apiSaveFormula() {
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || !isset($input['subwarehouse_id'])) {
                throw new \Exception('ข้อมูลไม่ครบถ้วน');
            }
            
            $this->subwarehouseModel->saveFormula(
                $input['subwarehouse_id'],
                $input['formula_type'],
                $input['config']
            );
            
            echo json_encode([
                'success' => true,
                'message' => 'บันทึกสูตรคำนวณสำเร็จ'
            ], JSON_UNESCAPED_UNICODE);
            
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    /**
     * API: สถิติคลังย่อย
     */
    public function apiGetStatistics($code) {
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            $subwarehouse = $this->subwarehouseModel->getByCode($code);
            
            if (!$subwarehouse) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'ไม่พบคลังย่อย'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            $stats = $this->subwarehouseModel->getStatistics($subwarehouse['id']);
            echo json_encode($stats, JSON_UNESCAPED_UNICODE);
            
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
}
