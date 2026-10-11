<?php
declare(strict_types=1);

namespace CommerceGears\MegaMenu\Controller\Adminhtml\Builder;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use CommerceGears\MegaMenu\Model\CategoryImporter;

class ImportCategories extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'CommerceGears_MegaMenu::items_save';

    public function __construct(Context $context, private readonly CategoryImporter $importer)
    {
        parent::__construct($context);
    }

    public function execute()
    {
        $storeId = (int)$this->getRequest()->getParam('store');
        try {
            $created = $this->importer->import($storeId, $storeId ? [$storeId] : []);
            if ($created) {
                $this->messageManager->addSuccessMessage(__('%1 category item(s) have been added.', count($created)));
            } else {
                $this->messageManager->addNoticeMessage(__('Every top-level category already has a menu item.'));
            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('The categories could not be added: %1', $e->getMessage()));
        }
        return $this->resultRedirectFactory->create()->setPath('*/*/index', $storeId ? ['store' => $storeId] : []);
    }
}
