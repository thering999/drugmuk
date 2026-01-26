<?php

namespace App\Controllers;

use App\Core\Database;
use PDO;

/**
 * Auto-update System Controller
 * จัดการการอัพเดทระบบอัตโนมัติ
 */
class UpdateController
{
    private $db;
    private $currentVersion = '2.2.0'; // เวอร์ชันปัจจุบัน
    private $updateServer = 'https://updates.drugmuk.local/api'; // Mock URL

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * หน้า Update Dashboard
     */
    public function index()
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        // ส่งตัวแปรไปยัง View
        $currentVersion = $this->currentVersion;
        $latestVersion = $this->checkLatestVersion();
        $updateHistory = $this->getUpdateHistory();

        require_once __DIR__ . '/../Views/updates/index.php';
    }

    /**
     * ตรวจสอบเวอร์ชันล่าสุดจาก Update Server
     */
    public function checkLatestVersion()
    {
        // Mock data - ในระบบจริงจะเรียก API
        return [
            'version' => '2.3.0',
            'release_date' => '2025-01-15',
            'changelog' => [
                'เพิ่มฟีเจอร์ Real-time Sync',
                'ปรับปรุง Performance',
                'แก้ไข Bug ต่างๆ'
            ],
            'download_url' => $this->updateServer . '/download/2.3.0',
            'is_critical' => false
        ];
    }

    /**
     * ดึงประวัติการอัพเดท
     */
    private function getUpdateHistory()
    {
        try {
            // สร้างตารางถ้ายังไม่มี
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS system_updates (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    version VARCHAR(20) NOT NULL,
                    update_type ENUM('major', 'minor', 'patch') DEFAULT 'patch',
                    status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
                    notes TEXT,
                    updated_by INT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");

            $stmt = $this->db->query("
                SELECT * FROM system_updates 
                ORDER BY created_at DESC 
                LIMIT 10
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            return [];
        }
    }

    /**
     * API: ตรวจสอบอัพเดท
     */
    public function checkUpdate()
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $latest = $this->checkLatestVersion();
        $hasUpdate = version_compare($latest['version'], $this->currentVersion, '>');

        echo json_encode([
            'success' => true,
            'current_version' => $this->currentVersion,
            'latest_version' => $latest['version'],
            'has_update' => $hasUpdate,
            'update_info' => $hasUpdate ? $latest : null
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * API: ดาวน์โหลดและติดตั้งอัพเดท
     */
    public function installUpdate()
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        try {
            // Mock installation process
            sleep(2); // Simulate download/install time

            // บันทึกประวัติ
            $stmt = $this->db->prepare("
                INSERT INTO system_updates (version, update_type, status, updated_by, notes)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                '2.3.0',
                'minor',
                'completed',
                $_SESSION['user_id'],
                'Auto-update completed successfully'
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'อัพเดทสำเร็จ! กรุณา Refresh หน้าเว็บ',
                'new_version' => '2.3.0'
            ], JSON_UNESCAPED_UNICODE);

        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
}
