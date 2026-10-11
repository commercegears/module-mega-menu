<?php
declare(strict_types=1);

namespace Softaware\MegaMenu\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Width of a dropdown panel.
 */
class PanelWidth implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => 'full', 'label' => __('Full width')],
            ['value' => 'auto', 'label' => __('Fit content')],
        ];
    }
}
