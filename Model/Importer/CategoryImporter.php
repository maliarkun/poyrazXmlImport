<?php
declare(strict_types=1);

namespace Poyraz\XmlImport\Model\Importer;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Api\Data\CategoryInterfaceFactory;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Store\Model\StoreManagerInterface;
use Poyraz\XmlImport\Logger\Logger;

class CategoryImporter
{
    /**
     * @var array<string, int>
     */
    private array $cache = [];

    public function __construct(
        private readonly CategoryRepositoryInterface $categoryRepository,
        private readonly CategoryInterfaceFactory $categoryFactory,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly FilterBuilder $filterBuilder,
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
        $key = sprintf('%d|%s', $parentId, mb_strtolower($name));
        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }

        $this->searchCriteriaBuilder->setFilterGroups([]);
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilters([
                $this->filterBuilder->setField('name')->setValue($name)->setConditionType('eq')->create(),
                $this->filterBuilder->setField('parent_id')->setValue($parentId)->setConditionType('eq')->create(),
            ])
            ->setPageSize(1)
            ->create();

        $result = $this->categoryRepository->getList($searchCriteria)->getItems();
        $existing = reset($result);
        if ($existing instanceof CategoryInterface && $existing->getId()) {
            $this->cache[$key] = (int)$existing->getId();
            return $this->cache[$key];
        }

        $category = $this->categoryFactory->create();
        $category->setName($name);
        $category->setParentId($parentId);
        $category->setIsActive(true);
        $category->setIncludeInMenu(true);

        $saved = $this->categoryRepository->save($category);
        $this->logger->info(sprintf('Created category %s under %d', $name, $parentId));

        $this->cache[$key] = (int)$saved->getId();
        return $this->cache[$key];
    }
}
