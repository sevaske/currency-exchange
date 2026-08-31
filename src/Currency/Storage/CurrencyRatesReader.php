<?php

namespace App\Currency\Storage;

use App\Currency\Exception\CurrencyStorageException;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\UnableToReadFile;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class CurrencyRatesReader implements CurrencyRatesReaderInterface
{
    public function __construct(
        #[Autowire(service: 'currency.storage')]
        private FilesystemOperator $storage,
        private CurrencyRatesPathResolver $pathResolver,
    ) {
    }

    public function read(string $baseCurrency = 'usd'): CurrencyRatesData
    {
        $json = $this->load($baseCurrency);

        try {
            $data = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new CurrencyStorageException(message: 'Failed to decode json string: '.$e->getMessage(), previous: $e);
        }

        try {
            return CurrencyRatesData::fromArray($data);
        } catch (\InvalidArgumentException $e) {
            throw new CurrencyStorageException('Malformed rates file.', previous: $e);
        }
    }

    private function load(string $baseCurrency): string
    {
        $path = $this->pathResolver->resolve($baseCurrency);

        try {
            return $this->storage->read($path);
        } catch (UnableToReadFile|FilesystemException $e) {
            throw new CurrencyStorageException(message: "Unable to read currency rates file. Path: $path", previous: $e);
        }
    }
}
