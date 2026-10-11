<?php

namespace App\Support;

/**
 * Helpers for client-side dismissible prompts that store a "hidden until" timestamp.
 */
final class TimedDismissal
{
    public const TWO_WEEKS_MS = 14 * 24 * 60 * 60 * 1000;

    public const TWO_YEARS_MS = 2 * 365 * 24 * 60 * 60 * 1000;

    public static function isHidden(?int $hiddenUntilMs, ?int $nowMs = null): bool
    {
        if ($hiddenUntilMs === null) {
            return false;
        }

        return ($nowMs ?? self::nowMs()) < $hiddenUntilMs;
    }

    public static function hiddenUntil(int $durationMs, ?int $nowMs = null): int
    {
        return ($nowMs ?? self::nowMs()) + $durationMs;
    }

    private static function nowMs(): int
    {
        return (int) floor(microtime(true) * 1000);
    }
}
