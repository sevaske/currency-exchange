<?php

namespace App\Currency\Exchange;

final readonly class CurrencyConversionResult implements \JsonSerializable
{
    public function __construct(
        public float $amount,
        public CurrencyRate $currencyFrom,
        public CurrencyRate $currencyTo,
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            'amount' => $this->amount,
            'currency_from' => $this->currencyFrom,
            'currency_to' => $this->currencyTo,
        ];
    }
}
