<?php
declare(strict_types=1);

namespace CommerceGears\MegaMenu\Model\Source;

use Magento\Cms\Model\ResourceModel\Page\CollectionFactory;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * CMS pages by ID.
 */
class CmsPageOptions implements OptionSourceInterface
{
    public function __construct(private readonly CollectionFactory $collectionFactory)
    {
    }

    public function toOptionArray(): array
    {
        $options = [];
        $collection = $this->collectionFactory->create()->setOrder('title', 'ASC');
        foreach ($collection as $page) {
            $options[] = [
                'value' => (string)$page->getId(),
                'label' => $page->getTitle() . ' (' . $page->getIdentifier() . ')'
                    . ($page->isActive() ? '' : ' [' . __('disabled') . ']'),
            ];
        }
        return $options;
    }
}
