<?php

namespace App\Tests\Currency\Exchange;

use App\Currency\Exchange\FileExchangeRateProvider;
use App\Currency\Storage\CurrencyRatesData;
use App\Currency\Storage\CurrencyRatesReaderInterface;
use Brick\Money\Currency;
use PHPUnit\Framework\TestCase;

final class FileExchangeRateProviderTest extends TestCase
{
    private function createProvider(array $rates): FileExchangeRateProvider
    {
        $data = new CurrencyRatesData(
            baseCurrency: 'USD',
            updatedAt: new \DateTimeImmutable(),
            rates: $rates,
        );

        $reader = $this->createStub(CurrencyRatesReaderInterface::class);
        $reader->method('read')->willReturn($data);

        return new FileExchangeRateProvider($reader, 'USD');
    }

    public function testExchangeRateBetweenTwoNonBaseCurrencies(): void
    {
        $provider = $this->createProvider([
            'EUR' => '0.85947077',
            'AMD' => '364.51086490',
        ]);

        $rate = $provider->getExchangeRate(Currency::of('EUR'), Currency::of('AMD'));

        // 1 EUR = (364.51.../0.8594...) AMD
        self::assertEqualsWithDelta(424.11, $rate->toFloat(), 0.01);
    }

    public function testExchangeRateWhenSourceIsBaseCurrency(): void
    {
        $provider = $this->createProvider(['EUR' => '0.85947077']);

        $rate = $provider->getExchangeRate(Currency::of('USD'), Currency::of('EUR'));

        self::assertTrue($rate->isEqualTo('0.85947077'));
    }

    public function testExchangeRateWhenTargetIsBaseCurrency(): void
    {
        $provider = $this->createProvider(['EUR' => '0.85947077']);

        $rate = $provider->getExchangeRate(Currency::of('EUR'), Currency::of('USD'));

        self::assertEqualsWithDelta(1.1635, $rate->toFloat(), 0.0001);
    }

    public function testExchangeRateBetweenBaseCurrencyAndItself(): void
    {
        $provider = $this->createProvider([]);

        $rate = $provider->getExchangeRate(Currency::of('USD'), Currency::of('USD'));

        self::assertTrue($rate->isEqualTo(1));
    }

    public function testExchangeRateReturnsNullWhenSourceCurrencyUnknown(): void
    {
        $provider = $this->createProvider(['EUR' => '0.85947077']);

        $rate = $provider->getExchangeRate(Currency::of('AMD'), Currency::of('EUR'));

        self::assertNull($rate);
    }

    public function testExchangeRateReturnsNullWhenTargetCurrencyUnknown(): void
    {
        $provider = $this->createProvider(['EUR' => '0.85947077']);

        $rate = $provider->getExchangeRate(Currency::of('EUR'), Currency::of('AMD'));

        self::assertNull($rate);
    }

    public function testHasCurrencyReturnsTrueForRateInFile(): void
    {
        $provider = $this->createProvider(['EUR' => '0.85947077']);

        self::assertTrue($provider->hasCurrency('EUR'));
    }

    public function testHasCurrencyReturnsTrueForBaseCurrency(): void
    {
        $provider = $this->createProvider(['EUR' => '0.85947077']);

        self::assertTrue($provider->hasCurrency('USD'));
    }

    public function testHasCurrencyReturnsFalseForUnknownCode(): void
    {
        $provider = $this->createProvider(['EUR' => '0.85947077']);

        self::assertFalse($provider->hasCurrency('XYZ'));
    }

    public function testHasCurrencyIsCaseInsensitive(): void
    {
        $provider = $this->createProvider(['EUR' => '0.85947077']);

        self::assertTrue($provider->hasCurrency('eur'));
    }

    public function testReaderIsCalledOnlyOncePerProviderInstance(): void
    {
        $data = new CurrencyRatesData(
            baseCurrency: 'USD',
            updatedAt: new \DateTimeImmutable(),
            rates: ['EUR' => '0.85947077', 'AMD' => '364.51086490'],
        );

        $reader = $this->createMock(CurrencyRatesReaderInterface::class);
        $reader->expects($this->once())
            ->method('read')
            ->willReturn($data);

        $provider = new FileExchangeRateProvider($reader, 'USD');

        $provider->getExchangeRate(Currency::of('USD'), Currency::of('EUR'));
        $provider->getExchangeRate(Currency::of('EUR'), Currency::of('AMD'));
        $provider->hasCurrency('EUR');
    }
}
