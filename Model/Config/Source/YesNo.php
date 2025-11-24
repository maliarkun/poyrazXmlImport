<?php
declare(strict_types=1);

namespace Poyraz\XmlImport\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class YesNo implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => 1, 'label' => __('Yes')],
            ['value' => 0, 'label' => __('No')],
        ];
    }
}