<?php

namespace App\Currency\Storage;

use App\Currency\Exception\CurrencyStorageException;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class CurrencyRatesStorage
{
    public function __construct(
        #[Autowire(service: 'currency.storage')]
        private FilesystemOperator $storage,
        private CurrencyRatesPathResolver $pathResolver,
    ) {
    }

    /**
     * @param array<string, string> $rates
     */
    public function save(string $providerName, string $baseCurrency, array $rates): void
    {
        $payload = [
            'provider' => $providerName,
            'base_currency' => $baseCurrency,
            'updated_at' => new \DateTimeImmutable()->format(DATE_ATOM),
            'rates' => $rates,
        ];

        try {
            $json = json_encode($payload, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
            $path = $this->pathResolver->resolve($providerName);
            $this->storage->write($path, $json);
        } catch (\JsonException|FilesystemException $e) {
            throw new CurrencyStorageException(message: "Unable to save rates for provider $providerName.", previous: $e);
        }
    }
}
