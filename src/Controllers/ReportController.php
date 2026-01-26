<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\CustomReport;

class ReportController extends Controller
{
    private $reportModel;

    public function __construct()
    {
        $this->reportModel = new CustomReport();
    }

    public function index()
    {
        // Optional: Allow public access to reports
        // if (!isset($_SESSION['user_id'])) {
        //     $this->redirect('/login');
        // }

        try {
            $reports = $this->reportModel->getAll();
        } catch (\Exception $e) {
            // Table doesn't exist yet, use empty array
            $reports = [];
        }
        
        $predefined = $this->reportModel->getPredefinedReports();

        $this->view('reports/index', [
            'reports' => $reports,
            'predefined' => $predefined
        ]);
    }

    public function builder()
    {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/login');
        }

        $this->view('reports/builder');
    }

    public function create()
    {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/login');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'name' => $_POST['name'],
                'description' => $_POST['description'] ?? '',
                'query' => $_POST['query'],
                'columns' => json_encode($_POST['columns'] ?? []),
                'filters' => json_encode($_POST['filters'] ?? []),
                'created_by' => $_SESSION['user_id']
            ];

            $this->reportModel->create($data);
            $_SESSION['success'] = 'สร้างรายงานสำเร็จ';
            $this->redirect('/reports');
        }
    }

    public function generate($id)
    {
        // Optional: Allow public access to reports
        // if (!isset($_SESSION['user_id'])) {
        //     $this->redirect('/login');
        // }

        try {
            $params = $_GET;
            unset($params['url']);
            
            $data = $this->reportModel->executeReport($id, $params);
            $report = $this->reportModel->getById($id);

            $this->view('reports/view', [
                'report' => $report,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            // Show error details for debugging
            echo "<h1>Error executing report</h1>";
            echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<p><a href='/reports'>← กลับหน้ารายงาน</a></p>";
        }
    }

    public function predefined($id)
    {
        // Optional: Allow public access to reports
        // if (!isset($_SESSION['user_id'])) {
        //     $this->redirect('/login');
        // }

        $predefined = $this->reportModel->getPredefinedReports();
        $report = null;
        
        foreach ($predefined as $r) {
            if ($r['id'] === $id) {
                $report = $r;
                break;
            }
        }

        if (!$report) {
            $_SESSION['error'] = 'ไม่พบรายงาน';
            $this->redirect('/reports');
            return;
        }

        try {
            // Execute predefined query directly
            $db = \App\Core\Database::getInstance()->getConnection();
            $stmt = $db->query($report['query']);
            $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $this->view('reports/view', [
                'report' => $report,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            // Show error details for debugging
            echo "<h1>Error executing report</h1>";
            echo "<p><strong>Report:</strong> " . htmlspecialchars($report['name']) . "</p>";
            echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<p><strong>Query:</strong></p>";
            echo "<pre>" . htmlspecialchars($report['query']) . "</pre>";
            echo "<p><a href='/reports'>← กลับหน้ารายงาน</a></p>";
        }
    }

    public function export()
    {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/login');
        }

        $reportId = $_POST['report_id'] ?? null;
        $format = $_POST['format'] ?? 'csv';
        $params = $_POST['params'] ?? [];

        try {
            $data = $this->reportModel->executeReport($reportId, $params);
            $report = $this->reportModel->getById($reportId);
            
            $filename = 'report_' . date('Ymd_His');

            if ($format === 'csv') {
                $content = $this->reportModel->exportToCSV($data, $filename);
                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
                echo $content;
            } else if ($format === 'excel') {
                $content = $this->reportModel->exportToExcel($data, $filename);
                header('Content-Type: application/vnd.ms-excel');
                header('Content-Disposition: attachment; filename="' . $filename . '.xls"');
                echo $content;
            } else if ($format === 'json') {
                header('Content-Type: application/json');
                header('Content-Disposition: attachment; filename="' . $filename . '.json"');
                echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            }

            exit;

        } catch (\Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            $this->redirect('/reports');
        }
    }

    public function delete($id)
    {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/login');
        }

        $this->reportModel->delete($id);
        $_SESSION['success'] = 'ลบรายงานสำเร็จ';
        $this->redirect('/reports');
    }
}
