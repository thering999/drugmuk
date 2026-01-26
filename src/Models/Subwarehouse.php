<?php

namespace App\Models;

use PDO;

class Subwarehouse {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    // ========================================================================
    // CRUD Operations
    // ========================================================================
    
    /**
     * ดึงรายการคลังย่อยทั้งหมด
     */
    public function getAll($status = null) {
        $sql = "SELECT * FROM subwarehouses";
        
        if ($status) {
            $sql .= " WHERE status = :status";
        }
        
        $sql .= " ORDER BY code";
        
        $stmt = $this->db->prepare($sql);
        
        if ($status) {
            $stmt->execute(['status' => $status]);
        } else {
            $stmt->execute();
        }
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * ดึงข้อมูลคลังย่อยตาม ID
     */
    public function getById($id) {
        $stmt = $this->db->prepare("
            SELECT 
                s.*,
                u.username as manager_name
            FROM subwarehouses s
            LEFT JOIN users u ON s.manager_id = u.id
            WHERE s.id = :id
        ");
        
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * ดึงข้อมูลคลังย่อยตาม Code
     */
    public function getByCode($code) {
        $stmt = $this->db->prepare("
            SELECT 
                s.*,
                u.username as manager_name
            FROM subwarehouses s
            LEFT JOIN users u ON s.manager_id = u.id
            WHERE s.code = :code
        ");
        
        $stmt->execute(['code' => $code]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * สร้างคลังย่อยใหม่
     */
    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO subwarehouses (code, name, location, manager_id, status, notes)
            VALUES (:code, :name, :location, :manager_id, :status, :notes)
        ");
        
        $stmt->execute([
            'code' => $data['code'],
            'name' => $data['name'],
            'location' => $data['location'] ?? null,
            'manager_id' => $data['manager_id'] ?? null,
            'status' => $data['status'] ?? 'active',
            'notes' => $data['notes'] ?? null
        ]);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * อัพเดทข้อมูลคลังย่อย
     */
    public function update($id, $data) {
        $stmt = $this->db->prepare("
            UPDATE subwarehouses 
            SET name = :name,
                location = :location,
                manager_id = :manager_id,
                status = :status,
                notes = :notes
            WHERE id = :id
        ");
        
        return $stmt->execute([
            'id' => $id,
            'name' => $data['name'],
            'location' => $data['location'] ?? null,
            'manager_id' => $data['manager_id'] ?? null,
            'status' => $data['status'] ?? 'active',
            'notes' => $data['notes'] ?? null
        ]);
    }
    
    /**
     * ลบคลังย่อย
     */
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM subwarehouses WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
    
    // ========================================================================
    // Inventory Management
    // ========================================================================
    
    /**
     * ดึงสต็อกยาในคลังย่อย
     */
    public function getInventory($subwarehouseId, $drugId = null) {
        $sql = "SELECT * FROM v_subwarehouse_stock_summary WHERE subwarehouse_id = :subwarehouse_id";
        
        if ($drugId) {
            $sql .= " AND drug_id = :drug_id";
        }
        
        $sql .= " ORDER BY drug_name";
        
        $stmt = $this->db->prepare($sql);
        
        $params = ['subwarehouse_id' => $subwarehouseId];
        if ($drugId) {
            $params['drug_id'] = $drugId;
        }
        
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * อัพเดทสต็อกยา
     */
    public function updateInventory($subwarehouseId, $drugId, $data) {
        $stmt = $this->db->prepare("
            INSERT INTO subwarehouse_inventory 
            (subwarehouse_id, drug_id, quantity, min_quantity, max_quantity, reorder_point)
            VALUES (:subwarehouse_id, :drug_id, :quantity, :min_quantity, :max_quantity, :reorder_point)
            ON DUPLICATE KEY UPDATE
                quantity = :quantity,
                min_quantity = :min_quantity,
                max_quantity = :max_quantity,
                reorder_point = :reorder_point
        ");
        
        return $stmt->execute([
            'subwarehouse_id' => $subwarehouseId,
            'drug_id' => $drugId,
            'quantity' => $data['quantity'] ?? 0,
            'min_quantity' => $data['min_quantity'] ?? 0,
            'max_quantity' => $data['max_quantity'] ?? 0,
            'reorder_point' => $data['reorder_point'] ?? 0
        ]);
    }
    
    /**
     * ดึงยาที่ใกล้หมด
     */
    public function getLowStockDrugs($subwarehouseId) {
        $stmt = $this->db->prepare("
            SELECT * FROM v_subwarehouse_stock_summary 
            WHERE subwarehouse_id = :subwarehouse_id
            AND stock_status IN ('low', 'critical')
            ORDER BY stock_status DESC, drug_name
        ");
        
        $stmt->execute(['subwarehouse_id' => $subwarehouseId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // ========================================================================
    // Statistics & Reports
    // ========================================================================
    
    /**
     * สรุปสถิติคลังย่อย
     */
    public function getStatistics($subwarehouseId) {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total_drugs,
                SUM(CASE WHEN stock_status = 'critical' THEN 1 ELSE 0 END) as critical_count,
                SUM(CASE WHEN stock_status = 'low' THEN 1 ELSE 0 END) as low_count,
                SUM(CASE WHEN stock_status = 'normal' THEN 1 ELSE 0 END) as normal_count,
                SUM(CASE WHEN stock_status = 'overstock' THEN 1 ELSE 0 END) as overstock_count
            FROM v_subwarehouse_stock_summary
            WHERE subwarehouse_id = :subwarehouse_id
        ");
        
        $stmt->execute(['subwarehouse_id' => $subwarehouseId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * รายงานการจ่ายยา
     */
    public function getDispensingReport($subwarehouseId, $fromDate, $toDate) {
        $stmt = $this->db->prepare("
            SELECT 
                d.code as drug_code,
                d.name as drug_name,
                d.unit,
                SUM(sd.quantity) as total_dispensed,
                COUNT(sd.id) as dispense_count,
                AVG(sd.quantity) as avg_quantity
            FROM subwarehouse_dispensing sd
            INNER JOIN drugs d ON sd.drug_id = d.id
            WHERE sd.subwarehouse_id = :subwarehouse_id
            AND sd.dispense_date BETWEEN :from_date AND :to_date
            GROUP BY d.id
            ORDER BY total_dispensed DESC
        ");
        
        $stmt->execute([
            'subwarehouse_id' => $subwarehouseId,
            'from_date' => $fromDate,
            'to_date' => $toDate
        ]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    /**
     * บันทึกการตั้งค่าสูตรคำนวณ
     */
    public function saveFormula($subwarehouseId, $formulaType, $config) {
        // ในที่นี้เราจะบันทึก Buffer Percentage ลงในคลังย่อยทั้งหมด (ในชีวิตจริงอาจจะแยกตามรายการยา)
        // หรือบันทึกลงในตาราง settings ของคลังย่อย
        $stmt = $this->db->prepare("
            UPDATE subwarehouses 
            SET notes = :notes -- ใช้ฟิลด์ notes เก็บ JSON config ชั่วคราว (หรือสร้างตารางใหม่ถ้าจำเป็น)
            WHERE id = :id
        ");
        
        $notes = json_encode([
            'formula_type' => $formulaType,
            'config' => $config,
            'updated_at' => date('Y-m-d H:i:s')
        ], JSON_UNESCAPED_UNICODE);
        
        return $stmt->execute([
            'id' => $subwarehouseId,
            'notes' => $notes
        ]);
    }
}
