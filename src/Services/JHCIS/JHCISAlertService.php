<?php

namespace App\Services\JHCIS;

use App\Services\LoggerService;

/**
 * JHCIS Alert Service
 * 
 * Manages alerts and notifications for JHCIS operations
 */
class JHCISAlertService
{
    private LoggerService $logger;
    private $db;
    
    // Alert types
    const ALERT_SYNC_FAILURE = 'sync_failure';
    const ALERT_DISCREPANCY = 'discrepancy';
    const ALERT_LOW_STOCK = 'low_stock';
    const ALERT_EXPIRING = 'expiring';
    const ALERT_MAPPING_NEEDED = 'mapping_needed';
    
    public function __construct()
    {
        $this->logger = new LoggerService();
        $this->db = \App\Core\Database::getInstance()->getConnection();
    }
    
    /**
     * Create alert
     * 
     * @param string $type
     * @param int $hospitalId
     * @param string $message
     * @param array $data
     * @param string $severity
     * @return int Alert ID
     */
    public function createAlert(string $type, int $hospitalId, string $message, array $data = [], string $severity = 'info'): int
    {
        $title = $this->getAlertTitle($type);
        
        $stmt = $this->db->prepare(
            "INSERT INTO jhcis_alerts 
             (alert_type, hospital_id, title, message, severity, status, created_at)
             VALUES (?, ?, ?, ?, ?, 'active', NOW())"
        );
        
        $stmt->execute([
            $type,
            $hospitalId,
            $title,
            $message . (!empty($data) ? ' | Data: ' . json_encode($data) : ''),
            $severity
        ]);
        
        $alertId = $this->db->lastInsertId();
        
        // Send notifications
        $this->sendNotifications($alertId, $type, $hospitalId, $message, $severity);
        
        return $alertId;
    }
    
    /**
     * Get alert title based on type
     */
    private function getAlertTitle(string $type): string
    {
        $titles = [
            self::ALERT_SYNC_FAILURE => 'Sync Failure',
            self::ALERT_DISCREPANCY => 'Inventory Discrepancy',
            self::ALERT_LOW_STOCK => 'Low Stock',
            self::ALERT_EXPIRING => 'Expiring Drugs',
            self::ALERT_MAPPING_NEEDED => 'Mapping Required'
        ];
        
        return $titles[$type] ?? 'Alert';
    }
    
    /**
     * Send notifications
     * 
     * @param int $alertId
     * @param string $type
     * @param int $hospitalId
     * @param string $message
     * @param string $severity
     * @return void
     */
    private function sendNotifications(int $alertId, string $type, int $hospitalId, string $message, string $severity): void
    {
        // Get notification settings
        $settings = $this->getNotificationSettings($hospitalId);
        
        if (empty($settings)) {
            return;
        }
        
        // Email notification
        if ($settings['email_enabled'] && !empty($settings['email_recipients'])) {
            $this->sendEmailNotification($settings['email_recipients'], $type, $message, $severity);
        }
        
        // LINE notification
        if ($settings['line_enabled'] && !empty($settings['line_token'])) {
            $this->sendLineNotification($settings['line_token'], $type, $message, $severity);
        }
        
        $this->logger->info("Notifications sent", [
            'alert_id' => $alertId,
            'type' => $type,
            'severity' => $severity
        ]);
    }
    
    /**
     * Get notification settings
     * 
     * @param int $hospitalId
     * @return array|null
     */
    private function getNotificationSettings(int $hospitalId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM jhcis_notification_settings WHERE hospital_id = ?"
        );
        $stmt->execute([$hospitalId]);
        
