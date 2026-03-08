<?php

use App\Jobs\PurgeStaleTemporaryUploads;
use App\Mail\StaleUploadsPurged;
use App\Models\TempUpload;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();
});

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

test('it sends a summary email after purging', function () {
    TempUpload::factory()->count(2)->create(['created_at' => now()->subHours(30)]);

    (new PurgeStaleTemporaryUploads)->handle();

    Mail::assertSent(StaleUploadsPurged::class, function (StaleUploadsPurged $mail) {
        return $mail->hasTo('shaulispitzer@gmail.com')
            && $mail->uploadCount === 2
            && $mail->mediaCount === 0;
    });
});

test('it sends a summary email even when nothing was purged', function () {
    (new PurgeStaleTemporaryUploads)->handle();

    Mail::assertSent(StaleUploadsPurged::class, function (StaleUploadsPurged $mail) {
        return $mail->hasTo('shaulispitzer@gmail.com')
            && $mail->uploadCount === 0
            && $mail->mediaCount === 0;
    });
});

test('exact 24-hour boundary: uploads at exactly 24 hours old are considered stale', function () {
    $exactlyStale = TempUpload::factory()->create(['created_at' => now()->subHours(24)->subSecond()]);
    $notYetStale = TempUpload::factory()->create(['created_at' => now()->subHours(24)->addSecond()]);

    (new PurgeStaleTemporaryUploads)->handle();

    expect(TempUpload::query()->find($exactlyStale->id))->toBeNull();
    expect(TempUpload::query()->find($notYetStale->id))->not->toBeNull();
});
