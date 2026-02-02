<?php
/**
 * Loan Management Controller
 * 
 * จัดการการยืม-คืนยาระหว่างหน่วยงาน
 * 
 * @package Drugmuk
 * @subpackage Controllers
 * @version 1.0
 * @since Phase 2
 */

namespace App\Controllers;

use PDO;
use Exception;

class LoanController {
    
    private $db;
    
    public function __construct() {
        $this->db = \App\Core\Database::getInstance()->getConnection();
    }
    
    // ========================================================================
    // VIEW METHODS
    // ========================================================================
    
    /**
     * หน้ารายการการยืมยา
     * GET /loans
     */
    public function index() {
        $status = $_GET['status'] ?? 'all';
        
        $sql = "
            SELECT 
                l.id,
                l.loan_number,
                l.loan_date,
                l.from_facility,
                l.to_facility,
                l.status,
                l.expected_return_date,
                COUNT(li.id) as total_items,
                SUM(li.quantity_borrowed) as total_borrowed,
                SUM(li.quantity_returned) as total_returned,
                u.username as created_by_name
            FROM drug_loans l
            LEFT JOIN drug_loan_items li ON l.id = li.loan_id
            LEFT JOIN users u ON l.created_by = u.id
        ";
        
        if ($status !== 'all') {
            $sql .= " WHERE l.status = :status";
        }
        
        $sql .= " GROUP BY l.id ORDER BY l.loan_date DESC, l.id DESC";
        
        $stmt = $this->db->prepare($sql);
        if ($status !== 'all') {
            $stmt->execute(['status' => $status]);
        } else {
            $stmt->execute();
        }
        
        $loans = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        include __DIR__ . '/../Views/loans/index.php';
    }
    
