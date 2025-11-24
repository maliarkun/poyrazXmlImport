<?php
declare(strict_types=1);

namespace Poyraz\XmlImport\Model\Mapping;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Poyraz\XmlImport\Logger\Logger;

class Config
{
    private const XML_PATH_MAPPING = 'poyraz_xml_import/mapping/definitions';

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly Logger $logger
    ) {
    }

    public function getMappingForSource(string $sourceCode): array
    {
        $json = (string)$this->scopeConfig->getValue(self::XML_PATH_MAPPING, ScopeInterface::SCOPE_STORE);
        if ($json === '') {
            return [];
        }

        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            $this->logger->error('Cannot decode mapping definitions JSON');
            return [];
        }

        return $decoded[$sourceCode] ?? [];
    }
}
