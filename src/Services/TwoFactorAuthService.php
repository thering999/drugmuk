<?php
/**
 * Two-Factor Authentication Service
 * 
 * Provides TOTP-based 2FA functionality
 * 
 * @package Drugmuk
 * @subpackage Services
 * @version 1.0
 * @since Phase 6.3
 */

namespace App\Services;

use PDO;

class TwoFactorAuthService
{
    private $db;
    
    public function __construct()
    {
        $this->db = \App\Core\Database::getInstance()->getConnection();
    }
    
    /**
     * Generate a new secret for TOTP
     * 
     * @return string Base32 encoded secret
     */
    public function generateSecret()
    {
        $secret = '';
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'; // Base32 alphabet
        
        for ($i = 0; $i < 16; $i++) {
            $secret .= $chars[random_int(0, 31)];
        }
        
        return $secret;
    }
    
    /**
     * Generate QR code URL for Google Authenticator
     * 
     * @param string $secret TOTP secret
     * @param string $username User's username
     * @param string $issuer Application name
     * @return string QR code URL
     */
    public function getQRCodeUrl($secret, $username, $issuer = 'Drugmuk')
    {
        $otpauthUrl = sprintf(
            'otpauth://totp/%s:%s?secret=%s&issuer=%s',
            urlencode($issuer),
            urlencode($username),
            $secret,
            urlencode($issuer)
        );
        
        // Use Google Charts API for QR code generation
        return 'https://chart.googleapis.com/chart?chs=200x200&chld=M|0&cht=qr&chl=' . urlencode($otpauthUrl);
    }
    
