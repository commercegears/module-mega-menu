<?php
declare(strict_types=1);

namespace CommerceGears\MegaMenu\Block\Adminhtml;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Cms\Model\ResourceModel\Page\CollectionFactory as PageCollectionFactory;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use CommerceGears\MegaMenu\Model\Config;
use CommerceGears\MegaMenu\Model\Item;
use CommerceGears\MegaMenu\Model\ResourceModel\Item\CollectionFactory;
use CommerceGears\MegaMenu\Model\Schedule;
use CommerceGears\MegaMenu\Model\Source\ItemType;

/**
 * Menu Builder page: the item tree with drag and drop ordering.
 */
class Builder extends Template
{
    private ?array $tree = null;

    public function __construct(
        Context $context,
        private readonly CollectionFactory $collectionFactory,
        private readonly CategoryCollectionFactory $categoryCollectionFactory,
        private readonly PageCollectionFactory $pageCollectionFactory,
        private readonly ItemType $itemType,
        private readonly Config $config,
        private readonly TimezoneInterface $timezone,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * @return array<int, array> top-level rows with 'children'
     */
    public function getTree(): array
    {
        if ($this->tree !== null) {
            return $this->tree;
        }
        $items = $this->collectionFactory->create()->setOrder('position', 'ASC')->setOrder('item_id', 'ASC');
        $rows = [];
        $categoryIds = [];
        $pageIds = [];
        foreach ($items as $item) {
            $row = $item->getData();
            $row['store_ids'] = $item->getStoreIds();
            $row['children'] = [];
            $rows[(int)$item->getId()] = $row;
            if (!empty($row['category_id'])) {
                $categoryIds[] = (int)$row['category_id'];
            }
            if (!empty($row['cms_page_id'])) {
                $pageIds[] = (int)$row['cms_page_id'];
            }
        }
        $names = $this->categoryNames($categoryIds);
        $pages = $this->pageTitles($pageIds);
        foreach ($rows as $id => &$row) {
            $row['display_title'] = $this->displayTitle($row, $names, $pages);
        }
        unset($row);
        $tree = [];
        $refs = [];
        foreach ($rows as $id => $row) {
            $refs[$id] = $row;
        }
        // attach children bottom-up (three levels)
        $childrenOf = [];
        foreach ($refs as $id => $row) {
            $childrenOf[(int)($row['parent_id'] ?? 0)][] = $id;
        }
        $build = function (int $parentId) use (&$build, $childrenOf, $refs): array {
            $out = [];
            foreach ($childrenOf[$parentId] ?? [] as $id) {
                $row = $refs[$id];
                $row['children'] = $build($id);
                $out[] = $row;
            }
            return $out;
        };
        $tree = $build(0);
        // items whose parent no longer exists are shown at the top level
        foreach ($childrenOf as $parentId => $ids) {
            if ($parentId && !isset($refs[$parentId])) {
                foreach ($ids as $id) {
                    $row = $refs[$id];
                    $row['children'] = $build($id);
                    $tree[] = $row;
                }
            }
        }
        return $this->tree = $tree;
    }

    public function getTypeLabel(string $type): string
    {
        foreach ($this->itemType->toOptionArray() as $option) {
            if ($option['value'] === $type) {
                return (string)$option['label'];
            }
        }
        return $type;
    }

    /**
     * @return array{0:string,1:string} [label, grid severity class]
     */
    public function getStatus(array $row): array
    {
        if (empty($row['is_active'])) {
            return [(string)__('Disabled'), 'grid-severity-minor'];
        }
        $today = $this->timezone->date()->format('Y-m-d');
        if (!Schedule::isActive($row['active_from'] ?? null, $row['active_to'] ?? null, $today)) {
            $upcoming = !empty($row['active_from']) && $today < substr((string)$row['active_from'], 0, 10);
            return [(string)($upcoming ? __('Scheduled') : __('Ended')), 'grid-severity-minor'];
        }
        if (!empty($row['active_from']) || !empty($row['active_to'])) {
            return [(string)__('Live (scheduled)'), 'grid-severity-notice'];
        }
        return [(string)__('Enabled'), 'grid-severity-notice'];
    }

    public function getStoreLabel(array $storeIds): string
    {
        if (!$storeIds || in_array(0, $storeIds, true)) {
            return (string)__('All Store Views');
        }
        $names = [];
        foreach ($storeIds as $storeId) {
            try {
                $names[] = $this->_storeManager->getStore($storeId)->getName();
            } catch (\Exception $e) {
                continue;
            }
        }
        return implode(', ', $names);
    }

    public function getStores(): array
    {
        $stores = [];
        foreach ($this->_storeManager->getStores() as $store) {
            $stores[(int)$store->getId()] = $store->getWebsite()->getName() . ' / ' . $store->getName();
        }
        return $stores;
    }

    public function isEnabledAnywhere(): bool
    {
        if ($this->config->isEnabled(0)) {
            return true;
        }
        foreach ($this->_storeManager->getStores() as $store) {
            if ($this->config->isEnabled((int)$store->getId())) {
                return true;
            }
        }
        return false;
    }

    public function canEdit(): bool
    {
        return $this->_authorization->isAllowed('CommerceGears_MegaMenu::items_save');
    }

    public function canDelete(): bool
    {
        return $this->_authorization->isAllowed('CommerceGears_MegaMenu::items_delete');
    }

    private function displayTitle(array $row, array $names, array $pages): string
    {
        $title = trim((string)($row['title'] ?? ''));
        if ($title !== '') {
            return $title;
        }
        return match ((string)$row['item_type']) {
            Item::TYPE_CATEGORY_TREE => !empty($row['category_id'])
                ? (string)__('Subcategories of %1', $names[(int)$row['category_id']] ?? '#' . $row['category_id'])
                : (string)__('All top-level categories'),
            Item::TYPE_CATEGORY => $names[(int)$row['category_id']] ?? (string)__('Missing category #%1', (int)$row['category_id']),
            Item::TYPE_CMS_PAGE => $pages[(int)$row['cms_page_id']] ?? (string)__('Missing page #%1', (int)$row['cms_page_id']),
            default => (string)__('(no title)'),
        };
    }

    private function categoryNames(array $ids): array
    {
        if (!$ids) {
            return [];
        }
        $names = [];
        $collection = $this->categoryCollectionFactory->create()->setStoreId(0)->addAttributeToSelect('name')
            ->addFieldToFilter('entity_id', ['in' => array_unique($ids)]);
        foreach ($collection as $category) {
            $names[(int)$category->getId()] = (string)$category->getName();
        }
        return $names;
    }

    private function pageTitles(array $ids): array
    {
        if (!$ids) {
            return [];
        }
        $titles = [];
        foreach ($this->pageCollectionFactory->create()->addFieldToFilter('page_id', ['in' => array_unique($ids)]) as $page) {
            $titles[(int)$page->getId()] = (string)$page->getTitle();
        }
        return $titles;
    }
}
