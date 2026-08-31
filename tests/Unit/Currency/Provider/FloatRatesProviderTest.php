<?php

namespace App\Tests\Unit\Currency\Provider;

use App\Currency\Exception\CurrencyProviderException;
use App\Currency\Provider\FloatRatesProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class FloatRatesProviderTest extends TestCase
{
    private function createProvider(array $response, ?LoggerInterface $logger = null): FloatRatesProvider
    {
        $httpClient = new MockHttpClient([
            new MockResponse(json_encode($response)),
        ]);

        return new FloatRatesProvider(
            $httpClient,
            $logger ?? new NullLogger(),
            'https://www.floatrates.com/daily',
        );
    }

    public function testFetchRatesReturnsRatesAsIs(): void
    {
        $provider = $this->createProvider([
            'eur' => ['code' => 'EUR', 'rate' => 0.85947077],
            'amd' => ['code' => 'AMD', 'rate' => 364.51086490],
        ]);

        $rates = $provider->fetchRates('usd');

        self::assertSame('0.85947077', $rates['EUR']);
        self::assertSame('364.5108649', $rates['AMD']);
    }

    public function testFetchRatesSkipsEntryMissingCode(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('warning');

        $provider = $this->createProvider([
            'eur' => ['rate' => 0.85947077],
        ], $logger);

        $rates = $provider->fetchRates('usd');

        self::assertSame([], $rates);
    }

    public function testFetchRatesSkipsEntryMissingRate(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('warning');

        $provider = $this->createProvider([
            'eur' => ['code' => 'EUR'],
        ], $logger);

        $rates = $provider->fetchRates('usd');

        self::assertSame([], $rates);
    }

    public function testFetchRatesBuildsUrlFromBaseCurrency(): void
    {
        $httpClient = new MockHttpClient(function (string $method, string $url) {
            self::assertSame('https://www.floatrates.com/daily/eur.json', $url);

            return new MockResponse(json_encode([]));
        });

        $provider = new FloatRatesProvider($httpClient, new NullLogger(), 'https://www.floatrates.com/daily');
        $provider->fetchRates('eur');
    }

    public function testFetchRatesThrowsCurrencyProviderExceptionOnTransportFailure(): void
    {
        $httpClient = new MockHttpClient(static function () {
            throw new TransportException('Connection failed.');
        });

        $provider = new FloatRatesProvider($httpClient, new NullLogger(), 'https://www.floatrates.com/daily');

        $this->expectException(CurrencyProviderException::class);
        $provider->fetchRates('usd');
    }

    public function testGetNameReturnsProviderIdentifier(): void
    {
        $provider = $this->createProvider([]);

        self::assertSame('floatrates', $provider->getName());
    }
}
