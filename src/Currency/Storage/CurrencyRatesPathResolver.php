<?php

namespace App\Currency\Storage;

class CurrencyRatesPathResolver
{
    public function resolve(string $baseCurrency = 'usd'): string
    {
        return $baseCurrency.'.json';
    }
}
