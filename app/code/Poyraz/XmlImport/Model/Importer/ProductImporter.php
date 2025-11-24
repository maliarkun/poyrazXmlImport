<?php
declare(strict_types=1);

namespace Poyraz\XmlImport\Model\Importer;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\Data\ProductInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Poyraz\XmlImport\Logger\Logger;

class ProductImporter
{
    private const DEFAULT_VISIBILITY = 4;

    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly ProductInterfaceFactory $productFactory,
        private readonly StockRegistryInterface $stockRegistry,
        private readonly CategoryImporter $categoryImporter,
        private readonly ImageImporter $imageImporter,
        private readonly StoreManagerInterface $storeManager,
        private readonly Logger $logger
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function import(array $data, array $source, array $mapping): ?ProductInterface
    {
        if (empty($data['sku'])) {
            $this->logger->error('SKU missing in payload');
            return null;
        }

        $sku = (string)$data['sku'];
        $product = $this->loadOrCreateProduct($sku, (int)($source['default_attribute_set'] ?? 4));
        $product->setName($data['name'] ?? $sku);
        $product->setSku($sku);
        $product->setTypeId($data['type'] ?? 'simple');
        $product->setPrice($this->normalizePrice($data['price'] ?? null, $source));
        if (isset($data['special_price'])) {
            $product->setSpecialPrice($this->normalizePrice($data['special_price'], $source));
        }
        if (!empty($data['description'])) {
            $product->setDescription((string)$data['description']);
            $product->setShortDescription((string)$data['description']);
        }

        $product->setAttributeSetId((int)($source['default_attribute_set'] ?? $product->getAttributeSetId()));
        $product->setVisibility($data['visibility'] ?? self::DEFAULT_VISIBILITY);
        $product->setStatus($data['status'] ?? 1);

        if (!empty($data['categories']) && is_array($data['categories'])) {
            $categoryIds = $this->categoryImporter->ensureCategories($data['categories']);
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
            return $this->productRepository->get($sku);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $exception) {
            $product = $this->productFactory->create();
            $product->setSku($sku);
            $product->setAttributeSetId($attributeSetId);
            $product->setWebsiteIds([$this->storeManager->getDefaultStoreView()->getWebsiteId()]);
            return $product;
        }
    }

    private function updateStock(ProductInterface $product, array $data): void
    {
        $stockItem = $this->stockRegistry->getStockItemBySku($product->getSku());
        $qty = isset($data['qty']) ? (float)$data['qty'] : 0.0;
        $isInStock = isset($data['is_in_stock']) ? (bool)$data['is_in_stock'] : ($qty > 0);
        $stockItem->setQty($qty);
        $stockItem->setIsInStock($isInStock);
        $this->stockRegistry->updateStockItemBySku($product->getSku(), $stockItem);
    }

    private function normalizePrice(mixed $price, array $source): float
    {
        $raw = is_numeric($price) ? (float)$price : (float)str_replace([','], ['.'], (string)$price);
        $currency = strtoupper((string)($source['currency'] ?? 'USD'));
        $target = strtoupper((string)($source['target_currency'] ?? 'USD'));
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
}
