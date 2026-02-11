<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Inventory;
use App\Models\Drug;

class InventoryController extends Controller {
    public function index() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/login');
        }

        $invModel = new Inventory();
        $inventory = $invModel->getAllWithDrugs();

        $this->view('inventory/index', ['inventory' => $inventory]);
    }

    public function receive() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/login');
        }

        $drugModel = new Drug();
        $drugs = $drugModel->getActiveDrugs();

        $this->view('inventory/receive', ['drugs' => $drugs]);
    }

    public function storeReceive() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/login');
        }

        $data = [
            'drug_id' => $_POST['drug_id'],
            'lot_no' => $_POST['lot_no'],
            'expire_date' => $_POST['expire_date'],
            'quantity' => $_POST['quantity'],
            'cost_price' => $_POST['cost_price']
        ];

        $invModel = new Inventory();
        $invModel->receive($data);

        $this->redirect('/inventory');
    }

    /**
     * AI Forecast - Predict stock depletion
     */
    public function forecast() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $db = \App\Core\Database::getInstance()->getConnection();
        
        // Query to get stock and usage history (90 days)
        $sql = "
            SELECT 
                d.id, 
                d.name as drug_name, 
                d.min_stock, 
                d.unit,
                COALESCE(SUM(i.quantity), 0) as current_stock,
                (
                    SELECT COALESCE(SUM(di.quantity), 0) 
                    FROM dispensing_items di 
                    JOIN dispensing disp ON di.dispense_id = disp.id 
                    WHERE di.drug_id = d.id 
                    AND disp.dispense_date >= DATE_SUB(NOW(), INTERVAL 90 DAY)
                ) as usage_90_days
            FROM drugs d
            LEFT JOIN inventory i ON d.id = i.drug_id AND i.quantity > 0
            GROUP BY d.id, d.name, d.min_stock, d.unit
            HAVING current_stock > 0 OR usage_90_days > 0
            ORDER BY usage_90_days DESC
        ";
        
        $stmt = $db->query($sql);
        $forecasts = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        // Calculate metrics
        foreach ($forecasts as &$item) {
            $avgDailyUsage = $item['usage_90_days'] / 90;
            $item['avg_daily_usage'] = $avgDailyUsage;
            
            if ($avgDailyUsage > 0) {
                $item['days_remaining'] = floor($item['current_stock'] / $avgDailyUsage);
            } else {
                $item['days_remaining'] = 999; // Infinite if no usage
            }
            
            // Recommendation
            if ($item['days_remaining'] < 30) {
                $item['status'] = 'critical'; // < 30 days
                $item['recommendation'] = 'สั่งซื้อด่วน (' . ceil($avgDailyUsage * 60) . ' ' . $item['unit'] . ')'; // Suggest buy for 60 days
            } elseif ($item['days_remaining'] < 60) {
                $item['status'] = 'warning'; // < 60 days
                $item['recommendation'] = 'ควรสั่งซื้อเพิ่ม';
            } else {
                $item['status'] = 'good'; // > 60 days
                $item['recommendation'] = 'เพียงพอ';
            }
        }
        
        // Render View directly (since no View class method in this simplified controller)
        $data = ['forecasts' => $forecasts];
        extract($data);
        require_once __DIR__ . '/../Views/inventory/forecast.php';
    }
}
