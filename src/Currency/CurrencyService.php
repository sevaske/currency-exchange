<?php

namespace App\Currency;

use App\Currency\Exception\CurrencyException;
use App\Currency\Exchange\CurrencyConversionResult;
use App\Currency\Exchange\CurrencyConverter;
use App\Currency\Exchange\CurrencyRate;
use App\Currency\Exchange\FileExchangeRateProvider;
use App\Currency\Storage\CurrencyRatesReaderInterface;
use Brick\Math\Exception\MathException;
use Brick\Money\Currency;
use Brick\Money\Money;

final readonly class CurrencyService
{
    public function __construct(
        private CurrencyRatesReaderInterface $reader,
        private FileExchangeRateProvider $exchangeRateProvider,
        private CurrencyConverter $converter,
        private string $providerName = 'floatrates',
        private string $storedBaseCurrency = 'USD',
    ) {
    }

    /**
     * @return CurrencyRate[]
     */
    public function getRates(string $baseCurrency): array
    {
        $rates = [];
        $data = $this->reader->read($this->providerName);
        $codes = [...array_keys($data['rates']), $this->storedBaseCurrency];
        $sourceCurrency = Currency::of(strtoupper($baseCurrency));

        foreach (array_unique($codes) as $code) {
            $rate = $this->exchangeRateProvider->getExchangeRate($sourceCurrency, Currency::of($code));

            if (null === $rate) {
                continue;
            }

            $rates[] = new CurrencyRate($code, $rate->toFloat());
        }

        return $rates;
    }

    public function convert(string $amount, string $fromCurrency, string $toCurrency): ?CurrencyConversionResult
    {
        $from = Currency::of($fromCurrency);
        $to = Currency::of($toCurrency);
        $rate = $this->exchangeRateProvider->getExchangeRate($to, $from);

        if (null === $rate) {
            return null;
        }

        try {
            $money = Money::of($amount, $from);
        } catch (MathException $e) {
            throw new CurrencyException(message: 'Cannot convert amount.', previous: $e);
        }

        $converted = $this->converter->convert($money, $to->getCurrencyCode());

        return new CurrencyConversionResult(
            amount: $converted->getAmount()->toFloat(),
            currencyFrom: new CurrencyRate($from->getCurrencyCode(), $rate->toFloat()),
            currencyTo: new CurrencyRate($to->getCurrencyCode(), 1.0),
        );
    }
}
