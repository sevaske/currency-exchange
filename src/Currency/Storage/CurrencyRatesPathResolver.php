<?php

namespace App\Currency\Storage;

class CurrencyRatesPathResolver
{
    public function resolve(string $baseCurrency = 'usd'): string
    {
        $baseCurrency = strtolower($baseCurrency);

        return $baseCurrency.'.json';
    }
}
