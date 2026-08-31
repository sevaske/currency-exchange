<?php

namespace App\Tests\Unit\Currency\Provider;

use App\Currency\Exception\CurrencyProviderException;
use App\Currency\Provider\CoinPaprikaProvider;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class CoinPaprikaProviderTest extends TestCase
{
    private function createProvider(array $marketsResponse, ?LoggerInterface $logger = null): CoinPaprikaProvider
    {
        $httpClient = new MockHttpClient([
            new MockResponse(json_encode($marketsResponse)),
        ]);

        return new CoinPaprikaProvider(
            $httpClient,
            $logger ?? new NullLogger(),
            'https://api.coinpaprika.com/v1/exchanges/coinbase/markets',
        );
    }

    public function testFetchRatesInvertsPrice(): void
    {
        $provider = $this->createProvider([
            ['pair' => 'BTC/USD', 'quotes' => ['USD' => ['price' => 78900]]],
        ]);

        $rates = $provider->fetchRates('USD');
        $expectedRate = BigDecimal::one()->dividedBy(78900, 20, RoundingMode::HalfUp);

        self::assertArrayHasKey('BTC', $rates);
        self::assertSame((string) $expectedRate, $rates['BTC']);
    }

    public function testFetchRatesSkipsZeroPrice(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('warning');

        $provider = $this->createProvider([
            ['pair' => 'DEAD/USD', 'quotes' => ['USD' => ['price' => 0]]],
            ['pair' => 'BTC/USD', 'quotes' => ['USD' => ['price' => 78900]]],
        ], $logger);

        $rates = $provider->fetchRates('USD');

        self::assertArrayNotHasKey('DEAD', $rates);
        self::assertArrayHasKey('BTC', $rates);
    }

    public function testFetchRatesSkipsNegativePrice(): void
    {
        $provider = $this->createProvider([
            ['pair' => 'WEIRD/USD', 'quotes' => ['USD' => ['price' => -10]]],
        ]);

        $rates = $provider->fetchRates('USD');

        self::assertArrayNotHasKey('WEIRD', $rates);
    }

    public function testFetchRatesSkipsMissingPriceField(): void
    {
        $provider = $this->createProvider([
            ['pair' => 'BTC/USD', 'quotes' => []],
        ]);

        $rates = $provider->fetchRates('USD');

        self::assertSame([], $rates);
    }

    public function testFetchRatesSkipsMissingPairField(): void
    {
        $provider = $this->createProvider([
            ['quotes' => ['USD' => ['price' => 78900]]],
        ]);

        $rates = $provider->fetchRates('USD');

        self::assertSame([], $rates);
    }

    public function testFetchRatesSkipsMalformedPairWithoutSlash(): void
    {
        $provider = $this->createProvider([
            ['pair' => 'BTCUSD', 'quotes' => ['USD' => ['price' => 78900]]],
        ]);

        $rates = $provider->fetchRates('USD');

        self::assertSame([], $rates);
    }

    public function testFetchRatesFiltersByQuoteCurrency(): void
    {
        $provider = $this->createProvider([
            ['pair' => 'BTC/EUR', 'quotes' => ['USD' => ['price' => 78900]]],
        ]);

        $rates = $provider->fetchRates('USD');

        self::assertSame([], $rates);
    }

    public function testFetchRatesKeepsFirstEntryOnDuplicateBase(): void
    {
        $provider = $this->createProvider([
            ['pair' => 'BTC/USD', 'quotes' => ['USD' => ['price' => 78900]]],
            ['pair' => 'BTC/USD', 'quotes' => ['USD' => ['price' => 99999]]],
        ]);

        $rates = $provider->fetchRates('USD');

        self::assertEqualsWithDelta(1 / 78900, (float) $rates['BTC'], 1e-12);
    }

    public function testFetchRatesThrowsCurrencyProviderExceptionOnTransportFailure(): void
    {
        $httpClient = new MockHttpClient(static function () {
            throw new TransportException('Connection failed.');
        });

        $provider = new CoinPaprikaProvider(
            $httpClient,
            new NullLogger(),
            'https://api.coinpaprika.com/v1/exchanges/coinbase/markets',
        );

        $this->expectException(CurrencyProviderException::class);
        $provider->fetchRates('USD');
    }

    public function testGetNameReturnsProviderIdentifier(): void
    {
        $provider = $this->createProvider([]);

        self::assertSame('coinpaprika', $provider->getName());
    }
}
