<?php

namespace App\Currency\Storage;

final readonly class CurrencyRatesData implements \JsonSerializable
{
    /**
     * @param array<string, string> $rates
     */
    public function __construct(
        public string $baseCurrency,
        public \DateTimeImmutable $updatedAt,
        public array $rates,
    ) {
    }

    /**
     * @throws \InvalidArgumentException
     */
    public static function fromArray(array $data): self
    {
        if (!isset($data['base_currency']) || !is_string($data['base_currency']) || '' === $data['base_currency']) {
            throw new \InvalidArgumentException('Malformed rates file: missing or invalid "base_currency".');
        }

        if (!isset($data['updated_at']) || !is_string($data['updated_at'])) {
            throw new \InvalidArgumentException('Malformed rates file: missing or invalid "updated_at".');
        }

        if (!isset($data['rates']) || !is_array($data['rates']) || [] === $data['rates']) {
            throw new \InvalidArgumentException('Malformed rates file: missing or empty "rates".');
        }

        try {
            $updatedAt = new \DateTimeImmutable($data['updated_at']);
        } catch (\Exception $e) {
            throw new \InvalidArgumentException('Malformed rates file: invalid "updated_at" format.', previous: $e);
        }

        return new self(
            baseCurrency: strtoupper($data['base_currency']),
            updatedAt: $updatedAt,
            rates: $data['rates'],
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'base_currency' => $this->baseCurrency,
            'updated_at' => $this->updatedAt->format(DATE_ATOM),
            'rates' => $this->rates,
        ];
    }
}
