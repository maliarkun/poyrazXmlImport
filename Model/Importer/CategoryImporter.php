<?php
declare(strict_types=1);

namespace Poyraz\XmlImport\Model\Importer;

use Magento\Catalog\Api\CategoryLinkManagementInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Api\Data\CategoryInterfaceFactory;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Poyraz\XmlImport\Logger\Logger;

class CategoryImporter
{
    public function __construct(
        private readonly CategoryRepositoryInterface $categoryRepository,
        private readonly CategoryInterfaceFactory $categoryFactory,
        private readonly CategoryLinkManagementInterface $categoryLinkManagement,
        private readonly CategoryCollectionFactory $categoryCollectionFactory,
        private readonly StoreManagerInterface $storeManager,
        private readonly Logger $logger
    ) {
    }

    /**
     * @param array<int, string> $categoryPaths
     * @return array<int>
     */
    public function ensureCategories(array $categoryPaths): array
    {
        $ids = [];
        $rootId = (int)$this->storeManager->getStore()->getRootCategoryId();

        foreach ($categoryPaths as $path) {
            $segments = array_filter(array_map('trim', explode('/', $path)));
            $parentId = $rootId;
            $categoryId = $rootId;

            foreach ($segments as $segment) {
                $categoryId = $this->getOrCreateCategory($segment, $parentId);
                $parentId = $categoryId;
            }

            if ($categoryId) {
                $ids[] = $categoryId;
            }
        }

        return array_values(array_unique($ids));
    }

    private function getOrCreateCategory(string $name, int $parentId): int
    {
        // Koleksiyon üzerinden mevcut kategori kontrolü
        $collection = $this->categoryCollectionFactory->create();
        $collection->addAttributeToFilter('name', $name);
        $collection->addAttributeToFilter('parent_id', $parentId);
        $collection->setPageSize(1);

        $existing = $collection->getFirstItem();
        if ($existing instanceof CategoryInterface && $existing->getId()) {
            return (int)$existing->getId();
        }

        // Yoksa yeni kategori oluştur
        /** @var \Magento\Catalog\Api\Data\CategoryInterface $category */
        $category = $this->categoryFactory->create();
        $category->setName($name);
        $category->setParentId($parentId);
        $category->setIsActive(true);
        $category->setIncludeInMenu(true);

        $saved = $this->categoryRepository->save($category);
        $this->logger->info(sprintf('Created category %s under %d', $name, $parentId));

        return (int)$saved->getId();
    }
}