<?php
declare(strict_types=1);

namespace CommerceGears\MegaMenu\Test\Unit\Model;

use Magento\Cms\Helper\Page as PageHelper;
use Magento\Cms\Model\ResourceModel\Page\CollectionFactory as PageCollectionFactory;
use Magento\Framework\DataObject;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;
use CommerceGears\MegaMenu\Model\Config;
use CommerceGears\MegaMenu\Model\Menu\CategoryProvider;
use CommerceGears\MegaMenu\Model\Menu\ContentRenderer;
use CommerceGears\MegaMenu\Model\Menu\Icons;
use CommerceGears\MegaMenu\Model\Menu\TreeBuilder;
use CommerceGears\MegaMenu\Model\ResourceModel\Item\Collection;
use CommerceGears\MegaMenu\Model\ResourceModel\Item\CollectionFactory;

class TreeBuilderTest extends TestCase
{
    private const CATEGORIES = [
        10 => ['id' => 10, 'name' => 'Women', 'url' => 'https://s.test/women.html', 'parent' => 2, 'level' => 2, 'in_menu' => true, 'children' => [11, 12]],
        11 => ['id' => 11, 'name' => 'Tops', 'url' => 'https://s.test/tops.html', 'parent' => 10, 'level' => 3, 'in_menu' => true, 'children' => [13]],
        12 => ['id' => 12, 'name' => 'Hidden', 'url' => 'https://s.test/hidden.html', 'parent' => 10, 'level' => 3, 'in_menu' => false, 'children' => []],
        13 => ['id' => 13, 'name' => 'Jackets', 'url' => 'https://s.test/jackets.html', 'parent' => 11, 'level' => 4, 'in_menu' => true, 'children' => []],
        20 => ['id' => 20, 'name' => 'Men', 'url' => 'https://s.test/men.html', 'parent' => 2, 'level' => 2, 'in_menu' => true, 'children' => []],
    ];

    private function builder(array $rows, int $depth = 2): TreeBuilder
    {
        $items = array_map(static fn(array $row) => new DataObject($row + ["id" => $row["item_id"] ?? null]), $rows);
        $collection = $this->createMock(Collection::class);
        foreach (['addFieldToFilter', 'addStoreFilter', 'setOrder'] as $method) {
            $collection->method($method)->willReturnSelf();
        }
        $collection->method('getIterator')->willReturn(new \ArrayIterator($items));
        $factory = $this->createMock(CollectionFactory::class);
        $factory->method('create')->willReturn($collection);

        $categories = $this->createMock(CategoryProvider::class);
        $categories->method('getNodes')->willReturn(self::CATEGORIES);
        $categories->method('getRootId')->willReturn(2);
        $categories->method('getMenuChildren')->willReturnCallback(static function (array $nodes, int $parent): array {
            return array_values(array_map(static fn($n) => $n['id'], array_filter($nodes, static fn($n) => $n['parent'] === $parent && $n['in_menu'])));
        });

        $content = $this->createMock(ContentRenderer::class);
        $content->method('hasContent')->willReturnCallback(static fn(array $row) => !empty($row['content_html']));
        $content->method('render')->willReturn(['html' => '<p>promo</p>', 'identities' => ['cms_b_5']]);

        $config = $this->createMock(Config::class);
        $config->method('getDepth')->willReturn($depth);
        $config->method('showViewAll')->willReturn(true);

        $timezone = $this->createMock(TimezoneInterface::class);
        $timezone->method('scopeDate')->willReturn(new \DateTime('2026-10-11'));
        $store = $this->createMock(Store::class);
        $store->method('getBaseUrl')->willReturn('https://s.test/');
        $storeManager = $this->createMock(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);
        $url = $this->createMock(UrlInterface::class);
        $url->method('getUrl')->willReturn('https://s.test/swmegamenu/panel/content/id/1/');

        return new TreeBuilder(
            $factory,
            $categories,
            $this->createMock(PageCollectionFactory::class),
            $this->createMock(PageHelper::class),
            $content,
            new Icons(),
            $config,
            $timezone,
            $storeManager,
            $url
        );
    }

