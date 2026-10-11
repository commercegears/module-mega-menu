<?php
declare(strict_types=1);

namespace Softaware\MegaMenu\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Width below which the mobile menu is used.
 */
class Breakpoint implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => '768', 'label' => __('768 px (tablets get the desktop menu)')],
            ['value' => '1024', 'label' => __('1024 px')],
        ];
    }
}
