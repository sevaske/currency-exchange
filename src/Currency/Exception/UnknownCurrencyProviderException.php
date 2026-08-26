<?php

namespace App\Currency\Exception;

final class UnknownCurrencyProviderException extends CurrencyProviderException
{
    public function __construct(string $providerName)
    {
        parent::__construct($providerName, "Unknown currency provider: $providerName");
    }
}
