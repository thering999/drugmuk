<?php

namespace App\Exceptions;

/**
 * Validation Exception
 * 
 * Thrown when input validation fails
 */
class ValidationException extends BaseException
{
    protected int $statusCode = 422;
    private array $errors = [];

    public function __construct(string $message = 'Validation failed', array $errors = [], int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
