<?php
declare(strict_types=1);

namespace CommerceGears\MegaMenu\Model\Menu;

use CommerceGears\MegaMenu\Model\Item;

/**
 * Checks a tree posted by the menu builder: known items only, no cycles, at most three levels, and no children
 * below a category tree item.
 */
class TreeValidator
{
    public const MAX_LEVELS = 3;

    /**
     * @param array $posted [['id' => int, 'parent' => int|null, 'position' => int], ...]
     * @param array<int|string, string> $existing item_id => item_type
     * @return array<int, array{parent_id: ?int, position: int}>
     * @throws \InvalidArgumentException
     */
    public function validate(array $posted, array $existing): array
    {
        $tree = [];
        foreach ($posted as $row) {
            if (!is_array($row) || !isset($row['id'])) {
                throw new \InvalidArgumentException('malformed entry');
            }
            $id = (int)$row['id'];
            $parent = (int)($row['parent'] ?? 0) ?: null;
            if (!isset($existing[$id])) {
                throw new \InvalidArgumentException(sprintf('item %d does not exist', $id));
            }
            if ($parent !== null && !isset($existing[$parent])) {
                throw new \InvalidArgumentException(sprintf('parent %d does not exist', $parent));
            }
            if ($parent === $id) {
                throw new \InvalidArgumentException(sprintf('item %d cannot be its own parent', $id));
            }
            if ($parent !== null && $existing[$parent] === Item::TYPE_CATEGORY_TREE) {
                throw new \InvalidArgumentException('a category tree item cannot have children');
            }
            $tree[$id] = ['parent_id' => $parent, 'position' => (int)($row['position'] ?? 0)];
        }
        foreach (array_keys($tree) as $id) {
            $level = 1;
            $seen = [$id => true];
            $current = $tree[$id]['parent_id'];
            while ($current !== null) {
                if (isset($seen[$current])) {
                    throw new \InvalidArgumentException(sprintf('item %d is inside itself', $id));
                }
                $seen[$current] = true;
                $level++;
                $current = $tree[$current]['parent_id'] ?? null;
            }
            if ($level > self::MAX_LEVELS) {
                throw new \InvalidArgumentException(sprintf('item %d is more than %d levels deep', $id, self::MAX_LEVELS));
            }
        }
        return $tree;
    }
}
