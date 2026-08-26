<?php

namespace App\Currency\Exchange;

use Brick\Math\RoundingMode;
use Brick\Money\CurrencyConverter as BrickCurrencyConverter;
use Brick\Money\Money;

final readonly class CurrencyConverter
{
    private BrickCurrencyConverter $converter;

    public function __construct(FileExchangeRateProvider $rateProvider)
    {
        $this->converter = new BrickCurrencyConverter($rateProvider);
    }

    public function convert(
        Money $money,
        string $targetCurrencyCode,
        RoundingMode $roundingMode = RoundingMode::HalfUp,
    ): Money {
        return $this->converter->convert($money, $targetCurrencyCode, roundingMode: $roundingMode);
    }
}
