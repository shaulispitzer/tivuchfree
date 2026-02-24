<?php

use App\Enums\Neighbourhood;
use App\Enums\PropertyFurnished;
use App\Enums\PropertyLeaseType;
use App\Jobs\NotifyPropertySubscribers;
use App\Mail\PropertySubscriptionConfirmation;
use App\Mail\PropertySubscriptionNotification;
use App\Mail\PropertySubscriptionOtp;
use App\Models\Property;
use App\Models\PropertySubscription;
use App\Models\PropertySubscriptionPending;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

beforeEach(function () {
    Mail::fake();
});

test('logged-in user can subscribe to property updates', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('property-subscriptions.store'), [
        'filters' => [
            'neighbourhoods' => [Neighbourhood::Sanhedria->value],
            'hide_taken_properties' => false,
            'bedrooms_range' => [2, 4],
            'furnished' => '',
            'type' => '',
            'available_from' => '',
            'available_to' => '',
        ],
    ]);

    $response->assertRedirect(route('properties.index'));
    $this->assertDatabaseHas('property_subscriptions', [
        'email' => $user->email,
        'user_id' => $user->id,
    ]);
    Mail::assertSent(PropertySubscriptionConfirmation::class, function ($mail) use ($user) {
        return $mail->hasTo($user->email);
    });
});

test('guest can subscribe and receives otp email', function () {
    $response = $this->post(route('property-subscriptions.store'), [
        'email' => 'guest@example.com',
        'filters' => [
            'neighbourhoods' => [],
            'hide_taken_properties' => false,
            'bedrooms_range' => [1, 10],
            'furnished' => '',
            'type' => '',
            'available_from' => '',
            'available_to' => '',
        ],
    ]);

    $response->assertRedirect(route('subscribe'));
    $response->assertSessionHas('subscription_otp_pending');
    $this->assertDatabaseHas('property_subscription_pendings', [
        'email' => 'guest@example.com',
    ]);
    Mail::assertSent(PropertySubscriptionOtp::class);
});

test('guest can verify otp and complete subscription', function () {
    $pending = PropertySubscriptionPending::create([
        'email' => 'verify@example.com',
        'filters' => [
            'neighbourhoods' => [],
            'availability' => 'all',
            'bedrooms_min' => 1,
            'bedrooms_max' => 10,
            'furnished' => null,
            'type' => null,
            'available_from' => null,
            'available_to' => null,
        ],
        'otp_code' => '123456',
        'otp_expires_at' => now()->addMinutes(10),
    ]);

    $response = $this->post(route('property-subscriptions.verify-otp'), [
        'email' => 'verify@example.com',
        'otp' => '123456',
    ]);

    $response->assertRedirect(route('properties.index'));
    $this->assertDatabaseHas('property_subscriptions', [
        'email' => 'verify@example.com',
        'user_id' => null,
    ]);
    $this->assertDatabaseMissing('property_subscription_pendings', [
        'id' => $pending->id,
    ]);
    Mail::assertSent(PropertySubscriptionConfirmation::class, function ($mail) {
        return $mail->hasTo('verify@example.com');
    });
});

test('existing subscriber can update filters', function () {
    $user = User::factory()->create();
    $subscription = PropertySubscription::create([
        'email' => $user->email,
        'user_id' => $user->id,
        'filters' => [
            'neighbourhoods' => [Neighbourhood::Sanhedria->value],
            'availability' => 'all',
            'bedrooms_min' => 1,
            'bedrooms_max' => 10,
            'furnished' => null,
            'type' => null,
            'available_from' => null,
            'available_to' => null,
        ],
        'token' => Str::random(64),
        'subscribed_at' => now(),
        'expires_at' => now()->addDays(30),
    ]);

    $response = $this->actingAs($user)->post(route('property-subscriptions.store'), [
        'filters' => [
            'neighbourhoods' => [Neighbourhood::Geula->value],
            'hide_taken_properties' => true,
            'bedrooms_range' => [3, 5],
            'furnished' => PropertyFurnished::Yes->value,
            'type' => PropertyLeaseType::LongTerm->value,
            'available_from' => '',
            'available_to' => '',
        ],
    ]);

    $response->assertRedirect(route('properties.index'));
    $subscription->refresh();
    expect($subscription->filters['neighbourhoods'])->toEqual([Neighbourhood::Geula->value]);
    expect($subscription->filters['availability'])->toBe('available');
});

test('unsubscribe page shows confirmation', function () {
    $subscription = PropertySubscription::create([
        'email' => 'sub@example.com',
        'user_id' => null,
        'filters' => [],
        'token' => $token = Str::random(64),
        'subscribed_at' => now(),
        'expires_at' => now()->addDays(30),
    ]);

    $response = $this->get(route('subscriptions.unsubscribe', $token));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('subscription/UnsubscribeConfirm')
        ->has('email')
        ->where('email', 'sub@example.com')
        ->has('token'));
});

test('confirm unsubscribe removes subscription', function () {
    $subscription = PropertySubscription::create([
        'email' => 'sub@example.com',
        'user_id' => null,
        'filters' => [],
        'token' => $token = Str::random(64),
        'subscribed_at' => now(),
        'expires_at' => now()->addDays(30),
    ]);

    $response = $this->post(route('subscriptions.confirm-unsubscribe', $token));

    $response->assertRedirect(route('properties.index'));
    $subscription->refresh();
    expect($subscription->unsubscribed_at)->not->toBeNull();
});

test('new property triggers notification to matching subscribers', function () {
    $subscriber = PropertySubscription::create([
        'email' => 'subscriber@example.com',
        'user_id' => null,
        'filters' => [
            'neighbourhoods' => [Neighbourhood::Sanhedria->value],
            'availability' => 'all',
            'bedrooms_min' => 2,
            'bedrooms_max' => 4,
            'furnished' => null,
            'type' => null,
            'available_from' => null,
            'available_to' => null,
        ],
        'token' => Str::random(64),
        'subscribed_at' => now(),
        'expires_at' => now()->addDays(30),
    ]);

    $property = Property::factory()->create([
        'neighbourhoods' => [Neighbourhood::Sanhedria->value],
        'bedrooms' => 3,
        'taken' => false,
    ]);

    NotifyPropertySubscribers::dispatchSync($property);

    Mail::assertSent(PropertySubscriptionNotification::class, function ($mail) use ($subscriber) {
        return $mail->hasTo($subscriber->email);
    });
});
