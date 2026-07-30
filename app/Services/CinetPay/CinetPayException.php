<?php

namespace App\Services\CinetPay;

use Exception;

class CinetPayException extends Exception
{
    private ?string $apiCode;

    public function __construct(string $message, ?string $apiCode = null, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->apiCode = $apiCode;
    }

    public function getApiCode(): ?string
    {
        return $this->apiCode;
    }
}
