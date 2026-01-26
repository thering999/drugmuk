<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\DMSIC;

class DMSICController extends Controller
{
    private $dmsicModel;

    public function __construct()
    {
        $this->dmsicModel = new DMSIC();
    }

    public function index()
    {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/login');
        }

        $history = $this->dmsicModel->getExportHistory(20);
        $stats = $this->dmsicModel->getStatistics();
        $config = $this->dmsicModel->getConfig();

        $this->view('dmsic/index', [
            'history' => $history,
            'stats' => $stats,
            'config' => $config
        ]);
    }

    public function export()
    {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/login');
        }

        $config = $this->dmsicModel->getConfig();
        $this->view('dmsic/export', ['config' => $config]);
    }

    public function processExport()
    {
        header('Content-Type: application/json; charset=utf-8');
        
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        try {
            $startDate = $_POST['start_date'] ?? date('Y-m-01');
            $endDate = $_POST['end_date'] ?? date('Y-m-d');
            
            // Gather data
            $data = $this->dmsicModel->gatherExportData($startDate, $endDate);
            
            // If no data in selected period, try all-time data
            if (empty($data)) {
                $data = $this->dmsicModel->gatherExportData('2000-01-01', date('Y-m-d'));
                
                if (empty($data)) {
                    throw new \Exception('ไม่พบข้อมูลการจ่ายยาในระบบ กรุณาเพิ่มข้อมูลการจ่ายยาก่อนส่งออก');
                }
                
                // Update message to indicate we're using all-time data
                $periodNote = ' (ใช้ข้อมูลทั้งหมดเนื่องจากไม่มีข้อมูลในเดือนปัจจุบัน)';
            } else {
                $periodNote = '';
            }

            // Validate data
            $validation = $this->dmsicModel->validateData($data);
            
            if (!$validation['valid']) {
                throw new \Exception('Data validation failed: ' . count($validation['errors']) . ' errors found');
            }

            // Get config
            $config = $this->dmsicModel->getConfig();
            
            // Format data
            $content = $this->dmsicModel->formatAsDMSIC($data, $config);
            
            // Generate filename
            $fileName = 'DMSIC_' . date('Ymd_His') . '.txt';
            
            // Export to file
            $filePath = $this->dmsicModel->exportToFile($content, $fileName);
            
            // Create export record
            $exportId = $this->dmsicModel->createExport([
                'export_date' => date('Y-m-d H:i:s'),
                'file_name' => $fileName,
                'file_path' => $filePath,
                'record_count' => count($data),
                'status' => 'exported',
                'exported_by' => $_SESSION['user_id']
            ]);

            // Auto-send if configured AND API URL is set
            if ($config && $config['auto_send'] == 1 && !empty($config['api_url'])) {
                try {
                    $result = $this->dmsicModel->sendToAPI($filePath, $config);
                    $this->dmsicModel->updateExportStatus($exportId, 'success', $result['message']);
                } catch (\Exception $e) {
                    $this->dmsicModel->updateExportStatus($exportId, 'failed', $e->getMessage());
                }
            }
            // If no auto-send or no API URL, just mark as exported
            // User can manually send later from history

            echo json_encode([
                'success' => true,
                'message' => 'ส่งข้อมูลสำเร็จ' . $periodNote,
                'file_name' => $fileName,
                'record_count' => count($data),
                'export_id' => $exportId
            ], JSON_UNESCAPED_UNICODE);
            exit;

        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    public function history()
    {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/login');
        }

        $history = $this->dmsicModel->getExportHistory(100);
        $this->view('dmsic/history', ['history' => $history]);
    }

    public function config()
    {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/login');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'hospcode' => $_POST['hospcode'],
                'hospname' => $_POST['hospname'],
                'api_url' => $_POST['api_url'],
                'api_key' => $_POST['api_key'],
                'auto_send' => isset($_POST['auto_send']) ? 1 : 0,
                'send_schedule' => $_POST['send_schedule'] ?? 'daily'
            ];

            $this->dmsicModel->updateConfig($data);
            $_SESSION['success'] = 'บันทึกการตั้งค่าสำเร็จ';
            $this->redirect('/dmsic/config');
        }

        $config = $this->dmsicModel->getConfig();
        $this->view('dmsic/config', ['config' => $config]);
    }

    public function send($id = null)
    {
        header('Content-Type: application/json; charset=utf-8');
        
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        try {
            // Get export record
            $history = $this->dmsicModel->getExportHistory(1000);
            $export = null;
            foreach ($history as $h) {
                if ($h['id'] == $id) {
                    $export = $h;
                    break;
                }
            }

            if (!$export) {
                throw new \Exception('Export record not found');
            }

            // Get config
            $config = $this->dmsicModel->getConfig();
            
            // Send to API
            $result = $this->dmsicModel->sendToAPI($export['file_path'], $config);
            
            // Update status
            $this->dmsicModel->updateExportStatus($id, 'success', $result['message']);

            echo json_encode([
                'success' => true,
                'message' => 'ส่งข้อมูลสำเร็จ',
                'transaction_id' => $result['transaction_id']
            ], JSON_UNESCAPED_UNICODE);
            exit;

        } catch (\Exception $e) {
            // Update status as failed
            if (isset($id) && $id !== null) {
                $this->dmsicModel->updateExportStatus($id, 'failed', $e->getMessage());
            }
            
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    public function download($id)
    {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/login');
        }

        // Get export record
        $history = $this->dmsicModel->getExportHistory(1000);
        $export = null;
        foreach ($history as $h) {
            if ($h['id'] == $id) {
                $export = $h;
                break;
            }
        }

        if (!$export || !file_exists($export['file_path'])) {
            $_SESSION['error'] = 'ไม่พบไฟล์';
            $this->redirect('/dmsic/history');
        }

        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="' . $export['file_name'] . '"');
        readfile($export['file_path']);
        exit;
    }
}
