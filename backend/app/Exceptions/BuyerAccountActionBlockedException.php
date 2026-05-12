<?php

namespace App\Exceptions;

use RuntimeException;

class BuyerAccountActionBlockedException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly string $errorCode = 'ACCOUNT_ACTION_BLOCKED',
        private readonly array $requirements = [],
    ) {
        parent::__construct($message);
    }

    public function errorCode(): string
    {
        return $this->errorCode;
    }

    public function requirements(): array
    {
        return $this->requirements;
    }
}
