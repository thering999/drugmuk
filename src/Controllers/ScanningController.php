<?php

namespace App\Controllers;

use App\Core\Database;
use PDO;

class ScanningController
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Scanning Interface (View)
     */
    public function index()
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
        require_once __DIR__ . '/../Views/scanning/index.php';
    }

    /**
     * API: Lookup drug by barcode
     * POST /api/scan/lookup
     */
    public function lookup()
    {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $code = $input['code'] ?? null;

        if (!$code) {
            echo json_encode(['success' => false, 'message' => 'Code is required']);
            exit;
        }

        try {
            // Search in drugs table (assuming 'code' or 'barcode' column)
            // Also searching in drug_barcodes table if exists (for multiple barcodes per drug)
            
            // Check if drug_barcodes table exists? For now assume simplified schema: drugs.code = barcode
            // Or drugs.barcode column. Let's check 'drugs' table schema later. 
            // For now, I'll search by 'code' (primary) and 'common_name' (fuzzy) just in case.
            
            // NOTE: Ideally we should have a 'barcode' column. I'll search 'code' for now.
            
            $stmt = $this->db->prepare("
                SELECT 
                    d.*,
                    i.quantity as stock_qty,
                    (SELECT SUM(quantity) FROM sub_inventory WHERE drug_id = d.id) as sub_stock_qty
                FROM drugs d
                LEFT JOIN inventory i ON d.id = i.drug_id
                WHERE d.code = ?
            ");
            $stmt->execute([$code]);
            $drug = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($drug) {
                echo json_encode([
                    'success' => true,
                    'found' => true,
                    'drug' => $drug
                ]);
            } else {
                echo json_encode([
                    'success' => true,
                    'found' => false,
                    'message' => 'Drug not found'
                ]);
            }

        } catch (\PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
    }
    /**
     * API: Process batch scan operations
     * POST /api/scan/batch
     */
    public function processBatch()
    {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $type = $input['type'] ?? null; // 'receive' or 'dispense'
        $items = $input['items'] ?? [];
        $userId = $_SESSION['user_id'];

        if (!$type || empty($items)) {
            echo json_encode(['success' => false, 'message' => 'Invalid data']);
            exit;
        }

        try {
            $this->db->beginTransaction();

            $successCount = 0;
            $errors = [];

            if ($type === 'receive') {
                // Use InventoryModel logic
                // Simple receive logic: Add to inventory, record transaction
                // For batch receive, we might need Lot No / Exp Date. 
                // For "Quick Scan Receive", we will assume default Lot (QuickBatch) and default year expiry if not set.
                // Or simply direct SQL update for simplicity if detailed receiving is not required here.
                // Better: Use a simplified logic similar to InventoryModel::receiveItems
                
                foreach ($items as $item) {
                    $drugId = $item['id'];
                    $qty = $item['quantity'];

                    // 1. Get current stock
                    $stmt = $this->db->prepare("SELECT quantity FROM inventory WHERE drug_id = ?");
                    $stmt->execute([$drugId]);
                    $current = $stmt->fetchColumn() ?: 0;

                    // 2. Update/Insert Inventory
                     $stmt = $this->db->prepare("
                        INSERT INTO inventory (drug_id, quantity, updated_at) 
                        VALUES (?, ?, NOW())
                        ON DUPLICATE KEY UPDATE quantity = quantity + ?, updated_at = NOW()
                    ");
                    $stmt->execute([$drugId, $qty, $qty]);
                    
                    // 3. Record Transaction
                    $stmt = $this->db->prepare("
                        INSERT INTO inventory_transactions 
                        (drug_id, transaction_type, quantity, balance, user_id, transaction_date, notes) 
                        VALUES (?, 'in', ?, ?, ?, NOW(), 'Batch Scan Receive')
                    ");
                    $stmt->execute([$drugId, $qty, $current + $qty, $userId]);
                    
                    $successCount++;
                }

            } elseif ($type === 'dispense') {
                // Use DispensingModel logic or similar
                // For 'Quick Dispense', we assume generic patient or 'Counter Sale' / 'Ward Stock'
                
                // Create a Dispensing Record first
                $stmt = $this->db->prepare("
                    INSERT INTO dispensing (hn, patient_name, dispense_date, user_id, notes)
                    VALUES ('QS-BATCH', 'Quick Batch Scan', NOW(), ?, 'Batch Mode Dispense')
                ");
                $stmt->execute([$userId]);
                $dispenseId = $this->db->lastInsertId();

                foreach ($items as $item) {
                    $drugId = $item['id'];
                    $qty = $item['quantity'];
                    
                    // 1. Check stock
                    $stmt = $this->db->prepare("SELECT quantity FROM inventory WHERE drug_id = ? FOR UPDATE");
                    $stmt->execute([$drugId]);
                    $current = $stmt->fetchColumn();

                    if ($current < $qty) {
                         $errors[] = "Drug ID {$drugId}: Insufficient stock (Has $current, Need $qty)";
                         continue; // Skip this one, or rollback all? Let's skip and report.
                    }

                    // 2. Deduct Stock
                    $stmt = $this->db->prepare("UPDATE inventory SET quantity = quantity - ? WHERE drug_id = ?");
                    $stmt->execute([$qty, $drugId]);

                    // 3. Add Dispensing Item
                    $stmt = $this->db->prepare("
                        INSERT INTO dispensing_items (dispensing_id, drug_id, quantity, price)
                        VALUES (?, ?, ?, (SELECT price FROM drugs WHERE id = ?))
                    ");
                    $stmt->execute([$dispenseId, $drugId, $qty, $drugId]);
                    
                    // 4. Record Transaction
                    $stmt = $this->db->prepare("
                        INSERT INTO inventory_transactions 
                        (drug_id, transaction_type, quantity, balance, user_id, transaction_date, ref_id, ref_type) 
                        VALUES (?, 'out', ?, ?, ?, NOW(), ?, 'dispensing')
                    ");
                    $stmt->execute([$drugId, $qty, $current - $qty, $userId, $dispenseId]);

                    $successCount++;
                }
            }

            $this->db->commit();
            
            echo json_encode([
                'success' => true, 
                'processed' => $successCount, 
                'errors' => $errors,
                'message' => "Successfully processed $successCount items." . (count($errors) > 0 ? " (" . count($errors) . " errors)" : "")
            ]);

        } catch (\PDOException $e) {
            $this->db->rollBack();
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
    }
}
