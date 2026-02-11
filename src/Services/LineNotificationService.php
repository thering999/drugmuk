<?php

namespace App\Services;

use App\Core\Config;

/**
 * Line Notification Service
 * 
 * Handles sending push notifications via LINE Messaging API
 */
class LineNotificationService
{
    private $accessToken;
    private $channelSecret;
    private $apiUrl = 'https://api.line.me/v2/bot/message';

    public function __construct()
    {
        Config::load();
        $this->accessToken = Config::get('LINE_ACCESS_TOKEN');
        $this->channelSecret = Config::get('LINE_CHANNEL_SECRET');
    }

    /**
     * Send a push message to a specific user or group
     */
    public function sendPush($to, $message, $type = 'text')
    {
        if (empty($this->accessToken)) return false;

        $payload = [
            'to' => $to,
            'messages' => [
                [
                    'type' => $type,
                    'text' => $message
                ]
            ]
        ];

        return $this->executeRequest($this->apiUrl . '/push', $payload);
    }

    /**
     * Alias for sendPush (Compatibility)
     */
    public function sendPushMessage($to, $message)
    {
        return $this->sendPush($to, $message);
    }
    
    /**
     * Send Image Push Message
     */
    public function sendPushImage($to, $originalUrl, $previewUrl = null)
    {
        if (empty($this->accessToken)) return false;

        $payload = [
            'to' => $to,
            'messages' => [
                [
                    'type' => 'image',
                    'originalContentUrl' => $originalUrl,
                    'previewImageUrl' => $previewUrl ?: $originalUrl
                ]
            ]
        ];

        return $this->executeRequest($this->apiUrl . '/push', $payload);
    }

    /**
     * Send a Flex Message (Advanced UI)
     */
    public function sendFlex($to, $altText, $contents)
    {
        if (empty($this->accessToken)) return false;

        $payload = [
            'to' => $to,
            'messages' => [
                [
                    'type' => 'flex',
                    'altText' => $altText,
                    'contents' => $contents
                ]
            ]
        ];

        return $this->executeRequest($this->apiUrl . '/push', $payload);
    }

    /**
     * Broadcast message to all followers
     */
    public function broadcast($message)
    {
        if (empty($this->accessToken)) return false;

        $payload = [
            'messages' => [
                [
                    'type' => 'text',
                    'text' => $message
                ]
            ]
        ];

        return $this->executeRequest($this->apiUrl . '/broadcast', $payload);
    }

    /**
     * Helper to execute CURL request to LINE API
     */
    private function executeRequest($url, $payload)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->accessToken
        ]);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            return true;
        }

        error_log("LINE API Error: $result (HTTP $httpCode)");
        return false;
    }

    /**
     * Send Smart Alert for Drug Shortage
     */
    public function sendShortageAlert($drugName, $currentStock, $daysRemaining)
    {
        $adminUserId = Config::get('LINE_ADMIN_USER_ID');
        if (!$adminUserId) return false;

        $color = $daysRemaining <= 2 ? '#FF0000' : '#FF8C00';
        $emoji = $daysRemaining <= 2 ? '🚨' : '⚠️';

        $text = "$emoji AI Alert: ยาใกล้หมดคลัง\n";
        $text .= "------------------\n";
        $text .= "📦 ชื่อยา: $drugName\n";
        $text .= "📉 สต็อกคงเหลือ: $currentStock\n";
        $text .= "⏳ คาดว่าจะหมดใน: $daysRemaining วัน\n";
        $text .= "------------------\n";
        $text .= "กรุณาวางแผนสั่งซื้อด่วนที่: " . Config::get('APP_URL') . "/orders/what-to-buy";

        return $this->sendPush($adminUserId, $text);
    }

    /**
     * Send Clinical Safety Alert
     */
    public function sendClinicalAlert($patientName, $alertType, $message)
    {
        $adminUserId = Config::get('LINE_ADMIN_USER_ID');
        if (!$adminUserId) return false;

        $text = "💊 Clinical Safety Alert 🚨\n";
        $text .= "------------------\n";
        $text .= "👤 ผู้ป่วย: $patientName\n";
        $text .= "🔍 ประเภท: $alertType\n";
        $text .= "⚠️ ข้อความ: $message\n";
        $text .= "------------------\n";
        $text .= "ตรวจสอบรายละเอียด: " . Config::get('APP_URL') . "/intelligence";

        return $this->sendPush($adminUserId, $text);
    }
}
