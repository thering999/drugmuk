<?php

namespace App\Controllers;

use App\Models\DataCleansing;

/**
 * Data Cleansing Controller
 * จัดการหน้าจอและการทำงานของระบบทำความสะอาดข้อมูล
 */
class DataCleansingController
{
    private $model;

    public function __construct()
    {
        $this->model = new DataCleansing();
    }

    /**
     * หน้าหลักของระบบทำความสะอาดข้อมูล
     */
    public function index()
    {
        // ตรวจสอบ session
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        // ตรวจสอบและสร้างตารางถ้ายังไม่มี
        $this->ensureTablesExist();

        // ดึงสรุปคุณภาพข้อมูล
        $qualitySummary = $this->model->getDataQualitySummary();
        $cleanupHistory = $this->model->getCleanupHistory(10);

        // แสดงหน้า dashboard
        require_once __DIR__ . '/../Views/data_cleansing/index.php';
    }
    
    /**
     * ตรวจสอบและสร้างตารางถ้ายังไม่มี
     */
    private function ensureTablesExist()
    {
        try {
            $db = \App\Core\Database::getInstance()->getConnection();
            
            // ตรวจสอบว่าตาราง duplicate_candidates มีหรือยัง
            $stmt = $db->query("SHOW TABLES LIKE 'duplicate_candidates'");
            $tableExists = $stmt->rowCount() > 0;
            
            if (!$tableExists) {
                // ลองใช้ไฟล์ simple ก่อน
                $sqlFile = __DIR__ . '/../../database/data_cleansing_simple.sql';
                
                if (file_exists($sqlFile)) {
                    $sql = file_get_contents($sqlFile);
                    
                    // รัน SQL ทีละ statement
                    $statements = explode(';', $sql);
                    
                    foreach ($statements as $statement) {
                        $statement = trim($statement);
                        
                        // ข้าม comment และ empty lines
                        if (empty($statement) || 
                            strpos($statement, '--') === 0 || 
                            strpos($statement, '/*') === 0) {
                            continue;
                        }
                        
                        try {
                            $db->exec($statement);
                        } catch (\PDOException $e) {
                            // Log error but continue
                            error_log("SQL Error: " . $e->getMessage());
                        }
                    }
                } else {
                    // ถ้าไม่มีไฟล์ ให้สร้างตารางด้วย PHP
                    $this->createTablesDirectly($db);
                }
            }
        } catch (\Exception $e) {
            error_log("Error ensuring tables exist: " . $e->getMessage());
        }
    }
    
