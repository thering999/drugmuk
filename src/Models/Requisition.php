<?php

namespace App\Models;

use PDO;

class Requisition {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    // ========================================================================
    // CRUD Operations
    // ========================================================================
    
    /**
     * ดึงรายการใบขอเบิกทั้งหมด
     */
    public function getAll($subwarehouseId = null, $status = null) {
        $sql = "SELECT * FROM v_requisition_summary WHERE 1=1";
        $params = [];
        
        if ($subwarehouseId) {
            $sql .= " AND subwarehouse_id = :subwarehouse_id";
            $params['subwarehouse_id'] = $subwarehouseId;
        }
        
        if ($status) {
            $sql .= " AND status = :status";
            $params['status'] = $status;
        }
        
        $sql .= " ORDER BY request_date DESC, id DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * ดึงข้อมูลใบขอเบิกตาม ID
     */
    public function getById($id) {
        $stmt = $this->db->prepare("
            SELECT 
                r.*,
                s.code as subwarehouse_code,
                s.name as subwarehouse_name,
                u1.username as requested_by_name,
                u2.username as approved_by_name,
                u3.username as dispensed_by_name
            FROM requisitions r
            INNER JOIN subwarehouses s ON r.subwarehouse_id = s.id
            INNER JOIN users u1 ON r.requested_by = u1.id
            LEFT JOIN users u2 ON r.approved_by = u2.id
            LEFT JOIN users u3 ON r.dispensed_by = u3.id
            WHERE r.id = :id
        ");
        
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * ดึงรายการยาในใบขอเบิก
     */
    public function getItems($requisitionId) {
        $stmt = $this->db->prepare("
            SELECT 
                ri.*,
                d.code as drug_code,
                d.name as drug_name,
                d.unit as drug_unit
            FROM requisition_items ri
            INNER JOIN drugs d ON ri.drug_id = d.id
            WHERE ri.requisition_id = :requisition_id
            ORDER BY d.name
        ");
        
        $stmt->execute(['requisition_id' => $requisitionId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * สร้างใบขอเบิกใหม่
     */
    public function create($data, $items) {
        try {
            $this->db->beginTransaction();
            
            // สร้างเลขที่ใบขอเบิก
            $requisitionNo = $this->generateRequisitionNo($data['subwarehouse_id']);
            
            // Insert requisition
            $stmt = $this->db->prepare("
                INSERT INTO requisitions 
                (requisition_no, subwarehouse_id, requested_by, request_date, status, notes)
                VALUES (:requisition_no, :subwarehouse_id, :requested_by, :request_date, :status, :notes)
            ");
            
            $stmt->execute([
                'requisition_no' => $requisitionNo,
                'subwarehouse_id' => $data['subwarehouse_id'],
                'requested_by' => $data['requested_by'],
                'request_date' => $data['request_date'] ?? date('Y-m-d'),
                'status' => 'pending',
                'notes' => $data['notes'] ?? null
            ]);
            
            $requisitionId = $this->db->lastInsertId();
            
            // Insert items
            $stmt = $this->db->prepare("
                INSERT INTO requisition_items 
                (requisition_id, drug_id, requested_quantity, unit, notes)
                VALUES (:requisition_id, :drug_id, :requested_quantity, :unit, :notes)
            ");
            
            foreach ($items as $item) {
                $stmt->execute([
                    'requisition_id' => $requisitionId,
                    'drug_id' => $item['drug_id'],
                    'requested_quantity' => $item['quantity'],
                    'unit' => $item['unit'] ?? null,
                    'notes' => $item['notes'] ?? null
                ]);
            }
            
            $this->db->commit();
            return $requisitionId;
            
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    /**
     * อนุมัติใบขอเบิก
     */
    public function approve($id, $userId, $items) {
        try {
            $this->db->beginTransaction();
            
            // Update requisition status
            $stmt = $this->db->prepare("
                UPDATE requisitions 
                SET status = 'approved',
                    approved_by = :approved_by,
                    approved_date = NOW()
                WHERE id = :id
            ");
            
            $stmt->execute([
                'id' => $id,
                'approved_by' => $userId
            ]);
            
            // Update approved quantities
            $stmt = $this->db->prepare("
                UPDATE requisition_items 
                SET approved_quantity = :approved_quantity
                WHERE id = :id
            ");
            
            foreach ($items as $item) {
                $stmt->execute([
                    'id' => $item['id'],
                    'approved_quantity' => $item['approved_quantity']
                ]);
            }
            
            $this->db->commit();
            return true;
            
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    /**
     * ปฏิเสธใบขอเบิก
     */
    public function reject($id, $userId, $reason) {
        $stmt = $this->db->prepare("
            UPDATE requisitions 
            SET status = 'rejected',
                approved_by = :approved_by,
                approved_date = NOW(),
                notes = CONCAT(COALESCE(notes, ''), '\n\nRejected: ', :reason)
            WHERE id = :id
        ");
        
        return $stmt->execute([
            'id' => $id,
            'approved_by' => $userId,
            'reason' => $reason
        ]);
    }
    
    /**
     * จ่ายยาตามใบขอเบิก
     */
    public function dispense($id, $userId, $items) {
        try {
            $this->db->beginTransaction();
            
            // Update requisition status
            $stmt = $this->db->prepare("
                UPDATE requisitions 
                SET status = 'dispensed',
                    dispensed_by = :dispensed_by,
                    dispensed_date = NOW()
                WHERE id = :id
            ");
            
            $stmt->execute([
                'id' => $id,
                'dispensed_by' => $userId
            ]);
            
            // Update dispensed quantities
            $stmt = $this->db->prepare("
                UPDATE requisition_items 
                SET dispensed_quantity = :dispensed_quantity
                WHERE id = :id
            ");
            
            foreach ($items as $item) {
                $stmt->execute([
                    'id' => $item['id'],
                    'dispensed_quantity' => $item['dispensed_quantity']
                ]);
                
                // ลดสต็อกคลังหลัก
                $this->updateMainInventory($item['drug_id'], -$item['dispensed_quantity']);
            }
            
            $this->db->commit();
            return true;
            
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    /**
     * ยกเลิกใบขอเบิก
     */
    public function cancel($id, $reason) {
        $stmt = $this->db->prepare("
            UPDATE requisitions 
            SET status = 'cancelled',
                notes = CONCAT(COALESCE(notes, ''), '\n\nCancelled: ', :reason)
            WHERE id = :id AND status = 'pending'
        ");
        
        return $stmt->execute([
            'id' => $id,
            'reason' => $reason
        ]);
    }
    
    // ========================================================================
    // Auto-Requisition
    // ========================================================================
    
    /**
     * สร้างใบขอเบิกอัตโนมัติ
     */
    public function autoGenerate($subwarehouseId, $userId) {
        // ดึงสูตรคำนวณ
        $formula = $this->getFormula($subwarehouseId);
        
        // คำนวณปริมาณที่ต้องเบิก
        $items = $this->calculateRequisitionQuantities($subwarehouseId, $formula);
        
        if (empty($items)) {
            return null;
        }
        
        // สร้างใบขอเบิก
        return $this->create([
            'subwarehouse_id' => $subwarehouseId,
            'requested_by' => $userId,
            'request_date' => date('Y-m-d'),
            'notes' => 'Auto-generated based on ' . $formula['formula_type'] . ' formula'
        ], $items);
    }
    
    /**
     * คำนวณปริมาณที่ต้องเบิก
     */
    private function calculateRequisitionQuantities($subwarehouseId, $formula) {
        $items = [];
        
        // ดึงสต็อกปัจจุบัน
        $stmt = $this->db->prepare("
            SELECT 
                drug_id,
                current_stock,
                min_quantity,
                max_quantity,
                reorder_point
            FROM v_subwarehouse_stock_summary
            WHERE subwarehouse_id = :subwarehouse_id
            AND stock_status IN ('low', 'critical')
        ");
        
        $stmt->execute(['subwarehouse_id' => $subwarehouseId]);
        $stocks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($stocks as $stock) {
            $quantity = 0;
            
            switch ($formula['formula_type']) {
                case 'max_min':
                    // ปริมาณเบิก = Max - Current
                    $quantity = $stock['max_quantity'] - $stock['current_stock'];
                    break;
                    
                case 'average_usage':
                    // ปริมาณเบิก = การใช้เฉลี่ย * จำนวนวัน
                    $avgUsage = $this->getAverageUsage(
                        $subwarehouseId, 
                        $stock['drug_id'], 
                        $formula['formula_config']['period_days'] ?? 30
                    );
                    $quantity = $avgUsage * ($formula['formula_config']['period_days'] ?? 30);
                    break;
                    
                case 'custom':
                    // Custom formula
                    $quantity = $this->applyCustomFormula($stock, $formula['formula_config']);
                    break;
            }
            
            if ($quantity > 0) {
                $items[] = [
                    'drug_id' => $stock['drug_id'],
                    'quantity' => round($quantity, 2),
                    'unit' => null
                ];
            }
        }
        
        return $items;
    }
    
    /**
     * ดึงการใช้ยาเฉลี่ย
     */
    private function getAverageUsage($subwarehouseId, $drugId, $days) {
        $stmt = $this->db->prepare("
            SELECT AVG(daily_usage) as avg_usage
            FROM (
                SELECT 
                    DATE(dispense_date) as date,
                    SUM(quantity) as daily_usage
                FROM subwarehouse_dispensing
                WHERE subwarehouse_id = :subwarehouse_id
                AND drug_id = :drug_id
                AND dispense_date >= DATE_SUB(CURDATE(), INTERVAL :days DAY)
                GROUP BY DATE(dispense_date)
            ) daily
        ");
        
        $stmt->execute([
            'subwarehouse_id' => $subwarehouseId,
            'drug_id' => $drugId,
            'days' => $days
        ]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['avg_usage'] ?? 0;
    }
    
    // ========================================================================
    // Helper Functions
    // ========================================================================
    
    /**
     * สร้างเลขที่ใบขอเบิก
     */
    private function generateRequisitionNo($subwarehouseId) {
        $prefix = 'REQ';
        $date = date('Ymd');
        
        // ดึงเลขที่ล่าสุด
        $stmt = $this->db->prepare("
            SELECT requisition_no 
            FROM requisitions 
            WHERE subwarehouse_id = :subwarehouse_id
            AND requisition_no LIKE :pattern
            ORDER BY id DESC 
            LIMIT 1
        ");
        
        $stmt->execute([
            'subwarehouse_id' => $subwarehouseId,
            'pattern' => $prefix . $date . '%'
        ]);
        
        $last = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($last) {
            $lastNo = intval(substr($last['requisition_no'], -4));
            $newNo = str_pad($lastNo + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNo = '0001';
        }
        
        return $prefix . $date . $newNo;
    }
    
    /**
     * ดึงสูตรคำนวณ
     */
    private function getFormula($subwarehouseId) {
        $stmt = $this->db->prepare("
            SELECT * FROM requisition_formulas
            WHERE subwarehouse_id = :subwarehouse_id
            AND is_default = TRUE
            LIMIT 1
        ");
        
        $stmt->execute(['subwarehouse_id' => $subwarehouseId]);
        $formula = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($formula && $formula['formula_config']) {
            $formula['formula_config'] = json_decode($formula['formula_config'], true);
        }
        
        return $formula ?: [
            'formula_type' => 'max_min',
            'formula_config' => ['buffer_percentage' => 10]
        ];
    }
    
    /**
     * อัพเดทสต็อกคลังหลัก
     */
    private function updateMainInventory($drugId, $quantity) {
        $stmt = $this->db->prepare("
            UPDATE inventory 
            SET quantity = quantity + :quantity
            WHERE drug_id = :drug_id
        ");
        
        return $stmt->execute([
            'drug_id' => $drugId,
            'quantity' => $quantity
        ]);
    }
    
    /**
     * Apply custom formula
     */
    private function applyCustomFormula($stock, $config) {
        // Implement custom formula logic here
        return 0;
    }
}
