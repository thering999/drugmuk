<?php

namespace App\Models;

use App\Core\Database;

class Order
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Get all orders
     */
    public function getAll()
    {
        $stmt = $this->db->query("
            SELECT o.*, s.name as supplier_name, u.full_name as created_by_name
            FROM orders o
            LEFT JOIN suppliers s ON o.supplier_id = s.id
            LEFT JOIN users u ON o.created_by = u.id
            ORDER BY o.order_date DESC
        ");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Get order by ID
     */
    public function getById($id)
    {
        $stmt = $this->db->prepare("
            SELECT o.*, s.name as supplier_name, u.full_name as created_by_name
            FROM orders o
            LEFT JOIN suppliers s ON o.supplier_id = s.id
            LEFT JOIN users u ON o.created_by = u.id
            WHERE o.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Get order items
     */
    public function getOrderItems($orderId)
    {
        $stmt = $this->db->prepare("
            SELECT oi.*, d.name as drug_name, d.code as drug_code
            FROM order_items oi
            JOIN drugs d ON oi.drug_id = d.id
            WHERE oi.order_id = ?
        ");
        $stmt->execute([$orderId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Create new order
     */
    public function create($data)
    {
        try {
            $this->db->beginTransaction();

            // Insert order
            $stmt = $this->db->prepare("
                INSERT INTO orders (order_no, supplier_id, order_date, delivery_date, status, total_amount, created_by)
                VALUES (?, ?, ?, ?, 'pending', ?, ?)
            ");
            $stmt->execute([
                $data['order_no'],
                $data['supplier_id'],
                $data['order_date'],
                $data['delivery_date'],
                $data['total_amount'],
                $data['created_by']
            ]);

            $orderId = $this->db->lastInsertId();

            // Insert order items
            $stmt = $this->db->prepare("
                INSERT INTO order_items (order_id, drug_id, quantity, unit_price, total_price)
                VALUES (?, ?, ?, ?, ?)
            ");

            foreach ($data['items'] as $item) {
                $totalPrice = $item['quantity'] * $item['unit_price'];
                $stmt->execute([
                    $orderId,
                    $item['drug_id'],
                    $item['quantity'],
                    $item['unit_price'],
                    $totalPrice
                ]);

                // Create pending record
                $pendingStmt = $this->db->prepare("
                    INSERT INTO order_pending (order_id, drug_id, quantity_ordered, quantity_pending)
                    VALUES (?, ?, ?, ?)
                ");
                $pendingStmt->execute([
                    $orderId,
                    $item['drug_id'],
                    $item['quantity'],
                    $item['quantity']
                ]);
            }

            $this->db->commit();
            return $orderId;
        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log("Order creation failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update order status
     */
    public function updateStatus($id, $status)
    {
        $stmt = $this->db->prepare("UPDATE orders SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $id]);
    }

    /**
     * Get drugs that need ordering
     */
    public function getDrugsNeedOrdering()
    {
        $stmt = $this->db->query("
            SELECT 
                d.id,
                d.code,
                d.name,
                COALESCE(SUM(i.quantity), 0) as current_stock,
                COALESCE(MAX(pending_receive.qty), 0) as pending_receive,
                COALESCE(MAX(pending_issue.qty), 0) as pending_issue,
                d.min_stock,
                COALESCE(MAX(f.forecast_quantity), 0) as next_month_forecast,
                (COALESCE(SUM(i.quantity), 0) + COALESCE(MAX(pending_receive.qty), 0) - COALESCE(MAX(pending_issue.qty), 0)) as net_stock,
                CASE 
                    WHEN (COALESCE(SUM(i.quantity), 0) + COALESCE(MAX(pending_receive.qty), 0) - COALESCE(MAX(pending_issue.qty), 0)) < d.min_stock 
                    THEN d.min_stock - (COALESCE(SUM(i.quantity), 0) + COALESCE(MAX(pending_receive.qty), 0) - COALESCE(MAX(pending_issue.qty), 0))
                    ELSE 0
                END as suggested_order_qty
            FROM drugs d
            LEFT JOIN inventory i ON d.id = i.drug_id
            LEFT JOIN (
                SELECT drug_id, SUM(quantity_pending) as qty
                FROM order_pending
                WHERE status = 'pending'
                GROUP BY drug_id
            ) pending_receive ON d.id = pending_receive.drug_id
            LEFT JOIN (
                SELECT drug_id, SUM(quantity_pending) as qty
                FROM inventory_pending
                WHERE status = 'pending'
                GROUP BY drug_id
            ) pending_issue ON d.id = pending_issue.drug_id
            LEFT JOIN analytics_demand_forecast f ON d.id = f.drug_id 
                AND f.forecast_date = DATE_FORMAT(DATE_ADD(NOW(), INTERVAL 1 MONTH), '%Y-%m-01')
            WHERE d.is_active = 1
            GROUP BY d.id
            HAVING net_stock < d.min_stock OR next_month_forecast > net_stock
            ORDER BY suggested_order_qty DESC
        ");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Get pending orders (not fully received)
     */
    public function getPendingOrders()
    {
        $stmt = $this->db->query("
            SELECT o.*, s.name as supplier_name
            FROM orders o
            LEFT JOIN suppliers s ON o.supplier_id = s.id
            WHERE o.status IN ('pending', 'approved')
            ORDER BY o.order_date DESC
        ");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Get pending receive quantity for a drug
     */
    public function getPendingReceive($drugId)
    {
        $stmt = $this->db->prepare("
            SELECT COALESCE(SUM(quantity_pending), 0) as pending
            FROM order_pending
            WHERE drug_id = ? AND status = 'pending'
        ");
        $stmt->execute([$drugId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result['pending'] ?? 0;
    }

    /**
     * Get last purchase info for a drug
     */
    public function getLastPurchase($drugId)
    {
        $stmt = $this->db->prepare("
            SELECT o.order_date, o.order_no, s.name as supplier_name, oi.unit_price, oi.quantity
            FROM orders o
            JOIN order_items oi ON o.id = oi.order_id
            LEFT JOIN suppliers s ON o.supplier_id = s.id
            WHERE oi.drug_id = ?
            ORDER BY o.order_date DESC
            LIMIT 1
        ");
        $stmt->execute([$drugId]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Get suppliers
     */
    public function getSuppliers()
    {
        $stmt = $this->db->query("SELECT * FROM suppliers ORDER BY name");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Get recent orders
     */
    public function getRecent($limit = 5)
    {
        $stmt = $this->db->prepare("
            SELECT o.*, s.name as supplier_name
            FROM orders o
            LEFT JOIN suppliers s ON o.supplier_id = s.id
            ORDER BY o.order_date DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Get pending orders count
     */
    public function getPendingOrdersCount()
    {
        $stmt = $this->db->query("
            SELECT COUNT(*) as count
            FROM orders
            WHERE status IN ('pending', 'approved')
        ");
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result['count'] ?? 0;
    }

    /**
     * Get receive history for an order
     */
    public function getReceiveHistory($orderId)
    {
        $stmt = $this->db->prepare("
            SELECT r.*, u.full_name as received_by_name,
                   COUNT(ri.id) as items_count,
                   SUM(ri.quantity_received) as total_received
            FROM order_receives r
            LEFT JOIN users u ON r.received_by = u.id
            LEFT JOIN order_receive_items ri ON r.id = ri.receive_id
            WHERE r.order_id = ?
            GROUP BY r.id
            ORDER BY r.receive_date DESC
        ");
        $stmt->execute([$orderId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Create receive record
     */
    public function createReceive($data)
    {
        try {
            $this->db->beginTransaction();

            // Insert receive record
            $stmt = $this->db->prepare("
                INSERT INTO order_receives (order_id, receive_date, received_by, notes)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([
                $data['order_id'],
                $data['receive_date'],
                $data['received_by'],
                $data['notes']
            ]);

            $receiveId = $this->db->lastInsertId();

            // Insert receive items
            $stmt = $this->db->prepare("
                INSERT INTO order_receive_items 
                (receive_id, order_item_id, drug_id, quantity_received, lot_no, expire_date)
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            foreach ($data['items'] as $item) {
                if (isset($item['quantity_received']) && $item['quantity_received'] > 0) {
                    $stmt->execute([
                        $receiveId,
                        $item['order_item_id'],
                        $item['drug_id'],
                        $item['quantity_received'],
                        $item['lot_no'] ?? '',
                        $item['expire_date'] ?? null
                    ]);
                }
            }

            $this->db->commit();
            return $receiveId;
        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log("Receive creation failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update inventory from receive
     */
    public function updateInventoryFromReceive($receiveId)
    {
        try {
            // Get receive items
            $stmt = $this->db->prepare("
                SELECT * FROM order_receive_items WHERE receive_id = ?
            ");
            $stmt->execute([$receiveId]);
            $items = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Update inventory for each item
            $inventoryStmt = $this->db->prepare("
                INSERT INTO inventory (drug_id, lot_no, expire_date, quantity, cost_price, location, received_date)
                VALUES (?, ?, ?, ?, 
                    (SELECT unit_price FROM order_items WHERE id = ?), 
                    'main', CURDATE())
                ON DUPLICATE KEY UPDATE 
                    quantity = quantity + VALUES(quantity)
            ");

            // Create transaction records
            $transactionStmt = $this->db->prepare("
                INSERT INTO transactions 
                (drug_id, transaction_type, quantity, balance_after, ref_document, user_id, lot_no)
                VALUES (?, 'receive', ?, 
                    (SELECT COALESCE(SUM(quantity), 0) FROM inventory WHERE drug_id = ?),
                    ?, ?, ?)
            ");

            foreach ($items as $item) {
                // Update inventory
                $inventoryStmt->execute([
                    $item['drug_id'],
                    $item['lot_no'],
                    $item['expire_date'],
                    $item['quantity_received'],
                    $item['order_item_id']
                ]);

                // Get receive info for transaction
                $receiveStmt = $this->db->prepare("
                    SELECT r.*, o.order_no 
                    FROM order_receives r
                    JOIN orders o ON r.order_id = o.id
                    WHERE r.id = ?
                ");
                $receiveStmt->execute([$receiveId]);
                $receive = $receiveStmt->fetch(\PDO::FETCH_ASSOC);

                // Create transaction
                $transactionStmt->execute([
                    $item['drug_id'],
                    $item['quantity_received'],
                    $item['drug_id'],
                    $receive['order_no'],
                    $receive['received_by'],
                    $item['lot_no']
                ]);
            }

            return true;
        } catch (\Exception $e) {
            error_log("Inventory update failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check and update order status based on received quantities
     */
    public function checkAndUpdateOrderStatus($orderId)
    {
        try {
            // Get total ordered vs total received
            $stmt = $this->db->prepare("
                SELECT 
                    SUM(oi.quantity) as total_ordered,
                    COALESCE(SUM(ri.quantity_received), 0) as total_received
                FROM order_items oi
                LEFT JOIN order_receive_items ri ON oi.id = ri.order_item_id
                WHERE oi.order_id = ?
            ");
            $stmt->execute([$orderId]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);

            // Update status
            if ($result['total_received'] >= $result['total_ordered']) {
                $this->updateStatus($orderId, 'received');
            } elseif ($result['total_received'] > 0) {
                $this->updateStatus($orderId, 'approved'); // Partially received
            }

            return true;
        } catch (\Exception $e) {
            error_log("Order status update failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get receive by ID
     */
    public function getReceiveById($receiveId)
    {
        $stmt = $this->db->prepare("
            SELECT r.*, o.order_no, s.name as supplier_name, u.full_name as received_by_name
            FROM order_receives r
            JOIN orders o ON r.order_id = o.id
            LEFT JOIN suppliers s ON o.supplier_id = s.id
            LEFT JOIN users u ON r.received_by = u.id
            WHERE r.id = ?
        ");
        $stmt->execute([$receiveId]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Get receive items
     */
    public function getReceiveItems($receiveId)
    {
        $stmt = $this->db->prepare("
            SELECT ri.*, d.name as drug_name, d.code as drug_code, d.unit,
                   oi.quantity as ordered_quantity, oi.unit_price
            FROM order_receive_items ri
            JOIN drugs d ON ri.drug_id = d.id
            JOIN order_items oi ON ri.order_item_id = oi.id
            WHERE ri.receive_id = ?
        ");
        $stmt->execute([$receiveId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