        return $stmt->fetch() ?: null;
    }
    
    /**
     * Send email notification
     * 
     * @param string $recipients Comma-separated emails
     * @param string $type
     * @param string $message
     * @param string $severity
     * @return void
     */
    private function sendEmailNotification(string $recipients, string $type, string $message, string $severity): void
    {
        $subject = $this->getEmailSubject($type, $severity);
        $body = $this->getEmailBody($type, $message, $severity);
        
        $emails = array_map('trim', explode(',', $recipients));
        
        foreach ($emails as $email) {
            // Use PHP mail() or a mail service
            mail($email, $subject, $body, "From: noreply@drugmuk.com\r\nContent-Type: text/html; charset=UTF-8");
        }
    }
    
    /**
     * Send LINE notification
     * 
     * @param string $token
     * @param string $type
     * @param string $message
     * @param string $severity
     * @return void
     */
    private function sendLineNotification(string $token, string $type, string $message, string $severity): void
    {
        $lineMessage = $this->getLineMessage($type, $message, $severity);
        
        $ch = curl_init('https://notify-api.line.me/api/notify');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query(['message' => $lineMessage]),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/x-www-form-urlencoded'
            ],
            CURLOPT_RETURNTRANSFER => true
        ]);
        
        $result = curl_exec($ch);
        curl_close($ch);
    }
    
    /**
     * Get email subject
     * 
     * @param string $type
     * @param string $severity
     * @return string
     */
    private function getEmailSubject(string $type, string $severity): string
    {
        $prefix = $severity === 'high' ? '[URGENT] ' : '';
        
        $subjects = [
            self::ALERT_SYNC_FAILURE => 'JHCIS Sync Failure',
            self::ALERT_DISCREPANCY => 'Inventory Discrepancy Detected',
            self::ALERT_LOW_STOCK => 'Low Stock Alert',
            self::ALERT_EXPIRING => 'Expiring Drugs Alert',
            self::ALERT_MAPPING_NEEDED => 'Drug Mapping Required'
        ];
        
        return $prefix . ($subjects[$type] ?? 'JHCIS Alert');
    }
    
    /**
     * Get email body
     * 
     * @param string $type
     * @param string $message
     * @param string $severity
     * @return string
     */
    private function getEmailBody(string $type, string $message, string $severity): string
    {
        $color = $severity === 'high' ? '#dc3545' : ($severity === 'medium' ? '#ffc107' : '#17a2b8');
        
        return "
        <html>
        <body style='font-family: Arial, sans-serif;'>
            <div style='background-color: {$color}; color: white; padding: 20px; border-radius: 5px;'>
                <h2>{$this->getEmailSubject($type, $severity)}</h2>
            </div>
            <div style='padding: 20px;'>
                <p>{$message}</p>
                <p style='color: #666; font-size: 12px;'>
                    Time: " . date('Y-m-d H:i:s') . "<br>
                    Severity: " . strtoupper($severity) . "
                </p>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Get LINE message
     * 
     * @param string $type
     * @param string $message
     * @param string $severity
     * @return string
     */
    private function getLineMessage(string $type, string $message, string $severity): string
    {
        $emoji = $severity === 'high' ? '🚨' : ($severity === 'medium' ? '⚠️' : 'ℹ️');
        
        return "\n{$emoji} JHCIS Alert\n\n{$message}\n\nSeverity: " . strtoupper($severity) . "\nTime: " . date('Y-m-d H:i:s');
    }
    
    /**
     * Get active alerts
     * 
     * @param int|null $hospitalId
     * @param int $limit
     * @return array
     */
    public function getActiveAlerts(?int $hospitalId = null, int $limit = 50): array
    {
        $sql = "SELECT * FROM jhcis_alerts WHERE status = 'active'";
        $params = [];
        
        if ($hospitalId) {
            $sql .= " AND hospital_id = ?";
            $params[] = $hospitalId;
        }
        
        $sql .= " ORDER BY created_at DESC LIMIT ?";
        $params[] = $limit;
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Mark alert as read
     * 
     * @param int $alertId
     * @return void
     */
    public function markAsRead(int $alertId): void
    {
        $stmt = $this->db->prepare(
            "UPDATE jhcis_alerts SET is_read = 1, status = 'resolved', resolved_at = NOW() WHERE id = ?"
        );
        $stmt->execute([$alertId]);
    }
}
