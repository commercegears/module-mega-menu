<?php
declare(strict_types=1);

namespace CommerceGears\MegaMenu\Controller\Adminhtml\Builder;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultFactory;
use CommerceGears\MegaMenu\Model\CacheCleaner;
use CommerceGears\MegaMenu\Model\Menu\TreeValidator;
use CommerceGears\MegaMenu\Model\ResourceModel\Item as ItemResource;

/**
 * Saves the order and nesting from the drag and drop tree (JSON: [{id, parent, position}, ...]).
 */
class SaveTree extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'CommerceGears_MegaMenu::items_save';

    public function __construct(
        Context $context,
        private readonly ItemResource $itemResource,
        private readonly TreeValidator $treeValidator,
        private readonly CacheCleaner $cacheCleaner
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        try {
            $posted = json_decode((string)$this->getRequest()->getParam('tree'), true, 8, JSON_THROW_ON_ERROR);
            $connection = $this->itemResource->getConnection();
            $existing = $connection->fetchPairs(
                $connection->select()->from($this->itemResource->getMainTable(), ['item_id', 'item_type'])
            );
            $tree = $this->treeValidator->validate(is_array($posted) ? $posted : [], $existing);
            $this->itemResource->saveTree($tree);
            $this->cacheCleaner->clean();
            return $result->setData(['success' => true, 'message' => (string)__('The menu order has been saved.')]);
        } catch (\InvalidArgumentException|\JsonException $e) {
            return $result->setData(['success' => false, 'message' => (string)__('The order could not be saved: %1', $e->getMessage())]);
        }
    }
}