    /**
     * หน้าสร้างการยืมยาใหม่
     * GET /loans/create
     */
    public function create() {
        // ดึงรายการยาทั้งหมด
        $drugs = $this->db->query("
            SELECT 
                d.id,
                d.code,
                d.name,
                i.quantity as available_quantity
            FROM drugs d
            LEFT JOIN inventory i ON d.id = i.drug_id
            WHERE i.quantity > 0
            ORDER BY d.name
        ")->fetchAll(PDO::FETCH_ASSOC);
        
        include __DIR__ . '/../Views/loans/create.php';
    }
    
    /**
     * หน้ารายละเอียดการยืม
     * GET /loans/{id}
     */
    public function show($id) {
        // ดึงข้อมูลการยืม
        $loan = $this->db->prepare("
            SELECT 
                l.*,
                u1.username as created_by_name,
                u2.username as approved_by_name
            FROM drug_loans l
            LEFT JOIN users u1 ON l.created_by = u1.id
            LEFT JOIN users u2 ON l.approved_by = u2.id
            WHERE l.id = ?
        ");
        $loan->execute([$id]);
        $loan = $loan->fetch(PDO::FETCH_ASSOC);
        
        if (!$loan) {
            header('Location: /loans?error=not_found');
            exit;
        }
        
        // ดึงรายการยาที่ยืม
        $items = $this->db->prepare("
            SELECT 
                li.*,
                d.code,
                d.name as drug_name
            FROM drug_loan_items li
            INNER JOIN drugs d ON li.drug_id = d.id
            WHERE li.loan_id = ?
            ORDER BY d.name
        ");
        $items->execute([$id]);
        $items = $items->fetchAll(PDO::FETCH_ASSOC);
        
        include __DIR__ . '/../Views/loans/show.php';
    }
    
    /**
     * หน้าคืนยา
     * GET /loans/{id}/return
     */
    public function returnForm($id) {
        // ดึงข้อมูลการยืม
        $loan = $this->db->prepare("
            SELECT * FROM drug_loans WHERE id = ?
        ");
        $loan->execute([$id]);
        $loan = $loan->fetch(PDO::FETCH_ASSOC);
        
        if (!$loan || !in_array($loan['status'], ['approved', 'partially_returned'])) {
            header('Location: /loans?error=invalid_status');
            exit;
        }
        
        // ดึงรายการยาที่ยังไม่ได้คืนครบ
        $items = $this->db->prepare("
            SELECT 
                li.*,
                d.code,
                d.name as drug_name,
                (li.quantity_borrowed - li.quantity_returned) as outstanding_quantity
            FROM drug_loan_items li
            INNER JOIN drugs d ON li.drug_id = d.id
            WHERE li.loan_id = ?
              AND li.quantity_returned < li.quantity_borrowed
            ORDER BY d.name
        ");
        $items->execute([$id]);
        $items = $items->fetchAll(PDO::FETCH_ASSOC);
        
        include __DIR__ . '/../Views/loans/return.php';
    }
    
    // ========================================================================
    // ACTION METHODS
    // ========================================================================
    
    /**
     * บันทึกการยืมยาใหม่
     * POST /loans/store
     */
    public function store() {
        $data = $_POST;
        
        // Validate
        if (empty($data['to_facility'])) {
            header('Location: /loans/create?error=facility_required');
            exit;
        }
        
        if (empty($data['items']) || !is_array($data['items'])) {
            header('Location: /loans/create?error=items_required');
            exit;
        }
        
        $this->db->beginTransaction();
        
        try {
            // 1. สร้างเลขที่การยืม
            $loanNumber = $this->generateLoanNumber();
            
            // 2. บันทึก Header
            $stmt = $this->db->prepare("
                INSERT INTO drug_loans (
                    loan_number,
                    loan_date,
                    from_facility,
                    to_facility,
                    expected_return_date,
                    status,
                    notes,
                    created_by,
                    created_at
                ) VALUES (?, ?, ?, ?, ?, 'pending', ?, ?, NOW())
            ");
            
            $stmt->execute([
                $loanNumber,
                $data['loan_date'] ?? date('Y-m-d'),
                $_SESSION['facility_name'] ?? 'Main Warehouse',
                $data['to_facility'],
                $data['expected_return_date'] ?? null,
                $data['notes'] ?? null,
                $_SESSION['user_id']
            ]);
            
            $loanId = $this->db->lastInsertId();
            
            // 3. บันทึกรายการยา
            foreach ($data['items'] as $item) {
                if (empty($item['drug_id']) || empty($item['quantity'])) {
                    continue;
                }
                
                // ตรวจสอบสต็อก
                $available = $this->getAvailableQuantity($item['drug_id']);
                if ($available < $item['quantity']) {
                    throw new Exception("สต็อกไม่พอสำหรับยา ID: {$item['drug_id']}");
                }
                
                // บันทึกรายการ
                $stmt = $this->db->prepare("
                    INSERT INTO drug_loan_items (
                        loan_id,
                        drug_id,
                        lot_number,
                        expire_date,
                        quantity_borrowed,
                        unit,
                        notes,
                        created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                
                $stmt->execute([
                    $loanId,
                    $item['drug_id'],
                    $item['lot_number'] ?? null,
                    $item['expire_date'] ?? null,
                    $item['quantity'],
                    $item['unit'] ?? 'tablet',
                    $item['notes'] ?? null
                ]);
            }
            
            $this->db->commit();
            
            header("Location: /loans/$loanId?success=created");
            exit;
            
        } catch (Exception $e) {
            $this->db->rollback();
            header('Location: /loans/create?error=' . urlencode($e->getMessage()));
            exit;
        }
    }
    
    /**
     * อนุมัติการยืม
     * POST /loans/{id}/approve
     */
    public function approve($id) {
        $this->db->beginTransaction();
        
        try {
            $stmt = $this->db->prepare("
                UPDATE drug_loans 
                SET status = 'approved',
                    approved_by = ?,
                    approved_at = NOW(),
                    updated_at = NOW()
                WHERE id = ? AND status = 'pending'
            ");
            
            $stmt->execute([$_SESSION['user_id'], $id]);
            
            if ($stmt->rowCount() === 0) {
                throw new Exception("ไม่สามารถอนุมัติได้ (สถานะไม่ถูกต้อง)");
            }
            
            $this->db->commit();
            
            header("Location: /loans/$id?success=approved");
            exit;
            
        } catch (Exception $e) {
            $this->db->rollback();
            header("Location: /loans/$id?error=" . urlencode($e->getMessage()));
            exit;
        }
    }
    
    /**
     * บันทึกการคืนยา
     * POST /loans/{id}/return
     */
    public function processReturn($id) {
        $data = $_POST;
        
        if (empty($data['items']) || !is_array($data['items'])) {
            header("Location: /loans/$id/return?error=items_required");
            exit;
        }
        
        $this->db->beginTransaction();
        
        try {
            foreach ($data['items'] as $itemId => $returnQty) {
                if (empty($returnQty) || $returnQty <= 0) {
                    continue;
                }
                
                // ดึงข้อมูลรายการ
                $stmt = $this->db->prepare("
                    SELECT 
                        quantity_borrowed,
                        quantity_returned,
                        drug_id
                    FROM drug_loan_items 
                    WHERE id = ?
                ");
                $stmt->execute([$itemId]);
                $item = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$item) {
                    throw new Exception("ไม่พบรายการ ID: $itemId");
                }
                
                // ตรวจสอบจำนวนคืน
                $newReturned = $item['quantity_returned'] + $returnQty;
                if ($newReturned > $item['quantity_borrowed']) {
                    throw new Exception("จำนวนคืนเกินจำนวนยืม");
                }
                
                // อัพเดทจำนวนคืน
                $stmt = $this->db->prepare("
                    UPDATE drug_loan_items 
                    SET quantity_returned = ?,
                        return_date = ?,
                        updated_at = NOW()
                    WHERE id = ?
                ");
                
                $stmt->execute([
                    $newReturned,
                    date('Y-m-d'),
                    $itemId
                ]);
            }
            
            // อัพเดทสถานะการยืม
            $this->updateLoanStatus($id);
            
            $this->db->commit();
            
            header("Location: /loans/$id?success=returned");
            exit;
            
        } catch (Exception $e) {
            $this->db->rollback();
            header("Location: /loans/$id/return?error=" . urlencode($e->getMessage()));
            exit;
        }
    }
    
    /**
     * ยกเลิกการยืม
     * POST /loans/{id}/cancel
     */
    public function cancel($id) {
        $this->db->beginTransaction();
        
        try {
            // ตรวจสอบว่ายังไม่มีการคืนยา
            $stmt = $this->db->prepare("
                SELECT SUM(quantity_returned) as total_returned
                FROM drug_loan_items
                WHERE loan_id = ?
            ");
            $stmt->execute([$id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['total_returned'] > 0) {
                throw new Exception("ไม่สามารถยกเลิกได้ (มีการคืนยาแล้ว)");
            }
            
            // ยกเลิก
            $stmt = $this->db->prepare("
                UPDATE drug_loans 
                SET status = 'cancelled',
                    updated_at = NOW()
                WHERE id = ? AND status = 'pending'
            ");
            $stmt->execute([$id]);
            
            if ($stmt->rowCount() === 0) {
                throw new Exception("ไม่สามารถยกเลิกได้ (สถานะไม่ถูกต้อง)");
            }
            
            // คืนสต็อก
            $this->restoreInventoryFromLoan($id);
            
            $this->db->commit();
            
            header("Location: /loans/$id?success=cancelled");
            exit;
            
        } catch (Exception $e) {
            $this->db->rollback();
            header("Location: /loans/$id?error=" . urlencode($e->getMessage()));
            exit;
        }
    }
    
    // ========================================================================
    // HELPER METHODS
    // ========================================================================
    
    /**
     * สร้างเลขที่การยืม
     * Format: LOAN-YYYYMMDD-XXX
     */
    private function generateLoanNumber() {
        $prefix = 'LOAN-' . date('Ymd') . '-';
        
        // หาเลขที่ล่าสุดของวันนี้
        $stmt = $this->db->prepare("
            SELECT loan_number 
            FROM drug_loans 
            WHERE loan_number LIKE ? 
            ORDER BY loan_number DESC 
            LIMIT 1
        ");
        $stmt->execute([$prefix . '%']);
        $last = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($last) {
            $lastNumber = intval(substr($last['loan_number'], -3));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        
        return $prefix . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    }
    
    /**
     * ดึงจำนวนสต็อกที่มี
     */
    private function getAvailableQuantity($drugId) {
        $stmt = $this->db->prepare("
            SELECT quantity 
            FROM inventory 
            WHERE drug_id = ?
        ");
        $stmt->execute([$drugId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ? $result['quantity'] : 0;
    }
    
    /**
     * อัพเดทสถานะการยืม
     */
    private function updateLoanStatus($loanId) {
        $stmt = $this->db->prepare("
            SELECT 
                SUM(quantity_borrowed) as total_borrowed,
                SUM(quantity_returned) as total_returned
            FROM drug_loan_items
            WHERE loan_id = ?
        ");
        $stmt->execute([$loanId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $newStatus = 'approved';
        if ($result['total_returned'] > 0) {
            if ($result['total_returned'] >= $result['total_borrowed']) {
                $newStatus = 'fully_returned';
            } else {
                $newStatus = 'partially_returned';
            }
        }
        
        $stmt = $this->db->prepare("
            UPDATE drug_loans 
            SET status = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$newStatus, $loanId]);
    }
    
    /**
     * คืนสต็อกเมื่อยกเลิกการยืม
     */
    private function restoreInventoryFromLoan($loanId) {
        $stmt = $this->db->prepare("
            SELECT drug_id, quantity_borrowed 
            FROM drug_loan_items 
            WHERE loan_id = ?
        ");
        $stmt->execute([$loanId]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($items as $item) {
            $stmt = $this->db->prepare("
                UPDATE inventory 
                SET quantity = quantity + ?,
                    updated_at = NOW()
                WHERE drug_id = ?
            ");
            $stmt->execute([$item['quantity_borrowed'], $item['drug_id']]);
        }
    }
    
    // ========================================================================
    // REPORT METHODS
    // ========================================================================
    
    /**
     * รายงานยาค้างคืน
     * GET /loans/outstanding
     */
    public function outstanding() {
        $loans = $this->db->query("
            SELECT * FROM v_outstanding_loans
            ORDER BY days_outstanding DESC
        ")->fetchAll(PDO::FETCH_ASSOC);
        
        include __DIR__ . '/../Views/loans/outstanding.php';
    }
    
    /**
     * รายงานสรุปการยืม-คืน
     * GET /loans/summary
     */
    public function summary() {
        $fromDate = $_GET['from_date'] ?? date('Y-m-01');
        $toDate = $_GET['to_date'] ?? date('Y-m-d');
        
        $stmt = $this->db->prepare("
            SELECT 
                d.name as drug_name,
                SUM(li.quantity_borrowed) as total_borrowed,
                SUM(li.quantity_returned) as total_returned,
                COUNT(DISTINCT l.id) as loan_count
            FROM drug_loan_items li
            INNER JOIN drug_loans l ON li.loan_id = l.id
            INNER JOIN drugs d ON li.drug_id = d.id
            WHERE l.loan_date BETWEEN ? AND ?
            GROUP BY d.id
            ORDER BY total_borrowed DESC
        ");
        $stmt->execute([$fromDate, $toDate]);
        $summary = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        include __DIR__ . '/../Views/loans/summary.php';
    }
}
