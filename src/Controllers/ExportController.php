<?php
<<<<<<< HEAD

namespace App\Controllers;

use App\Models\DataCleansing;
use App\Models\AuditTrail;

/**
 * Export Controller
 * จัดการการส่งออกรายงาน PDF/Excel
 */
class ExportController
{
    private $cleansingModel;
    private $auditModel;

    public function __construct()
    {
        $this->cleansingModel = new DataCleansing();
        $this->auditModel = new AuditTrail();
    }

    /**
     * Export รายงานคุณภาพข้อมูล
     */
    public function qualityReport()
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $format = $_GET['format'] ?? 'csv';
        $qualitySummary = $this->cleansingModel->getDataQualitySummary();
        $qualityTrends = $this->cleansingModel->getQualityTrends(30);

        if ($format === 'csv') {
            $this->exportCSV($qualitySummary, $qualityTrends);
        } else {
            $this->exportHTML($qualitySummary, $qualityTrends);
        }
    }

    /**
     * Export เป็น CSV
     */
    private function exportCSV($summary, $trends)
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="quality_report_' . date('Ymd_His') . '.csv"');
        
        // BOM for UTF-8
        echo "\xEF\xBB\xBF";
        
        $output = fopen('php://output', 'w');
        
        // Summary section
        fputcsv($output, ['รายงานคุณภาพข้อมูล - Drugmuk']);
        fputcsv($output, ['วันที่', date('d/m/Y H:i:s')]);
        fputcsv($output, []);
        
        fputcsv($output, ['สรุปคุณภาพข้อมูล']);
        fputcsv($output, ['ตัวชี้วัด', 'ค่า', 'คำอธิบาย']);
        
        foreach ($summary as $item) {
            fputcsv($output, [
                $item['metric_name'] ?? '',
                $item['metric_value'] ?? 0,
                $item['metric_description'] ?? ''
            ]);
        }
        
        fputcsv($output, []);
        fputcsv($output, ['แนวโน้ม 30 วัน']);
        fputcsv($output, ['วันที่', 'จำนวนตรวจสอบ', 'แก้ไขแล้ว']);
        
        foreach ($trends as $trend) {
            fputcsv($output, [
                $trend['check_date'],
                $trend['total_checks'],
                $trend['resolved_count']
            ]);
        }
        
        fclose($output);
        exit;
    }

    /**
     * Export เป็น HTML (สำหรับพิมพ์)
     */
    private function exportHTML($summary, $trends)
    {
        header('Content-Type: text/html; charset=utf-8');
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>รายงานคุณภาพข้อมูล - Drugmuk</title>
            <style>
                body { font-family: 'Sarabun', sans-serif; padding: 40px; }
                h1 { color: #333; border-bottom: 2px solid #667eea; padding-bottom: 10px; }
                .meta { color: #666; margin-bottom: 30px; }
                table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
                th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
                th { background: #667eea; color: white; }
                tr:nth-child(even) { background: #f9fafb; }
                .score { font-size: 48px; font-weight: bold; color: #10b981; text-align: center; margin: 30px 0; }
                @media print {
                    body { padding: 20px; }
                    .no-print { display: none; }
                }
            </style>
        </head>
        <body>
            <h1>📊 รายงานคุณภาพข้อมูล</h1>
            <div class="meta">
                <p>วันที่พิมพ์: <?php echo date('d/m/Y H:i:s'); ?></p>
                <p>ระบบ: Drugmuk - Pharmaceutical Inventory Management</p>
            </div>

            <?php 
            $score = 0;
            foreach ($summary as $item) {
                if (isset($item['quality_score'])) {
                    $score = $item['quality_score'];
                    break;
                }
            }
            ?>
            <div class="score"><?php echo number_format($score, 1); ?>%</div>
            <p style="text-align: center; color: #666;">คะแนนคุณภาพโดยรวม</p>

            <h2>สรุปคุณภาพข้อมูล</h2>
            <table>
                <tr>
                    <th>ตัวชี้วัด</th>
                    <th>ค่า</th>
                    <th>คำอธิบาย</th>
                </tr>
                <?php foreach ($summary as $item): ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['metric_name'] ?? ''); ?></td>
                    <td><?php echo number_format($item['metric_value'] ?? 0); ?></td>
                    <td><?php echo htmlspecialchars($item['metric_description'] ?? ''); ?></td>
                </tr>
                <?php endforeach; ?>
            </table>

            <h2>แนวโน้ม 30 วัน</h2>
            <table>
                <tr>
                    <th>วันที่</th>
                    <th>จำนวนตรวจสอบ</th>
                    <th>แก้ไขแล้ว</th>
                </tr>
                <?php foreach ($trends as $trend): ?>
                <tr>
                    <td><?php echo date('d/m/Y', strtotime($trend['check_date'])); ?></td>
                    <td><?php echo number_format($trend['total_checks']); ?></td>
                    <td><?php echo number_format($trend['resolved_count']); ?></td>
                </tr>
                <?php endforeach; ?>
            </table>

            <div class="no-print" style="text-align: center; margin-top: 30px;">
                <button onclick="window.print()" style="padding: 12px 30px; background: #667eea; color: white; border: none; border-radius: 8px; font-size: 16px; cursor: pointer;">
                    🖨️ พิมพ์รายงาน
                </button>
            </div>
        </body>
        </html>
        <?php
        exit;
    }

    /**
     * Export Audit Trail
     */
    public function auditTrail()
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $filters = [
            'table_name' => $_GET['table'] ?? null,
            'action' => $_GET['action'] ?? null,
            'date_from' => $_GET['from'] ?? null,
            'date_to' => $_GET['to'] ?? null
        ];

        $records = $this->auditModel->getAll($filters, 1000, 0);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="audit_trail_' . date('Ymd_His') . '.csv"');
        
        echo "\xEF\xBB\xBF";
        $output = fopen('php://output', 'w');
        
        fputcsv($output, ['Audit Trail - Drugmuk']);
        fputcsv($output, ['Exported:', date('d/m/Y H:i:s')]);
        fputcsv($output, []);
        
        fputcsv($output, ['ID', 'วันเวลา', 'ตาราง', 'Record ID', 'การกระทำ', 'ผู้ดำเนินการ', 'ฟิลด์ที่เปลี่ยน', 'IP Address']);
        
        foreach ($records as $rec) {
            fputcsv($output, [
                $rec['id'],
                $rec['created_at'],
                $rec['table_name'],
                $rec['record_id'],
                $rec['action'],
                $rec['user_name'] ?? 'System',
                $rec['changed_fields'] ?? '',
                $rec['ip_address'] ?? ''
            ]);
        }
        
        fclose($output);
        exit;
    }

    /**
     * Export รายการยา
     */
    public function drugs()
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $db = \App\Core\Database::getInstance()->getConnection();
        $stmt = $db->query("
            SELECT d.id, d.code, d.name, d.generic_name, d.unit, d.price, d.min_stock,
                   COALESCE(SUM(i.quantity), 0) as current_stock
            FROM drugs d
            LEFT JOIN inventory i ON d.id = i.drug_id
            GROUP BY d.id
            ORDER BY d.name
        ");
        $drugs = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="drugs_' . date('Ymd_His') . '.csv"');
        
        echo "\xEF\xBB\xBF";
        $output = fopen('php://output', 'w');
        
        fputcsv($output, ['รายการยา - Drugmuk', date('d/m/Y H:i:s')]);
        fputcsv($output, []);
        fputcsv($output, ['รหัส', 'ชื่อยา', 'ชื่อสามัญ', 'หน่วย', 'ราคา', 'สต็อกขั้นต่ำ', 'สต็อกปัจจุบัน']);
        
        foreach ($drugs as $drug) {
            fputcsv($output, [
                $drug['code'],
                $drug['name'],
                $drug['generic_name'],
                $drug['unit'],
                $drug['price'],
                $drug['min_stock'],
                $drug['current_stock']
            ]);
        }
        
        fclose($output);
        exit;
=======
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
>>>>>>> ec38baebc54407631f0440219d7ef94546b3ea7a
    }
}
