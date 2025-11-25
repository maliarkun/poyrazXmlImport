<?php
declare(strict_types=1);

namespace Poyraz\XmlImport\Model\Import;

use Magento\Framework\HTTP\Client\Curl;
use Poyraz\XmlImport\Logger\Logger;
use Poyraz\XmlImport\Model\Importer\ProductImporter;
use Poyraz\XmlImport\Model\Mapper\ProductMapper;
use Poyraz\XmlImport\Model\Source\SourceManager;
use SimpleXMLElement;

class ImportService
{
    public function __construct(
        private readonly SourceManager $sourceManager,
        private readonly ProductMapper $productMapper,
        private readonly ProductImporter $productImporter,
        private readonly Curl $curl,
        private readonly Logger $logger
    ) {
    }

    public function importSourceByCode(string $code): void
    {
        $source = $this->sourceManager->getSourceByCode($code);
        if ($source === null) {
            $this->logger->warning(sprintf('Source %s not found', $code));
            return;
        }

        $this->importSource($source);
    }

    /**
     * @param array<string, mixed> $source
     */
    public function importSource(array $source): void
    {
        $code = (string)($source['code'] ?? 'unknown');
        $this->logger->info(sprintf('Starting import for source %s', $code));

        $xmlContent = $this->fetchXml($source);
        if ($xmlContent === null) {
            $this->logger->warning(sprintf('No XML content fetched for source %s', $code));
            return;
        }

        try {
            $xml = new SimpleXMLElement($xmlContent, LIBXML_NONET | LIBXML_NOCDATA);
        } catch (\Throwable $exception) {
            $this->logger->error(sprintf('XML parse error for source %s: %s', $code, $exception->getMessage()));
            return;
        }

        $mapping = $this->productMapper->getMappingConfig($source);
        $nodes = $xml->xpath($mapping['product_node']);
        if ($nodes === false || $nodes === []) {
            $this->logger->warning(sprintf('No products found for source %s with path %s', $code, $mapping['product_node']));
            return;
        }

        $success = 0;
        $failures = 0;
        foreach ($nodes as $node) {
            try {
                $productData = $this->productMapper->map($node, $source);
                if ($productData === null) {
                    $failures++;
                    continue;
                }
                $result = $this->productImporter->import($productData, $source);
                if ($result !== null) {
                    $success++;
                } else {
                    $failures++;
                }
            } catch (\Throwable $exception) {
                $failures++;
                $this->logger->error(sprintf('Error importing product for %s: %s', $code, $exception->getMessage()));
            }
        }

        $this->logger->info(sprintf('Import finished for %s. Success: %d, Failures: %d', $code, $success, $failures));
    }

    /**
     * @param array<string, mixed> $source
     */
    private function fetchXml(array $source): ?string
    {
        $url = (string)($source['xml_url'] ?? $source['url'] ?? '');
        if ($url === '') {
            $this->logger->warning('XML URL missing for source');
            return null;
        }

        try {
            $this->curl->get($url);
            if ($this->curl->getStatus() >= 400) {
                $this->logger->error(sprintf('HTTP error %s while fetching %s', $this->curl->getStatus(), $url));
                return null;
            }

            return (string)$this->curl->getBody();
        } catch (\Throwable $exception) {
            $this->logger->error(sprintf('Error fetching XML from %s: %s', $url, $exception->getMessage()));
            return null;
        }
    }
}
