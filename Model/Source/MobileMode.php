<?php
declare(strict_types=1);

namespace Softaware\MegaMenu\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Mobile menu style.
 */
class MobileMode implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => 'drilldown', 'label' => __('Drill-down')],
            ['value' => 'accordion', 'label' => __('Accordion')],
        ];
    }
}
