<?php

namespace App\Currency\Exchange;

use App\Currency\Storage\CurrencyRatesReaderInterface;
use Brick\Math\BigDecimal;
use Brick\Math\BigNumber;
use Brick\Math\RoundingMode;
use Brick\Money\Currency;
use Brick\Money\ExchangeRateProvider;

final class FileExchangeRateProvider implements ExchangeRateProvider
{
    private const int DIVISION_SCALE = 20;

    private ?array $rates = null;

    public function __construct(
        private readonly CurrencyRatesReaderInterface $reader,
        private readonly string $providerName,
        private readonly string $baseCurrency = 'usd',
    ) {
    }

    public function getExchangeRate(
        Currency $sourceCurrency,
        Currency $targetCurrency,
        array $dimensions = [],
    ): ?BigNumber {
        $rates = $this->getRates();

        // no source rate
        if (!$this->hasRate($rates, $sourceCurrency->getCurrencyCode())) {
            return null;
        }

        // no target rate
        if (!$this->hasRate($rates, $targetCurrency->getCurrencyCode())) {
            return null;
        }

        $sourceRate = BigDecimal::of(
            $this->rateOf($rates, $sourceCurrency->getCurrencyCode()),
        );

        $targetRate = BigDecimal::of(
            $this->rateOf($rates, $targetCurrency->getCurrencyCode()),
        );

        return $targetRate->dividedBy(
            $sourceRate,
            self::DIVISION_SCALE,
            RoundingMode::HalfUp,
        );
    }

    private function hasRate(array $rates, string $currencyCode): bool
    {
        return $this->isBaseCurrency($currencyCode) || isset($rates[$currencyCode]);
    }

    private function rateOf(array $rates, string $currencyCode): string
    {
        return $this->isBaseCurrency($currencyCode) ? '1' : $rates[$currencyCode];
    }

    private function isBaseCurrency(string $currencyCode): bool
    {
        return strtoupper($currencyCode) === strtoupper($this->baseCurrency);
    }

    private function getRates(): array
    {
        if (null === $this->rates) {
            $data = $this->reader->read($this->providerName);
            $this->rates = $data['rates'];
        }

        return $this->rates;
    }
}
