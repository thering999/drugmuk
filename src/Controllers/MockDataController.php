<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;

/**
 * Mock Data Seeder for Testing
 * สร้างข้อมูลตัวอย่างสำหรับทดสอบระบบ
 */
class MockDataController extends Controller {
    
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * หน้าหลัก Mock Data
     */
    public function index() {
        $this->view('mock-data/index');
    }

    /**
     * สร้างข้อมูลยาตัวอย่าง
     */
    public function generateDrugs() {
        header('Content-Type: application/json');
        
        try {
            $mockDrugs = [
                ['code' => 'PAR500', 'name' => 'PARACETAMOL 500 MG', 'generic' => 'Paracetamol', 'unit' => 'เม็ด', 'price' => 0.50],
                ['code' => 'AMX500', 'name' => 'AMOXICILLIN 500 MG', 'generic' => 'Amoxicillin', 'unit' => 'แคปซูล', 'price' => 2.50],
                ['code' => 'OMP20', 'name' => 'OMEPRAZOLE 20 MG', 'generic' => 'Omeprazole', 'unit' => 'แคปซูล', 'price' => 3.00],
                ['code' => 'MET500', 'name' => 'METFORMIN 500 MG', 'generic' => 'Metformin', 'unit' => 'เม็ด', 'price' => 1.00],
                ['code' => 'AML5', 'name' => 'AMLODIPINE 5 MG', 'generic' => 'Amlodipine', 'unit' => 'เม็ด', 'price' => 1.50],
                ['code' => 'ATO10', 'name' => 'ATORVASTATIN 10 MG', 'generic' => 'Atorvastatin', 'unit' => 'เม็ด', 'price' => 2.00],
                ['code' => 'CPM4', 'name' => 'CHLORPHENIRAMINE 4 MG', 'generic' => 'Chlorpheniramine', 'unit' => 'เม็ด', 'price' => 0.30],
                ['code' => 'DIC50', 'name' => 'DICLOFENAC 50 MG', 'generic' => 'Diclofenac', 'unit' => 'เม็ด', 'price' => 1.20],
                ['code' => 'CET10', 'name' => 'CETIRIZINE 10 MG', 'generic' => 'Cetirizine', 'unit' => 'เม็ด', 'price' => 0.80],
                ['code' => 'VIT', 'name' => 'VITAMIN B-COMPLEX', 'generic' => 'Vitamin B Complex', 'unit' => 'เม็ด', 'price' => 0.50],
            ];

            $imported = 0;
            $updated = 0;

            foreach ($mockDrugs as $drug) {
                // ตรวจสอบว่ามียาอยู่แล้วหรือไม่
                $checkSql = "SELECT id FROM drugs WHERE code = ?";
                $checkStmt = $this->db->prepare($checkSql);
                $checkStmt->execute([$drug['code']]);
                $existing = $checkStmt->fetch(\PDO::FETCH_ASSOC);
                
                if ($existing) {
                    // อัพเดทข้อมูล
                    $updateSql = "UPDATE drugs SET 
                                    name = ?,
                                    generic_name = ?,
                                    unit = ?,
                                    price = ?
                                  WHERE code = ?";
                    $updateStmt = $this->db->prepare($updateSql);
                    $updateStmt->execute([
                        $drug['name'],
                        $drug['generic'],
                        $drug['unit'],
                        $drug['price'],
                        $drug['code']
                    ]);
                    $updated++;
                } else {
                    // เพิ่มยาใหม่
                    $insertSql = "INSERT INTO drugs 
                                    (code, name, generic_name, unit, price, category, is_active)
                                  VALUES (?, ?, ?, ?, ?, 'ยาทั่วไป', 1)";
                    $insertStmt = $this->db->prepare($insertSql);
                    $insertStmt->execute([
                        $drug['code'],
                        $drug['name'],
                        $drug['generic'],
                        $drug['unit'],
                        $drug['price']
                    ]);
                    $imported++;
                }
            }

            echo json_encode([
                'success' => true,
                'message' => 'สร้างข้อมูลยาตัวอย่างสำเร็จ',
                'imported' => $imported,
                'updated' => $updated,
                'total' => count($mockDrugs)
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
     * สร้างข้อมูล Inventory ตัวอย่าง
     */
    public function generateInventory() {
        header('Content-Type: application/json');
        
        try {
            // Get all drugs
            $drugsStmt = $this->db->query("SELECT id, code FROM drugs LIMIT 10");
            $drugs = $drugsStmt->fetchAll(\PDO::FETCH_ASSOC);

            $imported = 0;

            foreach ($drugs as $drug) {
                // สร้าง inventory 2-3 lot ต่อยา
                for ($i = 1; $i <= 2; $i++) {
                    $lotNo = 'LOT' . date('Ym') . sprintf('%03d', $i);
                    $quantity = rand(100, 500);
                    $expireDate = date('Y-m-d', strtotime('+' . rand(6, 24) . ' months'));

                    // ตรวจสอบว่ามี lot นี้แล้วหรือไม่
                    $checkSql = "SELECT id FROM inventory WHERE drug_id = ? AND lot_no = ?";
                    $checkStmt = $this->db->prepare($checkSql);
                    $checkStmt->execute([$drug['id'], $lotNo]);
                    
                    if (!$checkStmt->fetch()) {
                        $insertSql = "INSERT INTO inventory 
                                        (drug_id, lot_no, quantity, expire_date, received_date, location)
                                      VALUES (?, ?, ?, ?, NOW(), 'main')";
                        $insertStmt = $this->db->prepare($insertSql);
                        $insertStmt->execute([
                            $drug['id'],
                            $lotNo,
                            $quantity,
                            $expireDate
                        ]);
                        $imported++;
                    }
                }
            }

            echo json_encode([
                'success' => true,
                'message' => 'สร้างข้อมูล Inventory ตัวอย่างสำเร็จ',
                'imported' => $imported
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
     * สร้างข้อมูลการจ่ายยาตัวอย่าง
     */
    public function generateDispensing() {
        header('Content-Type: application/json');
        
        try {
            // Get drugs with inventory
            $drugsStmt = $this->db->query("
                SELECT DISTINCT d.id, d.code, d.name 
                FROM drugs d
                INNER JOIN inventory i ON d.id = i.drug_id
                WHERE i.quantity > 0
                LIMIT 5
            ");
            $drugs = $drugsStmt->fetchAll(\PDO::FETCH_ASSOC);

            if (empty($drugs)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'กรุณาสร้างข้อมูล Inventory ก่อน'
                ]);
                exit;
            }

            $imported = 0;

            // สร้างการจ่ายยา 10 ครั้ง
            for ($i = 1; $i <= 10; $i++) {
                $hn = 'HN' . sprintf('%06d', rand(1, 1000));
                $vn = 'VN' . date('Ymd') . sprintf('%04d', $i);
                $patientName = 'ผู้ป่วยตัวอย่าง ' . $i;
                $dispenseDate = date('Y-m-d H:i:s', strtotime('-' . rand(0, 30) . ' days'));

                // ตรวจสอบว่ามี VN นี้แล้วหรือไม่
                $checkSql = "SELECT id FROM dispensing WHERE vn = ?";
                $checkStmt = $this->db->prepare($checkSql);
                $checkStmt->execute([$vn]);
                
                if ($checkStmt->fetch()) {
                    continue; // ข้ามถ้ามีแล้ว
                }

                $this->db->beginTransaction();

                try {
                    // Insert dispensing header
                    $insertSql = "INSERT INTO dispensing (hn, vn, patient_name, dispense_date, user_id)
                                  VALUES (?, ?, ?, ?, 1)";
                    $insertStmt = $this->db->prepare($insertSql);
                    $insertStmt->execute([$hn, $vn, $patientName, $dispenseDate]);
                    
                    $dispenseId = $this->db->lastInsertId();

                    // Insert 2-3 items per dispensing (without stock deduction)
                    $numItems = rand(2, 3);
                    $selectedDrugs = array_rand($drugs, min($numItems, count($drugs)));
                    if (!is_array($selectedDrugs)) {
                        $selectedDrugs = [$selectedDrugs];
                    }

                    foreach ($selectedDrugs as $drugIndex) {
                        $drug = $drugs[$drugIndex];
                        $quantity = rand(10, 30);

                        $itemSql = "INSERT INTO dispensing_items (dispense_id, drug_id, quantity)
                                    VALUES (?, ?, ?)";
                        $itemStmt = $this->db->prepare($itemSql);
                        $itemStmt->execute([$dispenseId, $drug['id'], $quantity]);
                    }

                    $this->db->commit();
                    $imported++;

                } catch (\Exception $e) {
                    $this->db->rollBack();
                }
            }

            echo json_encode([
                'success' => true,
                'message' => 'สร้างข้อมูลการจ่ายยาตัวอย่างสำเร็จ',
                'imported' => $imported
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
     * ล้างข้อมูลทั้งหมด
     */
    public function clearAll() {
        header('Content-Type: application/json');
        
        try {
            $this->db->exec("SET FOREIGN_KEY_CHECKS = 0");
            $this->db->exec("TRUNCATE TABLE dispensing_items");
            $this->db->exec("TRUNCATE TABLE dispensing");
            $this->db->exec("TRUNCATE TABLE inventory");
            $this->db->exec("TRUNCATE TABLE transactions");
            $this->db->exec("DELETE FROM drugs WHERE id > 0");
            $this->db->exec("SET FOREIGN_KEY_CHECKS = 1");

            echo json_encode([
                'success' => true,
                'message' => 'ล้างข้อมูลทั้งหมดสำเร็จ'
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
