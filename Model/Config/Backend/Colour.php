<?php
declare(strict_types=1);

namespace Softaware\MegaMenu\Model\Config\Backend;

use Magento\Framework\App\Config\Value;
use Magento\Framework\Exception\LocalizedException;
use Softaware\MegaMenu\Model\Colour as ColourCheck;

/**
 * Accepts an empty value or a hex colour (#rgb or #rrggbb).
 */
class Colour extends Value
{
    public function beforeSave()
    {
        $value = trim((string)$this->getValue());
        if ($value !== '' && !ColourCheck::isValid($value)) {
            throw new LocalizedException(
                __('"%1" is not a colour. Use a hex value such as #1a1a1a, or leave the field empty.', $value)
            );
        }
        $this->setValue(strtolower($value));
        return parent::beforeSave();
    }
}
