<?php

namespace App\Command;

use App\Currency\Exception\CurrencyProviderException;
use App\Currency\Storage\CurrencyRatesWriterInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

#[AsCommand(
    name: 'app:currency:sync',
    description: 'Fetches rates from all registered providers and stores them into a single JSON file.',
)]
class CurrencySyncCommand extends Command
{
    public function __construct(
        #[AutowireIterator('app.currency_provider')]
        private readonly iterable $providers,
        private readonly CurrencyRatesWriterInterface $storage,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('currency', InputArgument::OPTIONAL, 'Base currency. "usd" by default', 'usd');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $baseCurrency = $input->getArgument('currency');
        $ratesByProvider = [];

        foreach ($this->providers as $provider) {
            $io->info('Fetching rates from '.$provider->getName());

            try {
                $ratesByProvider[] = $provider->fetchRates($baseCurrency);
            } catch (CurrencyProviderException $e) {
                $io->warning(sprintf('Provider "%s" failed: %s', $provider->getName(), $e->getMessage()));

                continue;
            }
        }

        if ([] === $ratesByProvider) {
            $io->error('No rates were fetched from any provider.');

            return Command::FAILURE;
        }

        $this->storage->save(
            rates: array_merge(...$ratesByProvider),
            baseCurrency: $baseCurrency,
        );

        $io->success('Done.');

        return Command::SUCCESS;
    }
}
