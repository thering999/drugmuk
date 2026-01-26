<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use PDO;

/**
 * JHCIS Data Import Controller
 * ดึงข้อมูลยาจริงจาก JHCIS มาใส่ในระบบ Drugmuk
 */
class JHCISDataImportController extends Controller {
    
    private $db;
    private $jhcisDb;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * หน้าหลักสำหรับ Import ข้อมูล
     */
    public function index() {
        $this->view('jhcis-import/index');
    }

    /**
     * Process import (alias for importDrugs)
     */
    public function process() {
        $this->importDrugs();
    }


    /**
     * หน้าตั้งค่าการเชื่อมต่อ JHCIS
     */
    public function settings() {
        $this->view('jhcis-import/settings');
    }

    /**
     * ทดสอบการเชื่อมต่อ JHCIS
     */
    public function testConnection() {
        header('Content-Type: application/json');
        
        try {
            $this->connectJHCIS();
            
            // ลองนับจำนวนยาใน JHCIS (รองรับทั้ง 2 แบบ)
                // ลองตาราง cdrug ก่อน (JHCIS มาตรฐาน)
                try {
                    $stmt = $this->jhcisDb->query("SELECT COUNT(*) as total FROM cdrug");
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    $tableName = 'cdrug';
                } catch (\PDOException $e) {
                    // ถ้าไม่มี ลองตาราง drugitems (อาจเป็น HOSxP หรือ version พิเศษ)
                    try {
                        $stmt = $this->jhcisDb->query("SELECT COUNT(*) as total FROM drugitems");
                        $result = $stmt->fetch(PDO::FETCH_ASSOC);
                        $tableName = 'drugitems';
                    } catch (\PDOException $e2) {
                    // ถ้าไม่มีทั้ง 2 ตาราง
                    echo json_encode([
                        'success' => false,
                        'message' => 'เชื่อมต่อได้ แต่ไม่พบตาราง JHCIS',
                        'suggestion' => 'กรุณาสร้างฐานข้อมูล JHCIS หรือใช้ Mock Data แทน',
                        'mock_data_url' => '/mock-data'
                    ]);
                    exit;
                }
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'เชื่อมต่อสำเร็จ',
                'total_drugs' => $result['total'],
                'table_name' => $tableName,
                'jhcis_version' => $tableName === 'drugitems' ? 'ใหม่' : 'เก่า'
            ]);
            
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'เชื่อมต่อไม่สำเร็จ: ' . $e->getMessage(),
                'suggestion' => 'ลองใช้ Mock Data แทน',
                'mock_data_url' => '/mock-data'
            ]);
        }
        exit;
    }

    /**
     * Import ข้อมูลยาจาก JHCIS
     */
    public function importDrugs() {
        header('Content-Type: application/json');
        
        try {
            $this->connectJHCIS();
            
            // ตรวจสอบว่าคอลัมน์ต่างๆ ในตาราง cdrug ชื่ออะไร
            $columnsStmt = $this->jhcisDb->query("SHOW COLUMNS FROM cdrug");
            $columns = $columnsStmt->fetchAll(PDO::FETCH_COLUMN);
            
            // ตรวจสอบคอลัมน์หน่วย (units/drugunit/unit)
            $unitCol = null;
            if (in_array('units', $columns)) {
                $unitCol = 'units';
            } elseif (in_array('drugunit', $columns)) {
                $unitCol = 'drugunit';
            } elseif (in_array('unit', $columns)) {
                $unitCol = 'unit';
            }
            
            // ตรวจสอบคอลัมน์ราคา (drugprice/price/unitprice)
            $priceCol = null;
            if (in_array('drugprice', $columns)) {
                $priceCol = 'drugprice';
            } elseif (in_array('price', $columns)) {
                $priceCol = 'price';
            } elseif (in_array('unitprice', $columns)) {
                $priceCol = 'unitprice';
            }
            
            // ตรวจสอบคอลัมน์ชื่อสามัญ (druggenericname/genericname/generic_name)
            $genericCol = null;
            if (in_array('druggenericname', $columns)) {
                $genericCol = 'druggenericname';
            } elseif (in_array('genericname', $columns)) {
                $genericCol = 'genericname';
            } elseif (in_array('generic_name', $columns)) {
                $genericCol = 'generic_name';
            }

            // สร้าง SQL SELECT โดยใช้คอลัมน์ที่มีจริง
            $unitSelect = $unitCol ? "cdrug.$unitCol" : "''";
            $priceSelect = $priceCol ? "cdrug.$priceCol" : "0";
            $genericSelect = $genericCol ? "cdrug.$genericCol" : "''";
            
            $sql = "SELECT 
                        cdrug.drugcode,
                        COALESCE(cdrug.drugname, '') as drug_name,
                        $genericSelect as genericname,
                        $unitSelect as units,
                        $priceSelect as unitprice,
                        '' as drugproperties,
                        'ทั่วไป' as categoryname
                    FROM cdrug
                    WHERE cdrug.drugcode IS NOT NULL
                      AND cdrug.drugname IS NOT NULL
                      AND cdrug.drugname != ''
                    ORDER BY cdrug.drugname
                    LIMIT 1000"; // จำกัด 1000 รายการต่อครั้ง
            
            $stmt = $this->jhcisDb->query($sql);
            $jhcisDrugs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $imported = 0;
            $updated = 0;
            $skipped = 0;
            
            foreach ($jhcisDrugs as $drug) {
                // Skip if name is empty
                if (empty($drug['drug_name']) || trim($drug['drug_name']) === '') {
                    $skipped++;
                    continue;
                }
                
                // ตรวจสอบว่ามียาอยู่แล้วหรือไม่
                $checkSql = "SELECT id FROM drugs WHERE code = ?";
                $checkStmt = $this->db->prepare($checkSql);
                $checkStmt->execute([$drug['drugcode']]);
                $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($existing) {
                    // อัพเดทข้อมูล
                    $updateSql = "UPDATE drugs SET 
                                    name = ?,
                                    generic_name = ?,
                                    unit = ?,
                                    price = ?,
                                    category = ?
                                  WHERE code = ?";
                    $updateStmt = $this->db->prepare($updateSql);
                    $updateStmt->execute([
                        $drug['drug_name'],
                        $drug['genericname'],
                        $drug['units'] ?? 'เม็ด',
                        $drug['unitprice'] ?? 0,
                        $drug['categoryname'] ?? 'อื่นๆ',
                        $drug['drugcode']
                    ]);
                    $updated++;
                } else {
                    // เพิ่มยาใหม่
                    $insertSql = "INSERT INTO drugs 
                                    (code, name, generic_name, unit, price, category, is_active)
                                  VALUES (?, ?, ?, ?, ?, ?, 1)";
                    $insertStmt = $this->db->prepare($insertSql);
                    $insertStmt->execute([
                        $drug['drugcode'],
                        $drug['drug_name'],
                        $drug['genericname'],
                        $drug['units'] ?? 'เม็ด',
                        $drug['unitprice'] ?? 0,
                        $drug['categoryname'] ?? 'อื่นๆ'
                    ]);
                    $imported++;
                }
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Import สำเร็จ',
                'imported' => $imported,
                'updated' => $updated,
                'total' => count($jhcisDrugs)
            ]);
            
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * Bulk Import ยาที่เลือกจาก JHCIS Drug List
     */
    public function bulkImportDrugs() {
        header('Content-Type: application/json');
        
        try {
            // รับข้อมูลจาก POST
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            if (!isset($data['drugs']) || !is_array($data['drugs'])) {
                throw new \Exception('ข้อมูลไม่ถูกต้อง');
            }
            
            $drugs = $data['drugs'];
            
            if (empty($drugs)) {
                throw new \Exception('กรุณาเลือกยาที่ต้องการ Import');
            }
            
            $imported = 0;
            $updated = 0;
            $skipped = 0;
            $errors = [];
            
            foreach ($drugs as $drug) {
                try {
                    // Validate required fields
                    if (empty($drug['drugcode']) || empty($drug['name'])) {
                        $skipped++;
                        $errors[] = "ข้อมูลไม่ครบ: " . ($drug['drugcode'] ?? 'unknown');
                        continue;
                    }
                    
                    // ตรวจสอบว่ามียาอยู่แล้วหรือไม่
                    $checkSql = "SELECT id FROM drugs WHERE code = ?";
                    $checkStmt = $this->db->prepare($checkSql);
                    $checkStmt->execute([$drug['drugcode']]);
                    $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($existing) {
                        // อัพเดทข้อมูล
                        $updateSql = "UPDATE drugs SET 
                                        name = ?,
                                        generic_name = ?,
                                        unit = ?,
                                        price = ?,
                                        updated_at = NOW()
                                      WHERE code = ?";
                        $updateStmt = $this->db->prepare($updateSql);
                        $updateStmt->execute([
                            $drug['name'],
                            $drug['genericname'] ?? null,
                            $drug['units'] ?? 'เม็ด',
                            $drug['unitprice'] ?? 0,
                            $drug['drugcode']
                        ]);
                        $updated++;
                    } else {
                        // เพิ่มยาใหม่
                        $insertSql = "INSERT INTO drugs 
                                        (code, name, generic_name, unit, price, category, is_active, created_at, updated_at)
                                      VALUES (?, ?, ?, ?, ?, ?, 1, NOW(), NOW())";
                        $insertStmt = $this->db->prepare($insertSql);
                        $insertStmt->execute([
                            $drug['drugcode'],
                            $drug['name'],
                            $drug['genericname'] ?? null,
                            $drug['units'] ?? 'เม็ด',
                            $drug['unitprice'] ?? 0,
                            'ทั่วไป' // Default category
                        ]);
                        $imported++;
                    }
                    
                } catch (\Exception $e) {
                    $skipped++;
                    $errors[] = "Error importing {$drug['drugcode']}: " . $e->getMessage();
                }
            }
            
            // บันทึก Import History
            try {
                $historySql = "INSERT INTO import_history 
                                (source, imported_count, updated_count, skipped_count, created_at)
                              VALUES (?, ?, ?, ?, NOW())";
                $historyStmt = $this->db->prepare($historySql);
                $historyStmt->execute([
                    'JHCIS Bulk Import',
                    $imported,
                    $updated,
                    $skipped
                ]);
            } catch (\Exception $e) {
                // Ignore history errors
            }
            
            echo json_encode([
                'success' => true,
                'message' => "Import สำเร็จ! เพิ่มใหม่ {$imported} รายการ, อัพเดท {$updated} รายการ" . 
                            ($skipped > 0 ? ", ข้าม {$skipped} รายการ" : ""),
                'imported' => $imported,
                'updated' => $updated,
                'skipped' => $skipped,
                'total' => count($drugs),
                'errors' => $errors
            ]);
            
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
            ]);
        }
        exit;
    }


    /**
     * Import ข้อมูลการจ่ายยาจาก JHCIS (ย้อนหลัง)
     */
    public function importDispensing() {
        header('Content-Type: application/json');
        
        try {
            $this->connectJHCIS();
            
            $startDate = $_POST['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
            $endDate = $_POST['end_date'] ?? date('Y-m-d');
            
            // ตรวจสอบว่าตาราง visitdrug มีคอลัมน์อะไรบ้าง
            $columnsStmt = $this->jhcisDb->query("SHOW COLUMNS FROM visitdrug");
            $columns = $columnsStmt->fetchAll(PDO::FETCH_COLUMN);
            
            // ตรวจสอบคอลัมน์ต่างๆ
            $vnCol = in_array('visitno', $columns) ? 'visitno' : (in_array('vn', $columns) ? 'vn' : 'visitno');
            $dateCol = in_array('datestart', $columns) ? 'datestart' : (in_array('dateupdate', $columns) ? 'dateupdate' : 'datestart');
            $qtyCol = in_array('qty', $columns) ? 'qty' : (in_array('quantity', $columns) ? 'quantity' : 'unit');
            
            // ตรวจสอบว่ามีตาราง patient หรือไม่
            $hasPatient = false;
            $patientJoin = "";
            $nameSelect = "v.$vnCol";
            
            try {
                $this->jhcisDb->query("SELECT 1 FROM patient LIMIT 1");
                $hasPatient = true;
                
                // ตรวจสอบว่า visitdrug มีคอลัมน์ hn หรือไม่
                if (in_array('hn', $columns)) {
                    $patientJoin = "LEFT JOIN patient p ON v.hn = p.hn";
                    $nameSelect = "COALESCE(CONCAT(p.fname, ' ', p.lname), v.$vnCol)";
                }
            } catch (\Exception $e) {
                // patient table doesn't exist, use visitno as name
            }
            
            // ดึงข้อมูลการจ่ายยา
            $sql = "SELECT 
                        " . (in_array('hn', $columns) ? "v.hn" : "v.$vnCol as hn") . ",
                        v.$vnCol as vn,
                        $nameSelect as patient_name,
                        v.$dateCol as vstdate,
                        v.drugcode,
                        d.drugname as drug_name,
                        v.$qtyCol as qty
                    FROM visitdrug v
                    $patientJoin
                    INNER JOIN cdrug d ON v.drugcode = d.drugcode
                    WHERE v.$dateCol BETWEEN ? AND ?
                    ORDER BY v.$dateCol DESC
                    LIMIT 500";
            
            $stmt = $this->jhcisDb->prepare($sql);
            $stmt->execute([$startDate, $endDate]);
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $imported = 0;
            $errors = [];
            
            // Group by VN
            $dispensingGroups = [];
            foreach ($records as $record) {
                $vn = $record['vn'];
                if (!isset($dispensingGroups[$vn])) {
                    $dispensingGroups[$vn] = [
                        'hn' => $record['hn'] ?? $vn,
                        'vn' => $record['vn'],
                        'patient_name' => $record['patient_name'] ?? 'ไม่ระบุชื่อ',
                        'dispense_date' => $record['vstdate'],
                        'items' => []
                    ];
                }
                
                // หา drug_id จาก drugcode
                $drugSql = "SELECT id FROM drugs WHERE code = ?";
                $drugStmt = $this->db->prepare($drugSql);
                $drugStmt->execute([$record['drugcode']]);
                $drug = $drugStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($drug) {
                    $dispensingGroups[$vn]['items'][] = [
                        'drug_id' => $drug['id'],
                        'quantity' => $record['qty'] ?? 1
                    ];
                }
            }
            
            // Insert dispensing records
            foreach ($dispensingGroups as $vn => $group) {
                if (empty($group['items'])) continue;
                
                // ตรวจสอบว่ามี VN นี้แล้วหรือไม่
                $checkSql = "SELECT id FROM dispensing WHERE vn = ?";
                $checkStmt = $this->db->prepare($checkSql);
                $checkStmt->execute([$vn]);
                if ($checkStmt->fetch()) {
                    continue; // ข้ามถ้ามีแล้ว
                }
                
                try {
                    $this->db->beginTransaction();
                    
                    // Insert dispensing header
                    $insertSql = "INSERT INTO dispensing (hn, vn, patient_name, dispense_date, user_id)
                                  VALUES (?, ?, ?, ?, 1)";
                    $insertStmt = $this->db->prepare($insertSql);
                    $insertStmt->execute([
                        $group['hn'],
                        $group['vn'],
                        $group['patient_name'],
                        $group['dispense_date']
                    ]);
                    
                    $dispenseId = $this->db->lastInsertId();
                    
                    // Insert dispensing items (without stock deduction for historical data)
                    $itemSql = "INSERT INTO dispensing_items (dispense_id, drug_id, quantity)
                                VALUES (?, ?, ?)";
                    $itemStmt = $this->db->prepare($itemSql);
                    
                    foreach ($group['items'] as $item) {
                        $itemStmt->execute([
                            $dispenseId,
                            $item['drug_id'],
                            $item['quantity']
                        ]);
                    }
                    
                    $this->db->commit();
                    $imported++;
                    
                } catch (\Exception $e) {
                    $this->db->rollBack();
                    $errors[] = "VN {$vn}: " . $e->getMessage();
                }
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Import การจ่ายยาสำเร็จ',
                'imported' => $imported,
                'total_records' => count($records),
                'errors' => $errors
            ]);
            
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * เชื่อมต่อกับ JHCIS Database
     */
    private function connectJHCIS() {
        // Load .env file if exists
        $this->loadEnv();
        
        // Get connection settings from environment or use defaults
        $host = $this->getEnv('JHCIS_DB_HOST', 'host.docker.internal');
        $port = $this->getEnv('JHCIS_DB_PORT', '3306');
        $database = $this->getEnv('JHCIS_DB_NAME', 'jhcisdb');
        $username = $this->getEnv('JHCIS_DB_USER', 'root');
        $password = $this->getEnv('JHCIS_DB_PASS', '123456');
        
        try {
            $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
            
            $this->jhcisDb = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ]);
        } catch (\PDOException $e) {
            // Provide helpful error message
            $errorMsg = "ไม่สามารถเชื่อมต่อ JHCIS Database ได้\n";
            $errorMsg .= "Host: {$host}:{$port}\n";
            $errorMsg .= "Database: {$database}\n";
            $errorMsg .= "Error: " . $e->getMessage();
            
            throw new \Exception($errorMsg);
        }
    }

    /**
     * Load .env file
     */
    private function loadEnv() {
        $envFile = __DIR__ . '/../../.env';
        
        if (!file_exists($envFile)) {
            return;
        }
        
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            // Skip comments
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            
            // Parse KEY=VALUE
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // Remove quotes if present
                $value = trim($value, '"\'');
                
                // Set environment variable
                putenv("{$key}={$value}");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
    }

    /**
     * Get environment variable with fallback
     */
    private function getEnv($key, $default = null) {
        // Try $_ENV first
        if (isset($_ENV[$key])) {
            return $_ENV[$key];
        }
        
        // Try $_SERVER
        if (isset($_SERVER[$key])) {
            return $_SERVER[$key];
        }
        
        // Try getenv()
        $value = getenv($key);
        if ($value !== false) {
            return $value;
        }
        
        // Return default
        return $default;
    }

    /**
     * ดึงสถิติข้อมูลจาก JHCIS
     */
    public function getStatistics() {
        header('Content-Type: application/json');
        
        try {
            $this->connectJHCIS();
            
            // นับจำนวนยา
            $drugCount = 0;
            try {
                $drugStmt = $this->jhcisDb->query("SELECT COUNT(*) as total FROM cdrug");
                $drugCount = $drugStmt->fetch(PDO::FETCH_ASSOC)['total'];
            } catch (\Exception $e) {
                // Table doesn't exist
            }
            
            // นับจำนวนผู้ป่วย
            $patientCount = 0;
            try {
                $patientStmt = $this->jhcisDb->query("SELECT COUNT(*) as total FROM patient");
                $patientCount = $patientStmt->fetch(PDO::FETCH_ASSOC)['total'];
            } catch (\Exception $e) {
                // Table doesn't exist
            }
            
            // นับจำนวนการจ่ายยาเดือนนี้
            $dispensingCount = 0;
            try {
                $dispensingStmt = $this->jhcisDb->query(
                    "SELECT COUNT(DISTINCT vn) as total 
                     FROM opd_opdcard 
                     WHERE MONTH(vstdate) = MONTH(CURDATE()) 
                     AND YEAR(vstdate) = YEAR(CURDATE())"
                );
                $dispensingCount = $dispensingStmt->fetch(PDO::FETCH_ASSOC)['total'];
            } catch (\Exception $e) {
                // Table doesn't exist, try alternative
                try {
                    $dispensingStmt = $this->jhcisDb->query(
                        "SELECT COUNT(DISTINCT visitno) as total 
                         FROM visitdrug 
                         WHERE MONTH(dateupdate) = MONTH(CURDATE()) 
                         AND YEAR(dateupdate) = YEAR(CURDATE())"
                    );
                    $dispensingCount = $dispensingStmt->fetch(PDO::FETCH_ASSOC)['total'];
                } catch (\Exception $e2) {
                    // Both tables don't exist
                }
            }
            
            echo json_encode([
                'success' => true,
                'statistics' => [
                    'total_drugs' => $drugCount,
                    'total_patients' => $patientCount,
                    'dispensing_this_month' => $dispensingCount
                ]
            ]);
            
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
            ]);
        }
        exit;
    }
}
