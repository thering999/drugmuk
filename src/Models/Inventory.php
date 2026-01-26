<?php

namespace App\Models;

use App\Core\Model;

class Inventory extends Model {
    protected $table = 'inventory';

    public function getAllWithDrugs() {
        $sql = "SELECT i.*, d.name as drug_name, d.code as drug_code, d.unit 
                FROM {$this->table} i 
                JOIN drugs d ON i.drug_id = d.id 
                WHERE i.quantity > 0
                ORDER BY d.name ASC, i.expire_date ASC";
        return $this->db->query($sql)->fetchAll();
    }

    public function receive($data) {
        // Insert into inventory
        $sql = "INSERT INTO {$this->table} (drug_id, lot_no, expire_date, quantity, cost_price, location, received_date) 
                VALUES (:drug_id, :lot_no, :expire_date, :quantity, :cost_price, 'main', CURDATE())";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);

        // Record transaction
        $this->recordTransaction($data['drug_id'], 'receive', $data['quantity'], $data['quantity'], $data['lot_no']);
    }

    private function recordTransaction($drug_id, $type, $quantity, $balance, $lot_no) {
        $sql = "INSERT INTO transactions (drug_id, transaction_type, quantity, balance_after, lot_no, user_id) 
                VALUES (:drug_id, :type, :quantity, :balance, :lot_no, :user_id)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'drug_id' => $drug_id,
            'type' => $type,
            'quantity' => $quantity,
            'balance' => $balance,
            'lot_no' => $lot_no,
            'user_id' => $_SESSION['user_id'] ?? 1
        ]);
    }

    /**
     * Get stock summary
     */
    public function getStockSummary() {
        $sql = "SELECT 
                    COUNT(DISTINCT drug_id) as total_items,
                    SUM(quantity) as total_quantity,
                    SUM(quantity * cost_price) as total_value
                FROM {$this->table}
                WHERE quantity > 0";
        return $this->db->query($sql)->fetch();
    }

    /**
     * Get low stock items
     */
    public function getLowStockItems($limit = null) {
        $sql = "SELECT d.id, d.code, d.name as drug_name, d.min_stock,
                    COALESCE(SUM(i.quantity), 0) as current_stock
                FROM drugs d
                LEFT JOIN {$this->table} i ON d.id = i.drug_id
                WHERE d.is_active = 1
                GROUP BY d.id, d.code, d.name, d.min_stock
                HAVING current_stock < d.min_stock
                ORDER BY current_stock ASC";
        if ($limit) {
            $sql .= " LIMIT $limit";
        }
        return $this->db->query($sql)->fetchAll();
    }

    /**
     * Get low stock count
     */
    public function getLowStockCount() {
        $sql = "SELECT COUNT(*) as count
                FROM (
                    SELECT d.id, d.min_stock, COALESCE(SUM(i.quantity), 0) as current_stock
                    FROM drugs d
                    LEFT JOIN {$this->table} i ON d.id = i.drug_id
                    WHERE d.is_active = 1
                    GROUP BY d.id, d.min_stock
                    HAVING current_stock < d.min_stock
                ) as low_stock";
        $result = $this->db->query($sql)->fetch();
        return $result['count'] ?? 0;
    }

    /**
     * Get expiring items
     */
    public function getExpiringItems($days = 90, $limit = null) {
        $sql = "SELECT i.*, d.code, d.name as drug_name
                FROM {$this->table} i
                JOIN drugs d ON i.drug_id = d.id
                WHERE i.expire_date <= DATE_ADD(CURDATE(), INTERVAL :days DAY)
                AND i.quantity > 0
                ORDER BY i.expire_date ASC";
        if ($limit) {
            $sql .= " LIMIT $limit";
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['days' => $days]);
        return $stmt->fetchAll();
    }

    /**
     * Get expiring soon count
     */
    public function getExpiringSoonCount($days = 90) {
        $sql = "SELECT COUNT(*) as count
                FROM {$this->table}
                WHERE expire_date <= DATE_ADD(CURDATE(), INTERVAL :days DAY)
                AND quantity > 0";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['days' => $days]);
        $result = $stmt->fetch();
        return $result['count'] ?? 0;
    }

    /**
     * Get current stock for a drug
     */
    public function getCurrentStock($drugId) {
        $sql = "SELECT COALESCE(SUM(quantity), 0) as stock
                FROM {$this->table}
                WHERE drug_id = :drug_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['drug_id' => $drugId]);
        $result = $stmt->fetch();
        return $result['stock'] ?? 0;
    }

    /**
     * Get available stock (for disbursement)
     */
    public function getAvailableStock($drugId) {
        return $this->getCurrentStock($drugId);
    }

    /**
     * Get pending issue quantity
     */
    public function getPendingIssue($drugId) {
        $sql = "SELECT COALESCE(SUM(quantity_pending), 0) as pending
                FROM inventory_pending
                WHERE drug_id = :drug_id AND status = 'pending'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['drug_id' => $drugId]);
        $result = $stmt->fetch();
        return $result['pending'] ?? 0;
    }

    /**
     * Get pending disbursements
     */
    public function getPendingDisbursements() {
        $sql = "SELECT ip.*, d.code, d.name as drug_name
                FROM inventory_pending ip
                JOIN drugs d ON ip.drug_id = d.id
                WHERE ip.status = 'pending'
                ORDER BY ip.urgent DESC, ip.request_date ASC";
        return $this->db->query($sql)->fetchAll();
    }

    /**
     * Get pending disbursements count
     */
    public function getPendingDisbursementsCount() {
        $sql = "SELECT COUNT(*) as count FROM inventory_pending WHERE status = 'pending'";
        $result = $this->db->query($sql)->fetch();
        return $result['count'] ?? 0;
    }

    /**
     * Get disbursement request by ID
     */
    public function getDisbursementRequest($requestId) {
        $sql = "SELECT * FROM inventory_pending WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $requestId]);
        return $stmt->fetch();
    }

    /**
     * Create pending disbursement
     */
    public function createPendingDisbursement($data) {
        $sql = "INSERT INTO inventory_pending (warehouse_code, drug_id, quantity_pending, status)
                VALUES (:warehouse_code, :drug_id, :quantity_pending, 'pending')";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }

    /**
     * Disburse using FEFO (First Expire, First Out)
     */
    public function disburseFEFO($data) {
        try {
            $this->db->beginTransaction();

            // Get lots ordered by expiry date (FEFO)
            $sql = "SELECT id, quantity, lot_no, expire_date
                    FROM {$this->table}
                    WHERE drug_id = :drug_id AND quantity > 0
                    ORDER BY expire_date ASC, received_date ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['drug_id' => $data['drug_id']]);
            $lots = $stmt->fetchAll();

            $remaining = $data['quantity'];
            foreach ($lots as $lot) {
                if ($remaining <= 0) break;

                $deduct = min($remaining, $lot['quantity']);
                
                // Update inventory
                $updateSql = "UPDATE {$this->table} SET quantity = quantity - :deduct WHERE id = :id";
                $updateStmt = $this->db->prepare($updateSql);
                $updateStmt->execute(['deduct' => $deduct, 'id' => $lot['id']]);

                // Record transaction
                $this->recordTransaction($data['drug_id'], 'transfer_out', $deduct, 0, $lot['lot_no']);

                $remaining -= $deduct;
            }

            // Update pending request
            if (isset($data['request_id'])) {
                $updatePending = "UPDATE inventory_pending 
                                 SET quantity_approved = :qty, status = 'completed'
                                 WHERE id = :id";
                $stmt = $this->db->prepare($updatePending);
                $stmt->execute(['qty' => $data['quantity'], 'id' => $data['request_id']]);
            }

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log("FEFO disbursement failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Reject disbursement request
     */
    public function rejectDisbursement($requestId) {
        $sql = "UPDATE inventory_pending SET status = 'cancelled' WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id' => $requestId]);
    }

    /**
     * Get stock card transactions
     */
    public function getStockCardTransactions($drugId, $limit = 100) {
        $sql = "SELECT t.*, u.full_name as user_name
                FROM transactions t
                LEFT JOIN users u ON t.user_id = u.id
                WHERE t.drug_id = :drug_id
                ORDER BY t.transaction_date DESC
                LIMIT :limit";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':drug_id', $drugId, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Create adjustment
     */
    public function createAdjustment($data) {
        $sql = "INSERT INTO inventory_adjustments 
                (warehouse_code, drug_id, lot_no, adjustment_type, quantity, reason, adjusted_by)
                VALUES (:warehouse_code, :drug_id, :lot_no, :adjustment_type, :quantity, :reason, :adjusted_by)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }

    /**
     * Create transfer
     */
    public function createTransfer($data) {
        $sql = "INSERT INTO inventory_transfers 
                (from_warehouse, to_warehouse, drug_id, lot_no, quantity, transferred_by, notes)
                VALUES (:from_warehouse, :to_warehouse, :drug_id, :lot_no, :quantity, :transferred_by, :notes)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }

    /**
     * Get warehouses
     */
    public function getWarehouses() {
        return [
            ['code' => 'main', 'name' => 'คลังใหญ่'],
            ['code' => 'sub1', 'name' => 'คลังย่อย 1'],
            ['code' => 'sub2', 'name' => 'คลังย่อย 2']
        ];
    }

    /**
     * Receive items
     */
    public function receiveItems($data) {
        try {
            $this->db->beginTransaction();

            foreach ($data['items'] as $item) {
                // Add to inventory
                $this->receive($item);

                // Update order pending if order_id exists
                if ($data['order_id']) {
                    $updateSql = "UPDATE order_pending 
                                 SET quantity_received = quantity_received + :qty,
                                     quantity_pending = quantity_pending - :qty,
                                     status = CASE WHEN quantity_pending - :qty <= 0 THEN 'completed' ELSE 'partial' END
                                 WHERE order_id = :order_id AND drug_id = :drug_id";
                    $stmt = $this->db->prepare($updateSql);
                    $stmt->execute([
                        'qty' => $item['quantity'],
                        'order_id' => $data['order_id'],
                        'drug_id' => $item['drug_id']
                    ]);
                }
            }

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log("Receive items failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get recent receives
     */
    public function getRecentReceives($limit = 5) {
        $sql = "SELECT t.*, d.name as drug_name, u.full_name as user_name
                FROM transactions t
                JOIN drugs d ON t.drug_id = d.id
                LEFT JOIN users u ON t.user_id = u.id
                WHERE t.transaction_type = 'receive'
                ORDER BY t.transaction_date DESC
                LIMIT :limit";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
