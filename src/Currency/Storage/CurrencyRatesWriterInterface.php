<?php

namespace App\Currency\Storage;

interface CurrencyRatesWriterInterface
{
    /**
     * @param array<string, string> $rates
     */
    public function save(
        string $providerName,
        string $baseCurrency,
        array $rates,
    ): void;
}
