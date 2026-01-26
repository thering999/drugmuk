<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use PDO;

class SampleDataController extends Controller {
    
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function index() {
        $this->view('sample-data/index');
    }
    
    public function insert() {
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            $this->db->beginTransaction();
            
            // 1. Insert Drugs
            $drugs = $this->insertDrugs();
            
            // 2. Insert Inventory
            $inventory = $this->insertInventory();
            
            // 3. Insert Hospital
            $hospitals = $this->insertHospital();
            
            // 4. Insert Sync Logs
            $syncLogs = $this->insertSyncLogs();
            
            $this->db->commit();
            
            echo json_encode([
                'success' => true,
                'message' => 'เพิ่มข้อมูลตัวอย่างสำเร็จ',
                'drugs' => $drugs,
                'inventory' => $inventory,
                'hospitals' => $hospitals,
                'sync_logs' => $syncLogs
            ], JSON_UNESCAPED_UNICODE);
            
        } catch (\Exception $e) {
            $this->db->rollBack();
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
    
    private function insertDrugs() {
        $drugs = [
            ['PAR500', 'Paracetamol 500mg', 'Paracetamol', 'เม็ด', 2.50, 'ยาแก้ปวด'],
            ['AMO500', 'Amoxicillin 500mg', 'Amoxicillin', 'แคปซูล', 5.00, 'ยาปฏิชีวนะ'],
            ['OME20', 'Omeprazole 20mg', 'Omeprazole', 'แคปซูล', 8.50, 'ยาลดกรด'],
            ['MET500', 'Metformin 500mg', 'Metformin', 'เม็ด', 3.00, 'ยาเบาหวาน'],
            ['AML5', 'Amlodipine 5mg', 'Amlodipine', 'เม็ด', 4.00, 'ยาความดัน'],
            ['CET10', 'Cetirizine 10mg', 'Cetirizine', 'เม็ด', 2.00, 'ยาแพ้'],
            ['IBU400', 'Ibuprofen 400mg', 'Ibuprofen', 'เม็ด', 3.50, 'ยาแก้ปวด'],
            ['VIT100', 'Vitamin C 100mg', 'Ascorbic Acid', 'เม็ด', 1.50, 'วิตามิน'],
            ['DIC50', 'Diclofenac 50mg', 'Diclofenac', 'เม็ด', 4.50, 'ยาแก้ปวด'],
            ['LOS50', 'Losartan 50mg', 'Losartan', 'เม็ด', 6.00, 'ยาความดัน'],
            ['SIM20', 'Simvastatin 20mg', 'Simvastatin', 'เม็ด', 5.50, 'ยาลดไขมัน'],
            ['ASP100', 'Aspirin 100mg', 'Acetylsalicylic Acid', 'เม็ด', 2.00, 'ยาละลายลิ่มเลือด'],
            ['DOM10', 'Domperidone 10mg', 'Domperidone', 'เม็ด', 3.00, 'ยาแก้คลื่นไส้'],
            ['GLI5', 'Glibenclamide 5mg', 'Glibenclamide', 'เม็ด', 2.50, 'ยาเบาหวาน'],
            ['PRE5', 'Prednisolone 5mg', 'Prednisolone', 'เม็ด', 4.00, 'ยาสเตียรอยด์'],
            ['SAL100', 'Salbutamol Inhaler', 'Salbutamol', 'ขวด', 150.00, 'ยาขยายหลอดลม'],
            ['FLU20', 'Fluoxetine 20mg', 'Fluoxetine', 'แคปซูล', 8.00, 'ยาซึมเศร้า'],
            ['ATO10', 'Atorvastatin 10mg', 'Atorvastatin', 'เม็ด', 7.00, 'ยาลดไขมัน'],
            ['CHL500', 'Chlorpheniramine 4mg', 'Chlorpheniramine', 'เม็ด', 1.50, 'ยาแพ้'],
            ['NOR5', 'Norfloxacin 400mg', 'Norfloxacin', 'เม็ด', 6.50, 'ยาปฏิชีวนะ']
        ];
        
        $sql = "INSERT INTO drugs (code, name, generic_name, unit, price, category, is_active, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, 1, NOW(), NOW())
                ON DUPLICATE KEY UPDATE 
                name = VALUES(name), 
                generic_name = VALUES(generic_name),
                unit = VALUES(unit),
                price = VALUES(price),
                category = VALUES(category),
                updated_at = NOW()";
        
        $stmt = $this->db->prepare($sql);
        $count = 0;
        
        foreach ($drugs as $drug) {
            $stmt->execute($drug);
            $count++;
        }
        
        return $count;
    }
    
    private function insertInventory() {
        $sql = "INSERT INTO inventory (drug_id, quantity, min_stock, max_stock, location, updated_at)
                SELECT id, 
                       FLOOR(100 + RAND() * 900) as quantity,
                       50 as min_stock,
                       1000 as max_stock,
                       'คลังหลัก' as location,
                       NOW() as updated_at
                FROM drugs
                ON DUPLICATE KEY UPDATE 
                quantity = VALUES(quantity),
                updated_at = NOW()";
        
        $this->db->exec($sql);
        return $this->db->query("SELECT COUNT(*) FROM inventory")->fetchColumn();
    }
    
    private function insertHospital() {
        $checkSql = "SELECT COUNT(*) FROM jhcis_hospitals";
        $count = $this->db->query($checkSql)->fetchColumn();
        
        if ($count == 0) {
            $sql = "INSERT INTO jhcis_hospitals (name, host, port, database_name, username, password, is_active, created_at)
                    VALUES ('รพ.สต.ตัวอย่าง', 'host.docker.internal', 3306, 'jhcisdb', 'root', '123456', 1, NOW())";
            $this->db->exec($sql);
            return 1;
        }
        
        return $count;
    }
    
    private function insertSyncLogs() {
        $hospitalSql = "SELECT id FROM jhcis_hospitals LIMIT 1";
        $hospitalId = $this->db->query($hospitalSql)->fetchColumn();
        
        if (!$hospitalId) {
            return 0;
        }
        
        $logs = [
            [$hospitalId, 'dispensing', 'completed', 150, '-5 days 08:00:00', '-5 days 08:05:30'],
            [$hospitalId, 'drugs', 'completed', 85, '-4 days 09:00:00', '-4 days 09:03:15'],
            [$hospitalId, 'dispensing', 'completed', 200, '-3 days 10:00:00', '-3 days 10:08:45'],
            [$hospitalId, 'drugs', 'completed', 120, '-2 days 11:00:00', '-2 days 11:04:20'],
            [$hospitalId, 'dispensing', 'completed', 175, '-1 day 14:00:00', '-1 day 14:06:30'],
            [$hospitalId, 'drugs', 'completed', 95, 'today 08:00:00', 'today 08:03:45']
        ];
        
        $sql = "INSERT INTO jhcis_sync_log (hospital_id, sync_type, status, records_synced, started_at, completed_at)
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $count = 0;
        
        foreach ($logs as $log) {
            $stmt->execute([
                $log[0],
                $log[1],
                $log[2],
                $log[3],
                date('Y-m-d H:i:s', strtotime($log[4])),
                date('Y-m-d H:i:s', strtotime($log[5]))
            ]);
            $count++;
        }
        
        return $count;
    }
}
