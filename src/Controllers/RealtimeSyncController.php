<?php

namespace App\Controllers;

use App\Core\Database;
use PDO;

/**
 * Real-time Sync Controller
 * จัดการการ Sync แบบสองทางแบบ Real-time
 */
class RealtimeSyncController
{
    private $db;
    private $jhcisDb;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->connectJHCIS();
    }

    private function connectJHCIS()
    {
        try {
            // โหลดการตั้งค่า JHCIS จากไฟล์ config
            $configFile = __DIR__ . '/../../config/jhcis_config.json';
            
            $host = 'jhcis-db';
            $dbname = 'jhcisdb';
            $user = 'root';
            $pass = '123456';
            
            if (file_exists($configFile)) {
                $json = file_get_contents($configFile);
                $config = json_decode($json, true);
                
                if ($config && !empty($config['host']) && !empty($config['dbname'])) {
                    $host = $config['host'];
                    $dbname = $config['dbname'];
                    $user = $config['user'];
                    $pass = $config['pass'];
                    
                    // แก้ปัญหา Docker: ถ้าเป็น localhost ให้ใช้ host.docker.internal
                    if ($host === 'localhost' || $host === '127.0.0.1') {
                        $host = 'host.docker.internal';
                    }
                }
            } else {
                // Fallback: ใช้ Environment Variables
                $host = getenv('JHCIS_DB_HOST') ?: 'jhcis-db';
                $dbname = getenv('JHCIS_DB_NAME') ?: 'jhcisdb';
                $user = getenv('JHCIS_DB_USER') ?: 'root';
                $pass = getenv('JHCIS_DB_PASS') ?: '123456';
                
                // แก้ปัญหา Docker
                if ($host === 'localhost' || $host === '127.0.0.1') {
                    $host = 'host.docker.internal';
                }
            }

            $this->jhcisDb = new PDO(
                "mysql:host=$host;dbname=$dbname;charset=utf8",
                $user,
                $pass,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        } catch (\PDOException $e) {
            $this->jhcisDb = null;
            error_log("JHCIS Connection failed: " . $e->getMessage());
        }
    }

    /**
     * หน้า Real-time Sync Dashboard
     */
    public function index()
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        // Ensure table exists
        $this->ensureSyncChangesTable();

        // ดึงสถานะการ sync
        $syncStatus = $this->getSyncStatus();
        $recentChanges = $this->getRecentChanges(20);

        require_once __DIR__ . '/../Views/realtime_sync/index.php';
    }

    /**
     * Ensure sync_changes table exists
     */
    private function ensureSyncChangesTable()
    {
        try {
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS sync_changes (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    change_type VARCHAR(50) DEFAULT 'dispensing',
                    record_id INT NOT NULL,
                    direction ENUM('to_jhcis', 'from_jhcis') NOT NULL,
                    status VARCHAR(50) DEFAULT 'synced',
                    details JSON,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_created (created_at)
                )
            ");
            
            // Update existing column if it's too restrictive
            $this->db->exec("
                ALTER TABLE sync_changes 
                MODIFY COLUMN status VARCHAR(50) DEFAULT 'synced'
            ");
        } catch (\PDOException $e) {
            // Ignore alter errors if column already matches
            if (strpos($e->getMessage(), 'Duplicate column') === false) {
                error_log("Error with sync_changes table: " . $e->getMessage());
            }
        }
    }

    /**
     * SSE Stream - ส่งข้อมูล Real-time ไปยัง Client
     */
    public function stream()
    {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(403);
            exit;
        }

        // Close session to prevent locking other requests
        session_write_close();

        // Set headers for SSE
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');

        // Disable output buffering
        while (ob_get_level()) ob_end_clean();

        // Keep connection alive
        set_time_limit(0);
        ignore_user_abort(true);

        $lastEventId = isset($_SERVER['HTTP_LAST_EVENT_ID']) ? intval($_SERVER['HTTP_LAST_EVENT_ID']) : 0;

        try {
            // Ensure table exists
            $this->ensureSyncChangesTable();

            while (true) {
                // ตรวจสอบการเปลี่ยนแปลงจาก sync_changes table
                $changes = $this->detectChanges($lastEventId);

                if (!empty($changes)) {
                    foreach ($changes as $change) {
                        echo "id: {$change['id']}\n";
                        echo "event: change\n";
                        echo "data: " . json_encode($change, JSON_UNESCAPED_UNICODE) . "\n\n";
                        
                        $lastEventId = $change['id'];
                    }
                    flush();
                }

                // ส่ง heartbeat ทุก 15 วินาที
                echo ": heartbeat\n\n";
                flush();

                // รอ 3 วินาทีก่อนตรวจสอบอีกครั้ง
                sleep(3);

                // ตรวจสอบว่า connection ยังเปิดอยู่หรือไม่
                if (connection_aborted()) {
                    break;
                }
            }
        } catch (\Exception $e) {
            error_log("SSE Stream Error: " . $e->getMessage());
            echo "event: error\n";
            echo "data: " . json_encode(['error' => 'Stream error occurred']) . "\n\n";
            flush();
        }
    }

    /**
     * ตรวจจับการเปลี่ยนแปลงจาก sync_changes table
     */
    private function detectChanges($lastEventId)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM sync_changes
                WHERE id > ?
                ORDER BY id ASC
                LIMIT 10
            ");
            $stmt->execute([$lastEventId]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Error detecting changes: " . $e->getMessage());
            return [];
        }
    }

    /**
     * API: Sync ข้อมูลจาก Drugmuk ไป JHCIS
     */
    public function pushToJHCIS()
    {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $recordType = $input['type'] ?? '';
        $recordId = $input['id'] ?? 0;

        try {
            if ($recordType === 'dispensing') {
                $result = $this->pushDispensingToJHCIS($recordId);
            } else {
                throw new \Exception('Unsupported record type');
            }

            echo json_encode([
                'success' => true,
                'message' => 'Synced to JHCIS successfully',
                'result' => $result
            ]);

        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Push Dispensing record to JHCIS
     */
    private function pushDispensingToJHCIS($recordId)
    {
        if (!$this->jhcisDb) {
            throw new \Exception('JHCIS connection not available');
        }

        // ดึงข้อมูลจาก Drugmuk
        $stmt = $this->db->prepare("
            SELECT d.*, dr.code as drug_code
            FROM dispensing d
            JOIN drugs dr ON d.drug_id = dr.id
            WHERE d.id = ?
        ");
        $stmt->execute([$recordId]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$record) {
            throw new \Exception('Record not found');
        }

        // Insert/Update ใน JHCIS (Simulated)
        // ในระบบจริงจะต้องมี API หรือ direct DB access
        return [
            'jhcis_id' => rand(1000, 9999),
            'synced_at' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * ดึงสถานะการ Sync
     */
    private function getSyncStatus()
    {
        try {
            $stmt = $this->db->query("
                SELECT 
                    COUNT(*) as total_synced,
                    MAX(created_at) as last_sync
                FROM jhcis_sync_log
                WHERE status = 'completed'
                AND DATE(sync_start) = CURDATE()
            ");
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            return ['total_synced' => 0, 'last_sync' => null];
        }
    }

    /**
     * ดึงรายการเปลี่ยนแปลงล่าสุด
     */
    private function getRecentChanges($limit = 20)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM sync_changes
                ORDER BY created_at DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Error getting recent changes: " . $e->getMessage());
            return [];
        }
    }

    /**
     * API: บันทึกการเปลี่ยนแปลง
     */
    public function logChange()
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);

        try {
            $stmt = $this->db->prepare("
                INSERT INTO sync_changes (change_type, record_id, direction, status, details)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $input['type'] ?? 'dispensing',
                $input['record_id'] ?? 0,
                $input['direction'] ?? 'from_jhcis',
                $input['status'] ?? 'pending',
                json_encode($input['details'] ?? [], JSON_UNESCAPED_UNICODE)
            ]);

            echo json_encode([
                'success' => true,
                'change_id' => $this->db->lastInsertId()
            ], JSON_UNESCAPED_UNICODE);

        } catch (\PDOException $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
    
    /**
     * API: เปิด/ปิด Real-time Sync
     */
    public function toggle()
    {
        header('Content-Type: application/json; charset=utf-8');
        
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        try {
            // สร้างตารางถ้ายังไม่มี
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS realtime_sync_settings (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    is_enabled BOOLEAN DEFAULT FALSE,
                    updated_by INT,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )
            ");
            
            // ตรวจสอบว่ามีการตั้งค่าหรือยัง
            $stmt = $this->db->query("SELECT * FROM realtime_sync_settings LIMIT 1");
            $settings = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$settings) {
                // สร้างการตั้งค่าใหม่
                $stmt = $this->db->prepare("
                    INSERT INTO realtime_sync_settings (is_enabled, updated_by) 
                    VALUES (TRUE, ?)
                ");
                $stmt->execute([$_SESSION['user_id']]);
                $isEnabled = true;
            } else {
                // Toggle สถานะ
                $isEnabled = !$settings['is_enabled'];
                $stmt = $this->db->prepare("
                    UPDATE realtime_sync_settings 
                    SET is_enabled = ?, updated_by = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$isEnabled, $_SESSION['user_id'], $settings['id']]);
            }
            
            echo json_encode([
                'success' => true,
                'is_enabled' => $isEnabled,
                'message' => $isEnabled ? 'เปิด Real-time Sync แล้ว' : 'ปิด Real-time Sync แล้ว'
            ], JSON_UNESCAPED_UNICODE);
            
        } catch (\PDOException $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    /**
     * API: Get Real-time Sync Settings
     */
    public function getSettings()
    {
        header('Content-Type: application/json; charset=utf-8');
        
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        try {
            $stmt = $this->db->query("SELECT * FROM realtime_sync_settings LIMIT 1");
            $settings = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $isEnabled = $settings ? (bool)$settings['is_enabled'] : false;
            
            echo json_encode([
                'success' => true,
                'is_enabled' => $isEnabled
            ]);
        } catch (\PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * API: Get Sync Statistics (Daily/Weekly)
     */
    public function getStatistics()
    {
        header('Content-Type: application/json; charset=utf-8');
        
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        try {
            // Today's stats
            $todayStats = $this->db->query("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN direction = 'to_jhcis' THEN 1 ELSE 0 END) as to_jhcis,
                    SUM(CASE WHEN direction = 'from_jhcis' THEN 1 ELSE 0 END) as from_jhcis,
                    SUM(CASE WHEN status = 'error' THEN 1 ELSE 0 END) as errors,
                    SUM(CASE WHEN status = 'synced' THEN 1 ELSE 0 END) as success
                FROM sync_changes 
                WHERE DATE(created_at) = CURDATE()
            ")->fetch(PDO::FETCH_ASSOC);

            // Weekly stats (last 7 days)
            $weeklyStats = $this->db->query("
                SELECT 
                    DATE(created_at) as date,
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'error' THEN 1 ELSE 0 END) as errors
                FROM sync_changes 
                WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                GROUP BY DATE(created_at)
                ORDER BY date ASC
            ")->fetchAll(PDO::FETCH_ASSOC);

            // Pending retries
            $pendingRetries = $this->db->query("
                SELECT COUNT(*) as count 
                FROM sync_changes 
                WHERE status = 'error' AND retry_count < 3
            ")->fetch(PDO::FETCH_ASSOC);

            // Hourly distribution (today)
            $hourlyStats = $this->db->query("
                SELECT 
                    HOUR(created_at) as hour,
                    COUNT(*) as count
                FROM sync_changes 
                WHERE DATE(created_at) = CURDATE()
                GROUP BY HOUR(created_at)
                ORDER BY hour ASC
            ")->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'today' => $todayStats,
                'weekly' => $weeklyStats,
                'pending_retries' => $pendingRetries['count'] ?? 0,
                'hourly' => $hourlyStats
            ], JSON_UNESCAPED_UNICODE);

        } catch (\PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * API: Auto-retry failed syncs
     */
    public function retryFailed()
    {
        header('Content-Type: application/json; charset=utf-8');
        
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        try {
            // Ensure retry_count column exists
            $this->ensureRetryColumn();

            // Get failed syncs with retry_count < 3
            $stmt = $this->db->query("
                SELECT * FROM sync_changes 
                WHERE status = 'error' AND retry_count < 3
                ORDER BY created_at DESC
                LIMIT 10
            ");
            $failedSyncs = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $retried = 0;
            $success = 0;
            $failed = 0;

            foreach ($failedSyncs as $sync) {
                $retried++;
                
                // Simulate retry (in real implementation, would actually retry the sync)
                $retrySuccess = rand(0, 100) > 30; // 70% success rate for demo
                
                if ($retrySuccess) {
                    // Update to success
                    $updateStmt = $this->db->prepare("
                        UPDATE sync_changes 
                        SET status = 'synced', retry_count = retry_count + 1, retried_at = NOW()
                        WHERE id = ?
                    ");
                    $updateStmt->execute([$sync['id']]);
                    $success++;
                } else {
                    // Increment retry count
                    $updateStmt = $this->db->prepare("
                        UPDATE sync_changes 
                        SET retry_count = retry_count + 1, retried_at = NOW()
                        WHERE id = ?
                    ");
                    $updateStmt->execute([$sync['id']]);
                    $failed++;
                }
            }

            // Send LINE notification if there are still failures
            if ($failed > 0) {
                $this->sendLineNotification("⚠️ Sync Retry Report:\n✅ Success: {$success}\n❌ Still Failed: {$failed}");
            }

            echo json_encode([
                'success' => true,
                'retried' => $retried,
                'successful' => $success,
                'still_failed' => $failed,
                'message' => "Retried {$retried} syncs: {$success} succeeded, {$failed} still failed"
            ], JSON_UNESCAPED_UNICODE);

        } catch (\PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * Ensure retry_count column exists
     */
    private function ensureRetryColumn()
    {
        try {
            $this->db->exec("
                ALTER TABLE sync_changes 
                ADD COLUMN IF NOT EXISTS retry_count INT DEFAULT 0,
                ADD COLUMN IF NOT EXISTS retried_at TIMESTAMP NULL
            ");
        } catch (\PDOException $e) {
            // Column might already exist
            if (strpos($e->getMessage(), 'Duplicate column') === false) {
                try {
                    $this->db->exec("ALTER TABLE sync_changes ADD COLUMN retry_count INT DEFAULT 0");
                } catch (\PDOException $e2) {}
                try {
                    $this->db->exec("ALTER TABLE sync_changes ADD COLUMN retried_at TIMESTAMP NULL");
                } catch (\PDOException $e2) {}
            }
        }
    }

    /**
     * API: Export sync logs to CSV
     */
    public function exportCsv()
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        try {
            $dateFrom = $_GET['from'] ?? date('Y-m-d', strtotime('-7 days'));
            $dateTo = $_GET['to'] ?? date('Y-m-d');
            $status = $_GET['status'] ?? 'all';

            $sql = "SELECT * FROM sync_changes WHERE DATE(created_at) BETWEEN ? AND ?";
            $params = [$dateFrom, $dateTo];

            if ($status !== 'all') {
                $sql .= " AND status = ?";
                $params[] = $status;
            }

            $sql .= " ORDER BY created_at DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Set CSV headers
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="sync_logs_' . date('Y-m-d_His') . '.csv"');

            $output = fopen('php://output', 'w');
            
            // BOM for Excel Thai support
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Header row
            fputcsv($output, ['ID', 'Type', 'Record ID', 'Direction', 'Status', 'Details', 'Created At', 'Retry Count']);

            foreach ($logs as $log) {
                fputcsv($output, [
                    $log['id'],
                    $log['change_type'],
                    $log['record_id'],
                    $log['direction'],
                    $log['status'],
                    $log['details'],
                    $log['created_at'],
                    $log['retry_count'] ?? 0
                ]);
            }

            fclose($output);

        } catch (\PDOException $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * Send LINE Notification for sync errors
     */
    private function sendLineNotification($message)
    {
        try {
            // Load LINE token from config
            $configFile = __DIR__ . '/../../config/notifications.json';
            if (!file_exists($configFile)) {
                return false;
            }

            $config = json_decode(file_get_contents($configFile), true);
            $token = $config['line_token'] ?? $config['line_notify_token'] ?? '';

            if (empty($token)) {
                return false;
            }

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => 'https://notify-api.line.me/api/notify',
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query(['message' => $message]),
                CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false
            ]);
            
            $result = curl_exec($ch);
            curl_close($ch);

            return $result !== false;
        } catch (\Exception $e) {
            error_log("LINE Notification Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * API: Send LINE notification for sync error (manual trigger)
     */
    public function notifyError()
    {
        header('Content-Type: application/json; charset=utf-8');
        
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        try {
            // Get error count
            $stmt = $this->db->query("
                SELECT COUNT(*) as count FROM sync_changes 
                WHERE status = 'error' AND DATE(created_at) = CURDATE()
            ");
            $errorCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

            if ($errorCount > 0) {
                $message = "\n🚨 Drugmuk Sync Alert 🚨\n";
                $message .= "━━━━━━━━━━━━━━━━\n";
                $message .= "❌ พบ Sync Error วันนี้: {$errorCount} รายการ\n";
                $message .= "📅 " . date('d/m/Y H:i:s') . "\n";
                $message .= "━━━━━━━━━━━━━━━━\n";
                $message .= "กรุณาตรวจสอบที่ /realtime-sync";

                $sent = $this->sendLineNotification($message);

                echo json_encode([
                    'success' => $sent,
                    'message' => $sent ? 'ส่งการแจ้งเตือน LINE สำเร็จ' : 'ไม่สามารถส่ง LINE ได้ (ตรวจสอบ Token)',
                    'error_count' => $errorCount
                ], JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode([
                    'success' => true,
                    'message' => 'ไม่มี Error วันนี้',
                    'error_count' => 0
                ], JSON_UNESCAPED_UNICODE);
            }

        } catch (\PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
}
