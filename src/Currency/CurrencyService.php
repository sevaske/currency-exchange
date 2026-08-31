<?php

namespace App\Currency;

use App\Currency\Exception\CurrencyException;
use App\Currency\Exchange\CurrencyConversionResult;
use App\Currency\Exchange\CurrencyConverter;
use App\Currency\Exchange\CurrencyRate;
use App\Currency\Exchange\FileExchangeRateProvider;
use App\Currency\Storage\CurrencyRatesReaderInterface;
use Brick\Math\Exception\MathException;
use Brick\Math\RoundingMode;
use Brick\Money\Money;

final readonly class CurrencyService
{
    public function __construct(
        private CurrencyRatesReaderInterface $reader,
        private FileExchangeRateProvider $exchangeRateProvider,
        private CurrencyConverter $converter,
        private CurrencyRegistry $currencyRegistry,
        private string $storedBaseCurrency = 'USD',
    ) {
    }

    /**
     * @return CurrencyRate[]
     * @throws MathException
     */
    public function getRates(string $baseCurrency): array
    {
        $rates = [];
        $data = $this->reader->read();
        $codes = [...array_keys($data->rates), $data->baseCurrency];
        $sourceCurrency = $this->currencyRegistry->get(strtoupper($baseCurrency));

        foreach (array_unique($codes) as $code) {
            $rate = $this->exchangeRateProvider->getExchangeRate($sourceCurrency, $this->currencyRegistry->get($code));

            if (null === $rate) {
                continue;
            }

            $rates[] = new CurrencyRate($code, $rate->toFloat());
        }

        return $rates;
    }

    /**
     * @throws MathException
     */
    public function convert(string $amount, string $fromCurrency, string $toCurrency): ?CurrencyConversionResult
    {
        $from = $this->currencyRegistry->get($fromCurrency);
        $to = $this->currencyRegistry->get($toCurrency);
        $base = $this->currencyRegistry->get($this->storedBaseCurrency);

        $fromRate = $this->exchangeRateProvider->getExchangeRate($base, $from);
        $toRate = $this->exchangeRateProvider->getExchangeRate($base, $to);

        if (null === $fromRate || null === $toRate) {
            return null;
        }

        try {
            $money = Money::of($amount, $from, roundingMode: RoundingMode::HalfUp);
        } catch (MathException $e) {
            throw new CurrencyException(message: 'Cannot convert amount.', previous: $e);
        }

        $converted = $this->converter->convert($money, $to);

        return new CurrencyConversionResult(
            amount: $converted->getAmount()->toFloat(),
            currencyFrom: new CurrencyRate($from->getCurrencyCode(), $fromRate->toFloat()),
            currencyTo: new CurrencyRate($to->getCurrencyCode(), $toRate->toFloat()),
        );
    }
}
