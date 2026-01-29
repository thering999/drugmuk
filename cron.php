#!/usr/bin/env php
<?php
/**
 * Drugmuk Scheduled Tasks Runner
 * รันด้วย Cron: * * * * * php /path/to/cron.php >> /var/log/drugmuk-cron.log 2>&1
 * 
 * ตัวอย่างการตั้ง Cron:
 * - ทุก 6 ชั่วโมง: 0 */6 * * * php /var/www/html/cron.php
 * - ทุกวันตอน 06:00: 0 6 * * * php /var/www/html/cron.php
 */

// Load autoloader
require_once __DIR__ . '/vendor/autoload.php';

// Load environment
if (file_exists(__DIR__ . '/.env')) {
    $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        putenv(trim($name) . "=" . trim($value));
        $_ENV[trim($name)] = trim($value);
    }
}

// Manual autoloader for App namespace
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/src/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) require $file;
});

echo "\n========================================\n";
echo "Drugmuk Scheduled Tasks\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n";
echo "========================================\n\n";

try {
    // 1. Generate Auto Notifications
    echo "[1] Generating notifications...\n";
    $notification = new \App\Models\Notification();
    $results = $notification->generateAutoNotifications();
    echo "    - Low stock alerts: {$results['low_stock']}\n";
    echo "    - Expiring alerts: {$results['expiring']}\n";
    echo "    - Data quality alerts: {$results['data_quality']}\n";

    // 2. Data Quality Check
    echo "\n[2] Running data quality check...\n";
    $cleansing = new \App\Models\DataCleansing();
    $qualityCheck = $cleansing->runFullDataQualityCheck(0); // System user
    echo "    - Duplicates found: " . ($qualityCheck['duplicates']['found'] ?? 0) . "\n";
    echo "    - Orphaned found: " . ($qualityCheck['orphaned']['transactions']['found'] ?? 0) . "\n";

    // 3. Send LINE notifications to users who enabled it
    echo "\n[3] Sending LINE notifications...\n";
    $db = \App\Core\Database::getInstance()->getConnection();
    $stmt = $db->query("
        SELECT ns.line_token, COUNT(n.id) as unread_count
        FROM notification_settings ns
        LEFT JOIN notifications n ON (ns.user_id = n.user_id OR n.user_id IS NULL) AND n.is_read = 0
        WHERE ns.line_enabled = 1 AND ns.line_token IS NOT NULL AND ns.line_token != ''
        GROUP BY ns.user_id, ns.line_token
        HAVING unread_count > 0
    ");
    $lineUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($lineUsers as $user) {
        $message = "\n📊 สรุปรายวัน Drugmuk\n";
        $message .= "━━━━━━━━━━━━━━━\n";
        $message .= "🔔 การแจ้งเตือนใหม่: {$user['unread_count']} รายการ\n";
        $message .= "⏰ เวลา: " . date('d/m/Y H:i') . "\n";
        $message .= "━━━━━━━━━━━━━━━";
        
        $result = $notification->sendLine($user['line_token'], $message);
        echo "    - Sent to user: " . (isset($result['status']) && $result['status'] == 200 ? 'OK' : 'FAILED') . "\n";
    }

    echo "\n✅ All tasks completed successfully!\n";

} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    error_log("Cron error: " . $e->getMessage());
}

echo "\n========================================\n\n";
