<?php

namespace App\Currency\Provider;

use App\Currency\Exception\CurrencyProviderException;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

readonly class FloatRatesProvider implements CurrencyProviderInterface
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        private string $baseUrl = 'https://www.floatrates.com/daily',
        private string $baseCurrency = 'usd',
    ) {
    }

    public function getName(): string
    {
        return 'floatrates';
    }

    public function fetchRates(): array
    {
        try {
            $response = $this->httpClient->request('GET', $this->getApiUrl());
            $items = $response->toArray();
        } catch (TransportExceptionInterface|ClientExceptionInterface|DecodingExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface $e) {
            throw new CurrencyProviderException(providerName: $this->getName(), previous: $e);
        }

        $pairs = [];

        foreach ($items as $item) {
            if (!isset($item['code'], $item['rate'])) {
                $this->logger->warning('Unexpected currency rate item format.', [
                    'provider' => $this->getName(),
                    'item' => $item,
                ]);

                continue;
            }

            $pairs[$item['code']] = (string) $item['rate'];
        }

        return $pairs;
    }

    private function getApiUrl(): string
    {
        $currency = strtolower($this->baseCurrency);

        return $this->baseUrl.'/'.$currency.'.json';
    }
}
