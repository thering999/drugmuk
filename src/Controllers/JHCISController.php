<?php
/**
 * JHCIS Integration Controller
 * 
 * จัดการการเชื่อมต่อและ Sync ข้อมูลระหว่าง JHCIS และ Drugmuk
 * 
 * @package Drugmuk
 * @subpackage Controllers
 * @version 1.0
 * @since Phase 2
 */

namespace App\Controllers;

use PDO;
use Exception;

class JHCISController {
    
    private $db;
    private $jhcisDb;
    
    /**
     * Constructor
     */
    public function __construct() {
        // Drugmuk Database
        $this->db = new PDO(
            "mysql:host=" . (getenv('DB_HOST') ?: 'db') . ";dbname=" . (getenv('DB_NAME') ?: 'drugmuk'),
            getenv('DB_USER') ?: 'root',
            getenv('DB_PASS') ?: '123456',
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        // โหลดการตั้งค่า JHCIS จากไฟล์ config
        $this->jhcisDb = $this->loadJHCISConnection();
    }
    
    /**
     * โหลดการเชื่อมต่อ JHCIS จากไฟล์ config
     */
    private function loadJHCISConnection() {
        $configFile = __DIR__ . '/../../config/jhcis_config.json';
        
        // ลองโหลดจากไฟล์ config ก่อน
        if (file_exists($configFile)) {
            $json = file_get_contents($configFile);
            $config = json_decode($json, true);
            
            if ($config && !empty($config['host']) && !empty($config['dbname'])) {
                $host = $config['host'];
                $port = $config['port'] ?? '3306';
                $dbname = $config['dbname'];
                $user = $config['user'];
                $pass = $config['pass'];
                
                // แก้ปัญหา Docker: ถ้าเป็น localhost ให้ใช้ host.docker.internal
                if ($host === 'localhost' || $host === '127.0.0.1') {
                    $host = 'host.docker.internal';
                }
                
                try {
                    $pdo = new PDO(
                        "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8",
                        $user,
                        $pass,
                        [
                            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                            PDO::ATTR_TIMEOUT => 5
                        ]
                    );
                    
                    return $pdo;
                } catch (\PDOException $e) {
                    // ถ้าเชื่อมต่อไม่ได้ ให้ fallback ไปใช้ environment variables
                    error_log("JHCIS Config Connection Failed: " . $e->getMessage());
                }
            }
        }
        
        // Fallback: ใช้ Environment Variables
        $jhcisHost = getenv('JHCIS_DB_HOST') ?: (getenv('DB_HOST') ?: 'db');
        $jhcisName = getenv('JHCIS_DB_NAME') ?: (getenv('DB_NAME') ?: 'drugmuk');
        $jhcisUser = getenv('JHCIS_DB_USER') ?: (getenv('DB_USER') ?: 'root');
        $jhcisPass = getenv('JHCIS_DB_PASS') ?: (getenv('DB_PASS') ?: '123456');
        
        // แก้ปัญหา Docker
        if ($jhcisHost === 'localhost' || $jhcisHost === '127.0.0.1') {
            $jhcisHost = 'host.docker.internal';
        }
        
        try {
            $pdo = new PDO(
                "mysql:host={$jhcisHost};dbname={$jhcisName}",
                $jhcisUser,
                $jhcisPass,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            
            return $pdo;
        } catch (\PDOException $e) {
            // ถ้าเชื่อมต่อไม่ได้ ให้ใช้ Drugmuk DB แทน (สำหรับ Development)
            error_log("JHCIS Connection Failed, using Drugmuk DB: " . $e->getMessage());
            return $this->db;
        }
    }
    
    /**
     * Get JHCIS Connection (helper for sync methods)
     */
    private function getJHCISConnection() {
        return $this->jhcisDb;
    }
    
    // ========================================================================
    // VIEW METHODS
    // ========================================================================
    
    /**
     * JHCIS Dashboard
     * GET /admin/jhcis/dashboard
     */
    public function dashboard() {
        include __DIR__ . '/../Views/jhcis/dashboard.php';
    }
    
    // ========================================================================
    // SYNC METHODS
    // ========================================================================
    
    /**
     * Sync Dispensing Data from JHCIS
     * 
     * @param string $fromDate วันที่เริ่มต้น (YYYY-MM-DD)
     * @param string $toDate วันที่สิ้นสุด (YYYY-MM-DD)
     * @return array ผลลัพธ์การ Sync
     */
    public function syncDispensing($fromDate = null, $toDate = null) {
        header('Content-Type: application/json');
        
        try {
            // รับพารามิเตอร์จาก POST
            if (!$fromDate && isset($_POST['from_date'])) {
                $fromDate = $_POST['from_date'];
            }
            if (!$toDate && isset($_POST['to_date'])) {
                $toDate = $_POST['to_date'];
            }
            
            // 1. เริ่ม Sync Log
            $logId = $this->startSyncLog('dispensing', [
                'from_date' => $fromDate,
                'to_date' => $toDate
            ]);
            
            // 2. กำหนดช่วงเวลา (ถ้าไม่ระบุ ใช้วันนี้)
            if (!$fromDate) {
                $fromDate = date('Y-m-d');
            }
            if (!$toDate) {
                $toDate = date('Y-m-d');
            }
            
            // 3. ดึงข้อมูลจาก JHCIS
            $jhcisData = $this->fetchJHCISDispensing($fromDate, $toDate);
            
            $processed = 0;
            $success = 0;
            $failed = 0;
            $errors = [];
            
            // 4. ประมวลผลแต่ละ Record
            foreach ($jhcisData as $record) {
                $processed++;
                
                try {
                    // 4.1 Validate
                    $this->validateDispensingRecord($record);
                    
                    // 4.2 Normalize Unit
                    $normalizedUnit = $this->normalizeUnit($record['unit']);
                    
                    // 4.3 Map Drug Code
                    $drugId = $this->mapDrugCode($record['drugcode']);
                    
                    // 4.4 Convert to Base Unit
                    $baseQuantity = $this->convertToBaseUnit(
                        $record['quantity'],
                        $normalizedUnit,
                        $drugId
                    );
                    
                    // 4.5 ตรวจสอบซ้ำ (Idempotent)
                    if ($this->isAlreadySynced($record['id'])) {
                        continue; // ข้าม Record ที่ Sync แล้ว
                    }
                    
                    // 4.6 Save to Drugmuk
                    $dispensingId = $this->saveDispensing([
                        'drug_id' => $drugId,
                        'quantity' => $baseQuantity,
                        'dispense_date' => $record['date'],
                        'patient_id' => $record['patient_id'] ?? null,
                        'source' => 'JHCIS',
                        'jhcis_ref' => $record['id'],
                        'notes' => 'Auto-synced from JHCIS'
                    ]);
                    
                    // 4.7 อัพเดท Cache
                    $this->updateDispensingCache($record['id'], $dispensingId);
                    
                    $success++;
                    
                } catch (Exception $e) {
                    $failed++;
                    $errors[] = [
                        'record_id' => $record['id'],
                        'error' => $e->getMessage()
                    ];
                    
                    // บันทึก Error
                    $this->logSyncError($logId, $record, $e);
                }
            }
            
            // 5. สรุปผล
            $this->completeSyncLog($logId, $processed, $success, $failed);
            
            $result = [
                'status' => 'success',
                'log_id' => $logId,
                'processed' => $processed,
                'success' => $success,
                'failed' => $failed,
                'errors' => $errors
            ];
            
            echo json_encode($result);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Sync Inventory Data from JHCIS
     */
    public function syncInventory() {
        header('Content-Type: application/json');
        
        try {
            // Start sync log
            $logStmt = $this->db->prepare("
                INSERT INTO jhcis_sync_log (sync_type, sync_status, triggered_by)
                VALUES ('inventory', 'running', 'manual')
            ");
            $logStmt->execute();
            $logId = $this->db->lastInsertId();
            
            // Get JHCIS database connection
            $jhcisDb = $this->getJHCISConnection();
            if (!$jhcisDb) {
                throw new \Exception('Cannot connect to JHCIS database');
            }
            
            // Query JHCIS inventory (cdrugremain table)
            $jhcisStmt = $jhcisDb->query("
                SELECT 
                    drugcode,
                    drugname,
                    remain as quantity,
                    lot_no,
                    expire_date
                FROM cdrugremain
                WHERE remain > 0
            ");
            $jhcisInventory = $jhcisStmt->fetchAll(\PDO::FETCH_ASSOC);
            
            $processed = 0;
            $success = 0;
            $failed = 0;
            $discrepancies = [];
            
            foreach ($jhcisInventory as $jhcisItem) {
                $processed++;
                
                try {
                    // Find mapped drug in Drugmuk
                    $mappingStmt = $this->db->prepare("
                        SELECT drugmuk_drug_id 
                        FROM jhcis_drug_mapping 
                        WHERE jhcis_drug_code = ?
                    ");
                    $mappingStmt->execute([$jhcisItem['drugcode']]);
                    $mapping = $mappingStmt->fetch();
                    
                    if (!$mapping) {
                        // Drug not mapped, skip
                        continue;
                    }
                    
                    $drugId = $mapping['drugmuk_drug_id'];
                    $jhcisQty = (int)$jhcisItem['quantity'];
                    
                    // Get current inventory in Drugmuk
                    $invStmt = $this->db->prepare("
                        SELECT SUM(quantity) as total_qty
                        FROM inventory
                        WHERE drug_id = ? AND lot_no = ?
                    ");
                    $invStmt->execute([$drugId, $jhcisItem['lot_no']]);
                    $currentInv = $invStmt->fetch();
                    $drugmukQty = (int)($currentInv['total_qty'] ?? 0);
                    
                    // Check for discrepancy
                    if ($jhcisQty != $drugmukQty) {
                        $discrepancies[] = [
                            'drug_code' => $jhcisItem['drugcode'],
                            'drug_name' => $jhcisItem['drugname'],
                            'lot_no' => $jhcisItem['lot_no'],
                            'jhcis_qty' => $jhcisQty,
                            'drugmuk_qty' => $drugmukQty,
                            'difference' => $jhcisQty - $drugmukQty
                        ];
                        
                        // Update inventory to match JHCIS
                        if ($drugmukQty > 0) {
                            // Update existing
                            $updateStmt = $this->db->prepare("
                                UPDATE inventory 
                                SET quantity = ?
                                WHERE drug_id = ? AND lot_no = ?
                            ");
                            $updateStmt->execute([$jhcisQty, $drugId, $jhcisItem['lot_no']]);
                        } else {
                            // Insert new
                            $insertStmt = $this->db->prepare("
                                INSERT INTO inventory (drug_id, lot_no, expire_date, quantity, location)
                                VALUES (?, ?, ?, ?, 'main')
                            ");
                            $insertStmt->execute([
                                $drugId,
                                $jhcisItem['lot_no'],
                                $jhcisItem['expire_date'],
                                $jhcisQty
                            ]);
                        }
                    }
                    
                    $success++;
                    
                } catch (\Exception $e) {
                    $failed++;
                    // Log error
                    $errorStmt = $this->db->prepare("
                        INSERT INTO jhcis_sync_errors (sync_log_id, error_type, error_message, record_data)
                        VALUES (?, 'inventory_sync', ?, ?)
                    ");
                    $errorStmt->execute([
                        $logId,
                        $e->getMessage(),
                        json_encode($jhcisItem)
                    ]);
                }
            }
            
            // Update sync log
            $updateLogStmt = $this->db->prepare("
                UPDATE jhcis_sync_log
                SET sync_status = 'completed',
                    records_processed = ?,
                    records_success = ?,
                    records_failed = ?,
                    completed_at = NOW()
                WHERE id = ?
            ");
            $updateLogStmt->execute([$processed, $success, $failed, $logId]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Inventory sync completed',
                'data' => [
                    'processed' => $processed,
                    'success' => $success,
                    'failed' => $failed,
                    'discrepancies' => count($discrepancies),
                    'details' => $discrepancies
                ]
            ]);
            
        } catch (\Exception $e) {
            // Update log as failed
            if (isset($logId)) {
                $failStmt = $this->db->prepare("
                    UPDATE jhcis_sync_log
                    SET sync_status = 'failed',
                        error_message = ?,
                        completed_at = NOW()
                    WHERE id = ?
                ");
                $failStmt->execute([$e->getMessage(), $logId]);
            }
            
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Sync failed: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Sync Loan Data from JHCIS
     */
    public function syncLoans() {
        header('Content-Type: application/json');
        
        try {
            // Start sync log
            $logStmt = $this->db->prepare("
                INSERT INTO jhcis_sync_log (sync_type, sync_status, triggered_by)
                VALUES ('loan', 'running', 'manual')
            ");
            $logStmt->execute();
            $logId = $this->db->lastInsertId();
            
            // Get JHCIS database connection
            $jhcisDb = $this->getJHCISConnection();
            if (!$jhcisDb) {
                throw new \Exception('Cannot connect to JHCIS database');
            }
            
            // Query JHCIS loan transactions
            // Note: Adjust table/column names based on actual JHCIS schema
            $jhcisStmt = $jhcisDb->query("
                SELECT 
                    loan_id,
                    drugcode,
                    quantity,
                    loan_date,
                    return_date,
                    status
                FROM drug_loan
                WHERE loan_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                ORDER BY loan_date DESC
            ");
            $jhcisLoans = $jhcisStmt->fetchAll(\PDO::FETCH_ASSOC);
            
            $processed = 0;
            $success = 0;
            $failed = 0;
            
            foreach ($jhcisLoans as $loan) {
                $processed++;
                
                try {
                    // Find mapped drug
                    $mappingStmt = $this->db->prepare("
                        SELECT drugmuk_drug_id 
                        FROM jhcis_drug_mapping 
                        WHERE jhcis_drug_code = ?
                    ");
                    $mappingStmt->execute([$loan['drugcode']]);
                    $mapping = $mappingStmt->fetch();
                    
                    if (!$mapping) {
                        continue;
                    }
                    
                    // Check if loan already imported
                    $checkStmt = $this->db->prepare("
                        SELECT id FROM transactions
                        WHERE ref_document = ?
                    ");
                    $checkStmt->execute(['JHCIS_LOAN_' . $loan['loan_id']]);
                    
                    if ($checkStmt->fetch()) {
                        // Already imported
                        continue;
                    }
                    
                    // Import as transaction
                    $transStmt = $this->db->prepare("
                        INSERT INTO transactions 
                        (drug_id, transaction_type, quantity, ref_document, transaction_date, user_id)
                        VALUES (?, 'transfer_out', ?, ?, ?, 1)
                    ");
                    $transStmt->execute([
                        $mapping['drugmuk_drug_id'],
                        $loan['quantity'],
                        'JHCIS_LOAN_' . $loan['loan_id'],
                        $loan['loan_date']
                    ]);
                    
                    $success++;
                    
                } catch (\Exception $e) {
                    $failed++;
                    $errorStmt = $this->db->prepare("
                        INSERT INTO jhcis_sync_errors (sync_log_id, error_type, error_message, record_data)
                        VALUES (?, 'loan_sync', ?, ?)
                    ");
                    $errorStmt->execute([
                        $logId,
                        $e->getMessage(),
                        json_encode($loan)
                    ]);
                }
            }
            
            // Update sync log
            $updateLogStmt = $this->db->prepare("
                UPDATE jhcis_sync_log
                SET sync_status = 'completed',
                    records_processed = ?,
                    records_success = ?,
                    records_failed = ?,
                    completed_at = NOW()
                WHERE id = ?
            ");
            $updateLogStmt->execute([$processed, $success, $failed, $logId]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Loan sync completed',
                'data' => [
                    'processed' => $processed,
                    'imported' => $success,
                    'failed' => $failed
                ]
            ]);
            
        } catch (\Exception $e) {
            if (isset($logId)) {
                $failStmt = $this->db->prepare("
                    UPDATE jhcis_sync_log
                    SET sync_status = 'failed',
                        error_message = ?,
                        completed_at = NOW()
                    WHERE id = ?
                ");
                $failStmt->execute([$e->getMessage(), $logId]);
            }
            
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Sync failed: ' . $e->getMessage()
            ]);
        }
    }
    // ดึงข้อมูลจาก drugstoretoloan
    
    // ========================================================================
    // DATA FETCHING METHODS
    // ========================================================================
    
    /**
     * ดึงข้อมูลการจ่ายยาจาก JHCIS
     * 
     * @param string $fromDate
     * @param string $toDate
     * @return array
     */
    private function fetchJHCISDispensing($fromDate, $toDate) {
        // ลองใช้ตาราง visitdrug ก่อน (ตารางหลักสำหรับการจ่ายยา)
        try {
            $sql = "
                SELECT 
                    CONCAT(v.visitno, '-', v.drugcode) as id,
                    v.drugcode,
                    v.datestart as date,
                    v.qty as quantity,
                    v.unit,
                    v.hn as patient_id,
                    '' as lot_number,
                    '' as expire_date
                FROM visitdrug v
                WHERE v.datestart BETWEEN :from_date AND :to_date
                ORDER BY v.datestart, v.visitno
                LIMIT 1000
            ";
            
            $stmt = $this->jhcisDb->prepare($sql);
            $stmt->execute([
                'from_date' => $fromDate,
                'to_date' => $toDate
            ]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (\Exception $e) {
            // ถ้า visitdrug ไม่มี ลอง drugrepositoryoutdetail
            try {
                $sql = "
                    SELECT 
                        CONCAT(d.drugcode, '-', d.dateservice) as id,
                        d.drugcode,
                        d.dateservice as date,
                        d.quantity,
                        d.lotunit as unit,
                        '' as patient_id,
                        d.lotno as lot_number,
                        d.dateexpire as expire_date
                    FROM drugrepositoryoutdetail d
                    WHERE d.dateservice BETWEEN :from_date AND :to_date
                    ORDER BY d.dateservice
                    LIMIT 1000
                ";
                
                $stmt = $this->jhcisDb->prepare($sql);
                $stmt->execute([
                    'from_date' => $fromDate,
                    'to_date' => $toDate
                ]);
                
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
                
            } catch (\Exception $e2) {
                // ถ้าไม่มีทั้ง 2 ตาราง return empty array
                return [];
            }
        }
    }
    
    // ========================================================================
    // MAPPING & CONVERSION METHODS
    // ========================================================================
    
    /**
     * Map JHCIS Drug Code to Drugmuk Drug ID
     * 
     * @param string $jhcisDrugCode
     * @return int Drug ID
     * @throws Exception ถ้าไม่เจอ Mapping
     */
    private function mapDrugCode($jhcisDrugCode) {
        $stmt = $this->db->prepare("
            SELECT drugmuk_drug_id 
            FROM jhcis_drug_mapping 
            WHERE jhcis_drug_code = ?
        ");
        $stmt->execute([$jhcisDrugCode]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result) {
            throw new Exception("Drug code not mapped: $jhcisDrugCode");
        }
        
        return $result['drugmuk_drug_id'];
    }
    
    /**
     * Normalize Unit (แปลงหน่วยให้เป็นมาตรฐาน)
     * 
     * @param string $rawUnit
     * @return string Normalized unit
     */
    private function normalizeUnit($rawUnit) {
        $stmt = $this->db->prepare("
            SELECT normalized_unit 
            FROM unit_normalization_map 
            WHERE raw_unit = ?
        ");
        $stmt->execute([$rawUnit]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            return $result['normalized_unit'];
        }
        
        // ถ้าไม่เจอ ให้ return lowercase
        return strtolower(trim($rawUnit));
    }
    
    /**
     * Convert Quantity to Base Unit
     * 
     * @param float $quantity
     * @param string $unit
     * @param int $drugId
     * @return float Base quantity
     * @throws Exception ถ้าไม่เจอ Conversion rule
     */
    private function convertToBaseUnit($quantity, $unit, $drugId) {
        $stmt = $this->db->prepare("
            SELECT conversion_factor 
            FROM unit_conversions 
            WHERE drug_id = ? 
              AND from_unit = ? 
              AND is_base_unit = TRUE
        ");
        $stmt->execute([$drugId, $unit]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result) {
            throw new Exception("No conversion rule for drug_id=$drugId, unit=$unit");
        }
        
        return $quantity * $result['conversion_factor'];
    }
    
    // ========================================================================
    // VALIDATION METHODS
    // ========================================================================
    
    /**
     * Validate Dispensing Record
     * 
     * @param array $record
     * @throws Exception ถ้า Validation ไม่ผ่าน
     */
    private function validateDispensingRecord($record) {
        if (empty($record['drugcode'])) {
            throw new Exception("Drug code is required");
        }
        
        if (empty($record['quantity']) || $record['quantity'] <= 0) {
            throw new Exception("Quantity must be greater than 0");
        }
        
        if (empty($record['unit'])) {
            throw new Exception("Unit is required");
        }
        
        if (empty($record['date'])) {
            throw new Exception("Date is required");
        }
    }
    
    /**
     * ตรวจสอบว่า Record นี้ Sync แล้วหรือยัง
     * 
     * @param string $jhcisRecordId
     * @return bool
     */
    private function isAlreadySynced($jhcisRecordId) {
        $stmt = $this->db->prepare("
            SELECT id 
            FROM jhcis_dispensing_cache 
            WHERE jhcis_record_id = ? AND synced = TRUE
        ");
        $stmt->execute([$jhcisRecordId]);
        
        return $stmt->fetch() !== false;
    }
    
    // ========================================================================
    // SAVE METHODS
    // ========================================================================
    
    /**
     * Save Dispensing to Drugmuk
     * 
     * @param array $data
     * @return int Dispensing ID
     */
    private function saveDispensing($data) {
        $this->db->beginTransaction();
        
        try {
            // 1. บันทึกการจ่ายยา
            $stmt = $this->db->prepare("
                INSERT INTO dispensing (
                    dispense_date,
                    patient_id,
                    notes,
                    created_at
                ) VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([
                $data['dispense_date'],
                $data['patient_id'],
                $data['notes']
            ]);
            
            $dispensingId = $this->db->lastInsertId();
            
            // 2. บันทึกรายการยา
            $stmt = $this->db->prepare("
                INSERT INTO dispensing_items (
                    dispensing_id,
                    drug_id,
                    quantity,
                    created_at
                ) VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([
                $dispensingId,
                $data['drug_id'],
                $data['quantity']
            ]);
            
            // 3. ตัดสต็อก
            $this->updateInventory($data['drug_id'], -$data['quantity'], 'dispensing', $dispensingId);
            
            $this->db->commit();
            
            return $dispensingId;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * อัพเดทสต็อก
     * 
     * @param int $drugId
     * @param float $quantity (+ = เพิ่ม, - = ลด)
     * @param string $type
     * @param int $referenceId
     */
    private function updateInventory($drugId, $quantity, $type, $referenceId) {
        // 1. อัพเดทสต็อกหลัก
        $stmt = $this->db->prepare("
            UPDATE inventory 
            SET quantity = quantity + ?,
                updated_at = NOW()
            WHERE drug_id = ?
        ");
        $stmt->execute([$quantity, $drugId]);
        
        // 2. บันทึก Transaction
        $stmt = $this->db->prepare("
            INSERT INTO transactions (
                drug_id,
                type,
                quantity,
                transaction_date,
                reference_type,
                reference_id,
                created_at
            ) VALUES (?, ?, ?, NOW(), ?, ?, NOW())
        ");
        $stmt->execute([
            $drugId,
            $quantity > 0 ? 'in' : 'out',
            abs($quantity),
            $type,
            $referenceId
        ]);
    }
    
    // ========================================================================
    // LOGGING METHODS
    // ========================================================================
    
    /**
     * เริ่ม Sync Log
     * 
     * @param string $syncType
     * @param array $params
     * @return int Log ID
     */
    private function startSyncLog($syncType, $params = []) {
        $stmt = $this->db->prepare("
            INSERT INTO jhcis_sync_log (
                sync_type,
                sync_start,
                sync_params,
                status
            ) VALUES (?, NOW(), ?, 'running')
        ");
        $stmt->execute([
            $syncType,
            json_encode($params)
        ]);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * สรุปผล Sync Log
     * 
     * @param int $logId
     * @param int $processed
     * @param int $success
     * @param int $failed
     */
    private function completeSyncLog($logId, $processed, $success, $failed) {
        $stmt = $this->db->prepare("
            UPDATE jhcis_sync_log 
            SET sync_end = NOW(),
                records_processed = ?,
                records_success = ?,
                records_failed = ?,
                status = 'completed'
            WHERE id = ?
        ");
        $stmt->execute([$processed, $success, $failed, $logId]);
    }
    
    /**
     * บันทึก Sync Error
     * 
     * @param int $logId
     * @param array $record
     * @param Exception $exception
     */
    private function logSyncError($logId, $record, $exception) {
        // กำหนดประเภท Error
        $errorType = 'other';
        $message = $exception->getMessage();
        
        if (strpos($message, 'not mapped') !== false) {
            $errorType = 'mapping';
        } elseif (strpos($message, 'conversion') !== false) {
            $errorType = 'conversion';
        } elseif (strpos($message, 'required') !== false) {
            $errorType = 'validation';
        }
        
        $stmt = $this->db->prepare("
            INSERT INTO jhcis_sync_errors (
                sync_log_id,
                jhcis_record_id,
                error_type,
                error_message,
                record_data
            ) VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $logId,
            $record['id'] ?? null,
            $errorType,
            $message,
            json_encode($record)
        ]);
    }
    
    /**
     * อัพเดท Dispensing Cache
     * 
     * @param string $jhcisRecordId
     * @param int $dispensingId
     */
    private function updateDispensingCache($jhcisRecordId, $dispensingId) {
        $stmt = $this->db->prepare("
            UPDATE jhcis_dispensing_cache 
            SET synced = TRUE,
                synced_at = NOW(),
                drugmuk_dispensing_id = ?
            WHERE jhcis_record_id = ?
        ");
        $stmt->execute([$dispensingId, $jhcisRecordId]);
    }
    
    // ========================================================================
    // API ENDPOINTS (for Web Interface)
    // ========================================================================
    
    /**
     * GET /api/jhcis/sync/status
     * ดูสถานะการ Sync
     */
    public function getSyncStatus() {
        header('Content-Type: application/json');
        
        try {
            $stmt = $this->db->query("
                SELECT 
                    id,
                    sync_type,
                    sync_start,
                    sync_end,
                    records_processed,
                    records_success,
                    records_failed,
                    status,
                    TIMESTAMPDIFF(SECOND, sync_start, sync_end) as duration_seconds
                FROM jhcis_sync_log
                ORDER BY sync_start DESC
                LIMIT 20
            ");
            
            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($logs);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error loading sync status: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * GET /api/jhcis/mapping/drugs
     * ดูรายการ Drug Mapping
     */
    public function getDrugMapping() {
        header('Content-Type: application/json');
        
        try {
            $stmt = $this->db->query("
                SELECT 
                    m.id,
                    m.jhcis_drug_code,
                    d.code as drug_code,
                    d.name as drug_name,
                    m.mapping_method,
                    m.confidence_score,
                    m.created_at,
                    h.name as hospital_name
                FROM jhcis_drug_mapping m
                INNER JOIN drugs d ON m.drugmuk_drug_id = d.id
                LEFT JOIN jhcis_hospitals h ON m.hospital_id = h.id
                ORDER BY m.created_at DESC
            ");
            
            $mappings = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($mappings);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error loading mappings: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * GET /api/jhcis/unmapped-drugs
     * ดูยาที่ยังไม่ได้ Map
     */
    public function getUnmappedDrugs() {
        header('Content-Type: application/json');
        
        try {
            $stmt = $this->db->query("
                SELECT * FROM v_unmapped_drugs
                LIMIT 100
            ");
            
            $drugs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($drugs);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error loading unmapped drugs: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * POST /api/jhcis/mapping/drugs
     * สร้าง Drug Mapping ใหม่
     */
    public function createDrugMapping($data) {
        $stmt = $this->db->prepare("
            INSERT INTO jhcis_drug_mapping (
                jhcis_drug_code,
                drugmuk_drug_id,
                mapping_method,
                confidence_score,
                hospital_id
            ) VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $data['jhcis_drug_code'] ?? $data['jhcis_drug_code'],
            $data['drugmuk_drug_id'],
            $data['mapping_method'] ?? $data['mapping_method'] ?? 'manual',
            $data['confidence_score'] ?? 1.0,
            $data['hospital_id'] ?? 1
        ]);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * GET /api/jhcis/reconciliation
     * เปรียบเทียบสต็อกระหว่าง JHCIS และ Drugmuk
     */
    public function getReconciliation() {
        header('Content-Type: application/json');
        
        try {
            // ตรวจสอบการเชื่อมต่อ JHCIS
            if (!$this->jhcisDb) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ไม่สามารถเชื่อมต่อ JHCIS ได้'
                ]);
                return;
            }
            
            // ลองหาตารางสต็อกที่มีอยู่จริง
            $stockQuery = null;
            $tableName = null;
            
            // ตารางที่เป็นไปได้ (เรียงตามลำดับความน่าจะเป็น)
            $possibleTables = [
                'cdrugremaiin' => ['drugcode', 'remain'],
                'drugitems' => ['drugcode', 'qty', 'quantity', 'amount'],
                'drugstore' => ['drugcode', 'qty', 'quantity', 'stock'],
                '_tmp_drugstock' => ['drugcode', 'qty', 'quantity']
            ];
            
            foreach ($possibleTables as $table => $possibleColumns) {
                try {
                    // ตรวจสอบว่าตารางมีอยู่หรือไม่
                    $this->jhcisDb->query("SELECT 1 FROM {$table} LIMIT 1");
                    
                    // ตรวจสอบว่ามีคอลัมน์ไหนที่ใช้ได้
                    $columns = $this->jhcisDb->query("SHOW COLUMNS FROM {$table}")->fetchAll(\PDO::FETCH_COLUMN);
                    
                    // หาคอลัมน์ที่เก็บจำนวน
                    $quantityColumn = null;
                    foreach ($possibleColumns as $col) {
                        if (in_array($col, $columns)) {
                            $quantityColumn = $col;
                            break;
                        }
                    }
                    
                    if ($quantityColumn) {
                        // สร้าง query ตามตารางและคอลัมน์ที่พบ
                        if ($table === 'drugitems') {
                            $stockQuery = "SELECT drugcode, SUM({$quantityColumn}) as remain FROM {$table} WHERE expire_date > CURDATE() GROUP BY drugcode";
                        } else {
                            $stockQuery = "SELECT drugcode, {$quantityColumn} as remain FROM {$table}";
                        }
                        $tableName = $table;
                        break;
                    }
                } catch (\Exception $e) {
                    // ตารางนี้ไม่มี ลองตารางถัดไป
                    continue;
                }
            }
            
            // ถ้าไม่พบตารางใดเลย
            if (!$stockQuery) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ไม่พบตารางสต็อกยาใน JHCIS หรือไม่มีคอลัมน์ที่เก็บจำนวน'
                ]);
                return;
            }
            
            // ดึงข้อมูลจาก JHCIS
            $jhcisStock = $this->jhcisDb->query($stockQuery)->fetchAll(\PDO::FETCH_KEY_PAIR);
            
            // ดึงข้อมูลจาก Drugmuk
            $stmt = $this->db->query("
                SELECT 
                    m.jhcis_drug_code,
                    d.name as drug_name,
                    i.quantity as drugmuk_stock
                FROM inventory i
                INNER JOIN drugs d ON i.drug_id = d.id
                INNER JOIN jhcis_drug_mapping m ON d.id = m.drugmuk_drug_id
            ");
            
            $results = [];
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $jhcisCode = $row['jhcis_drug_code'];
                $jhcisQty = $jhcisStock[$jhcisCode] ?? 0;
                $drugmukQty = $row['drugmuk_stock'];
                $difference = $jhcisQty - $drugmukQty;
                
                if (abs($difference) > 0.01) {
                    $results[] = [
                        'drug_name' => $row['drug_name'],
                        'jhcis_stock' => $jhcisQty,
                        'drugmuk_stock' => $drugmukQty,
                        'difference' => $difference
                    ];
                }
            }
            
            echo json_encode([
                'success' => true,
                'data' => $results,
                'total' => count($results),
                'jhcis_table_used' => $tableName
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error loading reconciliation: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * GET /admin/jhcis/mapping
     * หน้า Drug Mapping UI
     */
    public function drugMapping() {
        require_once __DIR__ . '/../Views/jhcis/drug_mapping.php';
    }
    
    /**
     * GET /api/jhcis/mapping/stats
     * ดึงสถิติ Drug Mapping
     */
    public function getMappingStats() {
        header('Content-Type: application/json');
        
        try {
            // นับจำนวนยาที่ map แล้ว
            $mapped = $this->db->query("
                SELECT COUNT(*) FROM jhcis_drug_mapping
            ")->fetchColumn();
            
            // นับจำนวนยาทั้งหมด
            $total = $this->db->query("
                SELECT COUNT(*) FROM drugs
            ")->fetchColumn();
            
            // นับจำนวนยาที่ยังไม่ได้ map
            $unmapped = $total - $mapped;
            
            echo json_encode([
                'success' => true,
                'mapped' => (int)$mapped,
                'unmapped' => (int)$unmapped,
                'total' => (int)$total,
                'rate' => $total > 0 ? round(($mapped / $total) * 100, 2) : 0
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * POST /api/jhcis/mapping/auto-map
     * Auto-mapping ยาที่มีรหัสตรงกัน
     */
    public function autoMapDrugs() {
        header('Content-Type: application/json');
        
        try {
            $this->db->beginTransaction();
            
            // โหลดการตั้งค่า JHCIS จากไฟล์ config
            $configFile = __DIR__ . '/../../config/jhcis_config.json';
            $jhcisDbName = 'jhcisdb'; // ค่าเริ่มต้น
            $hospitalId = $_GET['hospital_id'] ?? $_POST['hospital_id'] ?? null;
            
            // If hospital_id is provided, use ConnectionPool to get the correct JHCIS DB
            if ($hospitalId) {
                try {
                    $this->jhcisDb = \App\Services\JHCIS\JHCISConnectionPool::getConnection((int)$hospitalId);
                } catch (Exception $e) {
                    throw new Exception("ไม่สามารถเชื่อมต่อ JHCIS สำหรับโรงพยาบาลรหัส $hospitalId ได้: " . $e->getMessage());
                }
            } else {
                // Fallback to default connection if not provided
                $hStmt = $this->db->query("SELECT id FROM jhcis_hospitals WHERE is_active = 1 LIMIT 1");
                $hospitalId = $hStmt->fetchColumn() ?: 1;
                
                try {
                    $this->jhcisDb = \App\Services\JHCIS\JHCISConnectionPool::getConnection((int)$hospitalId);
                } catch (Exception $e) {
                    // Final fallback to the one loaded in constructor if pool fails
                    if (!$this->jhcisDb) {
                        throw new Exception("ไม่พบการเชื่อมต่อ JHCIS ที่ใช้งานได้");
                    }
                }
            }
            
            // ตรวจสอบว่าเชื่อมต่อ JHCIS ได้หรือไม่
            try {
                $this->jhcisDb->query("SELECT 1");
            } catch (Exception $e) {
                throw new Exception("ไม่สามารถเชื่อมต่อ JHCIS ได้ - กรุณาตรวจสอบการตั้งค่า");
            }
            
            // ตรวจสอบว่ามีตารางยาอะไรใน JHCIS
            $tableCheck = $this->jhcisDb->query("SHOW TABLES LIKE '%drug%'")->fetchAll(PDO::FETCH_COLUMN);
            
            if (empty($tableCheck)) {
                throw new Exception("ไม่พบตารางยาใน JHCIS database - กรุณาตรวจสอบการเชื่อมต่อ");
            }
            
            // เลือกตารางที่เหมาะสม
            $drugTable = null;
            $drugCodeField = 'drugcode';
            $drugNameField = 'drugname';
            
            if (in_array('cdrug', $tableCheck)) {
                $drugTable = 'cdrug';
                $drugCodeField = 'drugcode';
                $drugNameField = 'drugname';
            } elseif (in_array('drugitems', $tableCheck)) {
                $drugTable = 'drugitems';
                $drugCodeField = 'did';
                $drugNameField = 'name';
            } elseif (in_array('drug', $tableCheck)) {
                $drugTable = 'drug';
                $drugCodeField = 'did';
                $drugNameField = 'name';
            } else {
                // แสดงตารางที่พบ
                $foundTables = implode(', ', array_slice($tableCheck, 0, 10));
                throw new Exception("ไม่พบตารางยาที่รองรับ (cdrug, drug, drugitems) ใน JHCIS<br>ตารางที่พบ: " . $foundTables);
            }
            
            // ดึงรายการยาจาก JHCIS
            $query = "
                SELECT 
                    {$drugCodeField} as drugcode,
                    {$drugNameField} as name
                FROM {$drugTable}
                LIMIT 500
            ";
            
            $jhcisDrugs = $this->jhcisDb->query($query)->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($jhcisDrugs)) {
                throw new Exception("ไม่พบข้อมูลยาในตาราง {$drugTable}");
            }
            
            $mappedCount = 0;
            
            foreach ($jhcisDrugs as $jhcisDrug) {
                // ตรวจสอบว่ามี mapping อยู่แล้วหรือไม่ (ตามรหัสโรงพยาบาล)
                $checkStmt = $this->db->prepare("
                    SELECT id FROM jhcis_drug_mapping 
                    WHERE jhcis_drug_code = ? AND hospital_id = ?
                ");
                $checkStmt->execute([$jhcisDrug['drugcode'], $hospitalId]);
                
                if ($checkStmt->fetch()) {
                    continue; // มี mapping แล้ว ข้าม
                }
                
                // ลองหายาใน Drugmuk ที่มีรหัสตรงกัน
                $stmt = $this->db->prepare("
                    SELECT id FROM drugs 
                    WHERE code = ? OR name LIKE ?
                    LIMIT 1
                ");
                $stmt->execute([
                    $jhcisDrug['drugcode'],
                    '%' . $jhcisDrug['name'] . '%'
                ]);
                
                $drugmukDrug = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($drugmukDrug) {
                    // สร้าง mapping
                    $insertStmt = $this->db->prepare("
                        INSERT INTO jhcis_drug_mapping (
                            jhcis_drug_code,
                            drugmuk_drug_id,
                            mapping_method,
                            confidence_score,
                            hospital_id
                        ) VALUES (?, ?, 'exact', 1.0, ?)
                    ");
                    
                    $insertStmt->execute([
                        $jhcisDrug['drugcode'],
                        $drugmukDrug['id'],
                        $hospitalId
                    ]);
                    
                    $mappedCount++;
                }
            }
            
            $this->db->commit();
            
            echo json_encode([
                'success' => true,
                'mapped_count' => $mappedCount,
                'hospital_id' => $hospitalId,
                'drug_table' => $drugTable,
                'total_checked' => count($jhcisDrugs),
                'message' => "Auto-mapping สำเร็จ! จับคู่ได้ $mappedCount จาก " . count($jhcisDrugs) . " รายการ"
            ]);
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            http_response_code(500);
            
            echo json_encode([
                'success' => false,
                'message' => 'Auto-Mapping ล้มเหลว: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * DELETE /api/jhcis/mapping/drugs/{id}
     * ลบ Drug Mapping
     */
    public function deleteDrugMapping($id) {
        header('Content-Type: application/json');
        
        try {
            $stmt = $this->db->prepare("
                DELETE FROM jhcis_drug_mapping WHERE id = ?
            ");
            $stmt->execute([$id]);
            
            echo json_encode([
                'success' => true,
                'message' => 'ลบ Mapping สำเร็จ'
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * GET /admin/jhcis/unmapped-drugs
     * หน้าแสดงยาที่ยังไม่ได้ map
     */
    public function unmappedDrugs() {
        require_once __DIR__ . '/../Views/jhcis/unmapped_drugs.php';
    }
    /**
     * GET /api/jhcis/sync/errors
     * ดึงรายการ Error ของการ Sync
     */
    public function getSyncErrors() {
        // Parse ID from URL in Router or use $_GET
        $logId = $_GET['id'] ?? null;
        
        if (!$logId) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Log ID is required']);
            return;
        }

        $stmt = $this->db->prepare("
            SELECT 
                error_type,
                error_message,
                created_at,
                record_data
            FROM jhcis_sync_errors
            WHERE sync_log_id = ?
            ORDER BY id ASC
        ");
        $stmt->execute([$logId]);
        
        header('Content-Type: application/json');
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    }
}
