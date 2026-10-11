<?php
declare(strict_types=1);

namespace Softaware\MegaMenu\Model;

/**
 * Hex colour checks, shared by the settings, the item form and the storefront output.
 */
class Colour
{
    public static function isValid(string $value): bool
    {
        return (bool)preg_match('/^#([0-9a-f]{3}|[0-9a-f]{6})$/i', $value);
    }

    /**
     * Returns the normalised colour or '' for anything that is not a hex colour (never trust stored values in CSS).
     */
    public static function sanitise(?string $value): string
    {
        $value = trim((string)$value);
        return self::isValid($value) ? strtolower($value) : '';
    }
}
