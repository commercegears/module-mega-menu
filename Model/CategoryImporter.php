<?php
declare(strict_types=1);

namespace Softaware\MegaMenu\Model;

use Magento\Store\Model\StoreManagerInterface;
use Softaware\MegaMenu\Model\Menu\CategoryProvider;
use Softaware\MegaMenu\Model\ResourceModel\Item as ItemResource;
use Softaware\MegaMenu\Model\ResourceModel\Item\CollectionFactory;

/**
 * Adds one category item per top-level category of a store view's root category, so they can be ordered
 * among custom items. Categories that already have a top-level item are skipped.
 */
class CategoryImporter
{
    public function __construct(
        private readonly CategoryProvider $categoryProvider,
        private readonly CollectionFactory $collectionFactory,
        private readonly ItemFactory $itemFactory,
        private readonly ItemResource $itemResource,
        private readonly StoreManagerInterface $storeManager,
        private readonly CacheCleaner $cacheCleaner
    ) {
    }

    /**
     * @param int $storeId store view whose root category is used; 0 = default store view
     * @param int[] $assignStoreIds store views of the new items (empty = all)
     * @return int[] new item IDs
     */
    public function import(int $storeId, array $assignStoreIds = []): array
    {
        if ($storeId === 0) {
            $storeId = (int)$this->storeManager->getDefaultStoreView()->getId();
        }
        $nodes = $this->categoryProvider->getNodes($storeId, 0);
        $rootId = $this->categoryProvider->getRootId($storeId);
        $existing = [];
        $maxPosition = 0;
        foreach ($this->collectionFactory->create() as $item) {
            if (!$item->getData('parent_id')) {
                $maxPosition = max($maxPosition, (int)$item->getData('position'));
                if ($item->getData('item_type') === Item::TYPE_CATEGORY) {
                    $existing[(int)$item->getData('category_id')] = true;
                }
            }
        }
        $created = [];
        foreach ($this->categoryProvider->getMenuChildren($nodes, $rootId) as $categoryId) {
            if (isset($existing[$categoryId])) {
                continue;
            }
            $item = $this->itemFactory->create();
            $item->setData([
                'item_type' => Item::TYPE_CATEGORY,
                'category_id' => $categoryId,
                'is_active' => 1,
                'show_subcategories' => 1,
                'columns' => 4,
                'panel_width' => 'full',
                'visibility' => 'all',
                'position' => $maxPosition += 10,
                'store_ids' => $assignStoreIds ?: [0],
            ]);
            $this->itemResource->save($item);
            $created[] = (int)$item->getId();
        }
        if ($created) {
            $this->cacheCleaner->clean();
        }
        return $created;
    }
}
