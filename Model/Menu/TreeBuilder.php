<?php
declare(strict_types=1);

namespace CommerceGears\MegaMenu\Model\Menu;

use Magento\Cms\Helper\Page as PageHelper;
use Magento\Cms\Model\ResourceModel\Page\CollectionFactory as PageCollectionFactory;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use CommerceGears\MegaMenu\Model\Colour;
use CommerceGears\MegaMenu\Model\Config;
use CommerceGears\MegaMenu\Model\Item;
use CommerceGears\MegaMenu\Model\ResourceModel\Item\CollectionFactory as ItemCollectionFactory;
use CommerceGears\MegaMenu\Model\Schedule;
use CommerceGears\MegaMenu\Model\UrlResolver;

/**
 * Builds the menu tree of a store view from the menu items (or from the category tree when the store view has
 * no items) and collects the cache tags of everything it used.
 */
class TreeBuilder
{
    private array $identities = [];
    private array $categoryNodes = [];
    private int $depth = 2;

    public function __construct(
        private readonly ItemCollectionFactory $itemCollectionFactory,
        private readonly CategoryProvider $categoryProvider,
        private readonly PageCollectionFactory $pageCollectionFactory,
        private readonly PageHelper $pageHelper,
        private readonly ContentRenderer $contentRenderer,
        private readonly Icons $icons,
        private readonly Config $config,
        private readonly TimezoneInterface $timezone,
        private readonly StoreManagerInterface $storeManager,
        private readonly UrlInterface $urlBuilder
    ) {
    }

    /**
     * @return array{nodes: array, identities: string[]}
     */
    public function build(int $storeId): array
    {
        $this->identities = [Item::CACHE_TAG, 'cat_c'];
        $this->depth = $this->config->getDepth($storeId);
        $today = $this->timezone->scopeDate($storeId)->format('Y-m-d');

        $rows = $this->loadItems($storeId, $today);
        if (!$rows) {
            $rows = [0 => $this->defaultTreeItem()];
        }
        $byParent = [];
        $categoryIds = [];
        $pageIds = [];
        foreach ($rows as $id => $row) {
            $byParent[(int)($row['parent_id'] ?? 0)][] = $id;
            if (!empty($row['category_id'])) {
                $categoryIds[] = (int)$row['category_id'];
            }
            if ($row['item_type'] === Item::TYPE_CMS_PAGE && !empty($row['cms_page_id'])) {
                $pageIds[] = (int)$row['cms_page_id'];
            }
        }
        $this->categoryNodes = $this->categoryProvider->getNodes($storeId, $this->depth, array_values(array_unique($categoryIds)));
        $pages = $this->loadPages($pageIds, $storeId);
        $baseUrl = $this->storeManager->getStore($storeId)->getBaseUrl();

        $nodes = [];
        foreach ($byParent[0] ?? [] as $id) {
            foreach ($this->buildItem($rows[$id], $rows, $byParent, $pages, $baseUrl, 0, $storeId, $today) as $node) {
                $nodes[] = $node;
            }
        }
        return ['nodes' => $nodes, 'identities' => array_values(array_unique($this->identities))];
    }

    /**
     * Active items of the store view in tree order, without items outside their date window.
     *
     * @return array<int, array>
     */
    private function loadItems(int $storeId, string $today): array
    {
        $collection = $this->itemCollectionFactory->create()
            ->addFieldToFilter('is_active', 1)
            ->addStoreFilter($storeId)
            ->setOrder('position', 'ASC')
            ->setOrder('item_id', 'ASC');
        $rows = [];
        foreach ($collection as $item) {
            $row = $item->getData();
            if (!in_array($row['item_type'], Item::TYPES, true)
                || !Schedule::isActive($row['active_from'] ?? null, $row['active_to'] ?? null, $today)) {
                continue;
            }
            $rows[(int)$item->getId()] = $row;
        }
        // Children of hidden or missing parents are hidden too.
        $changed = true;
        while ($changed) {
            $changed = false;
            foreach ($rows as $id => $row) {
                $parent = (int)($row['parent_id'] ?? 0);
                if ($parent && !isset($rows[$parent])) {
                    unset($rows[$id]);
                    $changed = true;
                }
            }
        }
        return $rows;
    }

