<?php
declare(strict_types=1);

namespace Softaware\MegaMenu\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Subcategory levels shown in a category dropdown.
 */
class Depth implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => '1', 'label' => __('1 (headings only)')],
            ['value' => '2', 'label' => __('2 (headings and links)')],
            ['value' => '3', 'label' => __('3')],
        ];
    }
}
