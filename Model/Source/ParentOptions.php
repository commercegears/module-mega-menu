<?php
declare(strict_types=1);

namespace Softaware\MegaMenu\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Softaware\MegaMenu\Model\ResourceModel\Item\CollectionFactory;

/**
 * Items that can be a parent: top-level items and their direct children (the menu has three levels).
 */
class ParentOptions implements OptionSourceInterface
{
    public function __construct(private readonly CollectionFactory $collectionFactory)
    {
    }

    public function toOptionArray(): array
    {
        $items = [];
        foreach ($this->collectionFactory->create()->setOrder('position', 'ASC') as $item) {
            if ($item->getData('item_type') === 'category_tree') {
                continue;
            }
            $items[(int)$item->getId()] = $item;
        }
        $options = [['value' => '', 'label' => __('None (top level)')]];
        foreach ($items as $id => $item) {
            if ($item->getData('parent_id')) {
                continue;
            }
            $options[] = ['value' => (string)$id, 'label' => $item->getAdminTitle()];
            foreach ($items as $childId => $child) {
                if ((int)$child->getData('parent_id') === $id) {
                    $options[] = ['value' => (string)$childId, 'label' => $item->getAdminTitle() . ' / ' . $child->getAdminTitle()];
                }
            }
        }
        return $options;
    }
}
