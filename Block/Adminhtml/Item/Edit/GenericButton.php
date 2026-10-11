<?php
declare(strict_types=1);

namespace CommerceGears\MegaMenu\Block\Adminhtml\Item\Edit;

use Magento\Backend\Block\Widget\Context;

class GenericButton
{
    public function __construct(protected readonly Context $context)
    {
    }

    public function getItemId(): int
    {
        return (int)$this->context->getRequest()->getParam('id');
    }

    public function getUrl(string $route = '', array $params = []): string
    {
        return $this->context->getUrlBuilder()->getUrl($route, $params);
    }
}
