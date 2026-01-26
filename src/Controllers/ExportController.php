<?php
/**
 * Export Controller
 * จัดการการ Export รายงานเป็น Excel/CSV/PDF
 * 
 * @package Drugmuk
 * @version 3.4.0
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Services\ExcelExportService;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Dispensing;
use App\Models\PurchasingPlan;

class ExportController extends Controller
{
    private Inventory $inventoryModel;
    private Order $orderModel;
    private Dispensing $dispensingModel;
    private PurchasingPlan $purchasingPlanModel;
    
    public function __construct()
    {
        $this->inventoryModel = new Inventory();
        $this->orderModel = new Order();
        $this->dispensingModel = new Dispensing();
        $this->purchasingPlanModel = new PurchasingPlan();
    }
    
    /**
     * หน้าเลือก Export
     */
    public function index()
    {
        $this->view('export/index');
    }
    
    /**
     * Export รายงานสต็อก
     */
    public function stock()
    {
        $stockData = $this->inventoryModel->getAllWithDrugs();
        ExcelExportService::exportStockReport($stockData);
    }
    
    /**
     * Export รายงานยาใกล้หมดอายุ
     */
    public function expiring()
    {
        $days = (int)($_GET['days'] ?? 90);
        $expiringData = $this->inventoryModel->getExpiringItems($days);
        ExcelExportService::exportExpiringReport($expiringData);
    }
    
    /**
     * Export รายงานยาใกล้หมดสต็อก
     */
    public function lowStock()
    {
        $lowStockData = $this->inventoryModel->getLowStockItems();
        ExcelExportService::exportLowStockReport($lowStockData);
    }
    
    /**
     * Export รายงานการจ่ายยา
     */
    public function dispensing()
    {
        $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
        $dateTo = $_GET['date_to'] ?? date('Y-m-d');
        
        $dispensingData = $this->dispensingModel->getByDateRange($dateFrom, $dateTo);
        ExcelExportService::exportDispensingReport($dispensingData, $dateFrom, $dateTo);
    }
    
    /**
     * Export รายงาน ABC/VEN Analysis
     */
    public function abcVen()
    {
        $fiscalYear = $_GET['fiscal_year'] ?? date('Y');
        $analysisData = $this->purchasingPlanModel->getABCVENAnalysis($fiscalYear);
        ExcelExportService::exportABCVENReport($analysisData);
    }
    
    /**
     * Export รายงานสั่งซื้อ
     */
    public function orders()
    {
        $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
        $dateTo = $_GET['date_to'] ?? date('Y-m-d');
        $status = $_GET['status'] ?? '';
        
        $orderData = $this->orderModel->getByDateRange($dateFrom, $dateTo, $status);
        ExcelExportService::exportOrderReport($orderData);
    }
    
    /**
     * Export Custom Report
     */
    public function custom()
    {
        $reportType = $_POST['report_type'] ?? '';
        $dateFrom = $_POST['date_from'] ?? '';
        $dateTo = $_POST['date_to'] ?? '';
        $format = $_POST['format'] ?? 'excel';
        $columns = $_POST['columns'] ?? [];
        
        if (empty($reportType)) {
            $_SESSION['error'] = 'กรุณาเลือกประเภทรายงาน';
            header('Location: /export');
            exit;
        }
        
        // Get data based on report type
        $data = $this->getReportData($reportType, $dateFrom, $dateTo);
        
        // Filter columns if specified
        if (!empty($columns)) {
            $data = $this->filterColumns($data, $columns);
        }
        
        // Export based on format
        $service = new ExcelExportService();
        $service->setTitle($this->getReportTitle($reportType))
                ->setFilename($reportType . '_' . date('Y-m-d') . ($format === 'csv' ? '.csv' : '.xls'))
                ->setHeaders($this->getReportHeaders($reportType, $columns))
                ->setData($data);
        
        if ($format === 'csv') {
            $service->exportCSV();
        } else {
            $service->exportExcelHTML();
        }
    }
    
    /**
     * Export รายงานแบบ PDF (ใช้ HTML to PDF)
     */
    public function pdf()
    {
        $reportType = $_GET['type'] ?? 'stock';
        
        $data = $this->getReportData($reportType, '', '');
        $title = $this->getReportTitle($reportType);
        
        $html = $this->generatePDFHTML($title, $data, $reportType);
        
        // Output as HTML with print styles (user can print to PDF)
        header('Content-Type: text/html; charset=utf-8');
        echo $html;
    }
    
    // ===== Private Methods =====
    
    private function getReportData(string $type, string $dateFrom, string $dateTo): array
    {
        switch ($type) {
            case 'stock':
                return $this->inventoryModel->getAllWithDrugs();
            case 'expiring':
                return $this->inventoryModel->getExpiringItems(90);
            case 'low_stock':
                return $this->inventoryModel->getLowStockItems();
            case 'dispensing':
                return $this->dispensingModel->getByDateRange($dateFrom ?: date('Y-m-01'), $dateTo ?: date('Y-m-d'));
            case 'orders':
                return $this->orderModel->getByDateRange($dateFrom ?: date('Y-m-01'), $dateTo ?: date('Y-m-d'));
            default:
                return [];
        }
    }
    
    private function getReportTitle(string $type): string
    {
        $titles = [
            'stock' => 'รายงานสต็อกยา',
            'expiring' => 'รายงานยาใกล้หมดอายุ',
            'low_stock' => 'รายงานยาใกล้หมดสต็อก',
            'dispensing' => 'รายงานการจ่ายยา',
            'orders' => 'รายงานใบสั่งซื้อ',
            'abc_ven' => 'รายงาน ABC/VEN Analysis'
        ];
        return $titles[$type] ?? 'รายงาน';
    }
    
    private function getReportHeaders(string $type, array $customColumns = []): array
    {
        $headers = [
            'stock' => ['ลำดับ', 'รหัสยา', 'ชื่อยา', 'หน่วย', 'Lot No.', 'วันหมดอายุ', 'จำนวน', 'ราคา', 'มูลค่า'],
            'expiring' => ['ลำดับ', 'รหัสยา', 'ชื่อยา', 'Lot No.', 'วันหมดอายุ', 'เหลือ (วัน)', 'จำนวน', 'มูลค่า'],
            'low_stock' => ['ลำดับ', 'รหัสยา', 'ชื่อยา', 'คงเหลือ', 'Min', 'Max', 'ต้องสั่งซื้อ'],
            'dispensing' => ['ลำดับ', 'วันที่', 'HN', 'ชื่อผู้ป่วย', 'รหัสยา', 'ชื่อยา', 'จำนวน', 'หน่วย', 'ผู้จ่าย'],
            'orders' => ['ลำดับ', 'เลขที่', 'วันที่', 'ผู้ขาย', 'มูลค่า', 'สถานะ', 'ผู้สั่งซื้อ']
        ];
        
        if (!empty($customColumns)) {
            return array_merge(['ลำดับ'], $customColumns);
        }
        
        return $headers[$type] ?? ['ลำดับ', 'ข้อมูล'];
    }
    
    private function filterColumns(array $data, array $columns): array
    {
        return array_map(function($row) use ($columns) {
            $filtered = [];
            foreach ($columns as $col) {
                $filtered[$col] = $row[$col] ?? '';
            }
            return $filtered;
        }, $data);
    }
    
    private function generatePDFHTML(string $title, array $data, string $type): string
    {
        $headers = $this->getReportHeaders($type);
        
        $html = '<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>' . htmlspecialchars($title) . ' - Drugmuk</title>
    <style>
        @media print {
            body { -webkit-print-color-adjust: exact; }
        }
        body {
            font-family: "Sarabun", "TH Sarabun New", sans-serif;
            font-size: 14px;
            margin: 20px;
        }
        h1 {
            color: #4a90d9;
            text-align: center;
            margin-bottom: 5px;
        }
        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #4a90d9;
            color: white;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .footer {
            margin-top: 20px;
            text-align: right;
            color: #999;
            font-size: 12px;
        }
        @page {
            size: A4 landscape;
            margin: 10mm;
        }
    </style>
</head>
<body>
    <h1>🏥 ' . htmlspecialchars($title) . '</h1>
    <p class="subtitle">Drugmuk - ระบบบริหารคลังเวชภัณฑ์ยา | วันที่พิมพ์: ' . date('d/m/Y H:i:s') . '</p>
    
    <table>
        <thead>
            <tr>';
        
        foreach ($headers as $header) {
            $html .= '<th>' . htmlspecialchars($header) . '</th>';
        }
        
        $html .= '</tr>
        </thead>
        <tbody>';
        
        $num = 1;
        foreach ($data as $row) {
            $html .= '<tr><td>' . $num++ . '</td>';
            foreach ($row as $key => $value) {
                if ($key === 'id') continue;
                $html .= '<td>' . htmlspecialchars((string)$value) . '</td>';
            }
            $html .= '</tr>';
        }
        
        $html .= '</tbody>
    </table>
    
    <p class="footer">
        รวมทั้งหมด: ' . count($data) . ' รายการ | 
        Powered by Drugmuk v3.4.0
    </p>
    
    <script>
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html>';
        
        return $html;
    }
}
