<?php
declare(strict_types=1);

namespace Softaware\MegaMenu\Block\Adminhtml\Item\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class DeleteButton extends GenericButton implements ButtonProviderInterface
{
    public function getButtonData(): array
    {
        if (!$this->getItemId() || !$this->context->getAuthorization()->isAllowed('Softaware_MegaMenu::items_delete')) {
            return [];
        }
        return [
            'label' => __('Delete Item'),
            'class' => 'delete',
            'on_click' => 'deleteConfirm(' . json_encode((string)__('Delete this item and all items below it?'))
                . ', ' . json_encode($this->getUrl('*/*/delete', ['id' => $this->getItemId()])) . ', {data: {}})',
            'sort_order' => 20,
        ];
    }
}
