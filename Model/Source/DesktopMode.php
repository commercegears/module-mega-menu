<?php
declare(strict_types=1);

namespace Softaware\MegaMenu\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Desktop layout of the menu.
 */
class DesktopMode implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => 'horizontal', 'label' => __('Horizontal bar')],
            ['value' => 'hamburger', 'label' => __('Hamburger (vertical list)')],
        ];
    }
}
