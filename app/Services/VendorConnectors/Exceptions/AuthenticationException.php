<?php

namespace App\Services\VendorConnectors\Exceptions;

class AuthenticationException extends VendorApiException
{
    public function __construct(
        string $vendor,
        string $message = "Authentication failed",
        int $code = 401,
        \Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous, $vendor, 'authentication');
    }
}
