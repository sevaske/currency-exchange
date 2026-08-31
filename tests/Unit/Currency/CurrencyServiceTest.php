<?php

namespace App\Tests\Unit\Currency;

use App\Currency\CurrencyRegistry;
use App\Currency\CurrencyService;
use App\Currency\Exception\CurrencyException;
use App\Currency\Exchange\CurrencyConverter;
use App\Currency\Exchange\FileExchangeRateProvider;
use App\Currency\Storage\CurrencyRatesData;
use App\Currency\Storage\CurrencyRatesReaderInterface;
use Brick\Math\BigDecimal;
use Brick\Money\Currency;
use Brick\Money\Money;
use PHPUnit\Framework\TestCase;

final class CurrencyServiceTest extends TestCase
{
    private CurrencyRatesReaderInterface $reader;
    private FileExchangeRateProvider $exchangeRateProvider;
    private CurrencyConverter $converter;
    private CurrencyRegistry $currencyRegistry;

    protected function setUp(): void
    {
        $this->reader = $this->createStub(CurrencyRatesReaderInterface::class);
        $this->exchangeRateProvider = $this->createStub(FileExchangeRateProvider::class);
        $this->converter = $this->createStub(CurrencyConverter::class);
        $this->currencyRegistry = $this->createStub(CurrencyRegistry::class);
    }

    private function createService(): CurrencyService
    {
        return new CurrencyService(
            $this->reader,
            $this->exchangeRateProvider,
            $this->converter,
            $this->currencyRegistry,
        );
    }

    public function testGetRatesReturnsDifferentRateForEachCurrency(): void
    {
        $this->reader->method('read')->willReturn(new CurrencyRatesData(
            baseCurrency: 'USD',
            updatedAt: new \DateTimeImmutable(),
            rates: ['EUR' => '0.85947077', 'AMD' => '364.51086490'],
        ));

        $eur = Currency::of('EUR');
        $amd = Currency::of('AMD');
        $usd = Currency::of('USD');

        $this->currencyRegistry->method('get')->willReturnMap([
            ['EUR', $eur],
            ['AMD', $amd],
            ['USD', $usd],
        ]);

        $this->exchangeRateProvider->method('getExchangeRate')->willReturnMap([
            [$eur, $eur, [], BigDecimal::of(1)],
            [$eur, $amd, [], BigDecimal::of('424.11')],
            [$eur, $usd, [], BigDecimal::of('1.1635')],
        ]);

        $service = $this->createService();
        $rates = $service->getRates('EUR');

        $ratesByCode = [];
        foreach ($rates as $rate) {
            $ratesByCode[$rate->code] = $rate->rate;
        }

        self::assertSame(1.0, $ratesByCode['EUR']);
        self::assertEqualsWithDelta(424.11, $ratesByCode['AMD'], 0.01);
        self::assertEqualsWithDelta(1.1635, $ratesByCode['USD'], 0.0001);
    }

    public function testGetRatesSkipsCurrencyWithoutRate(): void
    {
        $this->reader->method('read')->willReturn(new CurrencyRatesData(
            baseCurrency: 'USD',
            updatedAt: new \DateTimeImmutable(),
            rates: ['EUR' => '0.85947077'],
        ));

        $eur = Currency::of('EUR');
        $usd = Currency::of('USD');

        $this->currencyRegistry->method('get')->willReturnMap([
            ['EUR', $eur],
            ['USD', $usd],
        ]);

        $this->exchangeRateProvider->method('getExchangeRate')->willReturnMap([
            [$eur, $eur, [], BigDecimal::of(1)],
            [$eur, $usd, [], null],
        ]);

        $service = $this->createService();
        $rates = $service->getRates('EUR');

        self::assertCount(1, $rates);
        self::assertSame('EUR', $rates[0]->code);
    }

    public function testConvertReturnsNullWhenFromRateUnavailable(): void
    {
        $usd = Currency::of('USD');
        $btc = new Currency('BTC', 0, 'BTC', 8);

        $this->currencyRegistry->method('get')->willReturnMap([
            ['BTC', $btc],
            ['USD', $usd],
        ]);

        $this->exchangeRateProvider->method('getExchangeRate')->willReturnMap([
            [$usd, $btc, [], null],
            [$usd, $usd, [], BigDecimal::of(1)],
        ]);

        $service = $this->createService();
        $result = $service->convert('1', 'BTC', 'USD');

        self::assertNull($result);
    }

    public function testConvertReturnsNullWhenToRateUnavailable(): void
    {
        $usd = Currency::of('USD');
        $eur = Currency::of('EUR');

        $this->currencyRegistry->method('get')->willReturnMap([
            ['EUR', $eur],
            ['USD', $usd],
        ]);

        $this->exchangeRateProvider->method('getExchangeRate')->willReturnMap([
            [$usd, $eur, [], BigDecimal::of('0.85947077')],
            [$usd, $usd, [], null],
        ]);

        $service = $this->createService();
        $result = $service->convert('1', 'EUR', 'USD');

        self::assertNull($result);
    }

    public function testConvertReturnsResultWithBothRates(): void
    {
        $usd = Currency::of('USD');
        $eur = Currency::of('EUR');

        $this->currencyRegistry->method('get')->willReturnMap([
            ['EUR', $eur],
            ['USD', $usd],
        ]);

        $this->exchangeRateProvider->method('getExchangeRate')->willReturnMap([
            [$usd, $eur, [], BigDecimal::of('0.85947077')],
            [$usd, $usd, [], BigDecimal::of(1)],
        ]);

        $convertedMoney = Money::of('85.95', $eur);
        $this->converter->method('convert')->willReturn($convertedMoney);

        $service = $this->createService();
        $result = $service->convert('100', 'EUR', 'USD');

        self::assertNotNull($result);
        self::assertSame(85.95, $result->amount);
        self::assertSame('EUR', $result->currencyFrom->code);
        self::assertEqualsWithDelta(0.85947077, $result->currencyFrom->rate, 0.00000001);
        self::assertSame('USD', $result->currencyTo->code);
        self::assertSame(1.0, $result->currencyTo->rate);
    }

    public function testConvertThrowsCurrencyExceptionOnInvalidAmount(): void
    {
        $usd = Currency::of('USD');
        $eur = Currency::of('EUR');

        $this->currencyRegistry->method('get')->willReturnMap([
            ['EUR', $eur],
            ['USD', $usd],
        ]);

        $this->exchangeRateProvider->method('getExchangeRate')->willReturnMap([
            [$usd, $eur, [], BigDecimal::of('0.85947077')],
            [$usd, $usd, [], BigDecimal::of(1)],
        ]);

        $service = $this->createService();

        $this->expectException(CurrencyException::class);
        $service->convert('not-a-number', 'EUR', 'USD');
    }
}
