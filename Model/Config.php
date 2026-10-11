<?php
declare(strict_types=1);

namespace CommerceGears\MegaMenu\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Store-view scoped settings of Stores > Configuration > CommerceGears > Mega Menu.
 */
class Config
{
    private const PREFIX = 'commercegears_megamenu/';
    public const COLOUR_FIELDS = [
        'bar_bg', 'bar_text', 'bar_hover', 'panel_bg', 'panel_text', 'panel_heading', 'link_hover', 'label_bg', 'label_text',
    ];

    public function __construct(private readonly ScopeConfigInterface $scopeConfig)
    {
    }

    public function isEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(self::PREFIX . 'general/enabled', ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function getDepth(?int $storeId = null): int
    {
        return max(1, min(3, (int)($this->value('general/depth', $storeId) ?: 2)));
    }

    public function showViewAll(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(self::PREFIX . 'general/view_all', ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function getDesktopMode(?int $storeId = null): string
    {
        return $this->value('layout/desktop_mode', $storeId) === 'hamburger' ? 'hamburger' : 'horizontal';
    }

    public function getHamburgerLabel(?int $storeId = null): string
    {
        return trim((string)$this->value('layout/hamburger_label', $storeId)) ?: (string)__('All Categories');
    }

    public function getOpenOn(?int $storeId = null): string
    {
        return $this->value('layout/open_on', $storeId) === 'click' ? 'click' : 'hover';
    }

    public function getMobileMode(?int $storeId = null): string
    {
        return $this->value('layout/mobile_mode', $storeId) === 'accordion' ? 'accordion' : 'drilldown';
    }

    public function getBreakpoint(?int $storeId = null): int
    {
        return (int)$this->value('layout/breakpoint', $storeId) === 768 ? 768 : 1024;
    }

    public function isSticky(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(self::PREFIX . 'layout/sticky', ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @return array<string, string> field => #rrggbb (only valid, non-empty values)
     */
    public function getColours(?int $storeId = null): array
    {
        $colours = [];
        foreach (self::COLOUR_FIELDS as $field) {
            $value = trim((string)$this->value('colours/' . $field, $storeId));
            if (Colour::isValid($value)) {
                $colours[$field] = strtolower($value);
            }
        }
        return $colours;
    }

    private function value(string $path, ?int $storeId): mixed
    {
        return $this->scopeConfig->getValue(self::PREFIX . $path, ScopeInterface::SCOPE_STORE, $storeId);
    }
}
