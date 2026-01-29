<?php
/**
 * LINE Notify Service
 * ส่งการแจ้งเตือนผ่าน LINE Notify API
 * 
 * @package Drugmuk
 * @version 3.4.0
 */

namespace App\Services;

class LineNotifyService
{
    private string $accessToken;
    private string $apiUrl = 'https://notify-api.line.me/api/notify';
    
    public function __construct(?string $accessToken = null)
    {
        $this->accessToken = $accessToken ?? ($_ENV['LINE_NOTIFY_TOKEN'] ?? '');
    }
    
    /**
     * ส่งข้อความแจ้งเตือน
     */
    public function send(string $message, ?string $imageUrl = null): array
    {
        if (empty($this->accessToken)) {
            return ['success' => false, 'error' => 'LINE Notify token not configured'];
        }
        
        $data = ['message' => $message];
        
        if ($imageUrl) {
            $data['imageThumbnail'] = $imageUrl;
            $data['imageFullsize'] = $imageUrl;
        }
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->apiUrl,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($data),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->accessToken,
                'Content-Type: application/x-www-form-urlencoded'
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return ['success' => false, 'error' => $error];
        }
        
        $result = json_decode($response, true);
        
        return [
            'success' => $httpCode === 200,
            'http_code' => $httpCode,
            'response' => $result
        ];
    }
    
    /**
     * แจ้งเตือนยาใกล้หมดสต็อก
     */
    public function notifyLowStock(array $items): array
    {
        if (empty($items)) {
            return ['success' => true, 'message' => 'No low stock items'];
        }
        
        $message = "\n🔴 แจ้งเตือน: ยาใกล้หมดสต็อก\n";
        $message .= "━━━━━━━━━━━━━━━\n";
        
        foreach (array_slice($items, 0, 10) as $item) {
            $message .= sprintf(
                "• %s\n  คงเหลือ: %d (ต่ำกว่า %d)\n",
                $item['drug_name'] ?? $item['name'],
                $item['current_stock'] ?? 0,
                $item['min_stock'] ?? 0
            );
        }
        
        if (count($items) > 10) {
            $message .= sprintf("\n...และอีก %d รายการ", count($items) - 10);
        }
        
        $message .= "\n━━━━━━━━━━━━━━━\n";
        $message .= "📅 " . date('d/m/Y H:i');
        
        return $this->send($message);
    }
    
    /**
     * แจ้งเตือนยาใกล้หมดอายุ
     */
    public function notifyExpiringDrugs(array $items): array
    {
        if (empty($items)) {
            return ['success' => true, 'message' => 'No expiring items'];
        }
        
        $message = "\n⚠️ แจ้งเตือน: ยาใกล้หมดอายุ\n";
        $message .= "━━━━━━━━━━━━━━━\n";
        
        foreach (array_slice($items, 0, 10) as $item) {
            $expDate = date('d/m/Y', strtotime($item['expire_date']));
            $daysLeft = (strtotime($item['expire_date']) - time()) / 86400;
            
            $message .= sprintf(
                "• %s\n  Lot: %s | หมดอายุ: %s (%d วัน)\n",
                $item['drug_name'] ?? $item['name'],
                $item['lot_no'] ?? 'N/A',
                $expDate,
                max(0, (int)$daysLeft)
            );
        }
        
        if (count($items) > 10) {
            $message .= sprintf("\n...และอีก %d รายการ", count($items) - 10);
        }
        
        $message .= "\n━━━━━━━━━━━━━━━\n";
        $message .= "📅 " . date('d/m/Y H:i');
        
        return $this->send($message);
    }
    
    /**
     * แจ้งเตือนสัญญาใกล้หมดอายุ
     */
    public function notifyExpiringContracts(array $contracts): array
    {
        if (empty($contracts)) {
            return ['success' => true, 'message' => 'No expiring contracts'];
        }
        
        $message = "\n📄 แจ้งเตือน: สัญญาใกล้หมดอายุ\n";
        $message .= "━━━━━━━━━━━━━━━\n";
        
        foreach ($contracts as $contract) {
            $endDate = date('d/m/Y', strtotime($contract['end_date']));
            $message .= sprintf(
                "• %s\n  คู่สัญญา: %s\n  หมดอายุ: %s\n",
                $contract['contract_no'],
                $contract['supplier_name'] ?? 'N/A',
                $endDate
            );
        }
        
        $message .= "━━━━━━━━━━━━━━━\n";
        $message .= "📅 " . date('d/m/Y H:i');
        
        return $this->send($message);
    }
    
    /**
     * แจ้งเตือนการรับยาเข้าคลัง
     */
    public function notifyDrugReceived(array $data): array
    {
        $message = "\n✅ รับยาเข้าคลังสำเร็จ\n";
        $message .= "━━━━━━━━━━━━━━━\n";
        $message .= sprintf("📦 %s\n", $data['drug_name']);
        $message .= sprintf("🔢 จำนวน: %s %s\n", number_format($data['quantity']), $data['unit'] ?? 'หน่วย');
        $message .= sprintf("📋 Lot: %s\n", $data['lot_no'] ?? 'N/A');
        $message .= sprintf("📅 หมดอายุ: %s\n", date('d/m/Y', strtotime($data['expire_date'])));
        $message .= sprintf("👤 ผู้รับ: %s\n", $data['received_by'] ?? 'N/A');
        $message .= "━━━━━━━━━━━━━━━\n";
        $message .= "🕐 " . date('d/m/Y H:i');
        
        return $this->send($message);
    }
    
    /**
     * แจ้งเตือนการจ่ายยา (Drug Allergy Warning)
     */
    public function notifyAllergyWarning(array $data): array
    {
        $message = "\n🚨 แจ้งเตือน: พบประวัติแพ้ยา!\n";
        $message .= "━━━━━━━━━━━━━━━\n";
        $message .= sprintf("👤 ผู้ป่วย: %s (HN: %s)\n", $data['patient_name'], $data['hn']);
        $message .= sprintf("💊 ยาที่สั่ง: %s\n", $data['drug_name']);
        $message .= sprintf("⚠️ แพ้ยา: %s\n", $data['allergy_drug']);
        $message .= sprintf("📝 อาการ: %s\n", $data['reaction'] ?? 'ไม่ระบุ');
        $message .= "━━━━━━━━━━━━━━━\n";
        $message .= "🕐 " . date('d/m/Y H:i');
        
        return $this->send($message);
    }
    
    /**
     * ส่งรายงานสรุปประจำวัน
     */
    public function sendDailySummary(array $stats): array
    {
        $message = "\n📊 รายงานสรุปประจำวัน\n";
        $message .= "━━━━━━━━━━━━━━━\n";
        $message .= sprintf("📅 วันที่: %s\n\n", date('d/m/Y'));
        
        $message .= sprintf("💊 จ่ายยา: %s ครั้ง\n", number_format($stats['dispensing_count'] ?? 0));
        $message .= sprintf("📦 รับยา: %s รายการ\n", number_format($stats['receive_count'] ?? 0));
        $message .= sprintf("🛒 สั่งซื้อ: %s ใบ\n", number_format($stats['order_count'] ?? 0));
        $message .= sprintf("⚠️ ยาใกล้หมด: %s รายการ\n", number_format($stats['low_stock_count'] ?? 0));
        $message .= sprintf("⏰ ยาใกล้หมดอายุ: %s รายการ\n", number_format($stats['expiring_count'] ?? 0));
        
        if (isset($stats['total_value'])) {
            $message .= sprintf("\n💰 มูลค่าคลัง: %s บาท\n", number_format($stats['total_value'], 2));
        }
        
        $message .= "━━━━━━━━━━━━━━━\n";
        $message .= "🏥 Drugmuk System";
        
        return $this->send($message);
    }
    
    /**
     * ตรวจสอบว่า token ใช้งานได้
     */
    public function validateToken(): bool
    {
        if (empty($this->accessToken)) {
            return false;
        }
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://notify-api.line.me/api/status',
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->accessToken
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return $httpCode === 200;
    }
    
    /**
     * ตั้งค่า token
     */
    public function setToken(string $token): self
    {
        $this->accessToken = $token;
        return $this;
    }
}
