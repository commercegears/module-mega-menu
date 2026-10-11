<?php
declare(strict_types=1);

namespace CommerceGears\MegaMenu\Controller\Adminhtml\Builder;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;

class Index extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'CommerceGears_MegaMenu::items';

    public function execute()
    {
        $page = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $page->setActiveMenu('CommerceGears_MegaMenu::builder');
        $page->getConfig()->getTitle()->prepend(__('Menu Builder'));
        return $page;
    }
}
