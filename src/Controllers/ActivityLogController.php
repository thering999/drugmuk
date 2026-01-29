<?php
/**
 * Activity Log Controller
 * แสดงและจัดการ Activity Logs
 * 
 * @package Drugmuk
 * @version 3.4.0
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Services\ActivityLogService;

class ActivityLogController extends Controller
{
    private ActivityLogService $logService;
    
    public function __construct()
    {
        $this->logService = new ActivityLogService();
    }
    
    /**
     * หน้าแสดง Activity Logs
     */
    public function index()
    {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 50;
        $offset = ($page - 1) * $limit;
        
        $filters = [
            'module' => $_GET['module'] ?? '',
            'action' => $_GET['action'] ?? '',
            'date_from' => $_GET['date_from'] ?? '',
            'date_to' => $_GET['date_to'] ?? '',
            'search' => $_GET['search'] ?? ''
        ];
        
        // Clean empty filters
        $filters = array_filter($filters);
        
        $logs = $this->logService->getLogs($filters, $limit, $offset);
        $totalLogs = $this->logService->countLogs($filters);
        $totalPages = ceil($totalLogs / $limit);
        
        $statistics = $this->logService->getStatistics(7);
        $dailyStats = $this->logService->getDailyStatistics(7);
        
        $this->view('activity-log/index', [
            'logs' => $logs,
            'filters' => $filters,
            'page' => $page,
            'total_pages' => $totalPages,
            'total_logs' => $totalLogs,
            'statistics' => $statistics,
            'daily_stats' => $dailyStats
        ]);
    }
    
    /**
     * API: ดึง Logs ล่าสุด
     */
    public function apiRecent()
    {
        header('Content-Type: application/json');
        
        $limit = min(100, max(1, (int)($_GET['limit'] ?? 20)));
        $logs = $this->logService->getRecentLogs($limit);
        
        echo json_encode([
            'success' => true,
            'data' => $logs
        ]);
    }
    
    /**
     * API: ดึงสถิติ
     */
    public function apiStatistics()
    {
        header('Content-Type: application/json');
        
        $days = min(90, max(1, (int)($_GET['days'] ?? 7)));
        
        echo json_encode([
            'success' => true,
            'data' => [
                'by_action' => $this->logService->getStatistics($days),
                'daily' => $this->logService->getDailyStatistics($days)
            ]
        ]);
    }
    
    /**
     * ลบ Logs เก่า
     */
    public function cleanup()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /activity-log');
            exit;
        }
        
        $this->validateCSRF();
        
        $days = max(30, (int)($_POST['retention_days'] ?? 90));
        $deleted = $this->logService->cleanOldLogs($days);
        
        $_SESSION['success'] = "ลบ Activity Logs เก่ากว่า $days วัน จำนวน $deleted รายการ";
        header('Location: /activity-log');
        exit;
    }
    
    /**
     * Export Logs
     */
    public function export()
    {
        $filters = [
            'module' => $_GET['module'] ?? '',
            'action' => $_GET['action'] ?? '',
            'date_from' => $_GET['date_from'] ?? date('Y-m-01'),
            'date_to' => $_GET['date_to'] ?? date('Y-m-d'),
            'search' => $_GET['search'] ?? ''
        ];
        
        $filters = array_filter($filters);
        $logs = $this->logService->getLogs($filters, 10000, 0);
        
        // Export as CSV
        $filename = 'activity_log_' . date('Y-m-d_His') . '.csv';
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        echo "\xEF\xBB\xBF"; // UTF-8 BOM
        
        $output = fopen('php://output', 'w');
        
        // Headers
        fputcsv($output, ['วันที่/เวลา', 'ผู้ใช้', 'การกระทำ', 'โมดูล', 'รายละเอียด', 'IP Address']);
        
        foreach ($logs as $log) {
            fputcsv($output, [
                $log['created_at'],
                $log['username'],
                $log['action'],
                $log['module'],
                $log['description'],
                $log['ip_address']
            ]);
        }
        
        fclose($output);
        exit;
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
