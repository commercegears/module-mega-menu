<?php
declare(strict_types=1);

namespace CommerceGears\MegaMenu\Model\Source;

use Magento\Cms\Model\ResourceModel\Block\CollectionFactory;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * CMS blocks by ID, with an empty first option.
 */
class CmsBlockOptions implements OptionSourceInterface
{
    public function __construct(private readonly CollectionFactory $collectionFactory)
    {
    }

    public function toOptionArray(): array
    {
        $options = [['value' => '', 'label' => __('None')]];
        $collection = $this->collectionFactory->create()->setOrder('title', 'ASC');
        foreach ($collection as $block) {
            $options[] = [
                'value' => (string)$block->getId(),
                'label' => $block->getTitle() . ' (' . $block->getIdentifier() . ')'
                    . ($block->isActive() ? '' : ' [' . __('disabled') . ']'),
            ];
        }
        return $options;
    }
}
