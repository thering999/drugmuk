<?php

namespace App\Exceptions;

/**
 * Not Found Exception
 * 
 * Thrown when a resource is not found
 */
class NotFoundException extends BaseException
{
    protected int $statusCode = 404;

    public function __construct(string $message = 'Resource not found', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
