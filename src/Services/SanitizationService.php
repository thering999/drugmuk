<?php

namespace App\Services;

/**
 * Sanitization Service
 * 
 * Provides input sanitization to prevent XSS and other injection attacks
 */
class SanitizationService
{
    /**
     * Sanitize string (remove HTML tags and special characters)
     * 
     * @param mixed $value
     * @return string
     */
    public static function sanitizeString($value): string
    {
        if ($value === null) {
            return '';
        }

        // Convert to string
        $string = (string) $value;

        // Remove HTML tags
        $string = strip_tags($string);

        // Convert special characters to HTML entities
        $string = htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Trim whitespace
        $string = trim($string);

        return $string;
    }

    /**
     * Sanitize HTML (allow safe HTML tags)
     * 
     * @param mixed $value
     * @param array $allowedTags
     * @return string
     */
    public static function sanitizeHTML($value, array $allowedTags = []): string
    {
        if ($value === null) {
            return '';
        }

        $string = (string) $value;

        // Default allowed tags for rich text
        if (empty($allowedTags)) {
            $allowedTags = ['p', 'br', 'strong', 'em', 'u', 'a', 'ul', 'ol', 'li'];
        }

        // Build allowed tags string
        $allowed = '<' . implode('><', $allowedTags) . '>';

        // Strip tags except allowed
        $string = strip_tags($string, $allowed);

        // Remove dangerous attributes
        $string = self::removeDangerousAttributes($string);

        return trim($string);
    }

    /**
     * Remove dangerous HTML attributes
     * 
     * @param string $html
     * @return string
     */
    private static function removeDangerousAttributes(string $html): string
    {
        // Remove event handlers (onclick, onload, etc.)
        $html = preg_replace('/\s*on\w+\s*=\s*["\'][^"\']*["\']/i', '', $html);

        // Remove javascript: protocol
        $html = preg_replace('/href\s*=\s*["\']javascript:[^"\']*["\']/i', '', $html);

        // Remove data: protocol
        $html = preg_replace('/src\s*=\s*["\']data:[^"\']*["\']/i', '', $html);

        return $html;
    }

    /**
     * Sanitize email
     * 
     * @param mixed $value
     * @return string
     */
    public static function sanitizeEmail($value): string
    {
        if ($value === null) {
            return '';
        }

        return filter_var($value, FILTER_SANITIZE_EMAIL) ?: '';
    }

    /**
     * Sanitize URL
     * 
     * @param mixed $value
     * @return string
     */
    public static function sanitizeUrl($value): string
    {
        if ($value === null) {
            return '';
        }

        return filter_var($value, FILTER_SANITIZE_URL) ?: '';
    }

    /**
     * Sanitize integer
     * 
     * @param mixed $value
     * @return int
     */
    public static function sanitizeInt($value): int
    {
        return (int) filter_var($value, FILTER_SANITIZE_NUMBER_INT);
    }

    /**
     * Sanitize float
     * 
     * @param mixed $value
     * @return float
     */
    public static function sanitizeFloat($value): float
    {
        return (float) filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    }

    /**
     * Sanitize filename
     * 
     * @param mixed $value
     * @return string
     */
    public static function sanitizeFilename($value): string
    {
        if ($value === null) {
            return '';
        }

        $filename = (string) $value;

        // Remove path separators
        $filename = str_replace(['/', '\\', '..'], '', $filename);

        // Remove special characters
        $filename = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $filename);

        // Limit length
        if (strlen($filename) > 255) {
            $filename = substr($filename, 0, 255);
        }

        return $filename;
    }

    /**
     * Sanitize array recursively
     * 
     * @param array $data
     * @param callable|null $sanitizer
     * @return array
     */
    public static function sanitizeArray(array $data, ?callable $sanitizer = null): array
    {
        $sanitizer = $sanitizer ?? [self::class, 'sanitizeString'];

        return array_map(function ($value) use ($sanitizer) {
            if (is_array($value)) {
                return self::sanitizeArray($value, $sanitizer);
            }
            return call_user_func($sanitizer, $value);
        }, $data);
    }

    /**
     * Sanitize SQL LIKE pattern
     * 
     * @param mixed $value
     * @return string
     */
    public static function sanitizeLikePattern($value): string
    {
        if ($value === null) {
            return '';
        }

        $string = (string) $value;

        // Escape special LIKE characters
        $string = str_replace(['%', '_'], ['\\%', '\\_'], $string);

        return $string;
    }

    /**
     * Sanitize phone number (keep only digits)
     * 
     * @param mixed $value
     * @return string
     */
    public static function sanitizePhone($value): string
    {
        if ($value === null) {
            return '';
        }

        // Keep only digits
        return preg_replace('/[^0-9]/', '', $value);
    }

    /**
     * Sanitize Thai text (remove non-Thai characters)
     * 
     * @param mixed $value
     * @return string
     */
    public static function sanitizeThaiText($value): string
    {
        if ($value === null) {
            return '';
        }

        $string = (string) $value;

        // Allow Thai characters, spaces, and common punctuation
        $string = preg_replace('/[^\p{Thai}\s\.,\-()]/u', '', $string);

        return trim($string);
    }

    /**
     * Remove null bytes
     * 
     * @param mixed $value
     * @return string
     */
    public static function removeNullBytes($value): string
    {
        if ($value === null) {
            return '';
        }

        return str_replace("\0", '', $value);
    }

    /**
     * Sanitize JSON
     * 
     * @param mixed $value
     * @return string
     */
    public static function sanitizeJSON($value): string
    {
        if ($value === null) {
            return '{}';
        }

        // If already a string, try to decode and re-encode
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return json_encode($decoded, JSON_UNESCAPED_UNICODE);
            }
            return '{}';
        }

        // If array or object, encode it
        return json_encode($value, JSON_UNESCAPED_UNICODE) ?: '{}';
    }

    /**
     * Sanitize multiple inputs at once
     * 
     * @param array $data
     * @param array $rules Format: ['field' => 'sanitizer_method']
     * @return array
     */
    public static function sanitizeMultiple(array $data, array $rules): array
    {
        $sanitized = [];

        foreach ($rules as $field => $method) {
            if (!isset($data[$field])) {
                continue;
            }

            $value = $data[$field];
            $methodName = 'sanitize' . ucfirst($method);

            if (method_exists(self::class, $methodName)) {
                $sanitized[$field] = self::$methodName($value);
            } else {
                $sanitized[$field] = self::sanitizeString($value);
            }
        }

        return $sanitized;
    }

    /**
     * Escape output for HTML
     * 
     * @param mixed $value
     * @return string
     */
    public static function escapeHTML($value): string
    {
        if ($value === null) {
            return '';
        }

        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Escape output for JavaScript
     * 
     * @param mixed $value
     * @return string
     */
    public static function escapeJS($value): string
    {
        if ($value === null) {
            return '';
        }

        return json_encode($value, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Escape output for URL
     * 
     * @param mixed $value
     * @return string
     */
    public static function escapeURL($value): string
    {
        if ($value === null) {
            return '';
        }

        return rawurlencode($value);
    }
}
