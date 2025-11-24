<?php
declare(strict_types=1);

namespace Poyraz\XmlImport\Console;

use Magento\Framework\Console\Cli;
use Poyraz\XmlImport\Cron\Import as ImportCron;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportAllCommand extends Command
{
    public function __construct(private readonly ImportCron $importCron, string $name = null)
    {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this->setName('poyraz:xml:import:all')
            ->setDescription('Import products for all active XML sources');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->importCron->execute();
        $output->writeln('<info>Import triggered for all active sources</info>');

        return Cli::RETURN_SUCCESS;
    }
}
