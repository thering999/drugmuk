<?php

namespace App\Exceptions;

/**
 * Database Exception
 * 
 * Thrown when database operations fail
 */
class DatabaseException extends BaseException
{
    protected int $statusCode = 500;

    public function __construct(string $message = 'Database error occurred', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
