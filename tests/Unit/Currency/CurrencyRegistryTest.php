<?php

namespace App\Tests\Unit\Currency;

use App\Currency\CurrencyRegistry;
use App\Currency\Exchange\FileExchangeRateProvider;
use Brick\Money\Exception\UnknownCurrencyException;
use PHPUnit\Framework\TestCase;

final class CurrencyRegistryTest extends TestCase
{
    public function testGetResolvesIsoCurrencyWithoutCheckingExchangeRateProvider(): void
    {
        $exchangeRateProvider = $this->createMock(FileExchangeRateProvider::class);
        $exchangeRateProvider->expects($this->never())->method('hasCurrency');

        $registry = new CurrencyRegistry($exchangeRateProvider);
        $currency = $registry->get('EUR');

        self::assertSame('EUR', $currency->getCurrencyCode());
    }

    public function testGetIsCaseInsensitiveForIsoCurrency(): void
    {
        $exchangeRateProvider = $this->createMock(FileExchangeRateProvider::class);
        $exchangeRateProvider->expects($this->never())->method('hasCurrency');

        $registry = new CurrencyRegistry($exchangeRateProvider);
        $currency = $registry->get('eur');

        self::assertSame('EUR', $currency->getCurrencyCode());
    }

    public function testGetCreatesCustomCurrencyWhenKnownToExchangeRateProvider(): void
    {
        $exchangeRateProvider = $this->createMock(FileExchangeRateProvider::class);
        $exchangeRateProvider->expects($this->once())->method('hasCurrency')->with('BTC')->willReturn(true);

        $registry = new CurrencyRegistry($exchangeRateProvider);
        $currency = $registry->get('BTC');

        self::assertSame('BTC', $currency->getCurrencyCode());
        self::assertSame(8, $currency->getDefaultFractionDigits());
    }

    public function testGetThrowsWhenCurrencyUnknownToExchangeRateProvider(): void
    {
        $exchangeRateProvider = $this->createMock(FileExchangeRateProvider::class);
        $exchangeRateProvider->expects($this->once())->method('hasCurrency')->with('XYZ')->willReturn(false);

        $registry = new CurrencyRegistry($exchangeRateProvider);

        $this->expectException(UnknownCurrencyException::class);
        $registry->get('XYZ');
    }

    public function testGetCachesResultAndDoesNotCallExchangeRateProviderTwice(): void
    {
        $exchangeRateProvider = $this->createMock(FileExchangeRateProvider::class);
        $exchangeRateProvider->expects($this->once())
            ->method('hasCurrency')
            ->with('BTC')
            ->willReturn(true);

        $registry = new CurrencyRegistry($exchangeRateProvider);
        $registry->get('BTC');
        $registry->get('BTC');
        $registry->get('btc');
    }
}
