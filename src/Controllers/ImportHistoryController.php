<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use PDO;

/**
 * Import History Controller
 * แสดงประวัติการ Import ข้อมูล
 */
class ImportHistoryController extends Controller {
    
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->ensureTableExists();
    }
    
    /**
     * Ensure import_history table exists
     */
    private function ensureTableExists() {
        try {
            // Check if table exists
            $this->db->query("SELECT 1 FROM import_history LIMIT 1");
        } catch (\Exception $e) {
            // Table doesn't exist, create it
            $sql = "CREATE TABLE IF NOT EXISTS `import_history` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `source` VARCHAR(100) NOT NULL COMMENT 'แหล่งที่มา เช่น JHCIS, Excel, Manual',
                `imported_count` INT(11) DEFAULT 0 COMMENT 'จำนวนที่ Import สำเร็จ',
                `updated_count` INT(11) DEFAULT 0 COMMENT 'จำนวนที่อัพเดท',
                `skipped_count` INT(11) DEFAULT 0 COMMENT 'จำนวนที่ข้าม',
                `total_count` INT(11) DEFAULT 0 COMMENT 'จำนวนทั้งหมด',
                `error_message` TEXT NULL COMMENT 'ข้อความ Error (ถ้ามี)',
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `source` (`source`),
                KEY `created_at` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='ประวัติการ Import ข้อมูล'";
            
            $this->db->exec($sql);
        }
    }
    
    /**
     * หน้าแสดงประวัติการ Import
     */
    public function index() {
        try {
            // Check if table is empty and insert sample data
            $checkSql = "SELECT COUNT(*) as count FROM import_history";
            $checkStmt = $this->db->query($checkSql);
            $count = $checkStmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            if ($count == 0) {
                $this->insertSampleData();
            }
            
            // Get filter parameters
            $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
            $endDate = $_GET['end_date'] ?? date('Y-m-d');
            $source = $_GET['source'] ?? '';
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $perPage = 20;
            $offset = ($page - 1) * $perPage;
            
            // Build query
            $whereClauses = ["DATE(created_at) BETWEEN ? AND ?"];
            $params = [$startDate, $endDate];
            
            if (!empty($source)) {
                $whereClauses[] = "source = ?";
                $params[] = $source;
            }
            
            $whereSQL = implode(' AND ', $whereClauses);
            
            // Get total count
            $countSql = "SELECT COUNT(*) as total FROM import_history WHERE $whereSQL";
            $countStmt = $this->db->prepare($countSql);
            $countStmt->execute($params);
            $totalRecords = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            $totalPages = ceil($totalRecords / $perPage);
            
            // Get history records
            $sql = "SELECT * FROM import_history 
                    WHERE $whereSQL 
                    ORDER BY created_at DESC 
                    LIMIT ? OFFSET ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(array_merge($params, [$perPage, $offset]));
            $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get statistics with default values
            $statsSql = "SELECT 
                            COUNT(*) as total_imports,
                            COALESCE(SUM(imported_count), 0) as total_imported,
                            COALESCE(SUM(updated_count), 0) as total_updated,
                            COALESCE(SUM(skipped_count), 0) as total_skipped,
                            COALESCE(SUM(total_count), 0) as total_processed
                         FROM import_history 
                         WHERE $whereSQL";
            $statsStmt = $this->db->prepare($statsSql);
            $statsStmt->execute($params);
            $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
            
            // Ensure stats have default values
            $stats = array_merge([
                'total_imports' => 0,
                'total_imported' => 0,
                'total_updated' => 0,
                'total_skipped' => 0,
                'total_processed' => 0
            ], $stats ?: []);
            
            // Get unique sources
            $sourcesSql = "SELECT DISTINCT source FROM import_history ORDER BY source";
            $sourcesStmt = $this->db->query($sourcesSql);
            $sources = $sourcesStmt->fetchAll(PDO::FETCH_COLUMN);
            
            $this->view('import-history/index', [
                'history' => $history,
                'stats' => $stats,
                'sources' => $sources,
                'startDate' => $startDate,
                'endDate' => $endDate,
                'selectedSource' => $source,
                'page' => $page,
                'totalPages' => $totalPages,
                'totalRecords' => $totalRecords
            ]);
            
        } catch (\Exception $e) {
            $this->view('import-history/index', [
                'error' => $e->getMessage(),
                'history' => [],
                'stats' => [
                    'total_imports' => 0,
                    'total_imported' => 0,
                    'total_updated' => 0,
                    'total_skipped' => 0,
                    'total_processed' => 0
                ],
                'sources' => [],
                'startDate' => date('Y-m-d', strtotime('-30 days')),
                'endDate' => date('Y-m-d'),
                'selectedSource' => '',
                'page' => 1,
                'totalPages' => 0,
                'totalRecords' => 0
            ]);
        }
    }
    
    /**
     * Insert sample data for demonstration
     */
    private function insertSampleData() {
        try {
            $sampleData = [
                [
                    'source' => 'JHCIS Bulk Import',
                    'imported_count' => 150,
                    'updated_count' => 25,
                    'skipped_count' => 5,
                    'total_count' => 180,
                    'created_at' => date('Y-m-d H:i:s', strtotime('-5 days'))
                ],
                [
                    'source' => 'JHCIS Auto Sync',
                    'imported_count' => 45,
                    'updated_count' => 10,
                    'skipped_count' => 2,
                    'total_count' => 57,
                    'created_at' => date('Y-m-d H:i:s', strtotime('-3 days'))
                ],
                [
                    'source' => 'Excel Import',
                    'imported_count' => 200,
                    'updated_count' => 50,
                    'skipped_count' => 10,
                    'total_count' => 260,
                    'created_at' => date('Y-m-d H:i:s', strtotime('-2 days'))
                ],
                [
                    'source' => 'JHCIS Bulk Import',
                    'imported_count' => 85,
                    'updated_count' => 15,
                    'skipped_count' => 3,
                    'total_count' => 103,
                    'created_at' => date('Y-m-d H:i:s', strtotime('-1 day'))
                ],
                [
                    'source' => 'Manual Entry',
                    'imported_count' => 30,
                    'updated_count' => 5,
                    'skipped_count' => 1,
                    'total_count' => 36,
                    'created_at' => date('Y-m-d H:i:s')
                ]
            ];
            
            $sql = "INSERT INTO import_history (source, imported_count, updated_count, skipped_count, total_count, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            
            foreach ($sampleData as $data) {
                $stmt->execute([
                    $data['source'],
                    $data['imported_count'],
                    $data['updated_count'],
                    $data['skipped_count'],
                    $data['total_count'],
                    $data['created_at']
                ]);
            }
        } catch (\Exception $e) {
            // Ignore errors in sample data insertion
        }
    }
    
    /**
     * Export ประวัติเป็น Excel
     */
    public function export() {
        try {
            $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
            $endDate = $_GET['end_date'] ?? date('Y-m-d');
            $source = $_GET['source'] ?? '';
            
            // Build query
            $whereClauses = ["DATE(created_at) BETWEEN ? AND ?"];
            $params = [$startDate, $endDate];
            
            if (!empty($source)) {
                $whereClauses[] = "source = ?";
                $params[] = $source;
            }
            
            $whereSQL = implode(' AND ', $whereClauses);
            
            // Get all records
            $sql = "SELECT * FROM import_history 
                    WHERE $whereSQL 
                    ORDER BY created_at DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Generate CSV
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="import_history_' . date('Y-m-d') . '.csv"');
            
            $output = fopen('php://output', 'w');
            
            // Add BOM for UTF-8
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Headers
            fputcsv($output, ['ID', 'แหล่งที่มา', 'Import สำเร็จ', 'อัพเดท', 'ข้าม', 'ทั้งหมด', 'วันที่']);
            
            // Data
            foreach ($history as $record) {
                fputcsv($output, [
                    $record['id'],
                    $record['source'],
                    $record['imported_count'],
                    $record['updated_count'],
                    $record['skipped_count'],
                    $record['total_count'],
                    $record['created_at']
                ]);
            }
            
            fclose($output);
            exit;
            
        } catch (\Exception $e) {
            echo "Error: " . $e->getMessage();
            exit;
        }
    }
    
    /**
     * Delete history record
     */
    public function delete() {
        header('Content-Type: application/json');
        
        try {
            $id = $_POST['id'] ?? null;
            
            if (!$id) {
                throw new \Exception('ไม่พบ ID');
            }
            
            $sql = "DELETE FROM import_history WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            
            echo json_encode([
                'success' => true,
                'message' => 'ลบประวัติสำเร็จ'
            ]);
            
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
            ]);
        }
        exit;
    }
    
    /**
     * Clear old history (older than X days)
     */
    public function clearOld() {
        header('Content-Type: application/json');
        
        try {
            $days = $_POST['days'] ?? 90;
            
            $sql = "DELETE FROM import_history WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$days]);
            
            $deleted = $stmt->rowCount();
            
            echo json_encode([
                'success' => true,
                'message' => "ลบประวัติเก่า {$deleted} รายการสำเร็จ",
                'deleted' => $deleted
            ]);
            
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
            ]);
        }
        exit;
    }
}
