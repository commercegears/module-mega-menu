<?php
declare(strict_types=1);

namespace CommerceGears\MegaMenu\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Number of link columns in a dropdown panel.
 */
class Columns implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        $options = [];
        for ($i = 1; $i <= 6; $i++) {
            $options[] = ['value' => (string)$i, 'label' => (string)$i];
        }
        return $options;
    }
}
