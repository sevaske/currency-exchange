<?php

namespace App\Currency\Storage;

interface CurrencyRatesWriterInterface
{
    /**
     * @param array<string, string> $rates
     */
    public function save(
        array $rates,
        string $baseCurrency = 'usd',
    ): void;
}
