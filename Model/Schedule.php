<?php
declare(strict_types=1);

namespace Softaware\MegaMenu\Model;

/**
 * Date windows for items and labels. Dates are whole days in the store's time zone; both ends are inclusive.
 */
class Schedule
{
    public static function isActive(?string $from, ?string $to, string $today): bool
    {
        $from = self::day($from);
        $to = self::day($to);
        if ($from !== null && $today < $from) {
            return false;
        }
        if ($to !== null && $today > $to) {
            return false;
        }
        return true;
    }

    private static function day(?string $value): ?string
    {
        $value = trim((string)$value);
        if ($value === '' || str_starts_with($value, '0000')) {
            return null;
        }
        return substr($value, 0, 10);
    }
}
