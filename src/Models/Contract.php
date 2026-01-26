<?php

namespace App\Models;

use App\Core\Model;

class Contract extends Model {
    protected $table = 'contracts';

    public function getAllWithDetails() {
        $sql = "SELECT c.*, s.name as supplier_name 
                FROM {$this->table} c 
                JOIN suppliers s ON c.supplier_id = s.id 
                ORDER BY c.end_date ASC";
        return $this->db->query($sql)->fetchAll();
    }

    public function create($data) {
        $sql = "INSERT INTO {$this->table} (contract_no, supplier_id, start_date, end_date, total_amount, status) 
                VALUES (:contract_no, :supplier_id, :start_date, :end_date, :total_amount, :status)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }

    /**
     * Get active contract for a drug
     */
    public function getActiveContractForDrug($drugId) {
        $sql = "SELECT c.*, ci.agreed_price, ci.agreed_quantity, s.name as supplier_name
                FROM {$this->table} c
                JOIN contract_items ci ON c.id = ci.contract_id
                JOIN suppliers s ON c.supplier_id = s.id
                WHERE ci.drug_id = :drug_id 
                AND c.status = 'active'
                AND c.end_date >= CURDATE()
                ORDER BY c.end_date DESC
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['drug_id' => $drugId]);
        return $stmt->fetch();
    }

    /**
     * Get remaining quantity in contract
     */
    public function getRemainingQuantity($contractId, $drugId) {
        // Get agreed quantity
        $sql = "SELECT agreed_quantity FROM contract_items 
                WHERE contract_id = :contract_id AND drug_id = :drug_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['contract_id' => $contractId, 'drug_id' => $drugId]);
        $result = $stmt->fetch();
        $agreedQty = $result['agreed_quantity'] ?? 0;

        // Get ordered quantity
        $orderSql = "SELECT COALESCE(SUM(oi.quantity), 0) as ordered
                     FROM orders o
                     JOIN order_items oi ON o.id = oi.order_id
                     WHERE oi.drug_id = :drug_id
                     AND o.order_date BETWEEN 
                         (SELECT start_date FROM {$this->table} WHERE id = :contract_id)
                         AND
                         (SELECT end_date FROM {$this->table} WHERE id = :contract_id)";
        $orderStmt = $this->db->prepare($orderSql);
        $orderStmt->execute(['drug_id' => $drugId, 'contract_id' => $contractId]);
        $orderResult = $orderStmt->fetch();
        $orderedQty = $orderResult['ordered'] ?? 0;

        return $agreedQty - $orderedQty;
    }

    /**
     * Get expiring contracts
     */
    public function getExpiringContracts($days = 30, $limit = null) {
        $sql = "SELECT c.*, s.name as supplier_name
                FROM {$this->table} c
                JOIN suppliers s ON c.supplier_id = s.id
                WHERE c.status = 'active'
                AND c.end_date <= DATE_ADD(CURDATE(), INTERVAL :days DAY)
                AND c.end_date >= CURDATE()
                ORDER BY c.end_date ASC";
        if ($limit) {
            $sql .= " LIMIT $limit";
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['days' => $days]);
        return $stmt->fetchAll();
    }

    /**
     * Get expiring contracts count
     */
    public function getExpiringContractsCount($days = 30) {
        $sql = "SELECT COUNT(*) as count
                FROM {$this->table}
                WHERE status = 'active'
                AND end_date <= DATE_ADD(CURDATE(), INTERVAL :days DAY)
                AND end_date >= CURDATE()";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['days' => $days]);
        $result = $stmt->fetch();
        return $result['count'] ?? 0;
    }

    /**
     * Get contract by ID
     */
    public function getById($id) {
        $sql = "SELECT c.*, s.name as supplier_name, s.phone as supplier_phone, s.email as supplier_email
                FROM {$this->table} c
                LEFT JOIN suppliers s ON c.supplier_id = s.id
                WHERE c.id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Update contract
     */
    public function update($id, $data) {
        $sql = "UPDATE {$this->table} 
                SET contract_no = :contract_no,
                    supplier_id = :supplier_id,
                    start_date = :start_date,
                    end_date = :end_date,
                    total_amount = :total_amount,
                    status = :status
                WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $data['id'] = $id;
        return $stmt->execute($data);
    }

    /**
     * Delete contract
     */
    public function delete($id) {
        // Delete contract items first
        $this->deleteContractItems($id);
        
        // Delete contract
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Get contract items
     */
    public function getContractItems($contractId) {
        $sql = "SELECT ci.*, d.name as drug_name, d.code as drug_code, d.unit
                FROM contract_items ci
                JOIN drugs d ON ci.drug_id = d.id
                WHERE ci.contract_id = :contract_id
                ORDER BY d.name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['contract_id' => $contractId]);
        return $stmt->fetchAll();
    }

    /**
     * Add contract item
     */
    public function addContractItem($data) {
        $sql = "INSERT INTO contract_items (contract_id, drug_id, agreed_price, agreed_quantity)
                VALUES (:contract_id, :drug_id, :agreed_price, :agreed_quantity)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }

    /**
     * Update contract item
     */
    public function updateContractItem($id, $data) {
        $sql = "UPDATE contract_items
                SET drug_id = :drug_id,
                    agreed_price = :agreed_price,
                    agreed_quantity = :agreed_quantity
                WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $data['id'] = $id;
        return $stmt->execute($data);
    }

    /**
     * Delete contract item
     */
    public function deleteContractItem($id) {
        $sql = "DELETE FROM contract_items WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Delete all contract items for a contract
     */
    public function deleteContractItems($contractId) {
        $sql = "DELETE FROM contract_items WHERE contract_id = :contract_id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['contract_id' => $contractId]);
    }

    /**
     * Get contract statistics
     */
    public function getStatistics() {
        $sql = "SELECT 
                    COUNT(*) as total_contracts,
                    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_contracts,
                    SUM(CASE WHEN status = 'expired' THEN 1 ELSE 0 END) as expired_contracts,
                    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_contracts,
                    SUM(total_amount) as total_value
                FROM {$this->table}";
        $stmt = $this->db->query($sql);
        return $stmt->fetch();
    }
}
