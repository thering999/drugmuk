<?php

namespace App\Exceptions;

/**
 * Base Exception for all custom exceptions
 */
class BaseException extends \Exception
{
    protected int $statusCode = 500;

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
