<?php
declare(strict_types=1);

namespace CommerceGears\MegaMenu\Model\Menu;

/**
 * Small built-in line icons (24 x 24, stroke = currentColor), so a menu item can get an icon without an upload.
 */
class Icons
{
    private const ICONS = [
        'tag' => ['Tag', '<path d="M3 12V3h9l9 9-9 9z"/><circle cx="7.5" cy="7.5" r="1.5"/>'],
        'percent' => ['Percent', '<path d="M19 5 5 19"/><circle cx="7" cy="7" r="2.5"/><circle cx="17" cy="17" r="2.5"/>'],
        'star' => ['Star', '<path d="m12 2 3 7 7 .3-5.5 4.7 2 7L12 17l-6.5 4 2-7L2 9.3 9 9z"/>'],
        'sparkle' => ['Sparkle', '<path d="M12 3v4M12 17v4M3 12h4M17 12h4M6 6l2.5 2.5M15.5 15.5 18 18M6 18l2.5-2.5M15.5 8.5 18 6"/>'],
        'bolt' => ['Lightning', '<path d="M13 2 4 14h7l-1 8 9-12h-7z"/>'],
        'flame' => ['Flame', '<path d="M12 22c4 0 7-3 7-7 0-5-4-7-5-12-3 3-4 5-4 8-1-1-2-2-2-4-2 2-3 5-3 8 0 4 3 7 7 7z"/>'],
        'gift' => ['Gift', '<path d="M3 8h18v5H3zM5 13v8h14v-8M12 8v13M12 8C10 4 6 4 6 6.5S10 8 12 8c2 0 6 0 6-1.5S14 4 12 8"/>'],
        'heart' => ['Heart', '<path d="M12 20s-8-5-8-11a4.5 4.5 0 0 1 8-2.8A4.5 4.5 0 0 1 20 9c0 6-8 11-8 11z"/>'],
        'shirt' => ['Clothing', '<path d="M8 3 3 6l2 4 2-1v12h10V9l2 1 2-4-5-3c-1 2-2 3-4 3S9 5 8 3z"/>'],
        'leaf' => ['Leaf', '<path d="M5 19C5 10 11 5 20 5c0 9-5 15-14 15zM5 19l8-8"/>'],
        'truck' => ['Delivery', '<path d="M2 6h12v10H2zM14 10h4l4 4v2h-8"/><circle cx="6" cy="18" r="2"/><circle cx="18" cy="18" r="2"/>'],
        'clock' => ['Clock', '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>'],
        'home' => ['Home', '<path d="M3 11 12 4l9 7M5 10v10h14V10"/>'],
        'info' => ['Information', '<circle cx="12" cy="12" r="9"/><path d="M12 11v6M12 7.5v.5"/>'],
    ];

    /**
     * @return array<string, string> code => label
     */
    public function getOptions(): array
    {
        return array_map(static fn(array $icon): string => $icon[0], self::ICONS);
    }

    public function has(string $code): bool
    {
        return isset(self::ICONS[$code]);
    }

    /**
     * Inline SVG markup; decorative, so hidden from screen readers.
     */
    public function getSvg(string $code, int $size = 18): string
    {
        if (!isset(self::ICONS[$code])) {
            return '';
        }
        return sprintf(
            '<svg class="sw-mm__icon" width="%1$d" height="%1$d" viewBox="0 0 24 24" fill="none" stroke="currentColor"'
            . ' stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">%2$s</svg>',
            $size,
            self::ICONS[$code][1]
        );
    }
}
