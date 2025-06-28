<?php

namespace App\Services\VendorConnectors\Exceptions;

use Exception;

class VendorApiException extends Exception
{
    protected $vendor;
    protected $operation;
    protected $apiResponse;

    public function __construct(
        string $message = "",
        int $code = 0,
        Exception $previous = null,
        string $vendor = null,
        string $operation = null,
        array $apiResponse = []
    ) {
        parent::__construct($message, $code, $previous);

        $this->vendor = $vendor;
        $this->operation = $operation;
        $this->apiResponse = $apiResponse;
    }

    public function getVendor(): ?string
    {
        return $this->vendor;
    }

    public function getOperation(): ?string
    {
        return $this->operation;
    }

    public function getApiResponse(): array
    {
        return $this->apiResponse;
    }

    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'vendor' => $this->vendor,
            'operation' => $this->operation,
            'api_response' => $this->apiResponse,
            'file' => $this->getFile(),
            'line' => $this->getLine()
        ];
    }
}