    private static function row(array $data): array
    {
        return $data + [
            'parent_id' => null, 'is_active' => 1, 'title' => '', 'category_id' => null, 'cms_page_id' => null, 'url' => null,
            'open_new_tab' => 0, 'show_subcategories' => 1, 'visibility' => 'all', 'css_class' => null,
            'active_from' => null, 'active_to' => null, 'label_text' => null, 'label_bg' => null, 'label_color' => null,
            'label_from' => null, 'label_to' => null, 'icon' => null, 'icon_image' => null, 'columns' => 4,
            'panel_width' => 'full', 'content_position' => 'right', 'content_lazy' => 0, 'content_html' => null,
        ];
    }

    public function testNoItemsFallsBackToCategoryTree(): void
    {
        $result = $this->builder([])->build(1);
        $this->assertSame(['Women', 'Men'], array_column($result['nodes'], 'title'));
        $women = $result['nodes'][0];
        $this->assertSame(['Tops'], array_column($women['children'], 'title'), 'categories hidden from menus are left out');
        $this->assertSame('Jackets', $women['children'][0]['children'][0]['title']);
        $this->assertNotNull($women['panel']);
        $this->assertNull($result['nodes'][1]['panel'], 'no subcategories and no content: no dropdown');
        $this->assertContains('cat_c_13', $result['identities']);
        $this->assertContains('sw_megamenu', $result['identities']);
    }

    public function testDepthLimitsSubcategories(): void
    {
        $result = $this->builder([], 1)->build(1);
        $this->assertSame([], $result['nodes'][0]['children'][0]['children']);
    }

    public function testItemsSchedulesLabelsAndContent(): void
    {
        $rows = [
            self::row(['item_id' => 1, 'item_type' => 'category', 'category_id' => 10, 'label_text' => 'Sale',
                'label_bg' => '#FF0000', 'label_color' => 'red;x', 'content_html' => '<p>x</p>', 'icon' => 'tag', 'columns' => 9]),
            self::row(['item_id' => 2, 'item_type' => 'url', 'title' => 'Later', 'url' => 'later.html', 'active_from' => '2026-12-01']),
            self::row(['item_id' => 3, 'item_type' => 'heading', 'title' => 'Help', 'content_html' => '<p>x</p>', 'content_lazy' => 1, 'visibility' => 'mobile']),
            self::row(['item_id' => 4, 'item_type' => 'url', 'title' => 'Contact', 'url' => 'contact', 'parent_id' => 3]),
            self::row(['item_id' => 5, 'item_type' => 'url', 'title' => 'Bad', 'url' => 'javascript:alert(1)', 'parent_id' => 3]),
            self::row(['item_id' => 6, 'item_type' => 'url', 'title' => 'Orphan', 'url' => 'x', 'parent_id' => 2]),
            self::row(['item_id' => 7, 'item_type' => 'category', 'category_id' => 999]),
            self::row(['item_id' => 8, 'item_type' => 'url', 'title' => 'Old label', 'url' => 'y', 'label_text' => 'Old', 'label_to' => '2026-10-10']),
        ];
        $nodes = $this->builder($rows)->build(1)['nodes'];
        $this->assertSame(['Women', 'Help', 'Old label'], array_column($nodes, 'title'), 'scheduled, orphaned and missing items are hidden');
        $women = $nodes[0];
        $this->assertSame(['text' => 'Sale', 'bg' => '#ff0000', 'color' => ''], $women['label']);
        $this->assertStringContainsString('<svg', $women['icon_svg']);
        $this->assertSame('<p>promo</p>', $women['panel']['content']);
        $this->assertSame(6, $women['panel']['columns']);
        $this->assertTrue($women['panel']['view_all']);
        $help = $nodes[1];
        $this->assertNull($help['url']);
        $this->assertSame('sw-mm--mobile-only', $help['class']);
        $this->assertSame('', $help['panel']['content']);
        $this->assertNotNull($help['panel']['lazy_url']);
        $this->assertSame(['https://s.test/contact', null], array_column($help['children'], 'url'));
        $this->assertNull($nodes[2]['label'], 'label after its end date is not shown');
    }
}
