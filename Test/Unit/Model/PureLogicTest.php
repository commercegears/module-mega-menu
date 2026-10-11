<?php
declare(strict_types=1);

namespace CommerceGears\MegaMenu\Test\Unit\Model;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use CommerceGears\MegaMenu\Model\Colour;
use CommerceGears\MegaMenu\Model\Menu\TreeValidator;
use CommerceGears\MegaMenu\Model\Schedule;
use CommerceGears\MegaMenu\Model\UrlResolver;

class PureLogicTest extends TestCase
{
    public function testScheduleWindowsAreInclusive(): void
    {
        $this->assertTrue(Schedule::isActive(null, null, '2026-10-11'));
        $this->assertTrue(Schedule::isActive('2026-10-11', '2026-10-11', '2026-10-11'));
        $this->assertTrue(Schedule::isActive('2026-10-01 00:00:00', '', '2026-10-11'));
        $this->assertFalse(Schedule::isActive('2026-10-12', null, '2026-10-11'));
        $this->assertFalse(Schedule::isActive(null, '2026-10-10', '2026-10-11'));
        $this->assertTrue(Schedule::isActive('0000-00-00', null, '2026-10-11'));
    }

    public function testUrlResolverAllowsHttpAndStorePathsOnly(): void
    {
        $base = 'https://shop.test/de/';
        $this->assertSame('https://shop.test/de/sale.html', UrlResolver::resolve('sale.html', $base));
        $this->assertSame('https://shop.test/de/customer/account/', UrlResolver::resolve('/customer/account/', $base));
        $this->assertSame('https://example.com/x?a=1', UrlResolver::resolve('https://example.com/x?a=1', $base));
        $this->assertSame('https://shop.test/de/#top', UrlResolver::resolve('#top', $base));
        foreach (['javascript:alert(1)', 'JaVaScRiPt:alert(1)', 'data:text/html,x', '//evil.test/', 'mailto:a@b.c', '', 'a b', 'x"onmouseover'] as $bad) {
            $this->assertNull(UrlResolver::resolve($bad, $base), $bad);
            $this->assertFalse(UrlResolver::isAllowed($bad), $bad);
        }
    }

    public function testColourSanitising(): void
    {
        $this->assertSame('#abcdef', Colour::sanitise('#ABCDEF'));
        $this->assertSame('#fff', Colour::sanitise(' #fff '));
        $this->assertSame('', Colour::sanitise('red'));
        $this->assertSame('', Colour::sanitise('#fff;background:url(x)'));
        $this->assertSame('', Colour::sanitise(null));
    }

    public function testTreeValidatorAcceptsThreeLevels(): void
    {
        $existing = [1 => 'category', 2 => 'heading', 3 => 'url', 4 => 'category_tree'];
        $tree = (new TreeValidator())->validate([
            ['id' => 1, 'parent' => null, 'position' => 10],
            ['id' => 2, 'parent' => 1, 'position' => 10],
            ['id' => 3, 'parent' => 2, 'position' => 10],
            ['id' => 4, 'parent' => null, 'position' => 20],
        ], $existing);
        $this->assertSame(['parent_id' => 2, 'position' => 10], $tree[3]);
        $this->assertNull($tree[1]['parent_id']);
    }

    #[DataProvider('invalidTrees')]
    public function testTreeValidatorRejects(array $posted): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new TreeValidator())->validate($posted, [1 => 'category', 2 => 'heading', 3 => 'url', 4 => 'category_tree', 5 => 'url']);
    }

    public static function invalidTrees(): array
    {
        return [
            'unknown item' => [[['id' => 99, 'parent' => null]]],
            'unknown parent' => [[['id' => 1, 'parent' => 99]]],
            'own parent' => [[['id' => 1, 'parent' => 1]]],
            'cycle' => [[['id' => 1, 'parent' => 2], ['id' => 2, 'parent' => 1]]],
            'four levels' => [[['id' => 1, 'parent' => null], ['id' => 2, 'parent' => 1], ['id' => 3, 'parent' => 2], ['id' => 5, 'parent' => 3]]],
            'child of category tree' => [[['id' => 4, 'parent' => null], ['id' => 3, 'parent' => 4]]],
            'malformed' => [['nope']],
        ];
    }
}
