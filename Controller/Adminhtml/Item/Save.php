<?php
declare(strict_types=1);

namespace CommerceGears\MegaMenu\Controller\Adminhtml\Item;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Catalog\Model\ImageUploader;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\Filter\Date as DateFilter;
use CommerceGears\MegaMenu\Model\Colour;
use CommerceGears\MegaMenu\Model\Item;
use CommerceGears\MegaMenu\Model\ItemFactory;
use CommerceGears\MegaMenu\Model\Menu\Icons;
use CommerceGears\MegaMenu\Model\Menu\TreeValidator;
use CommerceGears\MegaMenu\Model\ResourceModel\Item as ItemResource;
use CommerceGears\MegaMenu\Model\UrlResolver;
use CommerceGears\MegaMenu\Ui\DataProvider\ItemFormDataProvider;

class Save extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'CommerceGears_MegaMenu::items_save';

    private const FIELDS = [
        'is_active', 'item_type', 'title', 'category_id', 'cms_page_id', 'url', 'open_new_tab', 'show_subcategories',
        'parent_id', 'visibility', 'css_class', 'active_from', 'active_to', 'label_text', 'label_bg', 'label_color',
        'label_from', 'label_to', 'icon', 'columns', 'panel_width', 'content_position', 'content_block_id',
        'content_html', 'content_lazy', 'image_alt', 'image_link',
    ];

    public function __construct(
        Context $context,
        private readonly ItemFactory $itemFactory,
        private readonly ItemResource $itemResource,
        private readonly DataPersistorInterface $dataPersistor,
        private readonly ImageUploader $imageUploader,
        private readonly DateFilter $dateFilter,
        private readonly TreeValidator $treeValidator,
        private readonly Icons $icons
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $redirect = $this->resultRedirectFactory->create();
        $data = (array)$this->getRequest()->getPostValue();
        if (!$data) {
            return $redirect->setPath('*/builder/index');
        }
        $id = (int)($data['item_id'] ?? 0);
        $item = $this->itemFactory->create();
        if ($id) {
            $this->itemResource->load($item, $id);
            if (!$item->getId()) {
                $this->messageManager->addErrorMessage(__('This menu item no longer exists.'));
                return $redirect->setPath('*/builder/index');
            }
        }
        try {
            $clean = $this->clean($data, $id);
            $clean['image'] = $this->processImage($data['image'] ?? null);
            $clean['icon_image'] = $this->processImage($data['icon_image'] ?? null);
            if (!$id) {
                $clean['position'] = $this->nextPosition($clean['parent_id']);
            }
            $item->addData($clean);
            $item->setData('store_ids', $this->storeIds($data['store_ids'] ?? []));
            $this->itemResource->save($item);
            $this->dataPersistor->clear(ItemFormDataProvider::PERSISTOR_KEY);
            $this->messageManager->addSuccessMessage(__('The menu item has been saved.'));
            if ($this->getRequest()->getParam('back')) {
                return $redirect->setPath('*/*/edit', ['id' => $item->getId()]);
            }
            return $redirect->setPath('*/builder/index');
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('The menu item could not be saved.'));
        }
        $this->dataPersistor->set(ItemFormDataProvider::PERSISTOR_KEY, $data);
        return $id ? $redirect->setPath('*/*/edit', ['id' => $id]) : $redirect->setPath('*/*/new');
    }

    /**
     * @throws LocalizedException
     */
    private function clean(array $data, int $id): array
    {
        $clean = [];
        foreach (self::FIELDS as $field) {
            $value = $data[$field] ?? null;
            $clean[$field] = is_string($value) ? trim($value) : $value;
        }
        $type = (string)$clean['item_type'];
        if (!in_array($type, Item::TYPES, true)) {
            throw new LocalizedException(__('Please choose a type.'));
        }
        foreach (['is_active', 'open_new_tab', 'show_subcategories', 'content_lazy'] as $flag) {
            $clean[$flag] = (int)(bool)$clean[$flag];
        }
        foreach (['category_id', 'cms_page_id', 'content_block_id', 'parent_id'] as $ref) {
            $clean[$ref] = (int)$clean[$ref] ?: null;
        }
        if ($type === Item::TYPE_CATEGORY && !$clean['category_id']) {
            throw new LocalizedException(__('Please choose a category.'));
        }
        if ($type === Item::TYPE_CMS_PAGE && !$clean['cms_page_id']) {
            throw new LocalizedException(__('Please choose a CMS page.'));
        }
        if ($type === Item::TYPE_URL && !UrlResolver::isAllowed((string)$clean['url'])) {
            throw new LocalizedException(__('Please enter a link: a full http(s) address or a path in this store.'));
        }
        if (in_array($type, [Item::TYPE_URL, Item::TYPE_HEADING], true) && (string)$clean['title'] === '') {
            throw new LocalizedException(__('Please enter a title.'));
        }
        if ((string)$clean['image_link'] !== '' && !UrlResolver::isAllowed((string)$clean['image_link'])) {
            throw new LocalizedException(__('The image link must be a full http(s) address or a path in this store.'));
        }
        if (!in_array($type, [Item::TYPE_CATEGORY, Item::TYPE_CATEGORY_TREE], true)) {
            $clean['category_id'] = null;
        }
        if ($type !== Item::TYPE_CMS_PAGE) {
            $clean['cms_page_id'] = null;
        }
        if ($type !== Item::TYPE_URL) {
            $clean['url'] = null;
        }
        foreach (['label_bg', 'label_color'] as $colour) {
            if ((string)$clean[$colour] !== '' && !Colour::isValid((string)$clean[$colour])) {
                throw new LocalizedException(__('Label colours must be hex values such as #d32f2f.'));
            }
            $clean[$colour] = Colour::sanitise((string)$clean[$colour]) ?: null;
        }
        foreach (['active_from', 'active_to', 'label_from', 'label_to'] as $date) {
            $clean[$date] = (string)$clean[$date] !== '' ? $this->dateFilter->filter((string)$clean[$date]) : null;
        }
        if ($clean['active_from'] && $clean['active_to'] && $clean['active_from'] > $clean['active_to']) {
            throw new LocalizedException(__('"Show Until" must not be before "Show From".'));
        }
        if ($clean['label_from'] && $clean['label_to'] && $clean['label_from'] > $clean['label_to']) {
            throw new LocalizedException(__('"Show Label Until" must not be before "Show Label From".'));
        }
        $clean['icon'] = $this->icons->has((string)$clean['icon']) ? $clean['icon'] : null;
        $clean['columns'] = max(1, min(6, (int)$clean['columns'] ?: 4));
        $clean['panel_width'] = $clean['panel_width'] === 'auto' ? 'auto' : 'full';
        $clean['content_position'] = in_array($clean['content_position'], ['left', 'bottom'], true) ? $clean['content_position'] : 'right';
        $clean['visibility'] = in_array($clean['visibility'], ['desktop', 'mobile'], true) ? $clean['visibility'] : 'all';
        $clean['css_class'] = trim((string)preg_replace('/[^A-Za-z0-9_\- ]/', '', (string)$clean['css_class'])) ?: null;
        $this->checkParent($id, $clean['parent_id'], $type);
        return $clean;
    }

    /**
     * The new place must keep the tree valid (no cycles, three levels, no children below a category tree).
     *
     * @throws LocalizedException
     */
    private function checkParent(int $id, ?int $parentId, string $type): void
    {
        $connection = $this->itemResource->getConnection();
        $rows = $connection->fetchAll(
            $connection->select()->from($this->itemResource->getMainTable(), ['item_id', 'parent_id', 'item_type', 'position'])
        );
        $existing = [];
        $posted = [];
        foreach ($rows as $row) {
            $existing[(int)$row['item_id']] = (string)$row['item_type'];
            $posted[(int)$row['item_id']] = ['id' => (int)$row['item_id'], 'parent' => $row['parent_id'], 'position' => (int)$row['position']];
        }
        $selfId = $id ?: PHP_INT_MAX;
        $existing[$selfId] = $type;
        $posted[$selfId] = ['id' => $selfId, 'parent' => $parentId, 'position' => 0];
        if ($type === Item::TYPE_CATEGORY_TREE) {
            foreach ($posted as $row) {
                if ((int)$row['parent'] === $selfId) {
                    throw new LocalizedException(__('A category tree item cannot have items below it. Move them first.'));
                }
            }
        }
        try {
            $this->treeValidator->validate(array_values($posted), $existing);
        } catch (\InvalidArgumentException $e) {
            throw new LocalizedException(__('This parent item is not possible: %1.', $e->getMessage()));
        }
    }

    private function nextPosition(?int $parentId): int
    {
        $connection = $this->itemResource->getConnection();
        $select = $connection->select()->from($this->itemResource->getMainTable(), ['MAX(position)']);
        $parentId ? $select->where('parent_id = ?', $parentId) : $select->where('parent_id IS NULL');
        return (int)$connection->fetchOne($select) + 10;
    }

    private function storeIds(mixed $value): array
    {
        $ids = array_map('intval', is_array($value) ? $value : explode(',', (string)$value));
        return !$ids || in_array(0, $ids, true) ? [0] : array_values(array_unique($ids));
    }

    /**
     * @param mixed $value uploader value: [['name' => ..., 'file' => ..., 'tmp_name' => ...]]
     */
    private function processImage(mixed $value): ?string
    {
        if (!is_array($value) || empty($value[0]['name'])) {
            return null;
        }
        $image = $value[0];
        $name = basename((string)($image['file'] ?? $image['name']));
        if (!empty($image['tmp_name'])) {
            $name = (string)$this->imageUploader->moveFileFromTmp($name, true);
        }
        return basename($name);
    }
}
