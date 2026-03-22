<?php

use App\Jobs\PurgeStaleTemporaryUploads;
use App\Models\TempUpload;

test('it deletes temp uploads older than 24 hours', function () {
    $stale = TempUpload::factory()->create(['created_at' => now()->subHours(25)]);
    $fresh = TempUpload::factory()->create(['created_at' => now()->subHours(23)]);

    (new PurgeStaleTemporaryUploads)->handle();

    expect(TempUpload::query()->find($stale->id))->toBeNull();
    expect(TempUpload::query()->find($fresh->id))->not->toBeNull();
});

test('it keeps temp uploads younger than 24 hours', function () {
    TempUpload::factory()->count(3)->create(['created_at' => now()->subHours(23)]);

    (new PurgeStaleTemporaryUploads)->handle();

    expect(TempUpload::query()->count())->toBe(3);
});

test('it purges all stale uploads in one run', function () {
    TempUpload::factory()->count(2)->create(['created_at' => now()->subHours(30)]);
    TempUpload::factory()->create(['created_at' => now()->subHours(1)]);

    (new PurgeStaleTemporaryUploads)->handle();

    expect(TempUpload::query()->count())->toBe(1);
});

test('it handles an empty table without errors', function () {
    (new PurgeStaleTemporaryUploads)->handle();

    expect(TempUpload::query()->count())->toBe(0);
});

test('exact 24-hour boundary: uploads at exactly 24 hours old are considered stale', function () {
    $exactlyStale = TempUpload::factory()->create(['created_at' => now()->subHours(24)->subSecond()]);
    $notYetStale = TempUpload::factory()->create(['created_at' => now()->subHours(24)->addSecond()]);

    (new PurgeStaleTemporaryUploads)->handle();

    expect(TempUpload::query()->find($exactlyStale->id))->toBeNull();
    expect(TempUpload::query()->find($notYetStale->id))->not->toBeNull();
});
