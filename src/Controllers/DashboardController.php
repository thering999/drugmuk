<?php
/**
 * Dashboard Controller (Enhanced)
 * Dashboard พร้อม Charts และ Real-time Metrics
 * 
 * @package Drugmuk
 * @version 3.4.0
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Order;
use App\Models\Inventory;
use App\Models\Contract;
use App\Models\PurchasingPlan;
use App\Services\DashboardChartService;

class DashboardController extends Controller
{
    private $orderModel;
    private $inventoryModel;
    private $contractModel;
    private $purchasingPlanModel;
    private $chartService;

    public function __construct()
    {
        $this->orderModel = new Order();
        $this->inventoryModel = new Inventory();
        $this->contractModel = new Contract();
        $this->purchasingPlanModel = new PurchasingPlan();
        $this->chartService = new DashboardChartService();
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
            'pending_disbursements_count' => $this->inventoryModel->getPendingDisbursementsCount()
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

        // Chart data
        $chartData = $this->chartService->getDashboardData();

        $this->view('dashboard/index', [
            'metrics' => $metrics,
            'alerts' => $alerts,
            'recent_orders' => $recentOrders,
            'recent_receives' => $recentReceives,
            'chart_data' => $chartData
        ]);
    }

    /**
     * API: Get real-time metrics
     */
    public function apiMetrics()
    {
        header('Content-Type: application/json');
        $metrics = $this->chartService->getRealTimeMetrics();
        echo json_encode(['success' => true, 'data' => $metrics]);
    }

    /**
     * API: Get chart data
     */
    public function apiCharts()
    {
        header('Content-Type: application/json');
        $type = $_GET['type'] ?? 'all';
        
        switch ($type) {
            case 'dispensing':
                $data = $this->chartService->getDispensingTrend();
                break;
            case 'stock_category':
                $data = $this->chartService->getStockByCategory();
                break;
            case 'expiring':
                $data = $this->chartService->getExpiringByPeriod();
                break;
            case 'orders':
                $data = $this->chartService->getOrderTrend();
                break;
            case 'top_drugs':
                $data = $this->chartService->getTopDrugs();
                break;
            case 'ven':
                $data = $this->chartService->getStockByVEN();
                break;
            default:
                $data = $this->chartService->getDashboardData();
        }
        
        echo json_encode(['success' => true, 'data' => $data]);
    }
}
