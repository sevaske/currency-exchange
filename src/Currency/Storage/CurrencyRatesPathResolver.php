<?php

namespace App\Currency\Storage;

class CurrencyRatesPathResolver
{
    public function resolve(string $providerName): string
    {
        return "rates/$providerName.json";
    }
}
