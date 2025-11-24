<?php
declare(strict_types=1);

namespace Poyraz\XmlImport\Console;

use Magento\Framework\Console\Cli;
use Poyraz\XmlImport\Cron\Import as ImportCron;
use Poyraz\XmlImport\Model\Source\SourceManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportCommand extends Command
{
    private const ARG_SOURCE = 'source';

    public function __construct(
        private readonly ImportCron $importCron,
        private readonly SourceManager $sourceManager,
        string $name = null
    ) {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this->setName('poyraz:xml:import')
            ->setDescription('Import products for a specific XML source')
            ->addArgument(self::ARG_SOURCE, InputArgument::REQUIRED, 'Source code defined in configuration');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $sourceCode = (string)$input->getArgument(self::ARG_SOURCE);
        $source = $this->sourceManager->getSourceByCode($sourceCode);
        if ($source === null) {
            $output->writeln('<error>Source not found</error>');
            return Cli::RETURN_FAILURE;
        }

        $this->importCron->executeForSourceCode($sourceCode);
        $output->writeln(sprintf('<info>Import triggered for source %s</info>', $sourceCode));

        return Cli::RETURN_SUCCESS;
    }
}
