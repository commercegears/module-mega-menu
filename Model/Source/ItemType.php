<?php
declare(strict_types=1);

namespace Softaware\MegaMenu\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Menu item types.
 */
class ItemType implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => 'category_tree', 'label' => __('Category tree (all top-level categories)')],
            ['value' => 'category', 'label' => __('Category')],
            ['value' => 'cms_page', 'label' => __('CMS page')],
            ['value' => 'url', 'label' => __('Custom link')],
            ['value' => 'heading', 'label' => __('Heading (no link)')],
        ];
    }
}
