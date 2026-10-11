<?php
declare(strict_types=1);

namespace CommerceGears\MegaMenu\Model\Menu;

use Magento\Cms\Model\BlockFactory;
use Magento\Cms\Model\Template\FilterProvider;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Escaper;
use Magento\Framework\Filesystem;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use CommerceGears\MegaMenu\Model\UrlResolver;

/**
 * Renders the content area of a dropdown panel: image, CMS block and own (Page Builder or HTML) content.
 * Used for the menu itself and for lazily loaded panels, so both give the same HTML.
 */
class ContentRenderer
{
    public const MEDIA_PATH = 'commercegears/megamenu';

    public function __construct(
        private readonly BlockFactory $blockFactory,
        private readonly FilterProvider $filterProvider,
        private readonly StoreManagerInterface $storeManager,
        private readonly Filesystem $filesystem,
        private readonly Escaper $escaper,
        private readonly LoggerInterface $logger
    ) {
    }

    public function hasContent(array $item): bool
    {
        return !empty($item['content_block_id']) || trim((string)($item['content_html'] ?? '')) !== ''
            || !empty($item['image']);
    }

    /**
     * @param array $item item row data
     * @return array{html: string, identities: string[]}
     */
    public function render(array $item, int $storeId): array
    {
        $html = '';
        $identities = [];
        if (!empty($item['image'])) {
            $html .= $this->renderImage($item, $storeId);
        }
        $blockId = (int)($item['content_block_id'] ?? 0);
        if ($blockId) {
            $block = $this->blockFactory->create();
            $block->setStoreId($storeId)->load($blockId);
            if ($block->getId() && $block->isActive()) {
                $identities = array_merge($identities, $block->getIdentities());
                $html .= '<div class="commercegears-mm__cms">' . $this->filter((string)$block->getContent(), $storeId) . '</div>';
            }
        }
        $own = trim((string)($item['content_html'] ?? ''));
        if ($own !== '') {
            $html .= '<div class="commercegears-mm__html">' . $this->filter($own, $storeId) . '</div>';
        }
        return ['html' => $html, 'identities' => $identities];
    }

    public function getMediaUrl(string $file, int $storeId): string
    {
        return $this->storeManager->getStore($storeId)->getBaseUrl(UrlInterface::URL_TYPE_MEDIA)
            . self::MEDIA_PATH . '/' . rawurlencode(basename($file));
    }

    /**
     * @return array{0:int,1:int}|null width and height of an uploaded image
     */
    public function getImageSize(string $file): ?array
    {
        $media = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);
        $path = self::MEDIA_PATH . '/' . basename($file);
        if (!$media->isFile($path)) {
            return null;
        }
        $size = @getimagesize($media->getAbsolutePath($path));
        return $size ? [(int)$size[0], (int)$size[1]] : null;
    }

    private function renderImage(array $item, int $storeId): string
    {
        $file = (string)$item['image'];
        $size = $this->getImageSize($file);
        if ($size === null) {
            return '';
        }
        $img = sprintf(
            '<img src="%s" alt="%s" width="%d" height="%d" loading="lazy" decoding="async">',
            $this->escaper->escapeUrl($this->getMediaUrl($file, $storeId)),
            $this->escaper->escapeHtmlAttr((string)($item['image_alt'] ?? '')),
            $size[0],
            $size[1]
        );
        $link = trim((string)($item['image_link'] ?? ''));
        $url = $link !== '' ? UrlResolver::resolve($link, $this->storeManager->getStore($storeId)->getBaseUrl()) : null;
        if ($url !== null) {
            $img = '<a href="' . $this->escaper->escapeUrl($url) . '">' . $img . '</a>';
        }
        return '<div class="commercegears-mm__image">' . $img . '</div>';
    }

    private function filter(string $content, int $storeId): string
    {
        try {
            return $this->filterProvider->getBlockFilter()->setStoreId($storeId)->filter($content);
        } catch (\Throwable $e) {
            $this->logger->error('CommerceGears_MegaMenu: content could not be rendered: ' . $e->getMessage());
            return '';
        }
    }
}
