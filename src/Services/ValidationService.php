<?php

namespace App\Services;

/**
 * Validation Service
 * 
 * Provides centralized input validation with common rules
 */
class ValidationService
{
    private array $errors = [];

    /**
     * Validate integer
     * 
     * @param mixed $value
     * @param int|null $min
     * @param int|null $max
     * @return int|null
     */
    public static function validateInt($value, ?int $min = null, ?int $max = null): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_numeric($value)) {
            return null;
        }

        $int = (int) $value;

        if ($min !== null && $int < $min) {
            return null;
        }

        if ($max !== null && $int > $max) {
            return null;
        }

        return $int;
    }

    /**
     * Validate float
     * 
     * @param mixed $value
     * @param float|null $min
     * @param float|null $max
     * @return float|null
     */
    public static function validateFloat($value, ?float $min = null, ?float $max = null): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_numeric($value)) {
            return null;
        }

        $float = (float) $value;

        if ($min !== null && $float < $min) {
            return null;
        }

        if ($max !== null && $float > $max) {
            return null;
        }

        return $float;
    }

    /**
     * Validate string
     * 
     * @param mixed $value
     * @param int|null $minLength
     * @param int|null $maxLength
     * @return string|null
     */
    public static function validateString($value, ?int $minLength = null, ?int $maxLength = null): ?string
    {
        if ($value === null) {
            return null;
        }

        $string = (string) $value;
        $length = mb_strlen($string);

        if ($minLength !== null && $length < $minLength) {
            return null;
        }

        if ($maxLength !== null && $length > $maxLength) {
            return null;
        }

        return $string;
    }

    /**
     * Validate email
     * 
     * @param mixed $value
     * @return string|null
     */
    public static function validateEmail($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $email = filter_var($value, FILTER_VALIDATE_EMAIL);
        return $email !== false ? $email : null;
    }

    /**
     * Validate URL
     * 
     * @param mixed $value
     * @return string|null
     */
    public static function validateUrl($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $url = filter_var($value, FILTER_VALIDATE_URL);
        return $url !== false ? $url : null;
    }

    /**
     * Validate date
     * 
     * @param mixed $value
     * @param string $format
     * @return string|null
     */
    public static function validateDate($value, string $format = 'Y-m-d'): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $date = \DateTime::createFromFormat($format, $value);
        
        if ($date && $date->format($format) === $value) {
            return $value;
        }

        return null;
    }

    /**
     * Validate boolean
     * 
     * @param mixed $value
     * @return bool
     */
    public static function validateBool($value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
    }

    /**
     * Validate array
     * 
     * @param mixed $value
     * @return array
     */
    public static function validateArray($value): array
    {
        if (!is_array($value)) {
            return [];
        }

        return $value;
    }

    /**
     * Validate required field
     * 
     * @param mixed $value
     * @param string $fieldName
     * @return bool
     */
    public function required($value, string $fieldName): bool
    {
        if ($value === null || $value === '' || (is_array($value) && empty($value))) {
            $this->errors[$fieldName] = "$fieldName is required";
            return false;
        }

        return true;
    }

    /**
     * Validate field with rules
     * 
     * @param array $data
     * @param array $rules
     * @return bool
     */
    public function validate(array $data, array $rules): bool
    {
        $this->errors = [];

        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;
            $rulesArray = explode('|', $fieldRules);

            foreach ($rulesArray as $rule) {
                $this->applyRule($field, $value, $rule);
            }
        }

        return empty($this->errors);
    }

    /**
     * Apply validation rule
     * 
     * @param string $field
     * @param mixed $value
     * @param string $rule
     * @return void
     */
    private function applyRule(string $field, $value, string $rule): void
    {
        // Parse rule with parameters (e.g., "min:5")
        $parts = explode(':', $rule);
        $ruleName = $parts[0];
        $params = $parts[1] ?? null;

        switch ($ruleName) {
            case 'required':
                if ($value === null || $value === '') {
                    $this->errors[$field] = "$field is required";
                }
                break;

            case 'email':
                if ($value && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->errors[$field] = "$field must be a valid email";
                }
                break;

            case 'min':
                if (is_string($value) && mb_strlen($value) < (int)$params) {
                    $this->errors[$field] = "$field must be at least $params characters";
                } elseif (is_numeric($value) && $value < (int)$params) {
                    $this->errors[$field] = "$field must be at least $params";
                }
                break;

            case 'max':
                if (is_string($value) && mb_strlen($value) > (int)$params) {
                    $this->errors[$field] = "$field must not exceed $params characters";
                } elseif (is_numeric($value) && $value > (int)$params) {
                    $this->errors[$field] = "$field must not exceed $params";
                }
                break;

            case 'numeric':
                if ($value && !is_numeric($value)) {
                    $this->errors[$field] = "$field must be numeric";
                }
                break;

            case 'integer':
                if ($value && !filter_var($value, FILTER_VALIDATE_INT)) {
                    $this->errors[$field] = "$field must be an integer";
                }
                break;

            case 'url':
                if ($value && !filter_var($value, FILTER_VALIDATE_URL)) {
                    $this->errors[$field] = "$field must be a valid URL";
                }
                break;

            case 'date':
                $format = $params ?? 'Y-m-d';
                if ($value && !self::validateDate($value, $format)) {
                    $this->errors[$field] = "$field must be a valid date ($format)";
                }
                break;

            case 'in':
                $allowed = explode(',', $params);
                if ($value && !in_array($value, $allowed)) {
                    $this->errors[$field] = "$field must be one of: " . implode(', ', $allowed);
                }
                break;

            case 'regex':
                if ($value && !preg_match($params, $value)) {
                    $this->errors[$field] = "$field format is invalid";
                }
                break;
        }
    }

    /**
     * Get validation errors
     * 
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Check if validation failed
     * 
     * @return bool
     */
    public function fails(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Validate Thai phone number
     * 
     * @param mixed $value
     * @return string|null
     */
    public static function validateThaiPhone($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        // Remove spaces and dashes
        $phone = preg_replace('/[\s\-]/', '', $value);

        // Thai phone: 10 digits starting with 0
        if (preg_match('/^0[0-9]{9}$/', $phone)) {
            return $phone;
        }

        return null;
    }

    /**
     * Validate Thai ID card number
     * 
     * @param mixed $value
     * @return string|null
     */
    public static function validateThaiID($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        // Remove spaces and dashes
        $id = preg_replace('/[\s\-]/', '', $value);

        // Must be 13 digits
        if (!preg_match('/^[0-9]{13}$/', $id)) {
            return null;
        }

        // Validate checksum
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += (int)$id[$i] * (13 - $i);
        }
        $checksum = (11 - ($sum % 11)) % 10;

        if ($checksum != (int)$id[12]) {
            return null;
        }

        return $id;
    }

    /**
     * Validate TMT code (13 digits)
     * 
     * @param mixed $value
     * @return string|null
     */
    public static function validateTMT($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $tmt = preg_replace('/[\s\-]/', '', $value);

        if (preg_match('/^[0-9]{13}$/', $tmt)) {
            return $tmt;
        }

        return null;
    }
}
