<?php

use App\Support\TimedDismissal;

test('timed dismissal is not hidden when no timestamp is stored', function () {
    expect(TimedDismissal::isHidden(null, 1_000))->toBeFalse();
});

test('timed dismissal is hidden before the until timestamp', function () {
    expect(TimedDismissal::isHidden(2_000, 1_000))->toBeTrue();
});

test('timed dismissal is visible once the until timestamp has passed', function () {
    expect(TimedDismissal::isHidden(2_000, 2_000))->toBeFalse()
        ->and(TimedDismissal::isHidden(2_000, 2_001))->toBeFalse();
});

test('hidden until adds the requested duration', function () {
    expect(TimedDismissal::hiddenUntil(TimedDismissal::TWO_WEEKS_MS, 0))
        ->toBe(TimedDismissal::TWO_WEEKS_MS)
        ->and(TimedDismissal::hiddenUntil(TimedDismissal::TWO_YEARS_MS, 100))
        ->toBe(100 + TimedDismissal::TWO_YEARS_MS);
});

test('subscribe discovery durations match the product rules', function () {
    expect(TimedDismissal::TWO_WEEKS_MS)->toBe(14 * 24 * 60 * 60 * 1000)
        ->and(TimedDismissal::TWO_YEARS_MS)->toBe(2 * 365 * 24 * 60 * 60 * 1000);
});