    /**
     * สร้างตารางโดยตรงด้วย PHP
     */
    private function createTablesDirectly($db)
    {
        try {
            // สร้างตาราง duplicate_candidates
            $db->exec("
                CREATE TABLE IF NOT EXISTS `duplicate_candidates` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `table_name` varchar(100) NOT NULL,
                  `record1_id` int(11) NOT NULL,
                  `record2_id` int(11) NOT NULL,
                  `similarity_score` decimal(5,2) DEFAULT NULL,
                  `status` enum('pending','merged','false_positive','ignored') DEFAULT 'pending',
                  `merged_to` int(11) DEFAULT NULL,
                  `detected_by` int(11) DEFAULT NULL,
                  `detected_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  `resolved_by` int(11) DEFAULT NULL,
                  `resolved_at` timestamp NULL DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `unique_duplicate` (`table_name`,`record1_id`,`record2_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
            
            // สร้างตาราง orphaned_records
            $db->exec("
                CREATE TABLE IF NOT EXISTS `orphaned_records` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `table_name` varchar(100) NOT NULL,
                  `record_id` int(11) NOT NULL,
                  `reason` text,
                  `status` enum('pending','deleted','fixed','ignored') DEFAULT 'pending',
                  `detected_by` int(11) DEFAULT NULL,
                  `detected_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  `resolved_by` int(11) DEFAULT NULL,
                  `resolved_at` timestamp NULL DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `unique_orphan` (`table_name`,`record_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
            
            // สร้างตาราง cleanup_history
            $db->exec("
                CREATE TABLE IF NOT EXISTS `cleanup_history` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `operation_type` enum('merge','delete','fix','check') NOT NULL,
                  `table_name` varchar(100) NOT NULL,
                  `records_affected` int(11) DEFAULT 0,
                  `operation_details` text,
                  `performed_by` int(11) DEFAULT NULL,
                  `performed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
            
        } catch (\PDOException $e) {
            error_log("Error creating tables directly: " . $e->getMessage());
        }
    }

    /**
     * หน้าแสดงรายการ duplicates
     */
    public function duplicates()
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $tableName = $_GET['table'] ?? null;
        $duplicates = $this->model->getPendingDuplicates($tableName);

        require_once __DIR__ . '/../Views/data_cleansing/duplicates.php';
    }

    /**
     * หน้าแสดงรายการ orphaned records
     */
    public function orphanedRecords()
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $tableName = $_GET['table'] ?? null;
        $orphanedRecords = $this->model->getPendingOrphanedRecords($tableName);

        require_once __DIR__ . '/../Views/data_cleansing/orphaned.php';
    }

    /**
     * หน้าแสดงรายงานคุณภาพข้อมูล
     */
    public function qualityReports()
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        // ดึงข้อมูลรายงาน
        $qualitySummary = $this->model->getDataQualitySummary();
        $qualityTrends = $this->model->getQualityTrends(30); // 30 วันล่าสุด
        
        require_once __DIR__ . '/../Views/data_cleansing/quality_reports.php';
    }

    /**
     * ตรวจหายาซ้ำ (AJAX)
     */
    public function detectDuplicates()
    {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'กรุณา login']);
            exit;
        }

        $threshold = $_POST['threshold'] ?? 75.0;
        $userId = $_SESSION['user_id'];

        $result = $this->model->detectDuplicateDrugs($userId, $threshold);
        echo json_encode($result);
    }

    /**
     * ตรวจหา orphaned records (AJAX)
     */
    public function detectOrphaned()
    {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'กรุณา login']);
            exit;
        }

        $userId = $_SESSION['user_id'];
        $results = [];

        // ตรวจหา orphaned transactions
        $results['transactions'] = $this->model->detectOrphanedTransactions($userId);

        // ตรวจหา orphaned order items
        $results['order_items'] = $this->model->detectOrphanedOrderItems($userId);

        echo json_encode([
            'success' => true,
            'results' => $results
        ]);
    }

    /**
     * รวม (merge) รายการซ้ำ (AJAX)
     */
    public function mergeDuplicates()
    {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'กรุณา login']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            exit;
        }

        $duplicateId = $_POST['duplicate_id'] ?? null;
        $keepId = $_POST['keep_id'] ?? null;
        $removeId = $_POST['remove_id'] ?? null;
        $userId = $_SESSION['user_id'];

        if (!$duplicateId || !$keepId || !$removeId) {
            echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ครบถ้วน']);
            exit;
        }

        $result = $this->model->mergeDuplicates($duplicateId, $keepId, $removeId, $userId);
        echo json_encode($result);
    }

    /**
     * ลบ orphaned records (AJAX)
     */
    public function deleteOrphaned()
    {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'กรุณา login']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            exit;
        }

        $recordIds = $_POST['record_ids'] ?? [];
        $userId = $_SESSION['user_id'];

        if (empty($recordIds)) {
            echo json_encode(['success' => false, 'message' => 'กรุณาเลือกรายการที่ต้องการลบ']);
            exit;
        }

        $result = $this->model->deleteOrphanedRecords($recordIds, $userId);
        echo json_encode($result);
    }

    /**
     * ทำเครื่องหมาย duplicate เป็น false positive (AJAX)
     */
    public function markFalsePositive()
    {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'กรุณา login']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            exit;
        }

        $duplicateId = $_POST['duplicate_id'] ?? null;
        $userId = $_SESSION['user_id'];

        if (!$duplicateId) {
            echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ครบถ้วน']);
            exit;
        }

        $result = $this->model->markAsFalsePositive($duplicateId, $userId);
        echo json_encode($result);
    }

    /**
     * รันการตรวจสอบคุณภาพข้อมูลทั้งหมด (AJAX)
     */
    public function runFullCheck()
    {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'กรุณา login']);
            exit;
        }

        $userId = $_SESSION['user_id'];
        $results = $this->model->runFullDataQualityCheck($userId);

        echo json_encode([
            'success' => true,
            'results' => $results
        ]);
    }

    /**
     * ดึงคะแนนคุณภาพข้อมูล (AJAX)
     */
    public function getQualityScore()
    {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'กรุณา login']);
            exit;
        }

        $scores = $this->model->getDataQualityScore();
        echo json_encode([
            'success' => true,
            'scores' => $scores
        ]);
    }

    /**
     * ดึงประวัติการทำความสะอาด (AJAX)
     */
    public function getCleanupHistory()
    {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'กรุณา login']);
            exit;
        }

        $limit = $_GET['limit'] ?? 50;
        $history = $this->model->getCleanupHistory($limit);

        echo json_encode([
            'success' => true,
            'history' => $history
        ]);
    }
    
    /**
     * หน้า Setup สำหรับสร้างตาราง
     */
    public function setup()
    {
        require_once __DIR__ . '/../Views/data-cleansing/setup.php';
    }
    
    /**
     * สร้างตารางและ procedures สำหรับ Data Cleansing (AJAX)
     */
    public function setupTables()
    {
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            $db = \App\Core\Database::getInstance()->getConnection();
            
            // อ่าน SQL file
            $sqlFile = __DIR__ . '/../../database/data_cleansing_tables.sql';
            if (!file_exists($sqlFile)) {
                throw new \Exception('ไม่พบไฟล์ SQL');
            }
            
            $sql = file_get_contents($sqlFile);
            
            // แยก statements
            $statements = array_filter(
                array_map('trim', explode(';', $sql)),
                function($stmt) {
                    return !empty($stmt) && 
                           !preg_match('/^(--|\/\*|DELIMITER)/', $stmt);
                }
            );
            
            $tablesCreated = 0;
            $viewsCreated = 0;
            $proceduresCreated = 0;
            
            foreach ($statements as $statement) {
                if (empty(trim($statement))) continue;
                
                try {
                    $db->exec($statement);
                    
                    if (stripos($statement, 'CREATE TABLE') !== false) {
                        $tablesCreated++;
                    } elseif (stripos($statement, 'CREATE OR REPLACE VIEW') !== false || 
                              stripos($statement, 'CREATE VIEW') !== false) {
                        $viewsCreated++;
                    } elseif (stripos($statement, 'CREATE PROCEDURE') !== false) {
                        $proceduresCreated++;
                    }
                } catch (\PDOException $e) {
                    // Ignore errors for existing objects
                    if (strpos($e->getMessage(), 'already exists') === false) {
                        error_log("Error executing statement: " . $e->getMessage());
                    }
                }
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'สร้างตารางสำเร็จ',
                'tables_created' => $tablesCreated,
                'views_created' => $viewsCreated,
                'procedures_created' => $proceduresCreated
            ], JSON_UNESCAPED_UNICODE);
            
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
}
