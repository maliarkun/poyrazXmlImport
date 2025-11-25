<?php
declare(strict_types=1);

namespace Poyraz\XmlImport\Model\Source;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Poyraz\XmlImport\Logger\Logger;

class SourceManager
{
    private const XML_PATH_SOURCES = 'poyraz_xml_import/sources/definitions';

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
        $json = (string)$this->scopeConfig->getValue(
            self::XML_PATH_SOURCES,
            ScopeInterface::SCOPE_STORE
        );

        if (trim($json) === '') {
            return [];
        }

        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            $this->logger->error('Cannot decode source definitions JSON', ['raw' => $json]);
            return [];
        }

        // Tek obje mi, liste mi kontrol et
        if (function_exists('array_is_list') && array_is_list($decoded)) {
            $sources = $decoded;
        } else {
            // Tek obje geldiği durumda onu listeye sar
            $sources = [$decoded];
        }

        return array_values(
            array_filter(
                $sources,
                static function ($source): bool {
                    return is_array($source) && !empty($source['code']);
                }
            )
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getActiveSources(): array
    {
        return array_values(
            array_filter(
                $this->getSources(),
                static function ($source): bool {
                    return is_array($source) && !empty($source['active']);
                }
            )
        );
    }

    public function getSourceByCode(string $code): ?array
    {
        foreach ($this->getSources() as $source) {
            if (!is_array($source) || empty($source['code'])) {
                continue;
            }

            if (strcasecmp((string)$source['code'], $code) === 0) {
                return $source;
            }
        }

        return null;
    }
}