<?php

namespace App\Currency\Exchange;

final readonly class CurrencyRate implements \JsonSerializable
{
    public function __construct(
        public string $code,
        public float $rate,
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            'code' => $this->code,
            'rate' => $this->rate,
        ];
    }
}
