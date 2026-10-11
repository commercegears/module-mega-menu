<?php
declare(strict_types=1);

namespace Softaware\MegaMenu\Block\Adminhtml\System;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Text field with a native colour picker next to it (the text field stays the value, so it can be emptied).
 */
class ColourField extends Field
{
    protected function _getElementHtml(AbstractElement $element)
    {
        $element->setData('class', trim($element->getData('class') . ' sw-mm-colour-text'));
        $element->setData('placeholder', '#rrggbb');
        $html = parent::_getElementHtml($element);
        $value = (string)$element->getData('value');
        $swatch = preg_match('/^#[0-9a-f]{6}$/i', $value) ? $value : '#ffffff';
        return '<div class="sw-mm-colour" data-mage-init=\'{"Softaware_MegaMenu/js/colour-field":{}}\'>'
            . $html
            . '<input type="color" class="sw-mm-colour-picker" value="' . $this->escapeHtmlAttr($swatch) . '"'
            . ' aria-label="' . $this->escapeHtmlAttr(__('Pick a colour')) . '"'
            . ($element->getData('disabled') ? ' disabled' : '') . '/></div>';
    }
}
