<?php
declare(strict_types=1);

namespace CommerceGears\MegaMenu\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;
use CommerceGears\MegaMenu\Model\Menu\Icons;

/**
 * Built-in icons for menu items.
 */
class Icon implements OptionSourceInterface
{
    public function __construct(private readonly Icons $icons)
    {
    }

    public function toOptionArray(): array
    {
        $options = [['value' => '', 'label' => __('No icon')]];
        foreach ($this->icons->getOptions() as $code => $label) {
            $options[] = ['value' => $code, 'label' => __($label)];
        }
        return $options;
    }
}
