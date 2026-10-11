<?php
declare(strict_types=1);

namespace Softaware\MegaMenu\Model\ResourceModel;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Item extends AbstractDb
{
    public const STORE_TABLE = 'softaware_megamenu_item_store';

    protected function _construct(): void
    {
        $this->_init('softaware_megamenu_item', 'item_id');
    }

    /**
     * @return int[]
     */
    public function lookupStoreIds(int $itemId): array
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getTable(self::STORE_TABLE), 'store_id')
            ->where('item_id = ?', $itemId);
        return array_map('intval', $connection->fetchCol($select));
    }

    protected function _afterLoad(AbstractModel $object)
    {
        if ($object->getId()) {
            $object->setData('store_ids', $this->lookupStoreIds((int)$object->getId()));
        }
        return parent::_afterLoad($object);
    }

    protected function _afterSave(AbstractModel $object)
    {
        $storeIds = $object->getData('store_ids');
        if ($storeIds !== null) {
            $storeIds = is_array($storeIds) ? $storeIds : explode(',', (string)$storeIds);
            $storeIds = array_values(array_unique(array_map('intval', $storeIds)));
            if ($storeIds === [] || in_array(0, $storeIds, true)) {
                $storeIds = [0];
            }
            $connection = $this->getConnection();
            $table = $this->getTable(self::STORE_TABLE);
            $connection->delete($table, ['item_id = ?' => (int)$object->getId()]);
            $rows = array_map(static fn(int $id): array => ['item_id' => (int)$object->getId(), 'store_id' => $id], $storeIds);
            $connection->insertMultiple($table, $rows);
        }
        return parent::_afterSave($object);
    }

    /**
     * Writes parent and position of several items in one transaction (menu builder drag and drop).
     *
     * @param array<int, array{parent_id: ?int, position: int}> $tree item_id => new place
     */
    public function saveTree(array $tree): void
    {
        $connection = $this->getConnection();
        $table = $this->getMainTable();
        $connection->beginTransaction();
        try {
            foreach ($tree as $itemId => $place) {
                $connection->update(
                    $table,
                    ['parent_id' => $place['parent_id'] ?: null, 'position' => (int)$place['position']],
                    ['item_id = ?' => (int)$itemId]
                );
            }
            $connection->commit();
        } catch (\Throwable $e) {
            $connection->rollBack();
            throw $e;
        }
    }
}
