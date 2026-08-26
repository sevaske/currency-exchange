<?php

namespace App\Currency\Provider;

use App\Currency\Exception\CurrencyProviderException;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

final class CoinPaprikaProvider extends AbstractCurrencyProvider
{
    public function getName(): string
    {
        return 'coinpaprika';
    }

    public function fetchRates(string $baseCurrency = 'usd'): array
    {
        try {
            $response = $this->httpClient->request('GET', $this->apiUrl, [
                'query' => [
                    'quotes' => $baseCurrency,
                ],
            ]);
            $items = $response->toArray();
        } catch (TransportExceptionInterface|ClientExceptionInterface|DecodingExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface $e) {
            throw new CurrencyProviderException(providerName: $this->getName(), previous: $e);
        }

        $rates = [];
        foreach ($items as $item) {
            $pair = $item['pair'] ?? null;
            $price = $item['quotes']['USD']['price'] ?? null;

            if (null === $pair || null === $price) {
                $this->logWarning(data: $item);

                continue;
            }

            [$base, $quote] = explode('/', $pair, 2);

            // keep only $baseCurrency currency
            if ($quote !== strtoupper($baseCurrency)) {
                continue;
            }

            $rates[$base] = (string) $price;
        }

        return $rates;
    }
}
