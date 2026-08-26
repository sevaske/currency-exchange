<?php

namespace App\Currency\Storage;

interface CurrencyRatesReaderInterface
{
    /**
     * @return array{
     *     provider: string,
     *     base_currency: string,
     *     updated_at: string,
     *     rates: array<string, string>
     * }
     */
    public function read(string $providerName): array;
}
