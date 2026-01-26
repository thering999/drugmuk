<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\PurchasingPlan;
use App\Models\Drug;
use App\Models\FiscalYear;

class PurchasingPlanController extends Controller {
    
    private $planModel;
    private $drugModel;
    private $fyModel;

    public function __construct() {
        $this->planModel = new PurchasingPlan();
        $this->drugModel = new Drug();
        $this->fyModel = new FiscalYear();
    }

    /**
     * List all purchasing plans
     */
    public function index() {
        $plans = $this->planModel->getAllWithDetails();
        $this->view('purchasing/index', ['plans' => $plans]);
    }

    /**
     * Calculate plan from 3-year history
     */
    public function calculate() {
        $fiscalYears = $this->fyModel->getAll();
        $this->view('purchasing/calculate', ['fiscal_years' => $fiscalYears]);
    }

    /**
     * Process calculation
     */
    public function processCalculation() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /purchasing/calculate');
            exit;
        }

        $fiscalYearId = $_POST['fiscal_year_id'] ?? null;
        $increasePercent = $_POST['increase_percent'] ?? 0;

        if (!$fiscalYearId) {
            $_SESSION['error'] = 'กรุณาเลือกปีงบประมาณ';
            header('Location: /purchasing/calculate');
            exit;
        }

        // Step 1: Calculate from 3-year history
        $plans = $this->planModel->calculateFrom3YearHistory($fiscalYearId, $increasePercent);

        // Step 2: Perform ABC Analysis
        $plans = $this->planModel->performABCAnalysis($plans);

        // Step 3: Assign VEN Classification
        $plans = $this->planModel->assignVENClass($plans);

        // Step 4: Save to database
        $result = $this->planModel->savePlans($fiscalYearId, $plans);

        if ($result) {
            $_SESSION['success'] = 'คำนวณแผนซื้อสำเร็จ พบ ' . count($plans) . ' รายการ';
            header('Location: /purchasing');
        } else {
            $_SESSION['error'] = 'เกิดข้อผิดพลาดในการบันทึกแผนซื้อ';
            header('Location: /purchasing/calculate');
        }
        exit;
    }

    /**
     * View ABC/VEN Analysis
     */
    public function analysis() {
        $fiscalYearId = $_GET['fiscal_year_id'] ?? null;
        $plans = [];
        
        if ($fiscalYearId) {
            // Get plans for selected fiscal year
            $sql = "SELECT pp.*, d.code as drug_code, d.name as drug_name, d.category
                    FROM purchasing_plans pp
                    JOIN drugs d ON pp.drug_id = d.id
                    WHERE pp.fiscal_year_id = ?
                    ORDER BY pp.abc_class ASC, pp.budget_plan DESC";
            $db = \App\Core\Database::getInstance()->getConnection();
            $stmt = $db->prepare($sql);
            $stmt->execute([$fiscalYearId]);
            $plans = $stmt->fetchAll();
        }

        $fiscalYears = $this->fyModel->getAll();
        
        $this->view('purchasing/analysis', [
            'plans' => $plans,
            'fiscal_years' => $fiscalYears,
            'selected_fy' => $fiscalYearId
        ]);
    }

    /**
     * Export to Excel (CSV)
     */
    public function export() {
        $fiscalYearId = $_GET['fiscal_year_id'] ?? null;
        
        if (!$fiscalYearId) {
            $_SESSION['error'] = 'กรุณาเลือกปีงบประมาณ';
            header('Location: /purchasing');
            exit;
        }

        // Get plans
        $sql = "SELECT pp.*, d.code as drug_code, d.name as drug_name, d.category, d.price as unit_price
                FROM purchasing_plans pp
                JOIN drugs d ON pp.drug_id = d.id
                WHERE pp.fiscal_year_id = ?
                ORDER BY d.name";
        $db = \App\Core\Database::getInstance()->getConnection();
        $stmt = $db->prepare($sql);
        $stmt->execute([$fiscalYearId]);
        $dbPlans = $stmt->fetchAll();

        // Transform to export format
        $plans = [];
        foreach ($dbPlans as $plan) {
            $plans[] = [
                'drug_code' => $plan['drug_code'],
                'drug_name' => $plan['drug_name'],
                'year1_qty' => 0, // Historical data not stored
                'year2_qty' => 0,
                'year3_qty' => 0,
                'avg_yearly' => $plan['quantity_plan'],
                'planned_qty' => $plan['quantity_plan'],
                'min_stock' => $plan['min_stock'],
                'unit_price' => $plan['unit_price'],
                'budget' => $plan['budget_plan'],
                'abc_class' => $plan['abc_class'],
                'ven_class' => $plan['ven_class'],
                'category' => $plan['category']
            ];
        }

        $this->planModel->exportToCSV($plans, "purchasing_plan_fy{$fiscalYearId}.csv");
    }

    /**
     * Import from Excel (CSV)
     */
    public function import() {
        $fiscalYears = $this->fyModel->getAll();
        $this->view('purchasing/import', ['fiscal_years' => $fiscalYears]);
    }

    /**
     * Process import
     */
    public function processImport() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /purchasing/import');
            exit;
        }

        $fiscalYearId = $_POST['fiscal_year_id'] ?? null;
        
        if (!$fiscalYearId) {
            $_SESSION['error'] = 'กรุณาเลือกปีงบประมาณ';
            header('Location: /purchasing/import');
            exit;
        }

        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['error'] = 'กรุณาเลือกไฟล์ CSV';
            header('Location: /purchasing/import');
            exit;
        }

        $tmpFile = $_FILES['csv_file']['tmp_name'];
        $result = $this->planModel->importFromCSV($tmpFile, $fiscalYearId);

        if ($result['success']) {
            $_SESSION['success'] = "นำเข้าข้อมูลสำเร็จ {$result['imported']} รายการ";
            if (!empty($result['errors'])) {
                $_SESSION['warning'] = implode(', ', $result['errors']);
            }
        } else {
            $_SESSION['error'] = $result['message'] ?? 'เกิดข้อผิดพลาดในการนำเข้าข้อมูล';
        }

        header('Location: /purchasing');
        exit;
    }

    /**
     * Quarterly adjustment
     */
    public function adjust() {
        $planId = $_GET['plan_id'] ?? null;
        
        if (!$planId) {
            $_SESSION['error'] = 'ไม่พบแผนซื้อ';
            header('Location: /purchasing');
            exit;
        }

        $adjustments = $this->planModel->getQuarterlyAdjustments($planId);
        
        $this->view('purchasing/adjust', [
            'plan_id' => $planId,
            'adjustments' => $adjustments
        ]);
    }

    /**
     * Save quarterly adjustment
     */
    public function saveAdjustment() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /purchasing');
            exit;
        }

        $data = [
            'plan_id' => $_POST['plan_id'],
            'quarter' => $_POST['quarter'],
            'quantity' => $_POST['adjusted_quantity'],
            'budget' => $_POST['adjusted_budget'],
            'reason' => $_POST['adjustment_reason'],
            'user_id' => $_SESSION['user_id'] ?? 1
        ];

        $result = $this->planModel->saveQuarterlyAdjustment($data);

        if ($result) {
            $_SESSION['success'] = 'บันทึกการปรับแผนสำเร็จ';
        } else {
            $_SESSION['error'] = 'เกิดข้อผิดพลาดในการบันทึก';
        }

        header('Location: /purchasing/adjust?plan_id=' . $data['plan_id']);
        exit;
    }
}
