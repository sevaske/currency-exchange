<?php

namespace App\Currency\Storage;

use App\Currency\Exception\CurrencyStorageException;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\UnableToReadFile;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class CurrencyRatesReader
{
    public function __construct(
        #[Autowire(service: 'currency.storage')]
        private FilesystemOperator $storage,
        private CurrencyRatesPathResolver $pathResolver,
    ) {
    }

    /**
     * @return array{provider: string, base_currency: string, updated_at: string, rates: array<string, string>}
     */
    public function read(string $providerName): array
    {
        $path = $this->pathResolver->resolve($providerName);

        try {
            $json = $this->storage->read($path);
        } catch (UnableToReadFile|FilesystemException $e) {
            throw new CurrencyStorageException(message: 'Unable to read currency rates file. Provider: '.$providerName, previous: $e);
        }

        try {
            $data = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new CurrencyStorageException(message: 'Failed to decode json string: '.$e->getMessage().'. Provider: '.$providerName, previous: $e);
        }

        return $data;
    }
}
