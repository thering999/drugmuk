#!/usr/bin/env php
<?php
/**
 * Drugmuk Scheduled Tasks Runner
 * รันด้วย Cron: * * * * * php /path/to/cron.php >> /var/log/drugmuk-cron.log 2>&1
 * 
 * ตัวอย่างการตั้ง Cron:
 * - ทุก 6 ชั่วโมง: 0 * / 6 * * * php /var/www/html/cron.php
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
    echo "    - Low stock alerts: " . ($results['low_stock'] ?? 0) . "\n";
    echo "    - Expiring alerts: " . ($results['expiring'] ?? 0) . "\n";
    echo "    - Data quality alerts: " . ($results['data_quality'] ?? 0) . "\n";

    // 2. Data Quality Check
    echo "\n[2] Running data quality check...\n";
    $cleansing = new \App\Models\DataCleansing();
    $qualityCheck = $cleansing->runFullDataQualityCheck(0); // System user
    echo "    - Quality check completed.\n";

    // 3. Multi-Channel Notifications (Discord, Telegram, LINE)
    echo "\n[3] Processing Multi-Channel Notifications...\n";
    $notifController = new \App\Controllers\NotificationController();
    $notifResults = $notifController->cronCheck();
    echo "    - Notification processing done.\n";

    // 4. AI Demand Forecasting
    echo "\n[4] Updating AI Demand Forecasts...\n";
    $forecasting = new \App\Services\AI\ForecastingEngine();
    $forecastResults = $forecasting->runAllForecasts();
    echo "    - AI Forecasting: {$forecastResults['processed']} processed, {$forecastResults['errors']} errors.\n";

    // 4.1 Intelligence Risk Recalculation
    echo "\n[4.1] Recalculating Patient Risks...\n";
    $intelligence = new \App\Services\IntelligenceService();
    $riskCount = $intelligence->updatePatientRiskScores();
    echo "    - Updated risk scores for {$riskCount} patients.\n";

    // 5. Automated Daily Backup
    echo "\n[5] Running Automated Backup...\n";
    $backup = new \App\Controllers\BackupController();
    $backupResults = $backup->cronBackup();
    echo "    - Backup status: " . ($backupResults['success'] ? 'SUCCESS' : 'FAILED') . "\n";

    echo "\n✅ All scheduled tasks completed successfully!\n";

} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    error_log("Cron error: " . $e->getMessage());
}

echo "\n========================================\n\n";
