<?php

namespace App\Currency\Provider;

use App\Currency\Exception\CurrencyProviderException;
use Brick\Math\BigDecimal;
use Brick\Math\Exception\MathException;
use Brick\Math\RoundingMode;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

final class CoinPaprikaProvider extends AbstractCurrencyProvider
{
    private const int DIVISION_SCALE = 20;

    public function getName(): string
    {
        return 'coinpaprika';
    }

    /**
     * @throws MathException
     */
    public function fetchRates(string $baseCurrency = 'USD'): array
    {
        $items = $this->fetchMarkets($baseCurrency);
        $rates = [];

        foreach ($items as $item) {
            $entry = $this->parseMarketEntry($item, $baseCurrency);

            if ($entry === null) {
                continue;
            }

            [$currencyCode, $price] = $entry;

            // already has rate
            if (isset($rates[$currencyCode])) {
                continue;
            }

            $rates[$currencyCode] = (string) BigDecimal::one()
                ->dividedBy($price, self::DIVISION_SCALE, RoundingMode::HalfUp);
        }

        return $rates;
    }

    private function fetchMarkets(string $baseCurrency): array
    {
        try {
            $response = $this->httpClient->request('GET', $this->apiUrl, [
                'query' => ['quotes' => $baseCurrency],
            ]);

            return $response->toArray();
        } catch (TransportExceptionInterface|ClientExceptionInterface|DecodingExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface $e) {
            throw new CurrencyProviderException(providerName: $this->getName(), previous: $e);
        }
    }

    /**
     * @return array{0: string, 1: BigDecimal}|null
     */
    private function parseMarketEntry(array $item, string $baseCurrency): ?array
    {
        $currencyCode = $this->extractCurrencyCode($item, $baseCurrency);

        if ($currencyCode === null) {
            $this->logWarning(data: $item);
            return null;
        }

        $price = $this->extractPrice($item);

        if ($price === null) {
            $this->logWarning(data: $item);
            return null;
        }

        return [$currencyCode, $price];
    }

    private function extractCurrencyCode(array $item, string $baseCurrency): ?string
    {
        $pair = $item['pair'] ?? null;

        if ($pair === null) {
            return null;
        }

        $parts = explode('/', $pair, 2);

        if (count($parts) !== 2) {
            return null;
        }

        [$currencyCode, $quoteCurrency] = $parts;

        if ($quoteCurrency !== strtoupper($baseCurrency)) {
            return null;
        }

        return $currencyCode;
    }

    private function extractPrice(array $item): ?BigDecimal
    {
        $rawPrice = $item['quotes']['USD']['price'] ?? null;

        if ($rawPrice === null) {
            return null;
        }

        try {
            $price = BigDecimal::of($rawPrice);
        } catch (MathException) {
            return null;
        }

        if ($price->isZero() || $price->isNegative()) {
            return null;
        }

        return $price;
    }
}
