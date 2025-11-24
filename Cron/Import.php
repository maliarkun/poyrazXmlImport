<?php
declare(strict_types=1);

namespace Poyraz\XmlImport\Cron;

use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Poyraz\XmlImport\Logger\Logger;
use Poyraz\XmlImport\Model\Mapping\Config as MappingConfig;
use Poyraz\XmlImport\Model\Parser\XmlParser;
use Poyraz\XmlImport\Model\Source\SourceManager;
use Poyraz\XmlImport\Model\Importer\ProductImporter;
use Magento\Store\Model\ScopeInterface;

class Import
{
    public function __construct(
        private readonly SourceManager $sourceManager,
        private readonly XmlParser $xmlParser,
        private readonly ProductImporter $productImporter,
        private readonly MappingConfig $mappingConfig,
        private readonly Curl $curl,
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
            $this->processSource($source);
        }
    }

    public function executeForSourceCode(string $sourceCode): void
    {
        $source = $this->sourceManager->getSourceByCode($sourceCode);
        if ($source === null) {
            $this->logger->warning(sprintf('Source %s not found', $sourceCode));
            return;
        }

        if (!$this->isEnabled()) {
            $this->logger->info('Poyraz XML Import is disabled via configuration.');
            return;
        }

        if (empty($source['active'])) {
            $this->logger->info(sprintf('Source %s is inactive', $sourceCode));
            return;
        }

        $this->processSource($source);
    }

    private function processSource(array $source): void
    {
        try {
            $this->logger->info(sprintf('Starting import for source %s', $source['code']));
            $this->curl->get($source['url']);
            $body = $this->curl->getBody();
            if (empty($body)) {
                $this->logger->warning(sprintf('No XML returned for source %s', $source['code']));
                return;
            }

            $mapping = $this->mappingConfig->getMappingForSource((string)$source['code']);
            $products = $this->xmlParser->parse($body, $mapping);
            $success = 0;
            foreach ($products as $productData) {
                try {
                    $product = $this->productImporter->import($productData, $source, $mapping);
                    if ($product) {
                        $success++;
                    }
                } catch (\Throwable $exception) {
                    $this->logger->error($exception->getMessage());
                }
            }
            $this->logger->info(sprintf('Import finished for %s. Success: %d', $source['code'], $success));
        } catch (\Throwable $exception) {
            $this->logger->error($exception->getMessage());
        }
    }

    private function isEnabled(): bool
    {
        return (bool)$this->scopeConfig->getValue('poyraz_xml_import/general/enabled', ScopeInterface::SCOPE_STORE);
    }
}
