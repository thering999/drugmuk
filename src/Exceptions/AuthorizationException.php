<?php

namespace App\Exceptions;

/**
 * Authorization Exception
 * 
 * Thrown when user doesn't have permission
 */
class AuthorizationException extends BaseException
{
    protected int $statusCode = 403;

    public function __construct(string $message = 'Access forbidden', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