    private function defaultTreeItem(): array
    {
        return [
            'item_id' => 0, 'parent_id' => null, 'item_type' => Item::TYPE_CATEGORY_TREE, 'category_id' => null,
            'show_subcategories' => 1, 'columns' => 4, 'panel_width' => 'full', 'visibility' => 'all',
        ];
    }

    /**
     * One item can stand for several nodes (category tree), or none (missing category or page).
     *
     * @return array[]
     */
    private function buildItem(
        array $row,
        array $rows,
        array $byParent,
        array $pages,
        string $baseUrl,
        int $level,
        int $storeId,
        string $today
    ): array {
        $type = (string)$row['item_type'];
        if ($type === Item::TYPE_CATEGORY_TREE) {
            $parent = (int)($row['category_id'] ?? 0);
            if (!$parent || !isset($this->categoryNodes[$parent])) {
                $parent = $this->categoryProvider->getRootId($storeId);
            }
            $nodes = [];
            foreach ($this->categoryProvider->getMenuChildren($this->categoryNodes, $parent) as $categoryId) {
                $node = $this->categoryNode($categoryId, !empty($row['show_subcategories']) ? $this->depth - $level : 0, $level);
                $node['class'] = $this->classes($row);
                if ($level === 0) {
                    $node['panel'] = $this->panel($row, $node, $storeId, false);
                }
                $nodes[] = $node;
            }
            return $nodes;
        }

        $node = [
            'key' => 'i' . (int)$row['item_id'],
            'title' => trim((string)($row['title'] ?? '')),
            'url' => null,
            'new_tab' => !empty($row['open_new_tab']),
            'icon_svg' => $this->icons->getSvg((string)($row['icon'] ?? '')),
            'icon_url' => !empty($row['icon_image'])
                ? $this->contentRenderer->getMediaUrl((string)$row['icon_image'], $storeId) : '',
            'label' => $this->label($row, $today),
            'class' => $this->classes($row),
            'children' => [],
        ];
        switch ($type) {
            case Item::TYPE_CATEGORY:
                $categoryId = (int)($row['category_id'] ?? 0);
                if (!isset($this->categoryNodes[$categoryId])) {
                    return [];
                }
                $category = $this->categoryNode(
                    $categoryId,
                    !empty($row['show_subcategories']) ? $this->depth - $level : 0,
                    $level
                );
                $node['title'] = $node['title'] !== '' ? $node['title'] : $category['title'];
                $node['url'] = $category['url'];
                $node['children'] = $category['children'];
                $node['category'] = true;
                break;
            case Item::TYPE_CMS_PAGE:
                $page = $pages[(int)($row['cms_page_id'] ?? 0)] ?? null;
                if ($page === null) {
                    return [];
                }
                $node['title'] = $node['title'] !== '' ? $node['title'] : $page['title'];
                $node['url'] = $page['url'];
                $this->identities[] = 'cms_p_' . $page['id'];
                break;
            case Item::TYPE_URL:
                $node['url'] = UrlResolver::resolve((string)($row['url'] ?? ''), $baseUrl);
                break;
            default:
                break;
        }
        if ($node['title'] === '') {
            return [];
        }
        foreach ($byParent[(int)$row['item_id']] ?? [] as $childId) {
            if ($level >= 2) {
                break; // three levels of own items at most
            }
            foreach ($this->buildItem($rows[$childId], $rows, $byParent, $pages, $baseUrl, $level + 1, $storeId, $today) as $child) {
                $node['children'][] = $child;
            }
        }
        if ($level === 0) {
            $node['panel'] = $this->panel($row, $node, $storeId, true);
        }
        return [$node];
    }

