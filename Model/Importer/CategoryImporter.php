<?php
declare(strict_types=1);

namespace Poyraz\XmlImport\Model\Importer;

use Magento\Catalog\Api\CategoryLinkManagementInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Api\Data\CategoryInterfaceFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Store\Model\StoreManagerInterface;
use Poyraz\XmlImport\Logger\Logger;

class CategoryImporter
{
    public function __construct(
        private readonly CategoryRepositoryInterface $categoryRepository,
        private readonly CategoryInterfaceFactory $categoryFactory,
        private readonly CategoryLinkManagementInterface $categoryLinkManagement,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
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
        $criteria = $this->searchCriteriaBuilder
            ->addFilter(CategoryInterface::KEY_NAME, $name)
            ->addFilter('parent_id', $parentId)
            ->create();
        $results = $this->categoryRepository->getList($criteria)->getItems();
        $existing = current($results);
        if ($existing instanceof CategoryInterface) {
            return (int)$existing->getId();
        }

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
