<?php
declare(strict_types=1);

namespace CommerceGears\MegaMenu\Block;

use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use CommerceGears\MegaMenu\Model\Config;
use CommerceGears\MegaMenu\Model\Item;
use CommerceGears\MegaMenu\Model\Menu\TreeBuilder;

/**
 * The mega menu. One render for all screen sizes (CSS switches between desktop bar and mobile menu), cached per
 * store view and theme in the block cache and tagged for the full page cache, so category, CMS block and menu
 * item changes refresh it.
 */
class Menu extends Template implements IdentityInterface
{
    private ?array $tree = null;
    private ?array $identities = null;

    public function __construct(
        Context $context,
        private readonly Config $config,
        private readonly TreeBuilder $treeBuilder,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    protected function _construct(): void
    {
        parent::_construct();
        $this->setData('cache_lifetime', 86400);
    }

    public function getCacheKeyInfo(): array
    {
        return [
            'COMMERCEGEARS_MEGAMENU',
            $this->getStoreId(),
            $this->_design->getDesignTheme()->getId(),
            (int)$this->_request->isSecure(),
            $this->getTemplate(),
            (string)$this->getData('commercegears_mm_theme'),
        ];
    }

    public function getStoreId(): int
    {
        return (int)$this->_storeManager->getStore()->getId();
    }

    public function getConfig(): Config
    {
        return $this->config;
    }

    public function getTheme(): string
    {
        return $this->getData('commercegears_mm_theme') === 'hyva' ? 'hyva' : 'luma';
    }

    public function getNodes(): array
    {
        if ($this->tree === null) {
            $this->tree = $this->treeBuilder->build($this->getStoreId());
            $this->identities = $this->tree['identities'];
        }
        return $this->tree['nodes'];
    }

    /**
     * Cache tags of everything in the menu. When the HTML came from the block cache, the tags saved with it are used.
     */
    public function getIdentities(): array
    {
        if ($this->identities === null) {
            $saved = $this->_cache->load($this->getCacheKey() . '_ids');
            $this->identities = $saved ? (array)json_decode($saved, true) : [Item::CACHE_TAG, 'cat_c'];
        }
        return $this->identities;
    }

    protected function _toHtml(): string
    {
        if (!$this->config->isEnabled($this->getStoreId())) {
            return '';
        }
        $html = parent::_toHtml();
        $this->_cache->save(
            (string)json_encode($this->getIdentities()),
            $this->getCacheKey() . '_ids',
            [Item::CACHE_TAG, self::CACHE_GROUP],
            $this->getCacheLifetime()
        );
        return $html;
    }

    /**
     * CSS custom properties for the configured colours (validated hex values only).
     */
    public function getStyleAttribute(): string
    {
        $map = [
            'bar_bg' => '--commercegears-mm-bar-bg', 'bar_text' => '--commercegears-mm-bar-text', 'bar_hover' => '--commercegears-mm-bar-hover',
            'panel_bg' => '--commercegears-mm-panel-bg', 'panel_text' => '--commercegears-mm-panel-text',
            'panel_heading' => '--commercegears-mm-heading', 'link_hover' => '--commercegears-mm-link-hover',
            'label_bg' => '--commercegears-mm-label-bg', 'label_text' => '--commercegears-mm-label-text',
        ];
        $style = [];
        foreach ($this->config->getColours($this->getStoreId()) as $field => $colour) {
            $style[] = $map[$field] . ':' . $colour;
        }
        return implode(';', $style);
    }
}
