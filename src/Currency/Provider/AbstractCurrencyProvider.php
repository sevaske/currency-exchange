<?php

namespace App\Currency\Provider;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

abstract class AbstractCurrencyProvider implements CurrencyProviderInterface
{
    public function __construct(
        protected HttpClientInterface $httpClient,
        protected LoggerInterface $logger,
        protected readonly string $apiUrl,
    ) {
    }

    protected function logWarning(string $message = 'Unexpected currency rate issue.', mixed $data = null): void
    {
        $this->logger->warning($message, [
            'provider' => $this->getName(),
            'item' => $data,
            'api_url' => $this->apiUrl,
        ]);
    }
}