    /**
     * Category with its subcategories (only those shown in menus) down to $levels levels.
     */
    private function categoryNode(int $categoryId, int $levels, int $level): array
    {
        $category = $this->categoryNodes[$categoryId];
        $this->identities[] = 'cat_c_' . $categoryId;
        $node = [
            'key' => 'c' . $categoryId,
            'title' => $category['name'],
            'url' => $category['url'],
            'new_tab' => false,
            'icon_svg' => '',
            'icon_url' => '',
            'label' => null,
            'class' => '',
            'children' => [],
            'category' => true,
        ];
        if ($levels > 0 && $level < 3) {
            foreach ($category['children'] as $childId) {
                if (!empty($this->categoryNodes[$childId]['in_menu'])) {
                    $node['children'][] = $this->categoryNode($childId, $levels - 1, $level + 1);
                }
            }
        }
        return $node;
    }

    private function panel(array $row, array $node, int $storeId, bool $withContent): ?array
    {
        $hasContent = $withContent && $this->contentRenderer->hasContent($row);
        if (!$node['children'] && !$hasContent) {
            return null;
        }
        $content = '';
        $lazyUrl = null;
        if ($hasContent) {
            if (!empty($row['content_lazy'])) {
                $lazyUrl = $this->urlBuilder->getUrl(
                    'commercegears_megamenu/panel/content',
                    ['id' => (int)$row['item_id'], '_scope' => $storeId, '_nosid' => true]
                );
            } else {
                $row['image_alt'] = trim((string)($row['image_alt'] ?? '')) ?: $node['title'];
                $rendered = $this->contentRenderer->render($row, $storeId);
                $content = $rendered['html'];
                $this->identities = array_merge($this->identities, $rendered['identities']);
            }
        }
        return [
            'columns' => max(1, min(6, (int)($row['columns'] ?? 4) ?: 4)),
            'width' => ($row['panel_width'] ?? 'full') === 'auto' ? 'auto' : 'full',
            'position' => in_array($row['content_position'] ?? '', ['left', 'bottom'], true) ? $row['content_position'] : 'right',
            'content' => $content,
            'lazy_url' => $lazyUrl,
            'view_all' => !empty($node['category']) && $node['url'] && $this->config->showViewAll($storeId),
        ];
    }

    private function label(array $row, string $today): ?array
    {
        $text = trim((string)($row['label_text'] ?? ''));
        if ($text === '' || !Schedule::isActive($row['label_from'] ?? null, $row['label_to'] ?? null, $today)) {
            return null;
        }
        return [
            'text' => $text,
            'bg' => Colour::sanitise($row['label_bg'] ?? null),
            'color' => Colour::sanitise($row['label_color'] ?? null),
        ];
    }

    private function classes(array $row): string
    {
        $classes = [];
        $visibility = (string)($row['visibility'] ?? 'all');
        if ($visibility === 'desktop' || $visibility === 'mobile') {
            $classes[] = 'commercegears-mm--' . $visibility . '-only';
        }
        $custom = preg_replace('/[^A-Za-z0-9_\- ]/', '', (string)($row['css_class'] ?? ''));
        if (trim((string)$custom) !== '') {
            $classes[] = trim((string)$custom);
        }
        return implode(' ', $classes);
    }

    /**
     * @return array<int, array{id:int,title:string,url:string}>
     */
    private function loadPages(array $pageIds, int $storeId): array
    {
        if (!$pageIds) {
            return [];
        }
        $collection = $this->pageCollectionFactory->create()
            ->addFieldToFilter('page_id', ['in' => array_unique($pageIds)])
            ->addFieldToFilter('is_active', 1)
            ->addStoreFilter($storeId);
        $pages = [];
        foreach ($collection as $page) {
            $url = $this->pageHelper->getPageUrl((int)$page->getId());
            if ($url) {
                $pages[(int)$page->getId()] = ['id' => (int)$page->getId(), 'title' => (string)$page->getTitle(), 'url' => $url];
            }
        }
        return $pages;
    }
}
