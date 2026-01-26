<?php

namespace App\Exceptions;

/**
 * Authentication Exception
 * 
 * Thrown when authentication fails
 */
class AuthenticationException extends BaseException
{
    protected int $statusCode = 401;

    public function __construct(string $message = 'Authentication required', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
