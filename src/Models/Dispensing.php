<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class Dispensing {
    
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Get all dispensing records with pagination
     */
    public function getAll($page = 1, $perPage = 20) {
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT d.*, u.full_name as dispensed_by_name,
                COUNT(di.id) as item_count
                FROM dispensing d
                LEFT JOIN users u ON d.user_id = u.id
                LEFT JOIN dispensing_items di ON d.id = di.dispense_id
                GROUP BY d.id
                ORDER BY d.dispense_date DESC
                LIMIT ? OFFSET ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$perPage, $offset]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get total count for pagination
     */
    public function getTotalCount() {
        $sql = "SELECT COUNT(*) as total FROM dispensing";
        $stmt = $this->db->query($sql);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }

    /**
     * Get dispensing by ID
     */
    public function getById($id) {
        $sql = "SELECT d.*, u.full_name as dispensed_by_name
                FROM dispensing d
                LEFT JOIN users u ON d.user_id = u.id
                WHERE d.id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get dispensing items
     */
    public function getItems($dispenseId) {
        $sql = "SELECT di.*, dr.code as drug_code, dr.name as drug_name, 
                dr.unit, dr.generic_name, dr.video_url
                FROM dispensing_items di
                JOIN drugs dr ON di.drug_id = dr.id
                WHERE di.dispense_id = ?
                ORDER BY dr.name";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$dispenseId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Search patient by HN or Name
     */
    public function searchPatient($keyword) {
        // This would typically connect to JHCIS database
        // For now, return from dispensing history
        $sql = "SELECT DISTINCT hn, patient_name, 
                MAX(dispense_date) as last_visit
                FROM dispensing
                WHERE hn LIKE ? OR patient_name LIKE ?
                GROUP BY hn, patient_name
                ORDER BY last_visit DESC
                LIMIT 10";
        
        $searchTerm = "%{$keyword}%";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$searchTerm, $searchTerm]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get patient dispensing history
     */
    public function getPatientHistory($hn, $limit = 10) {
        $sql = "SELECT d.*, u.full_name as dispensed_by_name,
                COUNT(di.id) as item_count
                FROM dispensing d
                LEFT JOIN users u ON d.user_id = u.id
                LEFT JOIN dispensing_items di ON d.id = di.dispense_id
                WHERE d.hn = ?
                GROUP BY d.id
                ORDER BY d.dispense_date DESC
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$hn, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Create new dispensing record
     */
    public function create($data) {
        try {
            $this->db->beginTransaction();

            // Insert dispensing header
            $sql = "INSERT INTO dispensing (hn, vn, patient_name, dispense_date, user_id, clinical_notes)
                    VALUES (?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $data['hn'],
                $data['vn'] ?? null,
                $data['patient_name'],
                $data['dispense_date'] ?? date('Y-m-d H:i:s'),
                $data['user_id'],
                $data['clinical_notes'] ?? null
            ]);

            $dispenseId = $this->db->lastInsertId();

            // Insert dispensing items
            if (!empty($data['items'])) {
                $itemSql = "INSERT INTO dispensing_items (dispense_id, drug_id, quantity, usage_instruction)
                           VALUES (?, ?, ?, ?)";
                $itemStmt = $this->db->prepare($itemSql);

                foreach ($data['items'] as $item) {
                    $itemStmt->execute([
                        $dispenseId,
                        $item['drug_id'],
                        $item['quantity'],
                        $item['usage_instruction'] ?? null
                    ]);

                    // Update inventory - deduct stock
                    $this->deductStock($item['drug_id'], $item['quantity'], $dispenseId);
                }
            }

            $this->db->commit();
            return $dispenseId;

        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log("Dispensing creation error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Deduct stock from inventory
     */
    private function deductStock($drugId, $quantity, $dispenseId) {
        // Get available lots (FEFO - First Expire First Out)
        $sql = "SELECT id, quantity, lot_no 
                FROM inventory 
                WHERE drug_id = ? AND quantity > 0 AND location = 'main'
                ORDER BY expire_date ASC, received_date ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$drugId]);
        $lots = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $remaining = $quantity;
        
        foreach ($lots as $lot) {
            if ($remaining <= 0) break;

            $deduct = min($remaining, $lot['quantity']);
            
            // Update inventory
            $updateSql = "UPDATE inventory 
                         SET quantity = quantity - ? 
                         WHERE id = ?";
            $updateStmt = $this->db->prepare($updateSql);
            $updateStmt->execute([$deduct, $lot['id']]);

            // Record transaction
            $this->recordTransaction($drugId, $deduct, $lot['lot_no'], $dispenseId);

            $remaining -= $deduct;
        }

        if ($remaining > 0) {
            throw new \Exception("Insufficient stock for drug ID: {$drugId}");
        }
    }

    /**
     * Record stock transaction
     */
    private function recordTransaction($drugId, $quantity, $lotNo, $dispenseId) {
        // Get current balance
        $balanceSql = "SELECT COALESCE(SUM(quantity), 0) as total 
                      FROM inventory 
                      WHERE drug_id = ? AND location = 'main'";
        $balanceStmt = $this->db->prepare($balanceSql);
        $balanceStmt->execute([$drugId]);
        $balance = $balanceStmt->fetch(PDO::FETCH_ASSOC)['total'];

        $sql = "INSERT INTO transactions 
                (drug_id, transaction_type, quantity, balance_after, ref_document, user_id, lot_no)
                VALUES (?, 'dispense', ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $drugId,
            -$quantity, // Negative for dispensing
            $balance,
            "DISP-{$dispenseId}",
            $_SESSION['user_id'] ?? 1,
            $lotNo
        ]);
    }

    /**
     * Get dispensing statistics
     */
    public function getStatistics($startDate = null, $endDate = null) {
        $where = "1=1";
        $params = [];

        if ($startDate) {
            $where .= " AND DATE(d.dispense_date) >= ?";
            $params[] = $startDate;
        }

        if ($endDate) {
            $where .= " AND DATE(d.dispense_date) <= ?";
            $params[] = $endDate;
        }

        $sql = "SELECT 
                COUNT(DISTINCT d.id) as total_dispensing,
                COUNT(DISTINCT d.hn) as total_patients,
                COUNT(di.id) as total_items,
                SUM(di.quantity) as total_quantity,
                ROUND(COUNT(di.id) / NULLIF(COUNT(DISTINCT d.id), 0), 2) as avg_items_per_dispensing
                FROM dispensing d
                LEFT JOIN dispensing_items di ON d.id = di.dispense_id
                WHERE {$where}";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get top dispensed drugs
     */
    public function getTopDispensedDrugs($limit = 10, $startDate = null, $endDate = null) {
        $where = "1=1";
        $params = [];

        if ($startDate) {
            $where .= " AND DATE(d.dispense_date) >= ?";
            $params[] = $startDate;
        }

        if ($endDate) {
            $where .= " AND DATE(d.dispense_date) <= ?";
            $params[] = $endDate;
        }

        $sql = "SELECT dr.code as drug_code, dr.name as drug_name, 
                dr.generic_name, dr.unit,
                COUNT(di.id) as dispense_count,
                SUM(di.quantity) as total_quantity
                FROM dispensing_items di
                JOIN drugs dr ON di.drug_id = dr.id
                JOIN dispensing d ON di.dispense_id = d.id
                WHERE {$where}
                GROUP BY di.drug_id
                ORDER BY total_quantity DESC
                LIMIT ?";
        
        $params[] = $limit;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Delete dispensing record (admin only)
     */
    public function delete($id) {
        try {
            $this->db->beginTransaction();

            // Get dispensing items to restore stock
            $items = $this->getItems($id);

            // Delete items
            $deleteSql = "DELETE FROM dispensing_items WHERE dispense_id = ?";
            $deleteStmt = $this->db->prepare($deleteSql);
            $deleteStmt->execute([$id]);

            // Delete dispensing
            $sql = "DELETE FROM dispensing WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);

            // Note: Stock restoration would require more complex logic
            // to identify which lots to restore to

            $this->db->commit();
            return true;

        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log("Dispensing deletion error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get monthly trend data for charts
     */
    public function getMonthlyTrend($months = 6) {
        $sql = "SELECT 
                DATE_FORMAT(dispense_date, '%b %Y') as month,
                COUNT(*) as count
                FROM dispensing
                WHERE dispense_date >= DATE_SUB(NOW(), INTERVAL ? MONTH)
                GROUP BY DATE_FORMAT(dispense_date, '%Y-%m'), DATE_FORMAT(dispense_date, '%b %Y')
                ORDER BY MIN(dispense_date) ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$months]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format for Chart.js
        return array_map(function($row) {
            return [
                'month' => $row['month'],
                'count' => (int)$row['count']
            ];
        }, $results);
    }

    /**
     * Get daily activity data for charts
     */
    public function getDailyActivity($days = 7) {
        $sql = "SELECT 
                DATE_FORMAT(dispense_date, '%d/%m') as date,
                COUNT(*) as count
                FROM dispensing
                WHERE dispense_date >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY DATE(dispense_date), DATE_FORMAT(dispense_date, '%d/%m')
                ORDER BY DATE(dispense_date) ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$days]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format for Chart.js
        return array_map(function($row) {
            return [
                'date' => $row['date'],
                'count' => (int)$row['count']
            ];
        }, $results);
    }
}

