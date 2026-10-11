<?php
declare(strict_types=1);

namespace Softaware\MegaMenu\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * How desktop dropdowns open.
 */
class OpenOn implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => 'hover', 'label' => __('Hover')],
            ['value' => 'click', 'label' => __('Click')],
        ];
    }
}
