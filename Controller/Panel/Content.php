<?php
declare(strict_types=1);

namespace Softaware\MegaMenu\Controller\Panel;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\Http as HttpResponse;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Store\Model\StoreManagerInterface;
use Softaware\MegaMenu\Model\Config;
use Softaware\MegaMenu\Model\Item;
use Softaware\MegaMenu\Model\ItemFactory;
use Softaware\MegaMenu\Model\Menu\ContentRenderer;
use Softaware\MegaMenu\Model\ResourceModel\Item as ItemResource;
use Softaware\MegaMenu\Model\Schedule;

/**
 * Content of a lazily loaded dropdown panel. Public and cacheable (full page cache and Varnish), tagged like the menu.
 */
class Content implements HttpGetActionInterface
{
    private const TTL = 86400;

    public function __construct(
        private readonly RequestInterface $request,
        private readonly RawFactory $rawFactory,
        private readonly HttpResponse $response,
        private readonly ItemFactory $itemFactory,
        private readonly ItemResource $itemResource,
        private readonly ContentRenderer $contentRenderer,
        private readonly Config $config,
        private readonly StoreManagerInterface $storeManager,
        private readonly TimezoneInterface $timezone
    ) {
    }

    public function execute(): Raw
    {
        $result = $this->rawFactory->create();
        $storeId = (int)$this->storeManager->getStore()->getId();
        $item = $this->itemFactory->create();
        $this->itemResource->load($item, (int)$this->request->getParam('id'));
        $today = $this->timezone->scopeDate($storeId)->format('Y-m-d');
        $storeIds = $item->getStoreIds();

        if (!$this->config->isEnabled($storeId) || !$item->getId() || !$item->getData('is_active')
            || !$item->getData('content_lazy')
            || !(in_array(0, $storeIds, true) || in_array($storeId, $storeIds, true))
            || !Schedule::isActive($item->getData('active_from'), $item->getData('active_to'), $today)
        ) {
            $result->setHttpResponseCode(404);
            $result->setHeader('Cache-Control', 'no-store', true);
            return $result->setContents('');
        }
        $data = $item->getData();
        $data['image_alt'] = trim((string)($data['image_alt'] ?? '')) ?: (string)$item->getData('title');
        $rendered = $this->contentRenderer->render($data, $storeId);
        $tags = array_unique(array_merge([Item::CACHE_TAG], $rendered['identities']));

        $this->response->setPublicHeaders(self::TTL);
        $result->setHeader('Content-Type', 'text/html; charset=UTF-8', true);
        $result->setHeader('X-Robots-Tag', 'noindex', true);
        $result->setHeader('X-Magento-Tags', implode(',', $tags), true);
        return $result->setContents($rendered['html']);
    }
}
