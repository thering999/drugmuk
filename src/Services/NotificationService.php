<?php
/**
 * Notification Service
 * Send notifications via Email, SMS, Line, etc.
 */

namespace App\Services;

class NotificationService
{
    private $config;
    
    public function __construct()
    {
        $this->config = [
            'email' => [
                'from' => $_ENV['MAIL_FROM'] ?? 'noreply@drugmuk.local',
                'smtp_host' => $_ENV['SMTP_HOST'] ?? 'localhost',
                'smtp_port' => $_ENV['SMTP_PORT'] ?? 587,
            ],
            'line' => [
                'token' => $_ENV['LINE_NOTIFY_TOKEN'] ?? '',
            ],
        ];
    }
    
    /**
     * Send email notification
     */
    public function sendEmail(string $to, string $subject, string $message): bool
    {
        $headers = [
            'From: ' . $this->config['email']['from'],
            'Content-Type: text/html; charset=UTF-8',
            'MIME-Version: 1.0'
        ];
        
        return mail($to, $subject, $message, implode("\r\n", $headers));
    }
    
    /**
     * Send Line Notify
     */
    public function sendLine(string $message): bool
    {
        if (empty($this->config['line']['token'])) {
            return false;
        }
        
        $ch = curl_init('https://notify-api.line.me/api/notify');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query(['message' => $message]),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->config['line']['token'],
            ],
            CURLOPT_RETURNTRANSFER => true,
        ]);
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return $httpCode === 200;
    }
    
    /**
     * Notify about expiring drugs
     */
    public function notifyExpiringDrugs(array $drugs): void
    {
        if (empty($drugs)) {
            return;
        }
        
        $message = "⚠️ แจ้งเตือน: ยาใกล้หมดอายุ\n\n";
        
        foreach ($drugs as $drug) {
            $message .= "• {$drug['name']} (Lot: {$drug['lot_no']})\n";
            $message .= "  หมดอายุ: {$drug['expire_date']}\n";
            $message .= "  จำนวน: {$drug['quantity']} {$drug['unit']}\n\n";
        }
        
        $this->sendLine($message);
    }
    
    /**
     * Notify about low stock
     */
    public function notifyLowStock(array $drugs): void
    {
        if (empty($drugs)) {
            return;
        }
        
        $message = "📦 แจ้งเตือน: ยาสต็อกต่ำ\n\n";
        
        foreach ($drugs as $drug) {
            $message .= "• {$drug['name']}\n";
            $message .= "  คงเหลือ: {$drug['quantity']} {$drug['unit']}\n";
            $message .= "  ขั้นต่ำ: {$drug['min_level']} {$drug['unit']}\n\n";
        }
        
        $this->sendLine($message);
    }
    
    /**
     * Notify about pending orders
     */
    public function notifyPendingOrders(int $count): void
    {
        $message = "📋 แจ้งเตือน: มีใบสั่งซื้อรออนุมัติ {$count} รายการ";
        $this->sendLine($message);
    }
    
    /**
     * Notify about system backup
     */
    public function notifyBackupStatus(bool $success, string $details = ''): void
    {
        if ($success) {
            $message = "✅ Backup สำเร็จ\n{$details}";
        } else {
            $message = "❌ Backup ล้มเหลว!\n{$details}";
        }
        
        $this->sendLine($message);
    }
}
