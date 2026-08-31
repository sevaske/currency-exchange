<?php

namespace App\Tests\Unit\Currency\Storage;

use App\Currency\Storage\CurrencyRatesData;
use PHPUnit\Framework\TestCase;

final class CurrencyRatesDataTest extends TestCase
{
    public function testFromArrayCreatesDataFromValidPayload(): void
    {
        $data = CurrencyRatesData::fromArray([
            'base_currency' => 'usd',
            'updated_at' => '2026-08-30T14:06:34+00:00',
            'rates' => ['EUR' => '0.85947077', 'AMD' => '364.51086490'],
        ]);

        self::assertSame('USD', $data->baseCurrency);
        self::assertSame('2026-08-30T14:06:34+00:00', $data->updatedAt->format(DATE_ATOM));
        self::assertSame(['EUR' => '0.85947077', 'AMD' => '364.51086490'], $data->rates);
    }

    public function testFromArrayThrowsWhenBaseCurrencyMissing(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        CurrencyRatesData::fromArray([
            'updated_at' => '2026-08-30T14:06:34+00:00',
            'rates' => ['EUR' => '0.85947077'],
        ]);
    }

    public function testFromArrayThrowsWhenBaseCurrencyIsEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        CurrencyRatesData::fromArray([
            'base_currency' => '',
            'updated_at' => '2026-08-30T14:06:34+00:00',
            'rates' => ['EUR' => '0.85947077'],
        ]);
    }

    public function testFromArrayThrowsWhenUpdatedAtMissing(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        CurrencyRatesData::fromArray([
            'base_currency' => 'usd',
            'rates' => ['EUR' => '0.85947077'],
        ]);
    }

    public function testFromArrayThrowsWhenUpdatedAtIsInvalidFormat(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        CurrencyRatesData::fromArray([
            'base_currency' => 'usd',
            'updated_at' => 'not-a-date',
            'rates' => ['EUR' => '0.85947077'],
        ]);
    }

    public function testFromArrayThrowsWhenRatesMissing(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        CurrencyRatesData::fromArray([
            'base_currency' => 'usd',
            'updated_at' => '2026-08-30T14:06:34+00:00',
        ]);
    }

    public function testFromArrayThrowsWhenRatesIsEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        CurrencyRatesData::fromArray([
            'base_currency' => 'usd',
            'updated_at' => '2026-08-30T14:06:34+00:00',
            'rates' => [],
        ]);
    }

    public function testJsonSerializeReturnsExpectedStructure(): void
    {
        $data = CurrencyRatesData::fromArray([
            'base_currency' => 'usd',
            'updated_at' => '2026-08-30T14:06:34+00:00',
            'rates' => ['EUR' => '0.85947077'],
        ]);

        self::assertSame([
            'base_currency' => 'USD',
            'updated_at' => '2026-08-30T14:06:34+00:00',
            'rates' => ['EUR' => '0.85947077'],
        ], $data->jsonSerialize());
    }
}
