<?php

namespace App\Models;

use App\Core\Database;

class SubInventory
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Get stock for a sub-warehouse
     */
    public function getStock($warehouseCode)
    {
        $stmt = $this->db->prepare("
            SELECT si.*, d.code as drug_code, d.name as drug_name, d.unit
            FROM sub_inventory si
            JOIN drugs d ON si.drug_id = d.id
            WHERE si.warehouse_code = ?
            ORDER BY d.name
        ");
        $stmt->execute([$warehouseCode]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Calculate requisition quantities based on formula
     */
    public function calculateRequisitionQuantities($warehouseCode)
    {
        // Get formulas for this warehouse
        $formulas = $this->getRequisitionFormulas($warehouseCode);
        
        $suggestions = [];
        
        foreach ($formulas as $formula) {
            $drugId = $formula['drug_id'];
            
            // Get current stock
            $currentStock = $this->getCurrentStock($warehouseCode, $drugId);
            
            // Get usage rate (average daily usage from last 30 days)
            $usageRate = $this->getAverageUsageRate($warehouseCode, $drugId, 30);
            
            // Calculate suggested quantity
            // Formula: (usage_rate * reorder_cycle_days) - current_stock + (usage_rate * min_stock_days)
            $minStockDays = $formula['min_stock_days'] ?? 30;
            $reorderCycleDays = $formula['reorder_cycle_days'] ?? 30;
            
            $suggestedQty = ($usageRate * $reorderCycleDays) - $currentStock + ($usageRate * $minStockDays);
            $suggestedQty = max(0, ceil($suggestedQty)); // Round up, minimum 0
            
            if ($suggestedQty > 0) {
                $suggestions[] = [
                    'drug_id' => $drugId,
                    'current_stock' => $currentStock,
                    'usage_rate' => $usageRate,
                    'suggested_qty' => $suggestedQty,
                    'min_stock' => $usageRate * $minStockDays
                ];
            }
        }
        
        return $suggestions;
    }

    /**
     * Get current stock for a drug in warehouse
     */
    private function getCurrentStock($warehouseCode, $drugId)
    {
        $stmt = $this->db->prepare("
            SELECT COALESCE(SUM(quantity), 0) as stock
            FROM sub_inventory
            WHERE warehouse_code = ? AND drug_id = ?
        ");
        $stmt->execute([$warehouseCode, $drugId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result['stock'] ?? 0;
    }

    /**
     * Get average daily usage rate
     */
    private function getAverageUsageRate($warehouseCode, $drugId, $days = 30)
    {
        $stmt = $this->db->prepare("
            SELECT COALESCE(SUM(di.quantity), 0) / ? as avg_daily
            FROM dispensing d
            JOIN dispensing_items di ON d.id = di.dispense_id
            WHERE d.dispense_date >= DATE_SUB(NOW(), INTERVAL ? DAY)
            AND di.drug_id = ?
        ");
        $stmt->execute([$days, $days, $drugId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result['avg_daily'] ?? 0;
    }

    /**
     * Create requisition request
     */
    public function createRequisition($data)
    {
        try {
            $this->db->beginTransaction();

            // Create requisition header
            $reqNo = 'REQ' . date('YmdHis');
            $stmt = $this->db->prepare("
                INSERT INTO requisitions (req_no, req_date, status, requested_by)
                VALUES (?, NOW(), 'pending', ?)
            ");
            $stmt->execute([$reqNo, $data['requested_by']]);
            $requisitionId = $this->db->lastInsertId();

            // Create requisition items
            $itemStmt = $this->db->prepare("
                INSERT INTO requisition_items (requisition_id, drug_id, quantity_requested)
                VALUES (?, ?, ?)
            ");

            // Create pending records
            $pendingStmt = $this->db->prepare("
                INSERT INTO inventory_pending (warehouse_code, drug_id, quantity_requested, quantity_pending, urgent)
                VALUES (?, ?, ?, ?, ?)
            ");

            foreach ($data['items'] as $item) {
                $itemStmt->execute([
                    $requisitionId,
                    $item['drug_id'],
                    $item['quantity']
                ]);

                $pendingStmt->execute([
                    $data['warehouse_code'],
                    $item['drug_id'],
                    $item['quantity'],
                    $item['quantity'],
                    $data['urgent'] ? 1 : 0
                ]);
            }

            $this->db->commit();
            return $requisitionId;
        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log("Requisition creation failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get pending requisition requests
     */
    public function getPendingRequests($warehouseCode)
    {
        $stmt = $this->db->prepare("
            SELECT ip.*, d.code as drug_code, d.name as drug_name
            FROM inventory_pending ip
            JOIN drugs d ON ip.drug_id = d.id
            WHERE ip.warehouse_code = ? AND ip.status = 'pending'
            ORDER BY ip.urgent DESC, ip.request_date ASC
        ");
        $stmt->execute([$warehouseCode]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Receive items from main warehouse
     */
    public function receiveFromMain($data)
    {
        try {
            $this->db->beginTransaction();

            foreach ($data['items'] as $item) {
                // Add to sub-inventory
                $stmt = $this->db->prepare("
                    INSERT INTO sub_inventory (warehouse_code, drug_id, lot_no, expire_date, quantity, cost_price, received_date)
                    VALUES (?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([
                    $data['warehouse_code'],
                    $item['drug_id'],
                    $item['lot_no'],
                    $item['expire_date'],
                    $item['quantity'],
                    $item['cost_price']
                ]);

                // Update pending if exists
                $updatePending = $this->db->prepare("
                    UPDATE inventory_pending
                    SET quantity_approved = quantity_approved + ?,
                        quantity_pending = quantity_pending - ?,
                        status = CASE WHEN quantity_pending - ? <= 0 THEN 'completed' ELSE 'partial' END
                    WHERE warehouse_code = ? AND drug_id = ? AND status IN ('pending', 'partial')
                    LIMIT 1
                ");
                $updatePending->execute([
                    $item['quantity'],
                    $item['quantity'],
                    $item['quantity'],
                    $data['warehouse_code'],
                    $item['drug_id']
                ]);
            }

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log("Receive from main failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Dispense to patient
     */
    public function dispenseToPatient($data)
    {
        try {
            $this->db->beginTransaction();

            // Create dispensing record
            $stmt = $this->db->prepare("
                INSERT INTO dispensing (hn, vn, patient_name, dispense_date, user_id)
                VALUES (?, ?, ?, NOW(), ?)
            ");
            $stmt->execute([
                $data['hn'],
                $data['vn'],
                $data['patient_name'],
                $data['user_id']
            ]);
            $dispenseId = $this->db->lastInsertId();

            // Create dispensing items and deduct from sub-inventory
            foreach ($data['items'] as $item) {
                // Record dispensing
                $itemStmt = $this->db->prepare("
                    INSERT INTO dispensing_items (dispense_id, drug_id, quantity)
                    VALUES (?, ?, ?)
                ");
                $itemStmt->execute([
                    $dispenseId,
                    $item['drug_id'],
                    $item['quantity']
                ]);

                // Deduct from sub-inventory (FEFO)
                $this->deductStockFEFO($data['warehouse_code'], $item['drug_id'], $item['quantity']);
            }

            $this->db->commit();
            return $dispenseId;
        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log("Dispensing failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Deduct stock using FEFO (First Expire, First Out)
     */
    private function deductStockFEFO($warehouseCode, $drugId, $quantity)
    {
        // Get lots ordered by expiry date (FEFO)
        $stmt = $this->db->prepare("
            SELECT id, quantity, lot_no
            FROM sub_inventory
            WHERE warehouse_code = ? AND drug_id = ? AND quantity > 0
            ORDER BY expire_date ASC, received_date ASC
        ");
        $stmt->execute([$warehouseCode, $drugId]);
        $lots = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $remaining = $quantity;
        foreach ($lots as $lot) {
            if ($remaining <= 0) break;

            $deduct = min($remaining, $lot['quantity']);
            
            $updateStmt = $this->db->prepare("
                UPDATE sub_inventory
                SET quantity = quantity - ?
                WHERE id = ?
            ");
            $updateStmt->execute([$deduct, $lot['id']]);

            $remaining -= $deduct;
        }

        if ($remaining > 0) {
            throw new \Exception("Insufficient stock for drug ID: $drugId");
        }
    }

    /**
     * Get requisition formulas
     */
    public function getRequisitionFormulas($warehouseCode)
    {
        $stmt = $this->db->prepare("
            SELECT rf.*, d.code as drug_code, d.name as drug_name
            FROM requisition_formulas rf
            LEFT JOIN drugs d ON rf.drug_id = d.id
            WHERE rf.warehouse_code = ?
        ");
        $stmt->execute([$warehouseCode]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Save requisition formula
     */
    public function saveRequisitionFormula($data)
    {
        $stmt = $this->db->prepare("
            INSERT INTO requisition_formulas 
            (warehouse_code, drug_id, drug_category, formula, min_stock_days, reorder_cycle_days)
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            formula = VALUES(formula),
            min_stock_days = VALUES(min_stock_days),
            reorder_cycle_days = VALUES(reorder_cycle_days)
        ");
        
        return $stmt->execute([
            $data['warehouse_code'],
            $data['drug_id'],
            $data['drug_category'],
            $data['formula'],
            $data['min_stock_days'],
            $data['reorder_cycle_days']
        ]);
    }
}
