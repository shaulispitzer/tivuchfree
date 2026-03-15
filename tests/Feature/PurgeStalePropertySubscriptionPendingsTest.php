<?php

use App\Jobs\PurgeStalePropertySubscriptionPendings;
use App\Models\PropertySubscriptionPending;
use Illuminate\Support\Facades\DB;

test('it deletes pending subscriptions older than one week', function () {
    $stale = PropertySubscriptionPending::create([
        'email' => 'stale@example.com',
        'filters' => ['neighbourhoods' => [], 'availability' => 'all', 'bedrooms_min' => 1, 'bedrooms_max' => 10, 'furnished' => null, 'type' => null, 'available_from' => null, 'available_to' => null],
        'otp_code' => '111111',
        'otp_expires_at' => now()->addMinutes(10),
    ]);
    DB::table('property_subscription_pendings')->where('id', $stale->id)->update(['created_at' => now()->subWeek()->subDay()]);

    $fresh = PropertySubscriptionPending::create([
        'email' => 'fresh@example.com',
        'filters' => ['neighbourhoods' => [], 'availability' => 'all', 'bedrooms_min' => 1, 'bedrooms_max' => 10, 'furnished' => null, 'type' => null, 'available_from' => null, 'available_to' => null],
        'otp_code' => '222222',
        'otp_expires_at' => now()->addMinutes(10),
    ]);

    (new PurgeStalePropertySubscriptionPendings)->handle();

    expect(PropertySubscriptionPending::query()->find($stale->id))->toBeNull();
    expect(PropertySubscriptionPending::query()->find($fresh->id))->not->toBeNull();
});

test('it keeps pending subscriptions younger than one week', function () {
    $pending = PropertySubscriptionPending::create([
        'email' => 'recent@example.com',
        'filters' => ['neighbourhoods' => [], 'availability' => 'all', 'bedrooms_min' => 1, 'bedrooms_max' => 10, 'furnished' => null, 'type' => null, 'available_from' => null, 'available_to' => null],
        'otp_code' => '333333',
        'otp_expires_at' => now()->addMinutes(10),
    ]);
    DB::table('property_subscription_pendings')->where('id', $pending->id)->update(['created_at' => now()->subDays(6)]);

    (new PurgeStalePropertySubscriptionPendings)->handle();

    expect(PropertySubscriptionPending::query()->find($pending->id))->not->toBeNull();
});

test('exact one-week boundary: pendings at exactly one week old are deleted', function () {
    $exactlyStale = PropertySubscriptionPending::create([
        'email' => 'boundary@example.com',
        'filters' => ['neighbourhoods' => [], 'availability' => 'all', 'bedrooms_min' => 1, 'bedrooms_max' => 10, 'furnished' => null, 'type' => null, 'available_from' => null, 'available_to' => null],
        'otp_code' => '444444',
        'otp_expires_at' => now()->addMinutes(10),
    ]);
    DB::table('property_subscription_pendings')->where('id', $exactlyStale->id)->update(['created_at' => now()->subWeek()->subSecond()]);

    $notYetStale = PropertySubscriptionPending::create([
        'email' => 'notyet@example.com',
        'filters' => ['neighbourhoods' => [], 'availability' => 'all', 'bedrooms_min' => 1, 'bedrooms_max' => 10, 'furnished' => null, 'type' => null, 'available_from' => null, 'available_to' => null],
        'otp_code' => '555555',
        'otp_expires_at' => now()->addMinutes(10),
    ]);
    DB::table('property_subscription_pendings')->where('id', $notYetStale->id)->update(['created_at' => now()->subWeek()->addSecond()]);

    (new PurgeStalePropertySubscriptionPendings)->handle();

    expect(PropertySubscriptionPending::query()->find($exactlyStale->id))->toBeNull();
    expect(PropertySubscriptionPending::query()->find($notYetStale->id))->not->toBeNull();
});
