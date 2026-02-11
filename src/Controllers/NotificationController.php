<?php

namespace App\Controllers;

use App\Core\Database;
use App\Models\Notification;
use App\Models\AuditTrail;
use App\Models\Inventory;
use App\Models\Contract;
use PDO;

/**
 * Notification Controller
 * จัดการการแจ้งเตือนทั้งหมด
 * 
 * @package Drugmuk
 * @version 3.5.0
 */
class NotificationController
{
    private $db;
    private $notificationModel;
    private $auditModel;
    private $inventoryModel;
    private $contractModel;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->notificationModel = new Notification();
        $this->auditModel = new AuditTrail();
        $this->inventoryModel = new Inventory();
        $this->contractModel = new Contract();
    }

    /**
     * หน้าแสดงการแจ้งเตือนทั้งหมด
     */
    public function index()
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $userId = $_SESSION['user_id'];
        $notifications = $this->notificationModel->getAll($userId, 50);
        $unreadCount = $this->notificationModel->countUnread($userId);
        $settings = $this->notificationModel->getSettings($userId);

        require_once __DIR__ . '/../Views/notifications/index.php';
    }

    /**
     * หน้าตั้งค่าการแจ้งเตือน
     */
    public function settings()
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $userId = $_SESSION['user_id'];
        $settings = $this->notificationModel->getSettings($userId);
        $isLineConnected = $this->validateLineToken();

        require_once __DIR__ . '/../Views/notifications/settings.php';
    }

    /**
     * ดึงการแจ้งเตือนใหม่ (AJAX)
     */
    public function getUnread()
    {
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $userId = $_SESSION['user_id'];
        $notifications = $this->notificationModel->getUnread($userId, 10);
        $count = $this->notificationModel->countUnread($userId);

        echo json_encode([
            'success' => true,
            'notifications' => $notifications,
            'count' => $count
        ]);
    }

    /**
     * Get all notifications for widget (API)
     */
    public function getNotifications()
    {
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        try {
            $userId = $_SESSION['user_id'];
            $unreadOnly = isset($_GET['unread_only']) && $_GET['unread_only'] === 'true';
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
            
            // Use NotificationManager if available, fallback to model
            if (class_exists('\App\Services\NotificationManager')) {
                $manager = new \App\Services\NotificationManager();
                $notifications = $manager->getUserNotifications($userId, $unreadOnly, $limit);
                $unreadCount = $manager->getUnreadCount($userId);
            } else {
                $notifications = $this->notificationModel->getAll($userId, $limit);
                $unreadCount = $this->notificationModel->countUnread($userId);
            }
            
            echo json_encode([
                'success' => true,
                'notifications' => $notifications,
                'unread_count' => $unreadCount
            ]);
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        
        exit;
    }

    /**
     * Mark single notification as read (API)
     */
    public function markNotificationAsRead($id)
    {
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }

        try {
            $userId = $_SESSION['user_id'];
            
            // Use NotificationManager if available
            if (class_exists('\App\Services\NotificationManager')) {
                $manager = new \App\Services\NotificationManager();
                $result = $manager->markAsRead((int)$id, $userId);
            } else {
                $result = $this->notificationModel->markAsRead((int)$id);
            }
            
            echo json_encode([
                'success' => $result,
                'message' => $result ? 'Marked as read' : 'Failed to mark as read'
            ]);
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        
        exit;
    }

    /**
     * Mark all as read (API)
     */
    public function markAllNotificationsAsRead()
    {
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }

        try {
            $userId = $_SESSION['user_id'];
            
            // Use NotificationManager if available
            if (class_exists('\App\Services\NotificationManager')) {
                $manager = new \App\Services\NotificationManager();
                $count = $manager->markAllAsRead($userId);
            } else {
                $count = $this->notificationModel->markAllAsRead($userId);
            }
            
            echo json_encode([
                'success' => true,
                'message' => "Marked {$count} notifications as read",
                'count' => $count
            ]);
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        
        exit;
    }

    /**
     * ทำเครื่องหมายว่าอ่านแล้ว (AJAX)
     */
    public function markRead()
    {
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $id = $_POST['id'] ?? null;
        
        if ($id) {
            $result = $this->notificationModel->markAsRead($id);
        } else {
            $result = $this->notificationModel->markAllAsRead($_SESSION['user_id']);
        }

        echo json_encode(['success' => $result]);
    }

    /**
     * บันทึกการตั้งค่า (AJAX)
     */
    public function saveSettings()
    {
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $userId = $_SESSION['user_id'];
        $data = [
            'notify_low_stock' => isset($_POST['notify_low_stock']) ? 1 : 0,
            'notify_expiring' => isset($_POST['notify_expiring']) ? 1 : 0,
            'notify_data_quality' => isset($_POST['notify_data_quality']) ? 1 : 0,
            'notify_orders' => isset($_POST['notify_orders']) ? 1 : 0,
            'notify_contracts' => isset($_POST['notify_contracts']) ? 1 : 0,
            'notify_receive' => isset($_POST['notify_receive']) ? 1 : 0,
            'notify_allergy' => isset($_POST['notify_allergy']) ? 1 : 0,
            'email_enabled' => isset($_POST['email_enabled']) ? 1 : 0,
            'email_address' => $_POST['email_address'] ?? null,
            'line_enabled' => isset($_POST['line_enabled']) ? 1 : 0,
            'line_token' => $_POST['line_token'] ?? null,
            'discord_enabled' => isset($_POST['discord_enabled']) ? 1 : 0,
            'discord_webhook' => $_POST['discord_webhook'] ?? null,
            'telegram_enabled' => isset($_POST['telegram_enabled']) ? 1 : 0,
            'telegram_bot_token' => $_POST['telegram_bot_token'] ?? null,
            'telegram_chat_id' => $_POST['telegram_chat_id'] ?? null,
            'daily_summary' => isset($_POST['daily_summary']) ? 1 : 0,
            'daily_summary_time' => $_POST['daily_summary_time'] ?? '08:00',
            'low_stock_threshold' => (int)($_POST['low_stock_threshold'] ?? 20),
            'expiring_days' => (int)($_POST['expiring_days'] ?? 90)
        ];

        $result = $this->notificationModel->saveSettings($userId, $data);
        echo json_encode(['success' => $result]);
    }

    /**
     * สร้างการแจ้งเตือนอัตโนมัติ (Cron)
     */
    public function generate()
    {
        header('Content-Type: application/json');
        
        // อนุญาตให้เรียกจาก CLI หรือต้องล็อกอิน
        if (php_sapi_name() !== 'cli' && !isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $results = $this->notificationModel->generateAutoNotifications();
        echo json_encode([
            'success' => true,
            'generated' => $results
        ]);
    }

    /**
     * ทดสอบส่ง Discord Webhook
     */
    public function testDiscord()
    {
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $webhook = $_POST['webhook'] ?? '';
        if (empty($webhook)) {
            echo json_encode(['success' => false, 'message' => 'ไม่ได้ระบุ Webhook URL']);
            exit;
        }

        // Send test message to Discord
        $payload = json_encode([
            'content' => null,
            'embeds' => [[
                'title' => '🧪 ทดสอบการแจ้งเตือน',
                'description' => "หากคุณเห็นข้อความนี้ แสดงว่าการตั้งค่า Discord Webhook ถูกต้อง ✅\n\n📅 " . date('d/m/Y H:i:s'),
                'color' => 5763719, // Green
                'footer' => ['text' => 'Drugmuk System']
            ]]
        ]);

        $ch = curl_init($webhook);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false
        ]);
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            echo json_encode(['success' => true, 'message' => 'ส่งข้อความไป Discord สำเร็จ! ✅']);
        } else {
            echo json_encode(['success' => false, 'message' => 'ส่งไม่สำเร็จ (HTTP ' . $httpCode . ')']);
        }
        exit;
    }

    /**
     * ทดสอบส่ง Telegram Bot
     */
    public function testTelegram()
    {
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $token = $_POST['token'] ?? '';
        $chatId = $_POST['chat_id'] ?? '';
        
        if (empty($token) || empty($chatId)) {
            echo json_encode(['success' => false, 'message' => 'กรุณาระบุ Bot Token และ Chat ID']);
            exit;
        }

        // Send test message to Telegram
        $url = "https://api.telegram.org/bot{$token}/sendMessage";
        $message = "🧪 <b>ทดสอบการแจ้งเตือน</b>\n\nหากคุณเห็นข้อความนี้ แสดงว่าการตั้งค่า Telegram Bot ถูกต้อง ✅\n\n📅 " . date('d/m/Y H:i:s');

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'HTML'
            ]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false
        ]);
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $response = json_decode($result, true);

        if ($httpCode == 200 && isset($response['ok']) && $response['ok']) {
            echo json_encode(['success' => true, 'message' => 'ส่งข้อความไป Telegram สำเร็จ! ✅']);
        } else {
            $errorMsg = $response['description'] ?? 'Unknown error';
            echo json_encode(['success' => false, 'message' => 'ส่งไม่สำเร็จ: ' . $errorMsg]);
        }
        exit;
    }

    /**
     * หน้า Audit Trail
     */
    public function auditTrail()
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $filters = [
            'table_name' => $_GET['table'] ?? null,
            'action' => $_GET['action'] ?? null,
            'user_id' => $_GET['user'] ?? null,
            'date_from' => $_GET['from'] ?? null,
            'date_to' => $_GET['to'] ?? null
        ];

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 50;
        $offset = ($page - 1) * $limit;

        $records = $this->auditModel->getAll($filters, $limit, $offset);
        $totalCount = $this->auditModel->count($filters);
        $totalPages = ceil($totalCount / $limit);
        $statistics = $this->auditModel->getStatistics(30);
        $topUsers = $this->auditModel->getTopUsers(5, 30);

        require_once __DIR__ . '/../Views/notifications/audit_trail.php';
    }

    /**
     * ส่งการแจ้งเตือนทันที (Manual trigger)
     */
    public function sendNow()
    {
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $type = $_POST['type'] ?? '';
        $settings = $this->getNotificationSettings();
        $results = [];
        
        switch ($type) {
            case 'low_stock':
                $items = $this->inventoryModel->getLowStockItems();
                $results = $this->sendMultiChannelNotification('low_stock', $items);
                break;
                
            case 'expiring':
                $days = $settings['expiring_days'] ?? 90;
                $items = $this->inventoryModel->getExpiringItems($days);
                $results = $this->sendMultiChannelNotification('expiring', $items);
                break;
                
            case 'contracts':
                $contracts = $this->contractModel->getExpiringContracts(30);
                $results = $this->sendMultiChannelNotification('contracts', $contracts);
                break;
                
            case 'daily_summary':
                $stats = $this->getDailyStats();
                $results = $this->sendMultiChannelNotification('daily_summary', $stats);
                break;
                
            default:
                echo json_encode(['success' => false, 'error' => 'Unknown notification type']);
                return;
        }
        
        echo json_encode(['success' => true, 'results' => $results]);
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
                $results['low_stock'] = $this->sendMultiChannelNotification('low_stock', $items);
                $this->logNotification('low_stock', count($items) . ' items');
            }
        }
        
        // Check expiring
        if ($settings['notify_expiring'] ?? false) {
            $days = $settings['expiring_days'] ?? 90;
            $items = $this->inventoryModel->getExpiringItems($days);
            if (!empty($items)) {
                $results['expiring'] = $this->sendMultiChannelNotification('expiring', $items);
                $this->logNotification('expiring', count($items) . ' items');
            }
        }
        
        // Check contracts
        if ($settings['notify_contracts'] ?? false) {
            $contracts = $this->contractModel->getExpiringContracts(30);
            if (!empty($contracts)) {
                $results['contracts'] = $this->sendMultiChannelNotification('contracts', $contracts);
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
                $results['daily_summary'] = $this->sendMultiChannelNotification('daily_summary', $stats);
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
    
    private function saveNotificationSettingsToFile(array $settings): void
    {
        $file = __DIR__ . '/../../config/notifications.json';
        $dir = dirname($file);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($file, json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    
    private function validateLineToken(): bool
    {
        $settings = $this->getNotificationSettings();
        $token = $settings['line_token'] ?? '';
        
        if (empty($token)) {
            return false;
        }
        
        // Simple validation - check if token format is correct
        return strlen($token) > 10;
    }
    
    private function sendLineNotification(string $type, $data): array
    {
        error_log("sendLineNotification is deprecated, use sendMultiChannelNotification instead.");
        return $this->sendMultiChannelNotification($type, $data);
    }

    private function sendMultiChannelNotification(string $type, $data): array
    {
        $userId = $_SESSION['user_id'] ?? 1; // Fallback for cron
        $settings = $this->notificationModel->getSettings($userId);
        $message = $this->formatNotificationMessage($type, $data);
        $results = ['success' => false, 'channels' => []];

        // LINE (Deprecated)
        if (($settings['line_enabled'] ?? 0) && !empty($settings['line_token'])) {
            $results['channels']['line'] = $this->notificationModel->sendLine($settings['line_token'], $message);
            $results['success'] = true;
        }

        // Discord
        if (($settings['discord_enabled'] ?? 0) && !empty($settings['discord_webhook'])) {
            $results['channels']['discord'] = $this->notificationModel->sendDiscord($settings['discord_webhook'], $message);
            $results['success'] = $results['success'] || $results['channels']['discord'];
        }

        // Telegram
        if (($settings['telegram_enabled'] ?? 0) && !empty($settings['telegram_bot_token']) && !empty($settings['telegram_chat_id'])) {
            $results['channels']['telegram'] = $this->notificationModel->sendTelegram(
                $settings['telegram_bot_token'], 
                $settings['telegram_chat_id'], 
                $message
            );
            $results['success'] = $results['success'] || $results['channels']['telegram'];
        }

        return $results;
    }
    
    private function formatNotificationMessage(string $type, $data): string
    {
        switch ($type) {
            case 'low_stock':
                $count = is_array($data) ? count($data) : 0;
                return "\n📦 แจ้งเตือนสต็อกต่ำ\n━━━━━━━━━━━━━\nพบยาสต็อกต่ำ {$count} รายการ\nกรุณาตรวจสอบและสั่งซื้อเพิ่มเติม";
                
            case 'expiring':
                $count = is_array($data) ? count($data) : 0;
                return "\n⏰ แจ้งเตือนยาใกล้หมดอายุ\n━━━━━━━━━━━━━\nพบยาใกล้หมดอายุ {$count} รายการ\nกรุณาตรวจสอบและจัดการ";
                
            case 'contracts':
                $count = is_array($data) ? count($data) : 0;
                return "\n📝 แจ้งเตือนสัญญาใกล้หมดอายุ\n━━━━━━━━━━━━━\nพบสัญญาใกล้หมดอายุ {$count} รายการ";
                
            case 'daily_summary':
                return "\n📊 สรุปประจำวัน\n━━━━━━━━━━━━━\n" .
                       "📦 จ่ายยา: " . ($data['dispensing_count'] ?? 0) . " ครั้ง\n" .
                       "📥 รับยา: " . ($data['receive_count'] ?? 0) . " ครั้ง\n" .
                       "🛒 สั่งซื้อ: " . ($data['order_count'] ?? 0) . " รายการ\n" .
                       "⚠️ สต็อกต่ำ: " . ($data['low_stock_count'] ?? 0) . " รายการ";
                
            default:
                return "\n🔔 การแจ้งเตือนจาก Drugmuk";
        }
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
        try {
            $sql = "SELECT COUNT(*) as count FROM dispensing WHERE DATE(dispense_date) = CURDATE()";
            $result = $this->db->query($sql)->fetch();
            return $result['count'] ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }
    
    private function getTodayReceiveCount(): int
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM transactions WHERE transaction_type = 'receive' AND DATE(transaction_date) = CURDATE()";
            $result = $this->db->query($sql)->fetch();
            return $result['count'] ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }
    
    private function getTodayOrderCount(): int
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM orders WHERE DATE(order_date) = CURDATE()";
            $result = $this->db->query($sql)->fetch();
            return $result['count'] ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }
    
    private function getTotalInventoryValue(): float
    {
        try {
            $sql = "SELECT COALESCE(SUM(quantity * cost_price), 0) as total FROM inventory WHERE quantity > 0";
            $result = $this->db->query($sql)->fetch();
            return (float)($result['total'] ?? 0);
        } catch (\Exception $e) {
            return 0;
        }
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
}
