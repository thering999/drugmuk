<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Drug;

class WarehouseController extends Controller
{
    private $inventoryModel;
    private $orderModel;
    private $drugModel;

    public function __construct()
    {
        $this->inventoryModel = new Inventory();
        $this->orderModel = new Order();
        $this->drugModel = new Drug();
    }

    /**
     * Main warehouse dashboard
     */
    public function index()
    {
        $stockSummary = $this->inventoryModel->getStockSummary();
        $lowStockItems = $this->inventoryModel->getLowStockItems();
        $expiringItems = $this->inventoryModel->getExpiringItems(90); // 90 days
        
        $this->view('warehouse/index', [
            'stock_summary' => $stockSummary,
            'low_stock' => $lowStockItems,
            'expiring' => $expiringItems
        ]);
    }

    /**
     * Receive drugs page
     */
    public function receive()
    {
        $pendingOrders = $this->orderModel->getPendingOrders();
        $this->view('warehouse/receive', ['pending_orders' => $pendingOrders]);
    }

    /**
     * Store received drugs
     */
    public function storeReceive()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /warehouse/receive');
            exit;
        }

        $data = [
            'order_id' => $_POST['order_id'] ?? null,
            'receive_date' => $_POST['receive_date'] ?? date('Y-m-d'),
            'invoice_no' => $_POST['invoice_no'] ?? '',
            'items' => $_POST['items'] ?? []
        ];

        // Validate quantities against order
        if ($data['order_id']) {
            $order = $this->orderModel->getById($data['order_id']);
            $orderItems = $this->orderModel->getOrderItems($data['order_id']);
            
            foreach ($data['items'] as $item) {
                $orderedQty = 0;
                $receivedQty = 0;
                
                foreach ($orderItems as $orderItem) {
                    if ($orderItem['drug_id'] == $item['drug_id']) {
                        $orderedQty = $orderItem['quantity'];
                        $receivedQty = $orderItem['quantity_received'] ?? 0;
                        break;
                    }
                }
                
                // Prevent over-receiving
                if (($receivedQty + $item['quantity']) > $orderedQty) {
                    $_SESSION['error'] = "Cannot receive more than ordered quantity for drug {$item['drug_id']}";
                    header('Location: /warehouse/receive');
                    exit;
                }
            }
        }

        $receiveId = $this->inventoryModel->receiveItems($data);

        if ($receiveId) {
            $_SESSION['success'] = 'Items received successfully';
            header('/warehouse');
        } else {
            $_SESSION['error'] = 'Failed to receive items';
            header('Location: /warehouse/receive');
        }
        exit;
    }

    /**
     * Approve disbursement requests
     */
    public function approveDisbursement()
    {
        $pendingRequests = $this->inventoryModel->getPendingDisbursements();
        $this->view('warehouse/approve-disbursement', ['requests' => $pendingRequests]);
    }

    /**
     * Process disbursement approval
     */
    public function processDisbursement()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /warehouse/approve-disbursement');
            exit;
        }

        $requestId = $_POST['request_id'] ?? null;
        $approvedQty = $_POST['approved_quantity'] ?? 0;
        $action = $_POST['action'] ?? 'approve'; // approve or reject

        if ($action === 'approve') {
            // Check if main warehouse has enough stock
            $request = $this->inventoryModel->getDisbursementRequest($requestId);
            $available = $this->inventoryModel->getAvailableStock($request['drug_id']);
            
            if ($approvedQty > $available) {
                // Auto create pending record
                $pendingQty = $approvedQty - $available;
                $this->inventoryModel->createPendingDisbursement([
                    'warehouse_code' => $request['warehouse_code'],
                    'drug_id' => $request['drug_id'],
                    'quantity_pending' => $pendingQty
                ]);
                
                $approvedQty = $available; // Only approve what's available
                $_SESSION['warning'] = "Only {$available} units approved. {$pendingQty} units pending.";
            }

            // Process disbursement using FEFO
            $result = $this->inventoryModel->disburseFEFO([
                'request_id' => $requestId,
                'drug_id' => $request['drug_id'],
                'quantity' => $approvedQty,
                'to_warehouse' => $request['warehouse_code']
            ]);

            if ($result) {
                $_SESSION['success'] = 'Disbursement approved and processed';
            } else {
                $_SESSION['error'] = 'Failed to process disbursement';
            }
        } else {
            // Reject
            $this->inventoryModel->rejectDisbursement($requestId);
            $_SESSION['success'] = 'Disbursement request rejected';
        }

        header('Location: /warehouse/approve-disbursement');
        exit;
    }

    /**
     * Stock card report
     */
    public function stockCard($drugId = null)
    {
        $drugs = $this->drugModel->getAll();
        $transactions = [];
        
        if ($drugId) {
            $transactions = $this->inventoryModel->getStockCardTransactions($drugId);
        }

        $this->view('warehouse/stock-card', [
            'drugs' => $drugs,
            'selected_drug' => $drugId,
            'transactions' => $transactions
        ]);
    }

    /**
     * Inventory adjustment page
     */
    public function adjust()
    {
        $this->view('warehouse/adjust');
    }

    /**
     * Store inventory adjustment
     */
    public function storeAdjustment()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /warehouse/adjust');
            exit;
        }

        $data = [
            'warehouse_code' => 'main',
            'drug_id' => $_POST['drug_id'] ?? null,
            'lot_no' => $_POST['lot_no'] ?? null,
            'adjustment_type' => $_POST['adjustment_type'] ?? 'correct',
            'quantity' => $_POST['quantity'] ?? 0,
            'reason' => $_POST['reason'] ?? '',
            'adjusted_by' => $_SESSION['user_id'] ?? 1
        ];

        $result = $this->inventoryModel->createAdjustment($data);

        if ($result) {
            $_SESSION['success'] = 'Inventory adjusted successfully';
        } else {
            $_SESSION['error'] = 'Failed to adjust inventory';
        }

        header('Location: /warehouse');
        exit;
    }

    /**
     * Transfer between warehouses
     */
    public function transfer()
    {
        $warehouses = $this->inventoryModel->getWarehouses();
        $this->view('warehouse/transfer', ['warehouses' => $warehouses]);
    }

    /**
     * Process transfer
     */
    public function processTransfer()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /warehouse/transfer');
            exit;
        }

        $data = [
            'from_warehouse' => $_POST['from_warehouse'] ?? 'main',
            'to_warehouse' => $_POST['to_warehouse'] ?? '',
            'drug_id' => $_POST['drug_id'] ?? null,
            'lot_no' => $_POST['lot_no'] ?? null,
            'quantity' => $_POST['quantity'] ?? 0,
            'transferred_by' => $_SESSION['user_id'] ?? 1,
            'notes' => $_POST['notes'] ?? ''
        ];

        $result = $this->inventoryModel->createTransfer($data);

        if ($result) {
            $_SESSION['success'] = 'Transfer created successfully';
        } else {
            $_SESSION['error'] = 'Failed to create transfer';
        }

        header('Location: /warehouse');
        exit;
    }
}
