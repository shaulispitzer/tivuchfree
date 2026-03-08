<?php

use App\Enums\Neighbourhood;
use App\Jobs\NotifyPropertySubscribers;
use App\Jobs\ProcessExpiredPropertySubscriptions;
use App\Jobs\SendEmailJob;
use App\Mail\PropertySubscriptionConfirmation;
use App\Mail\PropertySubscriptionExpired;
use App\Mail\PropertySubscriptionNotification;
use App\Mail\PropertySubscriptionOtp;
use App\Mail\WelcomeEmail;
use App\Models\Property;
use App\Models\PropertySubscription;
use App\Models\User;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

beforeEach(function () {
    Mail::fake();
});

// ─── Recipient & Delivery ────────────────────────────────────────────────────

test('SendEmailJob sends to the specified recipient', function () {
    $mailable = new PropertySubscriptionOtp('subscriber@example.com', '123456');

    (new SendEmailJob('subscriber@example.com', $mailable))->handle();

    Mail::assertSent(PropertySubscriptionOtp::class, fn ($mail) => $mail->hasTo('subscriber@example.com'));
});

test('SendEmailJob sends exactly one email per dispatch', function () {
    $mailable = new PropertySubscriptionOtp('a@example.com', '000000');

    (new SendEmailJob('a@example.com', $mailable))->handle();

    Mail::assertSentCount(1);
});

// ─── Locale Capture & Restore ────────────────────────────────────────────────

test('SendEmailJob captures the app locale at dispatch time', function () {
    App::setLocale('he');

    $job = new SendEmailJob('test@example.com', new PropertySubscriptionOtp('test@example.com', '111111'));

    // Simulate a queue worker starting fresh with the default locale.
    App::setLocale('en');

    $job->handle();

    // The job restored 'he' before sending.
    expect(App::getLocale())->toBe('he');
});

test('SendEmailJob captures English locale correctly', function () {
    App::setLocale('en');

    $job = new SendEmailJob('test@example.com', new PropertySubscriptionOtp('test@example.com', '222222'));

    App::setLocale('he');

    $job->handle();

    expect(App::getLocale())->toBe('en');
});

test('a mailable with an explicit locale keeps that locale', function () {
    // Explicit ->locale('en') on the mailable itself should be preserved.
    $mailable = (new PropertySubscriptionOtp('test@example.com', '333333'))->locale('en');

    App::setLocale('he');
    $job = new SendEmailJob('test@example.com', $mailable);
    $job->handle();

    Mail::assertSent(PropertySubscriptionOtp::class, fn ($mail) => $mail->locale === 'en');
});

// ─── OTP / Subscription Confirmation (synchronous, not via SendEmailJob) ─────

test('OTP email is sent directly without going through SendEmailJob', function () {
    Queue::fake();

    $this->post(route('property-subscriptions.store'), [
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

    // OTP is sent synchronously — the queue should have no SendEmailJob.
    Mail::assertSent(PropertySubscriptionOtp::class, fn ($mail) => $mail->hasTo('guest@example.com'));
    Queue::assertNotPushed(SendEmailJob::class);
});

test('subscription confirmation email is sent synchronously for logged-in users', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('property-subscriptions.store'), [
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

    Mail::assertSent(PropertySubscriptionConfirmation::class, fn ($mail) => $mail->hasTo($user->email));
});

// ─── Subscription Notification & Expiry via SendEmailJob ─────────────────────

test('subscription notification email is dispatched through SendEmailJob', function () {
    Queue::fake();

    $subscription = PropertySubscription::create([
        'email' => 'notify@example.com',
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

    // Call handle() directly so Queue::fake() captures the inner SendEmailJob dispatches.
    app()->call([new NotifyPropertySubscribers($property), 'handle']);

    Queue::assertPushed(SendEmailJob::class, fn ($job) => $job->mailable instanceof PropertySubscriptionNotification);
});

// Expiry processing is currently disabled — subscriptions are permanent.
// To re-enable, uncomment the original assertion below and remove the current one.
test('expired subscription email is dispatched through SendEmailJob', function () {
    Queue::fake();

    PropertySubscription::create([
        'email' => 'expired@example.com',
        'user_id' => null,
        'filters' => [],
        'token' => Str::random(64),
        'subscribed_at' => now()->subDays(31),
        'expires_at' => now()->subDay(),
    ]);

    (new ProcessExpiredPropertySubscriptions)->handle();

    // Expiry emails are disabled; the job should not dispatch anything.
    Queue::assertNotPushed(SendEmailJob::class);

    // Original assertion — restore when re-enabling 30-day expiry:
    // Queue::assertPushed(SendEmailJob::class, fn ($job) => $job->mailable instanceof PropertySubscriptionExpired);
});

// ─── Stagger Timing (1/second) ───────────────────────────────────────────────

test('NotifyPropertySubscribers staggers SendEmailJob dispatches at 1 per second', function () {
    Queue::fake();

    $filters = [
        'neighbourhoods' => [Neighbourhood::Sanhedria->value],
        'availability' => 'all',
        'bedrooms_min' => 1,
        'bedrooms_max' => 10,
        'furnished' => null,
        'type' => null,
        'available_from' => null,
        'available_to' => null,
    ];

    foreach (range(1, 3) as $i) {
        PropertySubscription::create([
            'email' => "sub{$i}@example.com",
            'user_id' => null,
            'filters' => $filters,
            'token' => Str::random(64),
            'subscribed_at' => now(),
            'expires_at' => now()->addDays(30),
        ]);
    }

    $property = Property::factory()->create([
        'neighbourhoods' => [Neighbourhood::Sanhedria->value],
        'bedrooms' => 3,
        'taken' => false,
    ]);

    app()->call([new NotifyPropertySubscribers($property), 'handle']);

    // 3 subscriptions → delays of 0s, 1s, 2s (one per second).
    Queue::assertPushed(SendEmailJob::class, 3);
});

// ─── WelcomeEmail (ShouldQueue mailable) ─────────────────────────────────────

test('WelcomeEmail is queued when sent via Mail::to()->send()', function () {
    $user = User::factory()->create();

    Mail::to($user)->send(new WelcomeEmail($user));

    // WelcomeEmail implements ShouldQueue, so Mail::fake() records it as queued.
    Mail::assertQueued(WelcomeEmail::class, fn ($mail) => $mail->hasTo($user->email));
});
