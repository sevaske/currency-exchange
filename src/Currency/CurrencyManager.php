<?php

namespace App\Currency;

use App\Currency\Exception\CurrencyProviderException;
use App\Currency\Exception\UnknownCurrencyProviderException;
use App\Currency\Provider\CurrencyProviderInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;

final readonly class CurrencyManager
{
    public function __construct(
        #[AutowireLocator(services: 'app.currency_provider', indexAttribute: 'key')]
        private ContainerInterface $providers,
    ) {
    }

    public function provider(string $name): CurrencyProviderInterface
    {
        if (!$this->providers->has($name)) {
            throw new UnknownCurrencyProviderException($name);
        }

        try {
            return $this->providers->get($name);
        } catch (NotFoundExceptionInterface|ContainerExceptionInterface $e) {
            throw new CurrencyProviderException(providerName: $name, message: "Provider $name is not found or has container issue.", previous: $e);
        }
    }
}
