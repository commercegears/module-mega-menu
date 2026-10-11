<?php
declare(strict_types=1);

namespace CommerceGears\MegaMenu\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use CommerceGears\MegaMenu\Model\CacheCleaner;

/**
 * Settings saved: drop the cached menu so colours and layout changes show at once.
 */
class CleanMenuCache implements ObserverInterface
{
    public function __construct(private readonly CacheCleaner $cacheCleaner)
    {
    }

    public function execute(Observer $observer): void
    {
        $this->cacheCleaner->clean();
    }
}
