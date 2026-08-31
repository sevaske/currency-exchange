<?php

namespace App\Tests\Functional\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class CurrencyControllerTest extends WebTestCase
{
    public function testRatesReturnsSuccessfulResponse(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/rates?base=usd');

        self::assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent(), true);
        $ratesByCode = array_column($data, 'rate', 'code');

        self::assertEquals(1.0, $ratesByCode['USD']);
        self::assertEqualsWithDelta(0.85947077, $ratesByCode['EUR'], 0.00000001);
    }

    public function testRatesWithNonUsdBaseRecalculatesCrossRate(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/rates?base=eur');

        self::assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent(), true);
        $ratesByCode = array_column($data, 'rate', 'code');

        self::assertEqualsWithDelta(1.0, $ratesByCode['EUR'], 0.00000001);
        self::assertEqualsWithDelta(1.1635, $ratesByCode['USD'], 0.0001);
    }

    public function testRatesReturnsValidationErrorForUnknownBase(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/rates?base=xyz');

        self::assertResponseStatusCodeSame(422);
    }

    public function testConvertReturnsSuccessfulResponse(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/rates/convert?from=usd&to=eur&amount=100');

        self::assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent(), true);

        self::assertEqualsWithDelta(85.95, $data['amount'], 0.01);
        self::assertSame('USD', $data['currency_from']['code']);
        self::assertSame('EUR', $data['currency_to']['code']);
    }

    public function testConvertReturnsCryptoCurrencySuccessfully(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/rates/convert?from=btc&to=usd&amount=1');

        self::assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent(), true);

        self::assertSame('BTC', $data['currency_from']['code']);
        self::assertGreaterThan(0, $data['amount']);
    }

    public function testConvertReturnsValidationErrorForMissingAmount(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/rates/convert?from=usd&to=eur');

        self::assertResponseStatusCodeSame(422);
    }

    public function testConvertReturnsValidationErrorForUnknownCurrency(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/rates/convert?from=usd&to=xyz&amount=100');

        self::assertResponseStatusCodeSame(422);
    }

    public function testConvertReturnsValidationErrorForNegativeAmount(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/rates/convert?from=usd&to=eur&amount=-10');

        self::assertResponseStatusCodeSame(422);
    }
}
