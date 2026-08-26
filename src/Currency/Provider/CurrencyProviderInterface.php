<?php

namespace App\Currency\Provider;

/**
 * USD Based.
 */
interface CurrencyProviderInterface
{
    public function getName(): string;

    /**
     * @return array<string, string>
     */
    public function fetchRates(string $baseCurrency = 'usd'): array;
}
