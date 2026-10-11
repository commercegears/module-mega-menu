<?php
declare(strict_types=1);

namespace CommerceGears\MegaMenu\Controller\Adminhtml\Item;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Catalog\Model\ImageUploader;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultFactory;

class Upload extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'CommerceGears_MegaMenu::items_save';

    public function __construct(Context $context, private readonly ImageUploader $imageUploader)
    {
        parent::__construct($context);
    }

    public function execute()
    {
        $fileId = (string)$this->getRequest()->getParam('param_name', 'image');
        if (!in_array($fileId, ['image', 'icon_image'], true)) {
            $fileId = 'image';
        }
        try {
            $result = $this->imageUploader->saveFileToTmpDir($fileId);
        } catch (\Exception $e) {
            $result = ['error' => $e->getMessage(), 'errorcode' => $e->getCode()];
        }
        return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData($result);
    }
}
