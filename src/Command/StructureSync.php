<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Gally to newer versions in the future.
 *
 * @package   Gally
 * @author    Gally Team <elasticsuite@smile.fr>
 * @copyright 2024-present Smile
 * @license   Open Software License v. 3.0 (OSL-3.0)
 */

declare(strict_types=1);

namespace Gally\OroPlugin\Command;

use Gally\OroPlugin\Indexer\Provider\ProviderInterface;
use Gally\Sdk\Service\StructureSynchonizer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Sync catalog structure with Gally.
 */
class StructureSync extends Command
{
    /** @var string */
    protected static $defaultName = 'gally:structure-sync';

    /** @var ProviderInterface[] */
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
        $this->setDescription('Sync catalog structure with Gally.')
            ->addOption('clean', 'c', InputOption::VALUE_NONE, 'Delete entity that not exist in oro.')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Really remove the listed entity from the gally.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('');

        $clean = $input->getOption('clean');
        $isDryRun = !$input->getOption('force');

        if ($clean && $isDryRun) {
            $output->writeln('<error>Running in dry run mode, add -f to really delete entities from Gally.</error>');
            $output->writeln('');
        }

        foreach ($this->syncMethod as $entity => $method) {
            $message = "<comment>Sync $entity</comment>";
            $time = microtime(true);
            $output->writeln("$message ...");
            $this->synchonizer->{$method}($this->providers[$entity]->provide(), $clean, $isDryRun);
            $time = number_format(microtime(true) - $time, 2);
            $output->writeln("\033[1A$message <info>✔</info> ($time)s");
        }

        $output->writeln('');

        return Command::SUCCESS;
    }
}
