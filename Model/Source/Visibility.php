<?php
declare(strict_types=1);

namespace Softaware\MegaMenu\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Devices an item is shown on.
 */
class Visibility implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => 'all', 'label' => __('Desktop and mobile')],
            ['value' => 'desktop', 'label' => __('Desktop only')],
            ['value' => 'mobile', 'label' => __('Mobile only')],
        ];
    }
}
