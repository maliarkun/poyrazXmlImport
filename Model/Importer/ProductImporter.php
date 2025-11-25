<?php
declare(strict_types=1);

namespace Poyraz\XmlImport\Model\Importer;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\Data\ProductInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Poyraz\XmlImport\Logger\Logger;
use Poyraz\XmlImport\Model\Mapper\CategoryMapper;

class ProductImporter
{
    private const DEFAULT_VISIBILITY = 4;
    private const DEFAULT_STATUS = 1;
    private const DEFAULT_ATTRIBUTE_SET_ID = 4;
    private const DEFAULT_WEIGHT = 1.0;

    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly ProductInterfaceFactory $productFactory,
        private readonly StockRegistryInterface $stockRegistry,
        private readonly CategoryImporter $categoryImporter,
        private readonly CategoryMapper $categoryMapper,
        private readonly ImageImporter $imageImporter,
        private readonly StoreManagerInterface $storeManager,
        private readonly Logger $logger
    ) {
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $source
     */
    public function import(array $data, array $source): ?ProductInterface
    {
        if (empty($data['sku'])) {
            $this->logger->error('SKU missing in payload');
            return null;
        }

        $sku = (string)$data['sku'];
        $product = $this->loadOrCreateProduct($sku, (int)($source['default_attribute_set'] ?? self::DEFAULT_ATTRIBUTE_SET_ID));
        $product->setName((string)($data['name'] ?? $sku));
        $product->setSku($sku);
        $product->setTypeId((string)($data['type'] ?? 'simple'));
        $product->setPrice($this->normalizePrice($data['price'] ?? null, $source));
        if (isset($data['special_price']) && $data['special_price'] !== '') {
            $product->setSpecialPrice($this->normalizePrice($data['special_price'], $source));
        }
        if (!empty($data['description'])) {
            $product->setDescription((string)$data['description']);
            $product->setShortDescription((string)$data['description']);
        }

        $product->setAttributeSetId((int)($source['default_attribute_set'] ?? $product->getAttributeSetId()));
        $product->setVisibility($this->normalizeInt($data['visibility'] ?? self::DEFAULT_VISIBILITY, self::DEFAULT_VISIBILITY));
        $product->setStatus($this->normalizeInt($data['status'] ?? self::DEFAULT_STATUS, self::DEFAULT_STATUS));
        $product->setWeight($this->normalizeFloat($data['weight'] ?? self::DEFAULT_WEIGHT, self::DEFAULT_WEIGHT));
        $product->setTaxClassId($this->resolveTaxClassId($data['tax_class'] ?? null, $source));

        $categoryPaths = $this->categoryMapper->mapCategories($data['categories'] ?? [], $source);
        if ($categoryPaths !== []) {
            $categoryIds = $this->categoryImporter->ensureCategories($categoryPaths);
            $product->setCategoryIds($categoryIds);
        }

        $saved = $this->productRepository->save($product);

        if (!empty($data['images']) && is_array($data['images'])) {
            $this->imageImporter->attachImages($saved, $data['images'], (string)($source['image_path'] ?? 'import/poyraz'));
            $saved = $this->productRepository->save($saved);
        }

        $this->updateStock($saved, $data);
        $this->logger->info(sprintf('Product %s saved', $sku));

        return $saved;
    }

    private function loadOrCreateProduct(string $sku, int $attributeSetId): ProductInterface
    {
        try {
            return $this->productRepository->get($sku, false, null, true);
        } catch (\Magento\Framework\Exception\NoSuchEntityException) {
            $product = $this->productFactory->create();
            $product->setSku($sku);
            $product->setAttributeSetId($attributeSetId);
            $product->setWebsiteIds([$this->storeManager->getDefaultStoreView()->getWebsiteId()]);
            return $product;
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function updateStock(ProductInterface $product, array $data): void
    {
        $stockItem = $this->stockRegistry->getStockItemBySku($product->getSku());
        $qty = $this->normalizeFloat($data['qty'] ?? 0.0, 0.0);
        $isInStock = isset($data['is_in_stock']) ? (bool)$data['is_in_stock'] : ($qty > 0);
        $stockItem->setQty($qty);
        $stockItem->setIsInStock($isInStock);
        $stockItem->setManageStock(true);
        $stockItem->setUseConfigManageStock(false);
        $this->stockRegistry->updateStockItemBySku($product->getSku(), $stockItem);
    }

    private function normalizePrice(mixed $price, array $source): float
    {
        $raw = $this->normalizeFloat($price, 0.0);
        $currency = strtoupper((string)($source['currency'] ?? 'USD'));
        $target = strtoupper((string)($source['target_currency'] ?? $currency));
        $rate = 1.0;
        if ($currency !== $target) {
            $rate = (float)($source['rate'] ?? 1.0);
        }

        $margin = (float)($source['margin'] ?? 0.0);
        $normalized = $raw * $rate;
        if ($margin !== 0.0) {
            $normalized *= 1 + ($margin / 100);
        }

        return round($normalized, 4);
    }

    private function resolveTaxClassId(mixed $taxValue, array $source): int
    {
        $mapping = $source['tax_mapping'] ?? [];
        if (is_array($mapping)) {
            foreach ($mapping as $key => $classId) {
                if (strcasecmp((string)$key, (string)$taxValue) === 0) {
                    return (int)$classId;
                }
            }
        }

        if (is_numeric($taxValue)) {
            $rate = (float)$taxValue;
            if ($rate >= 18) {
                return (int)($source['tax_class_high'] ?? 0);
            }
            if ($rate >= 8) {
                return (int)($source['tax_class_mid'] ?? 0);
            }
        }

        return (int)($source['tax_class_default'] ?? 0);
    }

    private function normalizeFloat(mixed $value, float $default): float
    {
        if (is_numeric($value)) {
            return (float)$value;
        }

        if (is_string($value)) {
            $normalized = str_replace(',', '.', $value);
            if (is_numeric($normalized)) {
                return (float)$normalized;
            }
        }

        return $default;
    }

    private function normalizeInt(mixed $value, int $default): int
    {
        if (is_numeric($value)) {
            return (int)$value;
        }

        return $default;
    }
}