    /**
     * Verify TOTP code
     * 
     * @param string $secret User's secret
     * @param string $code Code to verify
     * @param int $window Time window (±30 seconds)
     * @return bool True if valid
     */
    public function verifyCode($secret, $code, $window = 1)
    {
        $timeSlice = floor(time() / 30);
        
        // Check current time and ±window
        for ($i = -$window; $i <= $window; $i++) {
            $calculatedCode = $this->getCode($secret, $timeSlice + $i);
            
            if ($this->timingSafeEquals($calculatedCode, $code)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get TOTP code for a given time
     * 
     * @param string $secret Base32 secret
     * @param int $timeSlice Time slice
     * @return string 6-digit code
     */
    private function getCode($secret, $timeSlice)
    {
        $secretKey = $this->base32Decode($secret);
        
        // Pack time into binary string
        $time = pack('N*', 0) . pack('N*', $timeSlice);
        
        // Hash with HMAC-SHA1
        $hash = hash_hmac('sha1', $time, $secretKey, true);
        
        // Extract dynamic binary code
        $offset = ord($hash[19]) & 0xf;
        $code = (
            ((ord($hash[$offset + 0]) & 0x7f) << 24) |
            ((ord($hash[$offset + 1]) & 0xff) << 16) |
            ((ord($hash[$offset + 2]) & 0xff) << 8) |
            (ord($hash[$offset + 3]) & 0xff)
        ) % 1000000;
        
        return str_pad($code, 6, '0', STR_PAD_LEFT);
    }
    
    /**
     * Base32 decode
     * 
     * @param string $secret Base32 string
     * @return string Binary string
     */
    private function base32Decode($secret)
    {
        $base32chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $base32charsFlipped = array_flip(str_split($base32chars));
        
        $paddingCharCount = substr_count($secret, '=');
        $allowedValues = [6, 4, 3, 1, 0];
        
        if (!in_array($paddingCharCount, $allowedValues)) {
            return false;
        }
        
        for ($i = 0; $i < 4; $i++) {
            if ($paddingCharCount == $allowedValues[$i] &&
                substr($secret, -($allowedValues[$i])) != str_repeat('=', $allowedValues[$i])) {
                return false;
            }
        }
        
        $secret = str_replace('=', '', $secret);
        $secret = str_split($secret);
        $binaryString = '';
        
        for ($i = 0; $i < count($secret); $i = $i + 8) {
            $x = '';
            if (!in_array($secret[$i], $base32charsFlipped)) {
                return false;
            }
            for ($j = 0; $j < 8; $j++) {
                $x .= str_pad(base_convert(@$base32charsFlipped[@$secret[$i + $j]], 10, 2), 5, '0', STR_PAD_LEFT);
            }
            $eightBits = str_split($x, 8);
            for ($z = 0; $z < count($eightBits); $z++) {
                $binaryString .= (($y = chr(base_convert($eightBits[$z], 2, 10))) || ord($y) == 48) ? $y : '';
            }
        }
        
        return $binaryString;
    }
    
    /**
     * Timing-safe string comparison
     * 
     * @param string $safe Known string
     * @param string $user User-provided string
     * @return bool True if equal
     */
    private function timingSafeEquals($safe, $user)
    {
        if (function_exists('hash_equals')) {
            return hash_equals($safe, $user);
        }
        
        $safeLen = strlen($safe);
        $userLen = strlen($user);
        
        if ($userLen != $safeLen) {
            return false;
        }
        
        $result = 0;
        for ($i = 0; $i < $userLen; $i++) {
            $result |= (ord($safe[$i]) ^ ord($user[$i]));
        }
        
        return $result === 0;
    }
    
    /**
     * Generate backup codes
     * 
     * @param int $count Number of codes to generate
     * @return array Array of backup codes
     */
    public function generateBackupCodes($count = 10)
    {
        $codes = [];
        
        for ($i = 0; $i < $count; $i++) {
            $code = '';
            for ($j = 0; $j < 4; $j++) {
                $code .= strtoupper(bin2hex(random_bytes(2)));
                if ($j < 3) $code .= '-';
            }
            $codes[] = $code;
        }
        
        return $codes;
    }
    
    /**
     * Enable 2FA for user
     * 
     * @param int $userId User ID
     * @param string $secret TOTP secret
     * @param array $backupCodes Backup codes
     * @return bool Success status
     */
    public function enable($userId, $secret, $backupCodes)
    {
        $stmt = $this->db->prepare("
            INSERT INTO user_2fa (user_id, secret, backup_codes, enabled, enabled_at)
            VALUES (?, ?, ?, TRUE, NOW())
            ON DUPLICATE KEY UPDATE
                secret = VALUES(secret),
                backup_codes = VALUES(backup_codes),
                enabled = TRUE,
                enabled_at = NOW()
        ");
        
        return $stmt->execute([
            $userId,
            $secret,
            json_encode($backupCodes)
        ]);
    }
    
    /**
     * Disable 2FA for user
     * 
     * @param int $userId User ID
     * @return bool Success status
     */
    public function disable($userId)
    {
        $stmt = $this->db->prepare("
            UPDATE user_2fa
            SET enabled = FALSE
            WHERE user_id = ?
        ");
        
        return $stmt->execute([$userId]);
    }
    
    /**
     * Check if user has 2FA enabled
     * 
     * @param int $userId User ID
     * @return bool True if enabled
     */
    public function isEnabled($userId)
    {
        $stmt = $this->db->prepare("
            SELECT enabled
            FROM user_2fa
            WHERE user_id = ?
        ");
        
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result && $result['enabled'];
    }
    
    /**
     * Get user's 2FA secret
     * 
     * @param int $userId User ID
     * @return string|null Secret or null
     */
    public function getSecret($userId)
    {
        $stmt = $this->db->prepare("
            SELECT secret
            FROM user_2fa
            WHERE user_id = ? AND enabled = TRUE
        ");
        
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ? $result['secret'] : null;
    }
    
    /**
     * Verify backup code
     * 
     * @param int $userId User ID
     * @param string $code Backup code
     * @return bool True if valid
     */
    public function verifyBackupCode($userId, $code)
    {
        $stmt = $this->db->prepare("
            SELECT backup_codes
            FROM user_2fa
            WHERE user_id = ? AND enabled = TRUE
        ");
        
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result) {
            return false;
        }
        
        $backupCodes = json_decode($result['backup_codes'], true);
        
        if (!$backupCodes || !in_array($code, $backupCodes)) {
            return false;
        }
        
        // Remove used backup code
        $backupCodes = array_diff($backupCodes, [$code]);
        
        $stmt = $this->db->prepare("
            UPDATE user_2fa
            SET backup_codes = ?
            WHERE user_id = ?
        ");
        
        $stmt->execute([
            json_encode(array_values($backupCodes)),
            $userId
        ]);
        
        return true;
    }
}
