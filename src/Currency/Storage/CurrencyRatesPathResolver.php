<?php

namespace App\Currency\Storage;

class CurrencyRatesPathResolver
{
    public function resolve(string $providerName, string $baseCurrency = 'usd'): string
    {
        return "rates/$providerName/$baseCurrency.json";
    }
}
