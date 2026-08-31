<?php

namespace App\Currency;

use App\Currency\Exchange\FileExchangeRateProvider;
use Brick\Money\Currency;
use Brick\Money\Exception\UnknownCurrencyException;

final class CurrencyRegistry
{
    private const int CRYPTO_FRACTION_DIGITS = 8;

    /** @var array<string, Currency> */
    private array $cache = [];

    public function __construct(
        private readonly FileExchangeRateProvider $exchangeRateProvider,
    ) {
    }

    public function get(string $code): Currency
    {
        $code = strtoupper($code);

        return $this->cache[$code] ??= $this->resolve($code);
    }

    private function resolve(string $code): Currency
    {
        try {
            return Currency::of($code);
        } catch (UnknownCurrencyException) {
            if (!$this->exchangeRateProvider->hasCurrency($code)) {
                throw UnknownCurrencyException::unknownCurrencyCode($code);
            }

            return new Currency($code, 0, $code, self::CRYPTO_FRACTION_DIGITS);
        }
    }
}
