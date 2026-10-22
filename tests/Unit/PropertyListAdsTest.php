<?php

use App\Support\PropertyListAds;

test('no ads are inserted when there are no properties', function () {
    expect(PropertyListAds::insertAfterIndexes(0))->toBe([]);
});

test('an ad is inserted after the last property when there are fewer than five', function (int $count) {
    expect(PropertyListAds::insertAfterIndexes($count))->toBe([$count - 1]);
})->with([1, 2, 3, 4]);

test('ads are inserted after every fifth property', function () {
    expect(PropertyListAds::insertAfterIndexes(10))->toBe([4, 9])
        ->and(PropertyListAds::insertAfterIndexes(5))->toBe([4])
        ->and(PropertyListAds::insertAfterIndexes(7))->toBe([4]);
});

test('pagination continues the every-fifth cadence', function () {
    expect(PropertyListAds::insertAfterIndexes(3, from: 13))->toBe([2])
        ->and(PropertyListAds::insertAfterIndexes(2, from: 13))->toBe([1])
        ->and(PropertyListAds::insertAfterIndexes(12, from: 13))->toBe([2, 7]);
});
