<?php

namespace App\Support;

/**
 * Determines where advertisements appear in a paginated property list.
 */
final class PropertyListAds
{
    public const INTERVAL = 5;

    /**
     * Zero-based indexes into the current page of properties after which an ad should appear.
     *
     * - No properties: no ads
     * - Fewer than INTERVAL properties: one ad after the last property
     * - Otherwise: an ad after every INTERVAL-th property (by global list position)
     *
     * @return list<int>
     */
    public static function insertAfterIndexes(int $propertyCount, int $from = 1): array
    {
        if ($propertyCount === 0) {
            return [];
        }

        $indexes = [];

        for ($index = 0; $index < $propertyCount; $index++) {
            $globalPosition = $from + $index;

            if ($globalPosition % self::INTERVAL === 0) {
                $indexes[] = $index;
            }
        }

        if ($propertyCount < self::INTERVAL) {
            $lastIndex = $propertyCount - 1;

            if (! in_array($lastIndex, $indexes, true)) {
                $indexes[] = $lastIndex;
            }
        }

        return $indexes;
    }
}
