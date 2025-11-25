<?php
declare(strict_types=1);

namespace Poyraz\XmlImport\Cron;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Poyraz\XmlImport\Logger\Logger;
use Poyraz\XmlImport\Model\Import\ImportService;
use Poyraz\XmlImport\Model\Source\SourceManager;

class Import
{
    public function __construct(
        private readonly SourceManager $sourceManager,
        private readonly ImportService $importService,
        private readonly Logger $logger,
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    public function execute(): void
    {
        if (!$this->isEnabled()) {
            $this->logger->info('Poyraz XML Import is disabled via configuration.');
            return;
        }

        foreach ($this->sourceManager->getActiveSources() as $source) {
            $this->importService->importSource($source);
        }
    }

    public function executeForSourceCode(string $sourceCode): void
    {
        if (!$this->isEnabled()) {
            $this->logger->info('Poyraz XML Import is disabled via configuration.');
            return;
        }

        $source = $this->sourceManager->getSourceByCode($sourceCode);
        if ($source === null) {
            $this->logger->warning(sprintf('Source %s not found', $sourceCode));
            return;
        }

        if (!($source['is_active'] ?? $source['active'] ?? false)) {
            $this->logger->info(sprintf('Source %s is inactive', $sourceCode));
            return;
        }

        $this->importService->importSource($source);
    }

    private function isEnabled(): bool
    {
        return (bool)$this->scopeConfig->getValue('poyraz_xml_import/general/enabled', ScopeInterface::SCOPE_STORE);
    }
}
