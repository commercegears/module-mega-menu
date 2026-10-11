<?php
declare(strict_types=1);

namespace CommerceGears\MegaMenu\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Position of the dropdown content next to the links.
 */
class ContentPosition implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => 'right', 'label' => __('Right of the links')],
            ['value' => 'left', 'label' => __('Left of the links')],
            ['value' => 'bottom', 'label' => __('Below the links')],
        ];
    }
}
