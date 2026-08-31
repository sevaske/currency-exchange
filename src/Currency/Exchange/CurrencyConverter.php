<?php

namespace App\Currency\Exchange;

use Brick\Math\RoundingMode;
use Brick\Money\Currency;
use Brick\Money\CurrencyConverter as BrickCurrencyConverter;
use Brick\Money\Money;

readonly class CurrencyConverter
{
    private BrickCurrencyConverter $converter;

    public function __construct(FileExchangeRateProvider $rateProvider)
    {
        $this->converter = new BrickCurrencyConverter($rateProvider);
    }

    public function convert(
        Money $money,
        Currency|string $targetCurrencyCode,
        RoundingMode $roundingMode = RoundingMode::HalfUp,
    ): Money {
        return $this->converter->convert(money: $money, currency: $targetCurrencyCode, roundingMode: $roundingMode);
    }
}
