<?php
declare(strict_types=1);

namespace Poyraz\XmlImport\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class Frequency implements ArrayInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => '*/5 * * * *', 'label' => __('Every 5 minutes')],
            ['value' => '*/15 * * * *', 'label' => __('Every 15 minutes')],
            ['value' => '0 * * * *', 'label' => __('Hourly')],
            ['value' => '0 */6 * * *', 'label' => __('Every 6 hours')],
            ['value' => '0 0 * * *', 'label' => __('Daily')],
        ];
    }
}
