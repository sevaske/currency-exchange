<?php

namespace App\Currency\Storage;

interface CurrencyRatesReaderInterface
{
    public function read(string $baseCurrency = 'usd'): CurrencyRatesData;
}
