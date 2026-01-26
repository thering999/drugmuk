<?php

namespace App\Exceptions;

/**
 * Business Logic Exception
 * 
 * Thrown when business rules are violated
 */
class BusinessLogicException extends BaseException
{
    protected int $statusCode = 400;

    public function __construct(string $message = 'Business rule violation', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
