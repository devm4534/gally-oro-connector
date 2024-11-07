<?php

declare(strict_types=1);

namespace Gally\OroPlugin\Command;

use Gally\OroPlugin\Provider\ProviderInterface;
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

    /** @var iterable<ProviderInterface> $providers*/
    private array $providers;
    private array $syncMethod = [
        'catalog' => 'syncAllLocalizedCatalogs',
        'sourceField' => 'syncAllSourceFields',
        'sourceFieldOption' => 'syncAllSourceFieldOptions',
    ];

    public function __construct(
        private StructureSynchonizer $synchonizer,
        \IteratorAggregate $providers,
    ) {
        parent::__construct();
        $this->providers = iterator_to_array($providers);
    }

    protected function configure(): void
    {
        $this->setDescription('Sync catalog structure with Gally.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('');

        foreach ($this->syncMethod as $entity => $method) {
            $message = "<comment>Sync $entity</comment>";
            $time = microtime(true);
            $output->writeln("$message ...");
            $this->synchonizer->$method($this->providers[$entity]->provide());
            $time = number_format(microtime(true) - $time, 2);
            $output->writeln("\033[1A$message <info>✔</info> ($time)s");
        }

        $output->writeln('');

        return Command::SUCCESS;
    }
}
