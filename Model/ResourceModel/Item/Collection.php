<?php
declare(strict_types=1);

namespace Softaware\MegaMenu\Model\ResourceModel\Item;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Softaware\MegaMenu\Model\Item;
use Softaware\MegaMenu\Model\ResourceModel\Item as ItemResource;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'item_id';

    protected function _construct(): void
    {
        $this->_init(Item::class, ItemResource::class);
    }

    /**
     * Items assigned to the store view or to all store views.
     */
    public function addStoreFilter(int $storeId): self
    {
        $this->getSelect()->where(
            'main_table.item_id IN (?)',
            $this->getConnection()->select()
                ->from($this->getTable(ItemResource::STORE_TABLE), 'item_id')
                ->where('store_id IN (?)', array_unique([0, $storeId]))
        );
        return $this;
    }

    /**
     * Adds store_ids (array) to every loaded item with one query.
     */
    protected function _afterLoad()
    {
        $ids = array_keys($this->_items);
        if ($ids) {
            $rows = $this->getConnection()->fetchAll(
                $this->getConnection()->select()
                    ->from($this->getTable(ItemResource::STORE_TABLE), ['item_id', 'store_id'])
                    ->where('item_id IN (?)', $ids)
            );
            $map = [];
            foreach ($rows as $row) {
                $map[(int)$row['item_id']][] = (int)$row['store_id'];
            }
            foreach ($this->_items as $id => $item) {
                $item->setData('store_ids', $map[(int)$id] ?? []);
            }
        }
        return parent::_afterLoad();
    }
}
