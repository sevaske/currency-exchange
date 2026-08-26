<?php

namespace App\Command;

use App\Currency\CurrencyManager;
use App\Currency\Storage\CurrencyRatesWriterInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:currency:parse',
    description: 'Parsing currency rates and saves into json file.',
)]
class CurrencyParseCommand extends Command
{
    public function __construct(
        private readonly CurrencyManager $currencyManager,
        private readonly CurrencyRatesWriterInterface $storage,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('provider', InputArgument::REQUIRED, 'Currency rates provider name')
            ->addArgument('currency', InputArgument::OPTIONAL, 'Base currency. "usd" by default', 'usd');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $providerName = $input->getArgument('provider');
        $baseCurrency = $input->getArgument('currency');
        $io->info("Parsing currency rates from provider $providerName ($baseCurrency)");

        $provider = $this->currencyManager->provider($providerName);
        $rates = $provider->fetchRates($baseCurrency);
        $this->storage->save(providerName: $providerName, baseCurrency: $baseCurrency, rates: $rates);

        $io->success('Done.');

        return Command::SUCCESS;
    }
}
