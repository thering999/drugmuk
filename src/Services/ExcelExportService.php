<?php
/**
 * Excel Export Service
 * สร้างไฟล์ Excel สำหรับรายงาน
 * 
 * @package Drugmuk
 * @version 3.4.0
 */

namespace App\Services;

class ExcelExportService
{
    private array $headers = [];
    private array $data = [];
    private string $title = '';
    private string $filename = '';
    
    /**
     * ตั้งค่าชื่อไฟล์
     */
    public function setFilename(string $filename): self
    {
        $this->filename = $filename;
        return $this;
    }
    
    /**
     * ตั้งค่าชื่อรายงาน
     */
    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }
    
    /**
     * ตั้งค่า headers
     */
    public function setHeaders(array $headers): self
    {
        $this->headers = $headers;
        return $this;
    }
    
    /**
     * ตั้งค่าข้อมูล
     */
    public function setData(array $data): self
    {
        $this->data = $data;
        return $this;
    }
    
    /**
     * Export เป็น CSV (ใช้งานได้ทันทีไม่ต้องติดตั้ง library เพิ่ม)
     */
    public function exportCSV(): void
    {
        $filename = $this->filename ?: 'export_' . date('Y-m-d_His') . '.csv';
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Add BOM for Excel to recognize UTF-8
        echo "\xEF\xBB\xBF";
        
        $output = fopen('php://output', 'w');
        
        // Title row
        if ($this->title) {
            fputcsv($output, [$this->title]);
            fputcsv($output, ['Generated: ' . date('d/m/Y H:i:s')]);
            fputcsv($output, []); // Empty row
        }
        
        // Headers
        if (!empty($this->headers)) {
            fputcsv($output, $this->headers);
        }
        
        // Data rows
        foreach ($this->data as $row) {
            fputcsv($output, array_values($row));
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Export เป็น HTML Table (สามารถเปิดใน Excel ได้)
     */
    public function exportExcelHTML(): void
    {
        $filename = $this->filename ?: 'export_' . date('Y-m-d_His') . '.xls';
        
        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel">';
        echo '<head><meta charset="UTF-8"></head>';
        echo '<body>';
        
        // Title
        if ($this->title) {
            echo '<h2>' . htmlspecialchars($this->title) . '</h2>';
            echo '<p>Generated: ' . date('d/m/Y H:i:s') . '</p>';
        }
        
        echo '<table border="1" cellpadding="5" cellspacing="0">';
        
        // Headers
        if (!empty($this->headers)) {
            echo '<tr style="background-color: #4a90d9; color: white; font-weight: bold;">';
            foreach ($this->headers as $header) {
                echo '<th>' . htmlspecialchars($header) . '</th>';
            }
            echo '</tr>';
        }
        
        // Data rows
        $rowNum = 0;
        foreach ($this->data as $row) {
            $bgColor = ($rowNum % 2 === 0) ? '#ffffff' : '#f5f5f5';
            echo '<tr style="background-color: ' . $bgColor . ';">';
            foreach ($row as $cell) {
                echo '<td>' . htmlspecialchars((string)$cell) . '</td>';
            }
            echo '</tr>';
            $rowNum++;
        }
        
        echo '</table>';
        echo '</body></html>';
        exit;
    }
    
    /**
     * Export รายงานสต็อก
     */
    public static function exportStockReport(array $stockData): void
    {
        $service = new self();
        $service->setTitle('รายงานสต็อกยา - Drugmuk')
                ->setFilename('stock_report_' . date('Y-m-d') . '.xls')
                ->setHeaders([
                    'ลำดับ', 'รหัสยา', 'ชื่อยา', 'หน่วย', 'Lot No.', 
                    'วันหมดอายุ', 'จำนวนคงเหลือ', 'ราคาต่อหน่วย', 'มูลค่า'
                ]);
        
        $data = [];
        $num = 1;
        foreach ($stockData as $item) {
            $value = ($item['quantity'] ?? 0) * ($item['cost_price'] ?? $item['price'] ?? 0);
            $data[] = [
                $num++,
                $item['drug_code'] ?? $item['code'] ?? '',
                $item['drug_name'] ?? $item['name'] ?? '',
                $item['unit'] ?? '',
                $item['lot_no'] ?? '-',
                isset($item['expire_date']) ? date('d/m/Y', strtotime($item['expire_date'])) : '-',
                number_format($item['quantity'] ?? 0),
                number_format($item['cost_price'] ?? $item['price'] ?? 0, 2),
                number_format($value, 2)
            ];
        }
        
        $service->setData($data)->exportExcelHTML();
    }
    
    /**
     * Export รายงานยาใกล้หมดอายุ
     */
    public static function exportExpiringReport(array $expiringData): void
    {
        $service = new self();
        $service->setTitle('รายงานยาใกล้หมดอายุ - Drugmuk')
                ->setFilename('expiring_drugs_' . date('Y-m-d') . '.xls')
                ->setHeaders([
                    'ลำดับ', 'รหัสยา', 'ชื่อยา', 'Lot No.', 
                    'วันหมดอายุ', 'เหลืออีก (วัน)', 'จำนวนคงเหลือ', 'มูลค่า'
                ]);
        
        $data = [];
        $num = 1;
        foreach ($expiringData as $item) {
            $daysLeft = (strtotime($item['expire_date']) - time()) / 86400;
            $value = ($item['quantity'] ?? 0) * ($item['cost_price'] ?? 0);
            
            $data[] = [
                $num++,
                $item['code'] ?? '',
                $item['drug_name'] ?? $item['name'] ?? '',
                $item['lot_no'] ?? '-',
                date('d/m/Y', strtotime($item['expire_date'])),
                max(0, (int)$daysLeft),
                number_format($item['quantity'] ?? 0),
                number_format($value, 2)
            ];
        }
        
        $service->setData($data)->exportExcelHTML();
    }
    
    /**
     * Export รายงานยาใกล้หมดสต็อก
     */
    public static function exportLowStockReport(array $lowStockData): void
    {
        $service = new self();
        $service->setTitle('รายงานยาใกล้หมดสต็อก - Drugmuk')
                ->setFilename('low_stock_' . date('Y-m-d') . '.xls')
                ->setHeaders([
                    'ลำดับ', 'รหัสยา', 'ชื่อยา', 'จำนวนคงเหลือ', 
                    'จุดสั่งซื้อ (Min)', 'จุดสูงสุด (Max)', 'ต้องสั่งซื้อ'
                ]);
        
        $data = [];
        $num = 1;
        foreach ($lowStockData as $item) {
            $needToOrder = max(0, ($item['max_stock'] ?? 0) - ($item['current_stock'] ?? 0));
            
            $data[] = [
                $num++,
                $item['code'] ?? '',
                $item['drug_name'] ?? $item['name'] ?? '',
                number_format($item['current_stock'] ?? 0),
                number_format($item['min_stock'] ?? 0),
                number_format($item['max_stock'] ?? 0),
                number_format($needToOrder)
            ];
        }
        
        $service->setData($data)->exportExcelHTML();
    }
    
    /**
     * Export รายงานการจ่ายยา
     */
    public static function exportDispensingReport(array $dispensingData, string $dateFrom = '', string $dateTo = ''): void
    {
        $service = new self();
        
        $period = '';
        if ($dateFrom && $dateTo) {
            $period = ' (' . date('d/m/Y', strtotime($dateFrom)) . ' - ' . date('d/m/Y', strtotime($dateTo)) . ')';
        }
        
        $service->setTitle('รายงานการจ่ายยา' . $period . ' - Drugmuk')
                ->setFilename('dispensing_report_' . date('Y-m-d') . '.xls')
                ->setHeaders([
                    'ลำดับ', 'วันที่', 'HN', 'ชื่อผู้ป่วย', 'รหัสยา',
                    'ชื่อยา', 'จำนวน', 'หน่วย', 'Lot No.', 'ผู้จ่าย'
                ]);
        
        $data = [];
        $num = 1;
        foreach ($dispensingData as $item) {
            $data[] = [
                $num++,
                isset($item['dispense_date']) ? date('d/m/Y H:i', strtotime($item['dispense_date'])) : '',
                $item['patient_hn'] ?? $item['hn'] ?? '',
                $item['patient_name'] ?? '',
                $item['drug_code'] ?? $item['code'] ?? '',
                $item['drug_name'] ?? $item['name'] ?? '',
                number_format($item['quantity'] ?? 0),
                $item['unit'] ?? '',
                $item['lot_no'] ?? '-',
                $item['dispensed_by'] ?? $item['user_name'] ?? ''
            ];
        }
        
        $service->setData($data)->exportExcelHTML();
    }
    
    /**
     * Export รายงาน ABC/VEN Analysis
     */
    public static function exportABCVENReport(array $analysisData): void
    {
        $service = new self();
        $service->setTitle('รายงาน ABC/VEN Analysis - Drugmuk')
                ->setFilename('abc_ven_analysis_' . date('Y-m-d') . '.xls')
                ->setHeaders([
                    'ลำดับ', 'รหัสยา', 'ชื่อยา', 'ABC Class', 'VEN Class',
                    'มูลค่าการใช้', 'สัดส่วน (%)', 'สัดส่วนสะสม (%)'
                ]);
        
        $data = [];
        $num = 1;
        foreach ($analysisData as $item) {
            $data[] = [
                $num++,
                $item['code'] ?? '',
                $item['drug_name'] ?? $item['name'] ?? '',
                $item['abc_class'] ?? '-',
                $item['ven_class'] ?? '-',
                number_format($item['total_value'] ?? 0, 2),
                number_format($item['percentage'] ?? 0, 2) . '%',
                number_format($item['cumulative_percentage'] ?? 0, 2) . '%'
            ];
        }
        
        $service->setData($data)->exportExcelHTML();
    }
    
    /**
     * Export รายงานสั่งซื้อ
     */
    public static function exportOrderReport(array $orderData): void
    {
        $service = new self();
        $service->setTitle('รายงานใบสั่งซื้อ - Drugmuk')
                ->setFilename('order_report_' . date('Y-m-d') . '.xls')
                ->setHeaders([
                    'ลำดับ', 'เลขที่ใบสั่งซื้อ', 'วันที่', 'ผู้ขาย',
                    'มูลค่ารวม', 'สถานะ', 'ผู้สั่งซื้อ'
                ]);
        
        $statusMap = [
            'pending' => 'รอดำเนินการ',
            'approved' => 'อนุมัติแล้ว',
            'partial' => 'รับบางส่วน',
            'completed' => 'รับครบแล้ว',
            'cancelled' => 'ยกเลิก'
        ];
        
        $data = [];
        $num = 1;
        foreach ($orderData as $item) {
            $status = $statusMap[$item['status'] ?? ''] ?? ($item['status'] ?? '-');
            
            $data[] = [
                $num++,
                $item['order_no'] ?? '',
                isset($item['order_date']) ? date('d/m/Y', strtotime($item['order_date'])) : '',
                $item['supplier_name'] ?? '',
                number_format($item['total_amount'] ?? 0, 2),
                $status,
                $item['created_by_name'] ?? $item['user_name'] ?? ''
            ];
        }
        
        $service->setData($data)->exportExcelHTML();
    }
}
