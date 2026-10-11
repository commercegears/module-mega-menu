<?php
declare(strict_types=1);

namespace CommerceGears\MegaMenu\Model\Source;

use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * All categories as "Root / Parent / Child", sorted by tree position (admin store names).
 */
class CategoryOptions implements OptionSourceInterface
{
    private ?array $options = null;

    public function __construct(private readonly CollectionFactory $collectionFactory)
    {
    }

    public function toOptionArray(): array
    {
        if ($this->options !== null) {
            return $this->options;
        }
        $collection = $this->collectionFactory->create();
        $collection->addAttributeToSelect('name')
            ->addFieldToFilter('level', ['gt' => 0])
            ->setStoreId(0)
            ->setOrder('path', 'ASC');
        $names = [];
        $rows = [];
        foreach ($collection as $category) {
            $names[(int)$category->getId()] = (string)$category->getName();
            $rows[] = $category;
        }
        $labelled = [];
        foreach ($rows as $category) {
            $parts = [];
            foreach (array_slice(explode('/', (string)$category->getPath()), 1) as $id) {
                $parts[] = $names[(int)$id] ?? $id;
            }
            $labelled[] = [
                'value' => (string)$category->getId(),
                'label' => implode(' / ', $parts) . ' (ID ' . $category->getId() . ')',
                'sort' => mb_strtolower(implode("\0", $parts)),
            ];
        }
        usort($labelled, static fn(array $a, array $b): int => strcmp($a['sort'], $b['sort']));
        $this->options = array_map(
            static fn(array $row): array => ['value' => $row['value'], 'label' => $row['label']],
            $labelled
        );
        return $this->options;
    }
}
