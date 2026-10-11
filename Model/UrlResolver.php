<?php
declare(strict_types=1);

namespace Softaware\MegaMenu\Model;

/**
 * Custom link targets: absolute http(s) addresses as entered, everything else relative to the store's base URL.
 * Other schemes (javascript:, data:, mailto: ...) are refused.
 */
class UrlResolver
{
    public static function isAllowed(string $url): bool
    {
        $url = trim($url);
        if ($url === '' || preg_match('/[\x00-\x1f\s<>"]/', $url)) {
            return false;
        }
        if (str_starts_with($url, '//')) {
            return false;
        }
        if (preg_match('/^([a-z][a-z0-9+.-]*):/i', $url, $m)) {
            return in_array(strtolower($m[1]), ['http', 'https'], true)
                && filter_var($url, FILTER_VALIDATE_URL) !== false;
        }
        return true;
    }

    public static function resolve(string $url, string $baseUrl): ?string
    {
        $url = trim($url);
        if (!self::isAllowed($url)) {
            return null;
        }
        if (preg_match('/^https?:/i', $url)) {
            return $url;
        }
        if (str_starts_with($url, '#') || str_starts_with($url, '?')) {
            return rtrim($baseUrl, '/') . '/' . $url;
        }
        return rtrim($baseUrl, '/') . '/' . ltrim($url, '/');
    }
}
