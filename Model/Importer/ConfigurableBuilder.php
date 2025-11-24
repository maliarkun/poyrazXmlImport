<?php
declare(strict_types=1);

namespace Poyraz\XmlImport\Model\Importer;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\ConfigurableProduct\Api\LinkManagementInterface;
use Magento\ConfigurableProduct\Api\OptionRepositoryInterface;
use Magento\ConfigurableProduct\Api\Data\OptionInterfaceFactory;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Api\Data\AttributeOptionLabelInterfaceFactory;
use Magento\Eav\Api\Data\AttributeOptionInterfaceFactory;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Poyraz\XmlImport\Logger\Logger;

class ConfigurableBuilder
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly LinkManagementInterface $linkManagement,
        private readonly OptionRepositoryInterface $optionRepository,
        private readonly OptionInterfaceFactory $optionFactory,
        private readonly AttributeRepositoryInterface $attributeRepository,
        private readonly AttributeOptionInterfaceFactory $attributeOptionFactory,
        private readonly AttributeOptionLabelInterfaceFactory $attributeOptionLabelFactory,
        private readonly Logger $logger
    ) {
    }

    /**
     * @param string $parentSku
     * @param array<int, string> $childSkus
     * @param array<int, string> $configurableAttributes
     */
    public function linkChildren(string $parentSku, array $childSkus, array $configurableAttributes): void
    {
        try {
            $this->linkManagement->associateSimpleProductsToParent($parentSku, $childSkus);
        } catch (\Throwable $exception) {
            $this->logger->error($exception->getMessage());
        }
    }

    public function ensureAttributeOption(string $attributeCode, string $label): int
    {
        $attribute = $this->attributeRepository->get('catalog_product', $attributeCode);
        foreach ($attribute->getOptions() as $option) {
            if (strcasecmp((string)$option->getLabel(), $label) === 0) {
                return (int)$option->getValue();
            }
        }

        $option = $this->attributeOptionFactory->create();
        $optionLabel = $this->attributeOptionLabelFactory->create();
        $optionLabel->setStoreId(0);
        $optionLabel->setLabel($label);
        $option->setLabel($label);
        $option->setStoreLabels([$optionLabel]);
        $option->setIsDefault(false);
        $option->setSortOrder(0);

        $attribute->setOption($option);
        $this->attributeRepository->save($attribute);

        foreach ($attribute->getOptions() as $savedOption) {
            if (strcasecmp((string)$savedOption->getLabel(), $label) === 0) {
                return (int)$savedOption->getValue();
            }
        }

        return 0;
    }
}
