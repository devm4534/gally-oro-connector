<?php

declare(strict_types=1);

namespace Gally\OroPlugin\Command;

use Gally\OroPlugin\Provider\CatalogProvider;
use Gally\OroPlugin\Provider\SourceFieldOptionProvider;
use Gally\OroPlugin\Provider\SourceFieldProvider;
use Gally\Sdk\Client\Configuration;
use Gally\Sdk\Service\StructureSynchonizer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Sync catalog structure with Gally.
 */
class StructureSync extends Command
{
    /** @var string */
    protected static $defaultName = 'gally:structure-sync';

    protected StructureSynchonizer $synchonizer;

    public function __construct(
        private CatalogProvider $catalogProvider,
        private SourceFieldProvider $sourceFieldProvider,
        private SourceFieldOptionProvider $sourceFieldOptionProvider,
        array $gallyConf,
        string $envCode,
    ) {
        parent::__construct();

        $gallyConfig = new Configuration(...$gallyConf);
        $this->synchonizer = new StructureSynchonizer($gallyConfig, $envCode);
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Sync catalog structure with Gally.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('');

        $message = "<comment>Sync Catalogs</comment>";
        $time = microtime(true);
        $output->writeln("$message ...");
        $this->synchonizer->syncAllLocalizedCatalogs($this->catalogProvider->provide());
        $time = number_format(microtime(true) - $time, 2);
        $output->writeln("\033[1A$message <info>✔</info> ($time)s");

        $message = "<comment>Sync SourceFields</comment>";
        $time = microtime(true);
        $output->writeln("$message ...");
        $this->synchonizer->syncAllSourceFields($this->sourceFieldProvider->provide());
        $time = number_format(microtime(true) - $time, 2);
        $output->writeln("\033[1A$message <info>✔</info> ($time)s");

//        $message = "<comment>Sync Catalog</comment>";
//        $time = microtime(true);
//        $output->writeln("$message ...");
//        $this->synchonizer->syncAllSourceFieldOptions($this->sourceFieldOptionProvider->provide());
//        $time = number_format(microtime(true) - $time, 2);
//        $output->writeln("\033[1A$message <info>✔</info> ($time)s");

        $output->writeln('');

        return Command::SUCCESS;
    }
}
