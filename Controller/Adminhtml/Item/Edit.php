<?php
declare(strict_types=1);

namespace Softaware\MegaMenu\Controller\Adminhtml\Item;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Softaware\MegaMenu\Model\ItemFactory;
use Softaware\MegaMenu\Model\ResourceModel\Item as ItemResource;

class Edit extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'Softaware_MegaMenu::items_save';

    public function __construct(
        Context $context,
        private readonly ItemFactory $itemFactory,
        private readonly ItemResource $itemResource,
        private readonly CategoryRepositoryInterface $categoryRepository
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $id = (int)$this->getRequest()->getParam('id');
        $title = __('New Menu Item');
        if ($id) {
            $item = $this->itemFactory->create();
            $this->itemResource->load($item, $id);
            if (!$item->getId()) {
                $this->messageManager->addErrorMessage(__('This menu item no longer exists.'));
                return $this->resultRedirectFactory->create()->setPath('*/builder/index');
            }
            $name = $item->getAdminTitle();
            if (trim((string)$item->getData('title')) === '' && $item->getData('category_id')) {
                try {
                    $name = (string)$this->categoryRepository->get((int)$item->getData('category_id'), 0)->getName();
                } catch (\Exception $e) {
                    $name = $item->getAdminTitle();
                }
            }
            $title = __('Edit Menu Item "%1"', $name);
        }
        $page = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $page->setActiveMenu('Softaware_MegaMenu::builder');
        $page->getConfig()->getTitle()->prepend($title);
        return $page;
    }
}
