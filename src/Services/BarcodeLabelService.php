<?php
/**
 * Barcode Label Service
 * 
 * Handles barcode and QR code generation for drug labels
 * 
 * @package Drugmuk
 * @subpackage Services
 * @version 1.0
 * @since Phase 6
 */

namespace App\Services;

use PDO;

class BarcodeLabelService
{
    private $db;
    
    public function __construct()
    {
        $this->db = \App\Core\Database::getInstance()->getConnection();
    }
    
    /**
     * Generate barcode label for a drug
     * 
     * @param int $drugId Drug ID
     * @param string $lotNo Lot number
     * @param int $quantity Quantity
     * @return array Label data
     */
    public function generateLabel($drugId, $lotNo = null, $quantity = 1)
    {
        // Get drug information
        $stmt = $this->db->prepare("
            SELECT d.*, c.name as category_name
            FROM drugs d
            LEFT JOIN drug_categories c ON d.category_id = c.id
            WHERE d.id = ?
        ");
        $stmt->execute([$drugId]);
        $drug = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$drug) {
            throw new \Exception("Drug not found");
        }
        
        // Get inventory info if lot number provided
        $inventory = null;
        if ($lotNo) {
            $stmt = $this->db->prepare("
                SELECT * FROM inventory
                WHERE drug_id = ? AND lot_no = ?
                LIMIT 1
            ");
            $stmt->execute([$drugId, $lotNo]);
            $inventory = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        // Generate barcode
        $barcodeData = $this->generateBarcode($drug['code']);
        
        // Generate QR code
        $qrData = $this->generateQRCode([
            'code' => $drug['code'],
            'name' => $drug['name'],
            'lot' => $lotNo,
            'expire' => $inventory['expire_date'] ?? null,
            'qty' => $quantity
        ]);
        
        return [
            'drug' => $drug,
            'inventory' => $inventory,
            'quantity' => $quantity,
            'barcode' => $barcodeData,
            'qr_code' => $qrData,
            'generated_at' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Generate barcode image (Code 128)
     * 
     * @param string $code Drug code
     * @return string Base64 encoded image
     */
    public function generateBarcode($code)
    {
        // Simple barcode generation using GD
        $width = 300;
        $height = 80;
        $barWidth = 2;
        
        $image = imagecreate($width, $height);
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        
        imagefill($image, 0, 0, $white);
        
        // Simple bar pattern (not real Code 128, just visual representation)
        $x = 10;
        $codeLength = strlen($code);
        $barCount = $codeLength * 6;
        
        for ($i = 0; $i < $barCount; $i++) {
            if ($i % 2 == 0) {
                imagefilledrectangle($image, $x, 10, $x + $barWidth, 50, $black);
            }
            $x += $barWidth + 1;
        }
        
        // Add text below barcode
        imagestring($image, 3, ($width - strlen($code) * 6) / 2, 55, $code, $black);
        
        // Convert to base64
        ob_start();
        imagepng($image);
        $imageData = ob_get_clean();
        imagedestroy($image);
        
        return 'data:image/png;base64,' . base64_encode($imageData);
    }
    
    /**
     * Generate QR code
     * 
     * @param array $data Data to encode
     * @return string Base64 encoded QR code image
     */
    public function generateQRCode($data)
    {
        // Create QR code data string
        $qrString = json_encode($data, JSON_UNESCAPED_UNICODE);
        
        // Simple QR code generation (placeholder - use library like phpqrcode in production)
        $size = 150;
        $image = imagecreate($size, $size);
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        
        imagefill($image, 0, 0, $white);
        
        // Draw simple pattern (placeholder for actual QR code)
        for ($i = 0; $i < 15; $i++) {
            for ($j = 0; $j < 15; $j++) {
                if (($i + $j) % 2 == 0) {
                    imagefilledrectangle(
                        $image,
                        $i * 10,
                        $j * 10,
                        ($i + 1) * 10,
                        ($j + 1) * 10,
                        $black
                    );
                }
            }
        }
        
        // Convert to base64
        ob_start();
        imagepng($image);
        $imageData = ob_get_clean();
        imagedestroy($image);
        
        return 'data:image/png;base64,' . base64_encode($imageData);
    }
    
    /**
     * Generate batch labels
     * 
     * @param array $items Array of [drug_id, lot_no, quantity]
     * @return array Array of label data
     */
    public function batchGenerate($items)
    {
        $labels = [];
        
        foreach ($items as $item) {
            try {
                $labels[] = $this->generateLabel(
                    $item['drug_id'],
                    $item['lot_no'] ?? null,
                    $item['quantity'] ?? 1
                );
            } catch (\Exception $e) {
                $labels[] = [
                    'error' => $e->getMessage(),
                    'item' => $item
                ];
            }
        }
        
        return $labels;
    }
    
    /**
     * Log label printing
     * 
     * @param int $drugId Drug ID
     * @param int $quantity Number of labels printed
     * @param int $userId User who printed
     */
    public function logPrinting($drugId, $quantity, $userId)
    {
        $stmt = $this->db->prepare("
            INSERT INTO label_print_log (drug_id, quantity, user_id, printed_at)
            VALUES (?, ?, ?, NOW())
        ");
        
        return $stmt->execute([$drugId, $quantity, $userId]);
    }
    
    /**
     * Get printing history
     * 
     * @param int $limit Number of records
     * @return array Printing history
     */
    public function getPrintHistory($limit = 50)
    {
        $stmt = $this->db->prepare("
            SELECT 
                l.*,
                d.code,
                d.name as drug_name,
                u.username
            FROM label_print_log l
            JOIN drugs d ON l.drug_id = d.id
            LEFT JOIN users u ON l.user_id = u.id
            ORDER BY l.printed_at DESC
            LIMIT ?
        ");
        
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
