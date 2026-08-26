<?php

namespace App\Currency\Exception;

class CurrencyProviderException extends CurrencyException
{
    public function __construct(
        private readonly string $providerName,
        ?string $message = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct(
            message: $message ?? "Failed to fetch currency rates from provider $providerName.",
            previous: $previous,
        );
    }

    public function getProviderName(): string
    {
        return $this->providerName;
    }
}
