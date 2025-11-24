<?php
declare(strict_types=1);

namespace Poyraz\XmlImport\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;
use Poyraz\XmlImport\Model\Source\SourceManager;

class SourceList implements ArrayInterface
{
    public function __construct(private readonly SourceManager $sourceManager)
    {
    }

    public function toOptionArray(): array
    {
        $options = [];
        foreach ($this->sourceManager->getSources() as $source) {
            $options[] = ['value' => $source['code'], 'label' => $source['name'] ?? $source['code']];
        }

        return $options;
    }
}
