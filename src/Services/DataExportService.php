<?php
/**
 * Data Export Service
 * Export data to various formats (Excel, CSV, PDF)
 */

namespace App\Services;

class DataExportService
{
    /**
     * Export data to CSV
     */
    public function exportToCSV(array $data, array $headers = []): string
    {
        if (empty($data)) {
            return '';
        }
        
        // Use headers from data if not provided
        if (empty($headers)) {
            $headers = array_keys($data[0]);
        }
        
        $output = fopen('php://temp', 'r+');
        
        // Write headers
        fputcsv($output, $headers);
        
        // Write data
        foreach ($data as $row) {
            $rowData = [];
            foreach ($headers as $header) {
                $rowData[] = $row[$header] ?? '';
            }
            fputcsv($output, $rowData);
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }
    
    /**
     * Export data to Excel (using simple HTML table)
     */
    public function exportToExcel(array $data, array $headers = [], string $title = 'Export'): string
    {
        if (empty($data)) {
            return '';
        }
        
        if (empty($headers)) {
            $headers = array_keys($data[0]);
        }
        
        $html = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $html .= '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">' . "\n";
        $html .= '<head><meta charset="UTF-8"><title>' . htmlspecialchars($title) . '</title></head>' . "\n";
        $html .= '<body>' . "\n";
        $html .= '<table border="1">' . "\n";
        
        // Headers
        $html .= '<thead><tr>';
        foreach ($headers as $header) {
            $html .= '<th>' . htmlspecialchars($header) . '</th>';
        }
        $html .= '</tr></thead>' . "\n";
        
        // Data
        $html .= '<tbody>' . "\n";
        foreach ($data as $row) {
            $html .= '<tr>';
            foreach ($headers as $header) {
                $value = $row[$header] ?? '';
                $html .= '<td>' . htmlspecialchars($value) . '</td>';
            }
            $html .= '</tr>' . "\n";
        }
        $html .= '</tbody>' . "\n";
        
        $html .= '</table>' . "\n";
        $html .= '</body></html>';
        
        return $html;
    }
    
    /**
     * Export data to JSON
     */
    public function exportToJSON(array $data, bool $pretty = true): string
    {
        $options = JSON_UNESCAPED_UNICODE;
        
        if ($pretty) {
            $options |= JSON_PRETTY_PRINT;
        }
        
        return json_encode($data, $options);
    }
    
    /**
     * Export data to XML
     */
    public function exportToXML(array $data, string $rootElement = 'data', string $rowElement = 'row'): string
    {
        $xml = new \SimpleXMLElement("<?xml version=\"1.0\" encoding=\"UTF-8\"?><{$rootElement}></{$rootElement}>");
        
        foreach ($data as $row) {
            $rowNode = $xml->addChild($rowElement);
            foreach ($row as $key => $value) {
                $rowNode->addChild($key, htmlspecialchars($value));
            }
        }
        
        return $xml->asXML();
    }
    
    /**
     * Download file
     */
    public function download(string $content, string $filename, string $mimeType): void
    {
        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($content));
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        
        echo $content;
        exit;
    }
    
    /**
     * Export and download CSV
     */
    public function downloadCSV(array $data, string $filename = 'export.csv', array $headers = []): void
    {
        $csv = $this->exportToCSV($data, $headers);
        $this->download($csv, $filename, 'text/csv; charset=UTF-8');
    }
    
    /**
     * Export and download Excel
     */
    public function downloadExcel(array $data, string $filename = 'export.xls', array $headers = [], string $title = 'Export'): void
    {
        $excel = $this->exportToExcel($data, $headers, $title);
        $this->download($excel, $filename, 'application/vnd.ms-excel; charset=UTF-8');
    }
    
    /**
     * Export and download JSON
     */
    public function downloadJSON(array $data, string $filename = 'export.json'): void
    {
        $json = $this->exportToJSON($data);
        $this->download($json, $filename, 'application/json; charset=UTF-8');
    }
    
    /**
     * Export and download XML
     */
    public function downloadXML(array $data, string $filename = 'export.xml', string $rootElement = 'data'): void
    {
        $xml = $this->exportToXML($data, $rootElement);
        $this->download($xml, $filename, 'application/xml; charset=UTF-8');
    }
}

// Usage example:
// $exporter = new DataExportService();
// $exporter->downloadCSV($data, 'drugs.csv');
// $exporter->downloadExcel($data, 'inventory.xls', ['Code', 'Name', 'Quantity']);
