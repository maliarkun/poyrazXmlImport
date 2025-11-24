<?php
declare(strict_types=1);

namespace Poyraz\XmlImport\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class LogLevel implements OptionSourceInterface
{
    /**
     * @inheritdoc
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 'debug',   'label' => __('Debug')],
            ['value' => 'info',    'label' => __('Info')],
            ['value' => 'notice',  'label' => __('Notice')],
            ['value' => 'warning', 'label' => __('Warning')],
            ['value' => 'error',   'label' => __('Error')],
        ];
    }
}
