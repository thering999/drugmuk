<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Order;
use App\Models\Drug;
use App\Models\Contract;
use App\Models\Inventory;

class OrderController extends Controller
{
    private $orderModel;
    private $drugModel;
    private $contractModel;
    private $inventoryModel;

    public function __construct()
    {
        $this->orderModel = new Order();
        $this->drugModel = new Drug();
        $this->contractModel = new Contract();
        $this->inventoryModel = new Inventory();
    }

    /**
     * List all purchase orders
     */
    public function index()
    {
        $orders = $this->orderModel->getAll();
        $this->view('orders/index', ['orders' => $orders]);
    }

    /**
     * Show create order form
     */
    public function create()
    {
        $suppliers = $this->orderModel->getSuppliers();
        $this->view('orders/create', ['suppliers' => $suppliers]);
    }

    /**
     * "What to buy?" decision support page
     * Shows drugs that need ordering based on:
     * - Current stock
     * - Pending receive
     * - Pending issue
     * - Minimum stock level
     */
    public function whatToBuy()
    {
        $drugsNeedOrdering = $this->orderModel->getDrugsNeedOrdering();
        $this->view('orders/what-to-buy', ['drugs' => $drugsNeedOrdering]);
    }

    /**
     * Store new purchase order
     */
    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /orders/create');
            exit;
        }

        $data = [
            'order_no' => $_POST['order_no'] ?? '',
            'supplier_id' => $_POST['supplier_id'] ?? null,
            'order_date' => $_POST['order_date'] ?? date('Y-m-d'),
            'delivery_date' => $_POST['delivery_date'] ?? null,
            'created_by' => $_SESSION['user_id'] ?? 1,
            'items' => $_POST['items'] ?? []
        ];

        // Validate contract if exists
        $contractWarnings = [];
        foreach ($data['items'] as $item) {
            $contract = $this->contractModel->getActiveContractForDrug($item['drug_id']);
            if ($contract) {
                // Check if ordering from contract supplier
                if ($contract['supplier_id'] != $data['supplier_id']) {
                    $contractWarnings[] = "Drug {$item['drug_id']} has active contract with different supplier";
                }
                
                // Check remaining quantity in contract
                $remaining = $this->contractModel->getRemainingQuantity($contract['id'], $item['drug_id']);
                if ($item['quantity'] > $remaining) {
                    $contractWarnings[] = "Order quantity exceeds contract remaining for drug {$item['drug_id']}";
                }
            }
        }

        // Calculate total amount
        $totalAmount = 0;
        foreach ($data['items'] as $item) {
            $totalAmount += $item['quantity'] * $item['unit_price'];
        }
        $data['total_amount'] = $totalAmount;

        // Check budget against purchasing plan
        $budgetWarnings = $this->checkBudget($data);
        if (!empty($budgetWarnings)) {
            $contractWarnings = array_merge($contractWarnings, $budgetWarnings);
        }

        $orderId = $this->orderModel->create($data);

        if ($orderId) {
            $_SESSION['success'] = 'Purchase order created successfully';
            if (!empty($contractWarnings)) {
                $_SESSION['warnings'] = $contractWarnings;
            }
            header('Location: /orders');
        } else {
            $_SESSION['error'] = 'Failed to create purchase order';
            header('Location: /orders/create');
        }
        exit;
    }

    /**
     * Check budget against purchasing plan
     * 
     * @param array $orderData Order data with items
     * @return array Budget warnings
     */
    private function checkBudget($orderData)
    {
        $warnings = [];
        
        try {
            // Get fiscal year from order date
            $orderDate = $orderData['order_date'];
            $fiscalYear = $this->getFiscalYearFromDate($orderDate);
            
            if (!$fiscalYear) {
                // No fiscal year found, skip budget check
                return [];
            }
            
            // Check budget for each item
            foreach ($orderData['items'] as $item) {
                $drugId = $item['drug_id'];
                $orderAmount = $item['quantity'] * $item['unit_price'];
                
                // Get purchasing plan for this drug
                $plan = $this->getPurchasingPlan($fiscalYear['id'], $drugId);
                
                if (!$plan) {
                    // No plan exists for this drug
                    $warnings[] = "No purchasing plan found for drug ID {$drugId} in fiscal year {$fiscalYear['year']}";
                    continue;
                }
                
                // Calculate YTD (Year-To-Date) spending
                $ytdSpending = $this->getYTDSpending($fiscalYear['id'], $drugId);
                
                // Calculate remaining budget
                $budgetPlan = $plan['adjusted_budget'] ?? $plan['budget_plan'];
                $remainingBudget = $budgetPlan - $ytdSpending;
                
                // Check if order exceeds remaining budget
                if ($orderAmount > $remainingBudget) {
                    $warnings[] = sprintf(
                        "Drug ID %d: Order amount (%.2f) exceeds remaining budget (%.2f). Plan: %.2f, Spent: %.2f",
                        $drugId,
                        $orderAmount,
                        $remainingBudget,
                        $budgetPlan,
                        $ytdSpending
                    );
                }
            }
            
        } catch (\Exception $e) {
            error_log("Budget check error: " . $e->getMessage());
            // Don't block order creation on budget check errors
        }
        
        return $warnings;
    }

    /**
     * Get fiscal year from date
     */
    private function getFiscalYearFromDate($date)
    {
        $db = \App\Core\Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT * FROM fiscal_years
            WHERE ? BETWEEN start_date AND end_date
            LIMIT 1
        ");
        $stmt->execute([$date]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Get purchasing plan for drug in fiscal year
     */
    private function getPurchasingPlan($fiscalYearId, $drugId)
    {
        $db = \App\Core\Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT * FROM purchasing_plans
            WHERE fiscal_year_id = ? AND drug_id = ?
            LIMIT 1
        ");
        $stmt->execute([$fiscalYearId, $drugId]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Get year-to-date spending for drug
     */
    private function getYTDSpending($fiscalYearId, $drugId)
    {
        $db = \App\Core\Database::getInstance()->getConnection();
        
        // Get fiscal year dates
        $stmt = $db->prepare("SELECT start_date, end_date FROM fiscal_years WHERE id = ?");
        $stmt->execute([$fiscalYearId]);
        $fy = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$fy) {
            return 0;
        }
        
        // Sum all orders for this drug in this fiscal year
        $stmt = $db->prepare("
            SELECT COALESCE(SUM(oi.total_price), 0) as total_spent
            FROM order_items oi
            INNER JOIN orders o ON oi.order_id = o.id
            WHERE oi.drug_id = ?
              AND o.order_date BETWEEN ? AND ?
              AND o.status != 'cancelled'
        ");
        $stmt->execute([$drugId, $fy['start_date'], $fy['end_date']]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        return (float)($result['total_spent'] ?? 0);
    }


    /**
     * Show order details
     */
    public function show($id)
    {
        $order = $this->orderModel->getById($id);
        if (!$order) {
            $_SESSION['error'] = 'Order not found';
            header('Location: /orders');
            exit;
        }

        $items = $this->orderModel->getOrderItems($id);
        $this->view('orders/show', [
            'order' => $order,
            'items' => $items
        ]);
    }

    /**
     * Update order status
     */
    public function updateStatus($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /orders');
            exit;
        }

        $status = $_POST['status'] ?? '';
        $result = $this->orderModel->updateStatus($id, $status);

        if ($result) {
            $_SESSION['success'] = 'Order status updated';
        } else {
            $_SESSION['error'] = 'Failed to update order status';
        }

        header("Location: /orders/show/$id");
        exit;
    }

    /**
     * Get drug info for order form (AJAX)
     */
    public function getDrugInfo($drugId)
    {
        header('Content-Type: application/json');
        
        $drug = $this->drugModel->getById($drugId);
        if (!$drug) {
            echo json_encode(['error' => 'Drug not found']);
            exit;
        }

        // Get current stock
        $stock = $this->inventoryModel->getCurrentStock($drugId);
        
        // Get pending receive
        $pendingReceive = $this->orderModel->getPendingReceive($drugId);
        
        // Get pending issue
        $pendingIssue = $this->inventoryModel->getPendingIssue($drugId);
        
        // Get last purchase info
        $lastPurchase = $this->orderModel->getLastPurchase($drugId);
        
        // Get active contract
        $contract = $this->contractModel->getActiveContractForDrug($drugId);

        echo json_encode([
            'drug' => $drug,
            'stock' => $stock,
            'pending_receive' => $pendingReceive,
            'pending_issue' => $pendingIssue,
            'last_purchase' => $lastPurchase,
            'contract' => $contract
        ]);
        exit;
    }

    /**
     * Show receive form for an order
     */
    public function receive($id)
    {
        $order = $this->orderModel->getById($id);
        if (!$order) {
            $_SESSION['error'] = 'Order not found';
            header('Location: /orders');
            exit;
        }

        // Check if order is approved
        if ($order['status'] !== 'approved' && $order['status'] !== 'pending') {
            $_SESSION['error'] = 'Only approved or pending orders can be received';
            header("Location: /orders/show/$id");
            exit;
        }

        $items = $this->orderModel->getOrderItems($id);
        $receiveHistory = $this->orderModel->getReceiveHistory($id);
        
        $this->view('orders/receive', [
            'order' => $order,
            'items' => $items,
            'receiveHistory' => $receiveHistory
        ]);
    }

    /**
     * Store received items and update inventory
     */
    public function storeReceive()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /orders');
            exit;
        }

        $orderId = $_POST['order_id'] ?? null;
        $receiveDate = $_POST['receive_date'] ?? date('Y-m-d');
        $notes = $_POST['notes'] ?? '';
        $items = $_POST['items'] ?? [];

        if (!$orderId || empty($items)) {
            $_SESSION['error'] = 'Invalid receive data';
            header("Location: /orders/receive/$orderId");
            exit;
        }

        try {
            // Create receive record
            $receiveId = $this->orderModel->createReceive([
                'order_id' => $orderId,
                'receive_date' => $receiveDate,
                'received_by' => $_SESSION['user_id'] ?? 1,
                'notes' => $notes,
                'items' => $items
            ]);

            if ($receiveId) {
                // Update inventory for each received item
                $this->orderModel->updateInventoryFromReceive($receiveId);
                
                // Check if order is fully received
                $this->orderModel->checkAndUpdateOrderStatus($orderId);
                
                $_SESSION['success'] = 'Drugs received successfully';
                header("Location: /orders/show/$orderId");
            } else {
                $_SESSION['error'] = 'Failed to record receive';
                header("Location: /orders/receive/$orderId");
            }
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Error: ' . $e->getMessage();
            header("Location: /orders/receive/$orderId");
        }
        exit;
    }

    /**
     * Print receive document
     */
    public function printReceive($receiveId)
    {
        $receive = $this->orderModel->getReceiveById($receiveId);
        if (!$receive) {
            $_SESSION['error'] = 'Receive record not found';
            header('Location: /orders');
            exit;
        }

        $items = $this->orderModel->getReceiveItems($receiveId);
        $this->view('orders/print-receive', [
            'receive' => $receive,
            'items' => $items
        ]);
    }
}
