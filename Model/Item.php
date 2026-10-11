<?php
declare(strict_types=1);

namespace Softaware\MegaMenu\Model;

use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractModel;

/**
 * One menu item. Saving or deleting cleans the menu cache tag, so the block cache and the full page cache
 * (built-in or Varnish) drop every page that shows the menu.
 */
class Item extends AbstractModel implements IdentityInterface
{
    public const CACHE_TAG = 'sw_megamenu';

    public const TYPE_CATEGORY_TREE = 'category_tree';
    public const TYPE_CATEGORY = 'category';
    public const TYPE_CMS_PAGE = 'cms_page';
    public const TYPE_URL = 'url';
    public const TYPE_HEADING = 'heading';
    public const TYPES = [
        self::TYPE_CATEGORY_TREE, self::TYPE_CATEGORY, self::TYPE_CMS_PAGE, self::TYPE_URL, self::TYPE_HEADING,
    ];

    protected $_cacheTag = self::CACHE_TAG;
    protected $_eventPrefix = 'softaware_megamenu_item';

    protected function _construct(): void
    {
        $this->_init(ResourceModel\Item::class);
    }

    public function getIdentities(): array
    {
        return [self::CACHE_TAG];
    }

    /**
     * @return int[] 0 = all store views
     */
    public function getStoreIds(): array
    {
        $ids = $this->getData('store_ids');
        if (is_string($ids)) {
            $ids = explode(',', $ids);
        }
        return is_array($ids) ? array_values(array_unique(array_map('intval', $ids))) : [];
    }

    /**
     * Title for admin lists: the own title, otherwise a type-specific placeholder.
     */
    public function getAdminTitle(): string
    {
        $title = trim((string)$this->getData('title'));
        if ($title !== '') {
            return $title;
        }
        return match ((string)$this->getData('item_type')) {
            self::TYPE_CATEGORY_TREE => (string)__('Category tree'),
            self::TYPE_CATEGORY => (string)__('Category ID %1', (int)$this->getData('category_id')),
            self::TYPE_CMS_PAGE => (string)__('CMS page ID %1', (int)$this->getData('cms_page_id')),
            default => (string)__('Item %1', (int)$this->getId()),
        };
    }
}
