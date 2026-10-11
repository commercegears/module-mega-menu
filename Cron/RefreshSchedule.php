<?php
declare(strict_types=1);

namespace CommerceGears\MegaMenu\Cron;

use Magento\Framework\FlagManager;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Store\Model\StoreManagerInterface;
use CommerceGears\MegaMenu\Model\CacheCleaner;
use CommerceGears\MegaMenu\Model\ResourceModel\Item\CollectionFactory;
use CommerceGears\MegaMenu\Model\Schedule;

/**
 * Scheduled items and labels: when an item or label starts or ends (store time zone), the cached menu is dropped
 * so the change shows without waiting for the cache to expire.
 */
class RefreshSchedule
{
    public const FLAG = 'commercegears_megamenu_schedule';

    public function __construct(
        private readonly CollectionFactory $collectionFactory,
        private readonly StoreManagerInterface $storeManager,
        private readonly TimezoneInterface $timezone,
        private readonly FlagManager $flagManager,
        private readonly CacheCleaner $cacheCleaner
    ) {
    }

    public function execute(): void
    {
        $signature = $this->signature();
        if ($signature === null) {
            return;
        }
        $previous = $this->flagManager->getFlagData(self::FLAG);
        if ($previous !== $signature) {
            if ($previous !== null) {
                $this->cacheCleaner->clean();
            }
            $this->flagManager->saveFlag(self::FLAG, $signature);
        }
    }

    /**
     * Hash of which scheduled items and labels are live today in each store's time zone; null if nothing is scheduled.
     */
    public function signature(): ?string
    {
        $collection = $this->collectionFactory->create();
        $collection->getSelect()->where(
            'active_from IS NOT NULL OR active_to IS NOT NULL OR label_from IS NOT NULL OR label_to IS NOT NULL'
        );
        $rows = $collection->getData();
        if (!$rows) {
            return null;
        }
        $state = [];
        foreach ($this->storeManager->getStores() as $store) {
            $today = $this->timezone->scopeDate((int)$store->getId())->format('Y-m-d');
            foreach ($rows as $row) {
                $state[] = $store->getId() . ':' . $row['item_id'] . ':'
                    . (int)Schedule::isActive($row['active_from'], $row['active_to'], $today)
                    . (int)Schedule::isActive($row['label_from'], $row['label_to'], $today);
            }
        }
        return sha1(implode('|', $state));
    }
}
