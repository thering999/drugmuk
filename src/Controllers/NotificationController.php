<?php

namespace App\Controllers;

use App\Models\Notification;
use App\Models\AuditTrail;

/**
 * Notification Controller
 * จัดการการแจ้งเตือน
 */
class NotificationController
{
    private $notificationModel;
    private $auditModel;

    public function __construct()
    {
        $this->notificationModel = new Notification();
        $this->auditModel = new AuditTrail();
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
            'email_enabled' => isset($_POST['email_enabled']) ? 1 : 0,
            'email_address' => $_POST['email_address'] ?? null,
            'line_enabled' => isset($_POST['line_enabled']) ? 1 : 0,
            'line_token' => $_POST['line_token'] ?? null
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
     * ทดสอบส่ง LINE
     */
    public function testLine()
    {
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $token = $_POST['token'] ?? '';
        if (empty($token)) {
            echo json_encode(['success' => false, 'message' => 'ไม่ได้ระบุ Token']);
            exit;
        }

        $result = $this->notificationModel->sendLine($token, "\n🧪 ทดสอบการแจ้งเตือนจาก Drugmuk\n\nหากคุณเห็นข้อความนี้ แสดงว่าการตั้งค่าถูกต้อง ✓");
        
        if (isset($result['status']) && $result['status'] == 200) {
            echo json_encode(['success' => true, 'message' => 'ส่งข้อความสำเร็จ']);
        } else {
            echo json_encode(['success' => false, 'message' => 'ส่งไม่สำเร็จ: ' . ($result['message'] ?? 'Unknown error')]);
        }
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
}
