<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Dispensing;
use App\Models\Drug;

class DispensingController extends Controller {
    
    private $dispensingModel;
    private $drugModel;

    public function __construct() {
        $this->dispensingModel = new Dispensing();
        $this->drugModel = new Drug();
    }

    /**
     * List all dispensing records
     */
    public function index() {
        $page = $_GET['page'] ?? 1;
        $perPage = 20;
        
        $dispensings = $this->dispensingModel->getAll($page, $perPage);
        $total = $this->dispensingModel->getTotalCount();
        $totalPages = ceil($total / $perPage);
        
        $this->view('dispensing/index', [
            'dispensings' => $dispensings,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'total' => $total
        ]);
    }

    /**
     * Show dispensing form
     */
    public function create() {
        $drugs = $this->drugModel->getAll();
        $this->view('dispensing/create', ['drugs' => $drugs]);
    }

    /**
     * Store new dispensing
     */
    public function store() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /dispensing/create');
            exit;
        }

        $data = [
            'hn' => $_POST['hn'] ?? '',
            'vn' => $_POST['vn'] ?? '',
            'patient_name' => $_POST['patient_name'] ?? '',
            'dispense_date' => $_POST['dispense_date'] ?? date('Y-m-d H:i:s'),
            'user_id' => $_SESSION['user_id'] ?? 1,
            'clinical_notes' => $_POST['clinical_notes'] ?? null,
            'items' => []
        ];

        // Parse items
        if (isset($_POST['drug_id']) && is_array($_POST['drug_id'])) {
            foreach ($_POST['drug_id'] as $index => $drugId) {
                $quantity = $_POST['quantity'][$index] ?? 0;
                if ($drugId && $quantity > 0) {
                    $data['items'][] = [
                        'drug_id' => $drugId,
                        'quantity' => $quantity
                    ];
                }
            }
        }

        if (empty($data['items'])) {
            $_SESSION['error'] = 'กรุณาเพิ่มรายการยาอย่างน้อย 1 รายการ';
            header('Location: /dispensing/create');
            exit;
        }

        $dispenseId = $this->dispensingModel->create($data);

        if ($dispenseId) {
            $_SESSION['success'] = 'บันทึกการจ่ายยาสำเร็จ';
            header("Location: /dispensing/show/{$dispenseId}");
        } else {
            $_SESSION['error'] = 'เกิดข้อผิดพลาดในการบันทึกการจ่ายยา (อาจเป็นเพราะยาไม่พอ)';
            header('Location: /dispensing/create');
        }
        exit;
    }

    /**
     * Show dispensing details
     */
    public function show($id) {
        $dispensing = $this->dispensingModel->getById($id);
        
        if (!$dispensing) {
            $_SESSION['error'] = 'ไม่พบข้อมูลการจ่ายยา';
            header('Location: /dispensing');
            exit;
        }

        $items = $this->dispensingModel->getItems($id);
        
        $this->view('dispensing/show', [
            'dispensing' => $dispensing,
            'items' => $items
        ]);
    }

    /**
     * Search patient (AJAX)
     */
    public function searchPatient() {
        header('Content-Type: application/json');
        
        $keyword = $_GET['q'] ?? '';
        
        if (strlen($keyword) < 2) {
            echo json_encode([]);
            exit;
        }

        $patients = $this->dispensingModel->searchPatient($keyword);
        echo json_encode($patients);
        exit;
    }

    /**
     * Get patient history (AJAX)
     */
    public function patientHistory($hn) {
        header('Content-Type: application/json');
        
        $history = $this->dispensingModel->getPatientHistory($hn);
        echo json_encode($history);
        exit;
    }

    /**
     * Print dispensing receipt
     */
    public function print($id) {
        $dispensing = $this->dispensingModel->getById($id);
        
        if (!$dispensing) {
            $_SESSION['error'] = 'ไม่พบข้อมูลการจ่ายยา';
            header('Location: /dispensing');
            exit;
        }

        $items = $this->dispensingModel->getItems($id);
        
        $this->view('dispensing/print', [
            'dispensing' => $dispensing,
            'items' => $items
        ], false); // No layout
    }

    /**
     * Dispensing statistics
     */
    public function statistics() {
        $startDate = $_GET['start_date'] ?? date('Y-m-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-d');
        
        $stats = $this->dispensingModel->getStatistics($startDate, $endDate);
        $topDrugs = $this->dispensingModel->getTopDispensedDrugs(20, $startDate, $endDate);
        
        // Monthly trend (last 6 months)
        $monthlyTrend = $this->dispensingModel->getMonthlyTrend(6);
        
        // Daily activity (last 7 days)
        $dailyActivity = $this->dispensingModel->getDailyActivity(7);
        
        $this->view('dispensing/statistics', [
            'stats' => $stats,
            'topDrugs' => $topDrugs,
            'monthlyTrend' => $monthlyTrend,
            'dailyActivity' => $dailyActivity,
            'startDate' => $startDate,
            'endDate' => $endDate
        ]);
    }

    /**
     * Delete dispensing (admin only)
     */
    public function delete($id) {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['error'] = 'กรุณาเข้าสู่ระบบก่อน';
            header('Location: /login');
            exit;
        }

        // Check if user is admin
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            $_SESSION['error'] = 'คุณไม่มีสิทธิ์ลบข้อมูล';
            header('Location: /dispensing');
            exit;
        }

        $result = $this->dispensingModel->delete($id);

        if ($result) {
            $_SESSION['success'] = 'ลบข้อมูลการจ่ายยาสำเร็จ';
        } else {
            $_SESSION['error'] = 'เกิดข้อผิดพลาดในการลบข้อมูล';
        }

        header('Location: /dispensing');
        exit;
    }

    /**
     * Manual dispensing form (legacy)
     */
    public function manual() {
        // Redirect to create
        header('Location: /dispensing/create');
        exit;
    }
}
