<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Order;
use App\Models\Inventory;
use App\Models\Contract;
use App\Models\PurchasingPlan;
use App\Models\DataCleansing;

class DashboardController extends Controller
{
    private $orderModel;
    private $inventoryModel;
    private $contractModel;
    private $purchasingPlanModel;
    private $cleansingModel;

    public function __construct()
    {
        $this->orderModel = new Order();
        $this->inventoryModel = new Inventory();
        $this->contractModel = new Contract();
        $this->purchasingPlanModel = new PurchasingPlan();
        $this->cleansingModel = new DataCleansing();
    }

    /**
     * Main dashboard
     */
    public function index()
    {
        // Get key metrics
        $metrics = [
            'low_stock_count' => $this->inventoryModel->getLowStockCount(),
            'expiring_soon_count' => $this->inventoryModel->getExpiringSoonCount(90),
            'pending_orders_count' => $this->orderModel->getPendingOrdersCount(),
            'expiring_contracts_count' => $this->contractModel->getExpiringContractsCount(30),
            'pending_disbursements_count' => $this->inventoryModel->getPendingDisbursementsCount(),
            'data_quality_score' => $this->cleansingModel->getDataQualityScore(),
            'jhcis_stats' => $this->getJhcisStats()
        ];

        // Get alerts
        $alerts = [];
        
        // Low stock alerts
        $lowStock = $this->inventoryModel->getLowStockItems(10);
        foreach ($lowStock as $item) {
            $alerts[] = [
                'type' => 'warning',
                'icon' => 'box',
                'message' => "Low stock: {$item['drug_name']} ({$item['current_stock']} units)",
                'link' => '/orders/what-to-buy'
            ];
        }

        // Expiring items
        $expiring = $this->inventoryModel->getExpiringItems(90, 5);
        foreach ($expiring as $item) {
            $alerts[] = [
                'type' => 'danger',
                'icon' => 'clock',
                'message' => "Expiring soon: {$item['drug_name']} (Lot: {$item['lot_no']}, Exp: {$item['expire_date']})",
                'link' => '/warehouse/stock-card/' . $item['drug_id']
            ];
        }

        // Expiring contracts
        $expiringContracts = $this->contractModel->getExpiringContracts(30, 5);
        foreach ($expiringContracts as $contract) {
            $alerts[] = [
                'type' => 'warning',
                'icon' => 'file-text',
                'message' => "Contract expiring: {$contract['contract_no']} with {$contract['supplier_name']} (Exp: {$contract['end_date']})",
                'link' => '/contracts'
            ];
        }

        // Recent activity
        $recentOrders = $this->orderModel->getRecent(5);
        $recentReceives = $this->inventoryModel->getRecentReceives(5);

        $this->view('dashboard/index', [
            'metrics' => $metrics,
            'alerts' => $alerts,
            'recent_orders' => $recentOrders,
            'recent_receives' => $recentReceives
        ]);
    }

    private function getJhcisStats()
    {
        try {
            $db = \App\Core\Database::getInstance()->getConnection();
            
            // Count active hospitals
            $stmt = $db->query("SELECT COUNT(*) FROM jhcis_hospitals WHERE is_active = 1");
            $activeHospitals = $stmt->fetchColumn();
            
            // Count recent failures (24h)
            $stmt = $db->query("SELECT COUNT(*) FROM jhcis_sync_log WHERE sync_status = 'failed' AND started_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
            $recentFailures = $stmt->fetchColumn();
            
            // Last sync time
            $stmt = $db->query("SELECT MAX(completed_at) FROM jhcis_sync_log WHERE sync_status = 'completed'");
            $lastSync = $stmt->fetchColumn();
            
            return [
                'active_hospitals' => $activeHospitals,
                'recent_failures' => $recentFailures,
                'last_sync' => $lastSync
            ];
        } catch (\Exception $e) {
            return [
                'active_hospitals' => 0,
                'recent_failures' => 0,
                'last_sync' => null
            ];
        }
    }
}
