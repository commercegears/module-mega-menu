<?php
declare(strict_types=1);

namespace Softaware\MegaMenu\Model;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\Event\ManagerInterface;

/**
 * Drops the cached menu: the block cache entries and every full page cache entry (built-in or Varnish)
 * that carries the menu tag.
 */
class CacheCleaner
{
    public function __construct(
        private readonly CacheInterface $cache,
        private readonly ManagerInterface $eventManager,
        private readonly ItemFactory $itemFactory
    ) {
    }

    public function clean(): void
    {
        $this->cache->clean([Item::CACHE_TAG]);
        // An object with the menu identity: the page cache observers (FlushCacheByTags, InvalidateVarnish) use it.
        $this->eventManager->dispatch('clean_cache_by_tags', ['object' => $this->itemFactory->create()]);
    }
}
