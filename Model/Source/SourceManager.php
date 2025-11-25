<?php
declare(strict_types=1);

namespace Poyraz\XmlImport\Model\Source;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Poyraz\XmlImport\Logger\Logger;

class SourceManager
{
    public const XML_PATH_SOURCES = 'poyraz_xml_import/sources/definitions';

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly Logger $logger
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getSources(): array
    {
        $rawJson = (string)$this->scopeConfig->getValue(self::XML_PATH_SOURCES, ScopeInterface::SCOPE_STORE);
        if (trim($rawJson) === '') {
            return [];
        }

        $decoded = json_decode($rawJson, true);
        if (!is_array($decoded)) {
            $this->logger->error('Cannot decode source definitions JSON', ['raw' => $rawJson]);
            return [];
        }

        $sources = function_exists('array_is_list') && array_is_list($decoded) ? $decoded : [$decoded];

        $filtered = array_values(array_filter($sources, static function (mixed $source): bool {
            return is_array($source) && trim((string)($source['code'] ?? '')) !== '';
        }));

        return array_map(function (array $source): array {
            $source['code'] = (string)$source['code'];
            return $source;
        }, $filtered);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getActiveSources(): array
    {
        return array_values(array_filter($this->getSources(), static function (array $source): bool {
            $flag = $source['is_active'] ?? $source['active'] ?? false;
            return (bool)$flag;
        }));
    }

    public function getSourceByCode(string $code): ?array
    {
        foreach ($this->getSources() as $source) {
            if (strcasecmp((string)$source['code'], $code) === 0) {
                return $source;
            }
        }

        return null;
    }
}
