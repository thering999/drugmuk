<?php
/**
 * Notification Controller
 * จัดการการแจ้งเตือนทั้งหมด
 * 
 * @package Drugmuk
 * @version 3.4.0
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Services\LineNotifyService;
use App\Models\Inventory;
use App\Models\Contract;

class NotificationController extends Controller
{
    private LineNotifyService $lineService;
    private Inventory $inventoryModel;
    private Contract $contractModel;
    
    public function __construct()
    {
        $this->lineService = new LineNotifyService();
        $this->inventoryModel = new Inventory();
        $this->contractModel = new Contract();
    }
    
    /**
     * หน้าตั้งค่าการแจ้งเตือน
     */
    public function settings()
    {
        $settings = $this->getNotificationSettings();
        $isLineConnected = $this->lineService->validateToken();
        
        $this->view('notifications/settings', [
            'settings' => $settings,
            'is_line_connected' => $isLineConnected
        ]);
    }
    
    /**
     * บันทึกการตั้งค่า
     */
    public function saveSettings()
    {
        $this->validateCSRF();
        
        $settings = [
            'line_notify_token' => $_POST['line_notify_token'] ?? '',
            'notify_low_stock' => isset($_POST['notify_low_stock']) ? 1 : 0,
            'notify_expiring' => isset($_POST['notify_expiring']) ? 1 : 0,
            'notify_contracts' => isset($_POST['notify_contracts']) ? 1 : 0,
            'notify_receive' => isset($_POST['notify_receive']) ? 1 : 0,
            'notify_allergy' => isset($_POST['notify_allergy']) ? 1 : 0,
            'daily_summary' => isset($_POST['daily_summary']) ? 1 : 0,
            'daily_summary_time' => $_POST['daily_summary_time'] ?? '08:00',
            'low_stock_threshold' => (int)($_POST['low_stock_threshold'] ?? 20),
            'expiring_days' => (int)($_POST['expiring_days'] ?? 90)
        ];
        
        $this->saveNotificationSettings($settings);
        
        // Update .env file with LINE token
        if (!empty($settings['line_notify_token'])) {
            $this->updateEnvValue('LINE_NOTIFY_TOKEN', $settings['line_notify_token']);
        }
        
        $_SESSION['success'] = 'บันทึกการตั้งค่าการแจ้งเตือนสำเร็จ';
        header('Location: /notifications/settings');
        exit;
    }
    
    /**
     * ทดสอบส่ง LINE Notify
     */
    public function testLine()
    {
        header('Content-Type: application/json');
        
        $token = $_POST['token'] ?? '';
        if (empty($token)) {
            echo json_encode(['success' => false, 'error' => 'กรุณาใส่ LINE Notify Token']);
            return;
        }
        
        $service = new LineNotifyService($token);
        $result = $service->send("\n🔔 ทดสอบการเชื่อมต่อ LINE Notify\n━━━━━━━━━━━━━━━\n✅ เชื่อมต่อสำเร็จ!\n🏥 ระบบ Drugmuk\n📅 " . date('d/m/Y H:i:s'));
        
        echo json_encode($result);
    }
    
    /**
     * ส่งการแจ้งเตือนทันที (Manual trigger)
     */
    public function sendNow()
    {
        header('Content-Type: application/json');
        
        $type = $_POST['type'] ?? '';
        $results = [];
        
        switch ($type) {
            case 'low_stock':
                $items = $this->inventoryModel->getLowStockItems();
                $results = $this->lineService->notifyLowStock($items);
                break;
                
            case 'expiring':
                $settings = $this->getNotificationSettings();
                $days = $settings['expiring_days'] ?? 90;
                $items = $this->inventoryModel->getExpiringItems($days);
                $results = $this->lineService->notifyExpiringDrugs($items);
                break;
                
            case 'contracts':
                $contracts = $this->contractModel->getExpiringContracts(30);
                $results = $this->lineService->notifyExpiringContracts($contracts);
                break;
                
            case 'daily_summary':
                $stats = $this->getDailyStats();
                $results = $this->lineService->sendDailySummary($stats);
                break;
                
            default:
                echo json_encode(['success' => false, 'error' => 'Unknown notification type']);
                return;
        }
        
        echo json_encode($results);
    }
    
    /**
     * API: รับการแจ้งเตือนล่าสุด
     */
    public function getRecent()
    {
        header('Content-Type: application/json');
        
        $notifications = $this->getRecentNotifications(10);
        echo json_encode(['success' => true, 'data' => $notifications]);
    }
    
    /**
     * Cron Job: ตรวจสอบและส่งการแจ้งเตือนอัตโนมัติ
     */
    public function cronCheck()
    {
        $settings = $this->getNotificationSettings();
        $results = [];
        
        // Check low stock
        if ($settings['notify_low_stock'] ?? false) {
            $items = $this->inventoryModel->getLowStockItems();
            if (!empty($items)) {
                $results['low_stock'] = $this->lineService->notifyLowStock($items);
                $this->logNotification('low_stock', count($items) . ' items');
            }
        }
        
        // Check expiring
        if ($settings['notify_expiring'] ?? false) {
            $days = $settings['expiring_days'] ?? 90;
            $items = $this->inventoryModel->getExpiringItems($days);
            if (!empty($items)) {
                $results['expiring'] = $this->lineService->notifyExpiringDrugs($items);
                $this->logNotification('expiring', count($items) . ' items');
            }
        }
        
        // Check contracts
        if ($settings['notify_contracts'] ?? false) {
            $contracts = $this->contractModel->getExpiringContracts(30);
            if (!empty($contracts)) {
                $results['contracts'] = $this->lineService->notifyExpiringContracts($contracts);
                $this->logNotification('contracts', count($contracts) . ' contracts');
            }
        }
        
        // Daily summary (check if it's the right time)
        if ($settings['daily_summary'] ?? false) {
            $summaryTime = $settings['daily_summary_time'] ?? '08:00';
            $currentTime = date('H:i');
            
            // Within 5 minutes of scheduled time
            if (abs(strtotime($currentTime) - strtotime($summaryTime)) < 300) {
                $stats = $this->getDailyStats();
                $results['daily_summary'] = $this->lineService->sendDailySummary($stats);
                $this->logNotification('daily_summary', 'sent');
            }
        }
        
        if (php_sapi_name() === 'cli') {
            echo json_encode($results, JSON_PRETTY_PRINT);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'results' => $results]);
        }
    }
    
    // ===== Private Methods =====
    
    private function getNotificationSettings(): array
    {
        $file = __DIR__ . '/../../config/notifications.json';
        if (file_exists($file)) {
            return json_decode(file_get_contents($file), true) ?? [];
        }
        return [];
    }
    
    private function saveNotificationSettings(array $settings): void
    {
        $file = __DIR__ . '/../../config/notifications.json';
        $dir = dirname($file);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($file, json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    
    private function updateEnvValue(string $key, string $value): void
    {
        $envFile = __DIR__ . '/../../.env';
        if (!file_exists($envFile)) return;
        
        $content = file_get_contents($envFile);
        $pattern = "/^{$key}=.*/m";
        $replacement = "{$key}={$value}";
        
        if (preg_match($pattern, $content)) {
            $content = preg_replace($pattern, $replacement, $content);
        } else {
            $content .= "\n{$replacement}";
        }
        
        file_put_contents($envFile, $content);
    }
    
    private function getDailyStats(): array
    {
        return [
            'dispensing_count' => $this->getTodayDispensingCount(),
            'receive_count' => $this->getTodayReceiveCount(),
            'order_count' => $this->getTodayOrderCount(),
            'low_stock_count' => $this->inventoryModel->getLowStockCount(),
            'expiring_count' => $this->inventoryModel->getExpiringSoonCount(90),
            'total_value' => $this->getTotalInventoryValue()
        ];
    }
    
    private function getTodayDispensingCount(): int
    {
        $db = \App\Core\Database::getInstance()->getConnection();
        $sql = "SELECT COUNT(*) as count FROM dispensing WHERE DATE(dispense_date) = CURDATE()";
        $result = $db->query($sql)->fetch();
        return $result['count'] ?? 0;
    }
    
    private function getTodayReceiveCount(): int
    {
        $db = \App\Core\Database::getInstance()->getConnection();
        $sql = "SELECT COUNT(*) as count FROM transactions WHERE transaction_type = 'receive' AND DATE(transaction_date) = CURDATE()";
        $result = $db->query($sql)->fetch();
        return $result['count'] ?? 0;
    }
    
    private function getTodayOrderCount(): int
    {
        $db = \App\Core\Database::getInstance()->getConnection();
        $sql = "SELECT COUNT(*) as count FROM orders WHERE DATE(order_date) = CURDATE()";
        $result = $db->query($sql)->fetch();
        return $result['count'] ?? 0;
    }
    
    private function getTotalInventoryValue(): float
    {
        $db = \App\Core\Database::getInstance()->getConnection();
        $sql = "SELECT COALESCE(SUM(quantity * cost_price), 0) as total FROM inventory WHERE quantity > 0";
        $result = $db->query($sql)->fetch();
        return (float)($result['total'] ?? 0);
    }
    
    private function getRecentNotifications(int $limit = 10): array
    {
        $file = __DIR__ . '/../../logs/notifications.log';
        if (!file_exists($file)) return [];
        
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $lines = array_reverse($lines);
        return array_slice($lines, 0, $limit);
    }
    
    private function logNotification(string $type, string $details): void
    {
        $file = __DIR__ . '/../../logs/notifications.log';
        $dir = dirname($file);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        $log = sprintf(
            "[%s] %s: %s\n",
            date('Y-m-d H:i:s'),
            strtoupper($type),
            $details
        );
        
        file_put_contents($file, $log, FILE_APPEND);
    }
    
    private function validateCSRF(): void
    {
        $token = $_POST['csrf_token'] ?? '';
        if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
            http_response_code(403);
            die('Invalid CSRF token');
        }
    }
}
