<?php
declare(strict_types=1);

namespace CommerceGears\MegaMenu\Controller\Adminhtml\Item;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use CommerceGears\MegaMenu\Model\CacheCleaner;
use CommerceGears\MegaMenu\Model\ItemFactory;
use CommerceGears\MegaMenu\Model\ResourceModel\Item as ItemResource;

class Delete extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'CommerceGears_MegaMenu::items_delete';

    public function __construct(
        Context $context,
        private readonly ItemFactory $itemFactory,
        private readonly ItemResource $itemResource,
        private readonly CacheCleaner $cacheCleaner
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $item = $this->itemFactory->create();
        $this->itemResource->load($item, (int)$this->getRequest()->getParam('id'));
        if (!$item->getId()) {
            $this->messageManager->addErrorMessage(__('This menu item no longer exists.'));
        } else {
            $title = $item->getAdminTitle();
            $this->itemResource->delete($item); // items below it go too (foreign key)
            $this->cacheCleaner->clean();
            $this->messageManager->addSuccessMessage(__('"%1" has been deleted.', $title));
        }
        return $this->resultRedirectFactory->create()->setPath('*/builder/index');
    }
}
