<?php
declare(strict_types=1);

namespace Softaware\MegaMenu\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\View\DesignInterface;
use Magento\Framework\View\Layout;
use Magento\Store\Model\StoreManagerInterface;
use Softaware\MegaMenu\Model\Config;

/**
 * layout_load_before: adds "softaware_megamenu_luma" or "softaware_megamenu_hyva" when the menu is enabled for the
 * store view. Those layout files replace the theme's top menu. Custom themes can use the same handles.
 */
class AddLayoutHandle implements ObserverInterface
{
    public const HANDLE_LUMA = 'softaware_megamenu_luma';
    public const HANDLE_HYVA = 'softaware_megamenu_hyva';

    public function __construct(
        private readonly Config $config,
        private readonly StoreManagerInterface $storeManager,
        private readonly DesignInterface $design
    ) {
    }

    public function execute(Observer $observer): void
    {
        /** @var Layout $layout */
        $layout = $observer->getData('layout');
        if (!$layout || !$this->config->isEnabled((int)$this->storeManager->getStore()->getId())) {
            return;
        }
        $layout->getUpdate()->addHandle($this->isHyva() ? self::HANDLE_HYVA : self::HANDLE_LUMA);
    }

    private function isHyva(): bool
    {
        $theme = $this->design->getDesignTheme();
        while ($theme) {
            if (str_starts_with((string)$theme->getCode(), 'Hyva/')) {
                return true;
            }
            $theme = $theme->getParentTheme();
        }
        return false;
    }
}
