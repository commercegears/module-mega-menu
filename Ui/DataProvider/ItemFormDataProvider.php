<?php
declare(strict_types=1);

namespace CommerceGears\MegaMenu\Ui\DataProvider;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Filesystem;
use Magento\Ui\DataProvider\AbstractDataProvider;
use CommerceGears\MegaMenu\Model\Menu\ContentRenderer;
use CommerceGears\MegaMenu\Model\ResourceModel\Item\CollectionFactory;

class ItemFormDataProvider extends AbstractDataProvider
{
    public const PERSISTOR_KEY = 'commercegears_megamenu_item';

    private ?array $loadedData = null;

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        private readonly DataPersistorInterface $dataPersistor,
        private readonly ContentRenderer $contentRenderer,
        private readonly Filesystem $filesystem,
        private readonly RequestInterface $request,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    public function getData()
    {
        if ($this->loadedData !== null) {
            return $this->loadedData;
        }
        $this->loadedData = [];
        $id = (int)$this->request->getParam('id');
        if ($id) {
            $this->collection->addFieldToFilter('item_id', $id);
            foreach ($this->collection->getItems() as $item) {
                $data = $item->getData();
                $data['store_ids'] = array_map('strval', $item->getStoreIds() ?: [0]);
                foreach (['image', 'icon_image'] as $field) {
                    $data[$field] = $this->imageValue((string)$item->getData($field));
                }
                foreach (['category_id', 'cms_page_id', 'content_block_id', 'parent_id'] as $field) {
                    $data[$field] = $data[$field] === null ? '' : (string)$data[$field];
                }
                $this->loadedData[$item->getId()] = $data;
            }
        } elseif ($parent = (int)$this->request->getParam('parent')) {
            $this->loadedData[''] = ['parent_id' => (string)$parent, 'item_type' => 'category', 'store_ids' => ['0']];
        }
        $persisted = $this->dataPersistor->get(self::PERSISTOR_KEY);
        if (!empty($persisted)) {
            $this->loadedData[$persisted['item_id'] ?? ''] = $persisted;
            $this->dataPersistor->clear(self::PERSISTOR_KEY);
        }
        return $this->loadedData;
    }

    private function imageValue(string $file): array
    {
        if ($file === '') {
            return [];
        }
        $path = ContentRenderer::MEDIA_PATH . '/' . $file;
        $media = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);
        return [[
            'name' => $file,
            'file' => $file,
            'url' => $this->contentRenderer->getMediaUrl($file, 0),
            'size' => $media->isFile($path) ? (int)$media->stat($path)['size'] : 0,
            'type' => 'image',
        ]];
    }
}
