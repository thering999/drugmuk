<?php
/**
 * Helper Functions
 * Utility functions used throughout the application
 */

/**
 * Format Thai date
 */
function formatThaiDate(string $date, string $format = 'd/m/Y'): string
{
    if (empty($date) || $date === '0000-00-00') {
        return '-';
    }
    
    $timestamp = strtotime($date);
    $thaiYear = date('Y', $timestamp) + 543;
    
    $formatted = date($format, $timestamp);
    $formatted = str_replace(date('Y', $timestamp), $thaiYear, $formatted);
    
    return $formatted;
}

/**
 * Format number with Thai format
 */
function formatNumber(float $number, int $decimals = 2): string
{
    return number_format($number, $decimals, '.', ',');
}

/**
 * Format currency (Thai Baht)
 */
function formatCurrency(float $amount): string
{
    return '฿' . formatNumber($amount, 2);
}

/**
 * Sanitize input
 */
function sanitize(string $input): string
{
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Check if user is logged in
 */
function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}

/**
 * Check if user has role
 */
function hasRole(string $role): bool
{
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

/**
 * Redirect to URL
 */
function redirect(string $url): void
{
    header("Location: {$url}");
    exit;
}

/**
 * Flash message
 */
function setFlash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/**
 * Get and clear flash message
 */
function getFlash(): ?array
{
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Generate random string
 */
function generateRandomString(int $length = 32): string
{
    return bin2hex(random_bytes($length / 2));
}

/**
 * Calculate days until expiry
 */
function daysUntilExpiry(string $expireDate): int
{
    $now = new DateTime();
    $expire = new DateTime($expireDate);
    $diff = $now->diff($expire);
    
    return $diff->invert ? -$diff->days : $diff->days;
}

/**
 * Get expiry status
 */
function getExpiryStatus(string $expireDate): string
{
    $days = daysUntilExpiry($expireDate);
    
    if ($days < 0) {
        return 'expired';
    } elseif ($days <= 30) {
        return 'urgent';
    } elseif ($days <= 90) {
        return 'warning';
    } else {
        return 'normal';
    }
}

/**
 * Get stock status
 */
function getStockStatus(float $current, float $min, float $max): string
{
    if ($current < $min) {
        return 'low';
    } elseif ($current > $max) {
        return 'high';
    } else {
        return 'normal';
    }
}

/**
 * Format file size
 */
function formatFileSize(int $bytes): string
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = 0;
    
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    
    return round($bytes, 2) . ' ' . $units[$i];
}

/**
 * Validate Thai ID card
 */
function validateThaiID(string $id): bool
{
    if (strlen($id) !== 13 || !ctype_digit($id)) {
        return false;
    }
    
    $sum = 0;
    for ($i = 0; $i < 12; $i++) {
        $sum += (int)$id[$i] * (13 - $i);
    }
    
    $checkDigit = (11 - ($sum % 11)) % 10;
    
    return (int)$id[12] === $checkDigit;
}

/**
 * Generate QR Code data URL
 */
function generateQRCode(string $data): string
{
    // Simple QR code generation using Google Charts API
    $size = '200x200';
    $url = "https://chart.googleapis.com/chart?chs={$size}&cht=qr&chl=" . urlencode($data);
    return $url;
}

/**
 * Log activity
 */
function logActivity(string $action, string $table, int $recordId, array $details = []): void
{
    global $db;
    
    try {
        $stmt = $db->prepare("
            INSERT INTO audit_logs (user_id, action, table_name, record_id, details, ip_address)
            VALUES (:user_id, :action, :table_name, :record_id, :details, :ip)
        ");
        
        $stmt->execute([
            'user_id' => $_SESSION['user_id'] ?? null,
            'action' => $action,
            'table_name' => $table,
            'record_id' => $recordId,
            'details' => json_encode($details),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
        ]);
    } catch (\Exception $e) {
        // Silent fail for logging
        error_log("Failed to log activity: " . $e->getMessage());
    }
}

/**
 * Send notification
 */
function sendNotification(string $type, array $data): void
{
    $notify = new \App\Services\NotificationService();
    
    switch ($type) {
        case 'expiring_drugs':
            $notify->notifyExpiringDrugs($data);
            break;
        case 'low_stock':
            $notify->notifyLowStock($data);
            break;
        case 'pending_orders':
            $notify->notifyPendingOrders($data['count']);
            break;
    }
}

/**
 * Get Thai month name
 */
function getThaiMonth(int $month): string
{
    $months = [
        1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม',
        4 => 'เมษายน', 5 => 'พฤษภาคม', 6 => 'มิถุนายน',
        7 => 'กรกฎาคม', 8 => 'สิงหาคม', 9 => 'กันยายน',
        10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
    ];
    
    return $months[$month] ?? '';
}

/**
 * Calculate percentage
 */
function calculatePercentage(float $value, float $total): float
{
    if ($total == 0) {
        return 0;
    }
    
    return ($value / $total) * 100;
}

/**
 * Truncate text
 */
function truncate(string $text, int $length = 50, string $suffix = '...'): string
{
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    
    return mb_substr($text, 0, $length) . $suffix;
}
