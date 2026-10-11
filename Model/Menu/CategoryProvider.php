<?php
declare(strict_types=1);

namespace CommerceGears\MegaMenu\Model\Menu;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Loads the active categories of a store view's category tree in one query (names and URLs in that store view).
 */
class CategoryProvider
{
    /** @var array<int, array<int, array>> store => id => node */
    private array $nodes = [];

    public function __construct(
        private readonly CollectionFactory $collectionFactory,
        private readonly StoreManagerInterface $storeManager,
        private readonly ResourceConnection $resource
    ) {
    }

    public function getRootId(int $storeId): int
    {
        return (int)$this->storeManager->getStore($storeId)->getRootCategoryId();
    }

    /**
     * @param int[] $extraCategoryIds categories of menu items; deep ones raise the level that is loaded
     * @return array<int, array{id:int,name:string,url:string,parent:int,level:int,in_menu:bool,children:int[]}>
     */
    public function getNodes(int $storeId, int $depth, array $extraCategoryIds = []): array
    {
        $rootId = $this->getRootId($storeId);
        $cacheKey = $storeId . ':' . $depth . ':' . implode(',', $extraCategoryIds);
        if (isset($this->nodes[$cacheKey])) {
            return $this->nodes[$cacheKey];
        }
        $rootPath = $this->getPath($rootId);
        if ($rootPath === null) {
            return $this->nodes[$cacheKey] = [];
        }
        $rootLevel = substr_count($rootPath, '/');
        $maxLevel = $rootLevel + 1 + $depth;
        if ($extraCategoryIds) {
            $connection = $this->resource->getConnection();
            $deepest = (int)$connection->fetchOne(
                $connection->select()
                    ->from($this->resource->getTableName('catalog_category_entity'), ['MAX(level)'])
                    ->where('entity_id IN (?)', $extraCategoryIds)
                    ->where('path LIKE ?', $rootPath . '/%')
            );
            $maxLevel = max($maxLevel, $deepest + $depth);
        }

        $collection = $this->collectionFactory->create();
        $collection->setStoreId($storeId)
            ->addAttributeToSelect(['name', 'include_in_menu'])
            ->addFieldToFilter('path', ['like' => $rootPath . '/%'])
            ->addFieldToFilter('level', ['lteq' => $maxLevel])
            ->addIsActiveFilter()
            ->addUrlRewriteToResult()
            ->setOrder('level', 'ASC')
            ->setOrder('position', 'ASC');

        $nodes = [];
        /** @var Category $category */
        foreach ($collection as $category) {
            $id = (int)$category->getId();
            $parent = (int)$category->getParentId();
            if ($parent !== $rootId && !isset($nodes[$parent])) {
                continue; // parent inactive: the branch is hidden, as in Magento's own menu
            }
            $nodes[$id] = [
                'id' => $id,
                'name' => (string)$category->getName(),
                'url' => (string)$category->getUrl(),
                'parent' => $parent,
                'level' => (int)$category->getLevel(),
                'in_menu' => (bool)$category->getData('include_in_menu'),
                'children' => [],
            ];
            if (isset($nodes[$parent])) {
                $nodes[$parent]['children'][] = $id;
            }
        }
        return $this->nodes[$cacheKey] = $nodes;
    }

    /**
     * Top-level categories (direct children of $parentId, or of the store's root category) shown in menus.
     *
     * @return int[]
     */
    public function getMenuChildren(array $nodes, int $parentId): array
    {
        $children = [];
        foreach ($nodes as $node) {
            if ($node['parent'] === $parentId && $node['in_menu']) {
                $children[] = $node['id'];
            }
        }
        return $children;
    }

    private function getPath(int $categoryId): ?string
    {
        $connection = $this->resource->getConnection();
        $path = $connection->fetchOne(
            $connection->select()
                ->from($this->resource->getTableName('catalog_category_entity'), 'path')
                ->where('entity_id = ?', $categoryId)
        );
        return $path === false ? null : (string)$path;
    }
}
