<?php

namespace App\Controllers;

use App\Core\Controller;

/**
 * Label Controller (Phase 4)
 * Generates printer-friendly medication labels with QR codes
 */
class LabelController extends Controller
{
    /**
     * View: Generate and Display Label
     * GET /label/print/{dispense_id}/{item_id}
     */
    public function printLabel($dispenseId, $itemId)
    {
        // Fetch dispensing details
        // (Assuming a method to get specific dispensed item details)
        $db = \App\Core\Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT d.hn, d.patient_name, di.drug_id, dr.name as drug_name, 
                   dr.unit, di.quantity, dr.video_url, dr.storage_advice
            FROM dispensing d
            JOIN dispensing_items di ON d.id = di.dispense_id
            JOIN drugs dr ON di.drug_id = dr.id
            WHERE d.id = ? AND di.id = ?
        ");
        $stmt->execute([$dispenseId, $itemId]);
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$data) {
            die('Data not found');
        }
        
        // Generate Secure Token for Patient Portal
        $hn = $data['hn'];
        $appSecret = $_ENV['APP_KEY'] ?? 'drugmuk_secret_2026';
        $hash = hash_hmac('sha256', $hn, $appSecret);
        $token = base64_encode($hn . '|' . substr($hash, 0, 16));
        
        $portalUrl = ($_SERVER['REQUEST_SCHEME'] ?? 'http') . "://" . $_SERVER['HTTP_HOST'] . "/patient/v/" . urlencode($token);
        
        /**
         * QR Code Generation
         * Phase 4: Using local library (chillerlan/php-qrcode)
         */
        try {
            if (class_exists('\chillerlan\QRCode\QRCode')) {
                $options = new \chillerlan\QRCode\QROptions([
                    'version'      => 5,
                    'outputType'   => \chillerlan\QRCode\QRCode::OUTPUT_MARKUP_SVG,
                    'eccLevel'     => \chillerlan\QRCode\QRCode::ECC_L,
                ]);
                $qrcode = new \chillerlan\QRCode\QRCode($options);
                $qrUrl = $qrcode->render($portalUrl); // This returns a Data URI (SVG)
            } else {
                $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($portalUrl);
            }
        } catch (\Exception $e) {
            $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($portalUrl);
        }
        
        $this->view('label/medication', [
            'data' => $data,
            'qr_url' => $qrUrl,
            'portal_url' => $portalUrl
        ]);
    }
}
