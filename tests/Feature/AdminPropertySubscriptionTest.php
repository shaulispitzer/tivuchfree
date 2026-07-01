<?php

use App\Mail\PropertySubscriptionConfirmation;
use App\Models\PropertySubscription;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Mail::fake();
});

test('admins can view subscriptions table', function () {
    $admin = User::factory()->admin()->create();
    $subscription = PropertySubscription::create([
        'email' => 'subscriber@example.com',
        'user_id' => null,
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
        'token' => Str::random(64),
        'subscribed_at' => now(),
        'expires_at' => null,
    ]);

    $response = $this->actingAs($admin)->get(route('admin.subscriptions.index'));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/subscriptions/Index')
            ->has('subscriptions.data', 1)
            ->where('subscriptions.data.0.email', $subscription->email)
            ->where('filters.search', '')
            ->where('filters.status', 'all')
        );
});

test('admins can search subscriptions by email', function () {
    $admin = User::factory()->admin()->create();

    PropertySubscription::create([
        'email' => 'match@example.com',
        'user_id' => null,
        'filters' => [],
        'token' => Str::random(64),
        'subscribed_at' => now(),
        'expires_at' => null,
    ]);

    PropertySubscription::create([
        'email' => 'other@example.com',
        'user_id' => null,
        'filters' => [],
        'token' => Str::random(64),
        'subscribed_at' => now(),
        'expires_at' => null,
    ]);

    $response = $this->actingAs($admin)->get(route('admin.subscriptions.index', [
        'search' => 'match@',
    ]));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('subscriptions.data', 1)
            ->where('subscriptions.data.0.email', 'match@example.com')
            ->where('filters.search', 'match@')
        );
});

test('admins can filter subscriptions by status', function () {
    $admin = User::factory()->admin()->create();

    PropertySubscription::create([
        'email' => 'active@example.com',
        'user_id' => null,
        'filters' => [],
        'token' => Str::random(64),
        'subscribed_at' => now(),
        'expires_at' => null,
    ]);

    PropertySubscription::create([
        'email' => 'inactive@example.com',
        'user_id' => null,
        'filters' => [],
        'token' => Str::random(64),
        'subscribed_at' => now(),
        'expires_at' => null,
        'unsubscribed_at' => now(),
    ]);

    $response = $this->actingAs($admin)->get(route('admin.subscriptions.index', [
        'status' => 'unsubscribed',
    ]));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('subscriptions.data', 1)
            ->where('subscriptions.data.0.email', 'inactive@example.com')
            ->where('filters.status', 'unsubscribed')
            ->where('subscriptions.data.0.is_active', false)
            ->whereNotNull('subscriptions.data.0.unsubscribed_at')
        );
});

test('admins can create subscriptions without otp verification', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->post(route('admin.subscriptions.store'), [
        'email' => 'new-subscriber@example.com',
        'filters' => [
            'neighbourhoods' => [neighbourhoodId()],
            'hide_taken_properties' => true,
            'bedrooms_range' => [2, 4],
            'furnished' => '',
            'type' => '',
            'available_from' => '',
            'available_to' => '',
        ],
    ]);

    $response->assertRedirect(route('admin.subscriptions.index'));

    $subscription = PropertySubscription::query()
        ->where('email', 'new-subscriber@example.com')
        ->first();

    expect($subscription)->not->toBeNull();
    expect($subscription->token)->toHaveLength(64);
    expect($subscription->filters['availability'])->toBe('available');

    Mail::assertSent(PropertySubscriptionConfirmation::class, function ($mail) {
        return $mail->hasTo('new-subscriber@example.com');
    });
});

test('admin creating subscription for existing user links user id', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create(['email' => 'linked@example.com']);

    $this->actingAs($admin)->post(route('admin.subscriptions.store'), [
        'email' => 'linked@example.com',
        'filters' => [
            'neighbourhoods' => [],
            'hide_taken_properties' => false,
            'bedrooms_range' => [1, 10],
            'furnished' => '',
            'type' => '',
            'available_from' => '',
            'available_to' => '',
        ],
    ])->assertRedirect(route('admin.subscriptions.index'));

    expect(PropertySubscription::query()->where('email', 'linked@example.com')->value('user_id'))
        ->toBe($user->id);
});

test('admins can delete subscriptions', function () {
    $admin = User::factory()->admin()->create();
    $subscription = PropertySubscription::create([
        'email' => 'delete-me@example.com',
        'user_id' => null,
        'filters' => [],
        'token' => Str::random(64),
        'subscribed_at' => now(),
        'expires_at' => null,
    ]);

    $response = $this->actingAs($admin)->delete(
        route('admin.subscriptions.destroy', $subscription),
    );

    $response->assertRedirect(route('admin.subscriptions.index'));
    expect(PropertySubscription::query()->find($subscription->id))->toBeNull();
});

test('admins can unsubscribe subscriptions without deleting them', function () {
    $admin = User::factory()->admin()->create();
    $subscription = PropertySubscription::create([
        'email' => 'unsubscribe-me@example.com',
        'user_id' => null,
        'filters' => [],
        'token' => Str::random(64),
        'subscribed_at' => now(),
        'expires_at' => null,
    ]);

    $response = $this->actingAs($admin)->post(
        route('admin.subscriptions.unsubscribe', $subscription),
    );

    $response->assertRedirect(route('admin.subscriptions.index'));

    $subscription->refresh();

    expect($subscription->unsubscribed_at)->not->toBeNull();
});

test('admins can subscribe unsubscribed subscriptions again', function () {
    $admin = User::factory()->admin()->create();
    $subscription = PropertySubscription::create([
        'email' => 'resubscribe-me@example.com',
        'user_id' => null,
        'filters' => [],
        'token' => Str::random(64),
        'subscribed_at' => now()->subDay(),
        'expires_at' => null,
        'unsubscribed_at' => now(),
    ]);

    $response = $this->actingAs($admin)->post(
        route('admin.subscriptions.subscribe', $subscription),
    );

    $response->assertRedirect(route('admin.subscriptions.index'));

    $subscription->refresh();

    expect($subscription->unsubscribed_at)->toBeNull();
});

test('non admins cannot access admin subscriptions', function () {
    /** @var User $user */
    $user = User::factory()->create();
    $subscription = PropertySubscription::create([
        'email' => 'sub@example.com',
        'user_id' => null,
        'filters' => [],
        'token' => Str::random(64),
        'subscribed_at' => now(),
        'expires_at' => null,
    ]);

    $this->actingAs($user)->get(route('admin.subscriptions.index'))->assertForbidden();
    $this->actingAs($user)->get(route('admin.subscriptions.create'))->assertForbidden();
    $this->actingAs($user)->post(route('admin.subscriptions.store'), [
        'email' => 'sub@example.com',
        'filters' => [
            'neighbourhoods' => [],
            'hide_taken_properties' => false,
            'bedrooms_range' => [1, 10],
            'furnished' => '',
            'type' => '',
            'available_from' => '',
            'available_to' => '',
        ],
    ])->assertForbidden();
    $this->actingAs($user)->delete(route('admin.subscriptions.destroy', $subscription))->assertForbidden();
    $this->actingAs($user)->post(route('admin.subscriptions.unsubscribe', $subscription))->assertForbidden();
    $this->actingAs($user)->post(route('admin.subscriptions.subscribe', $subscription))->assertForbidden();
});

test('guests cannot access admin subscriptions', function () {
    $subscription = PropertySubscription::create([
        'email' => 'sub@example.com',
        'user_id' => null,
        'filters' => [],
        'token' => Str::random(64),
        'subscribed_at' => now(),
        'expires_at' => null,
    ]);

    $this->get(route('admin.subscriptions.index'))->assertRedirect(route('login'));
    $this->get(route('admin.subscriptions.create'))->assertRedirect(route('login'));
    $this->post(route('admin.subscriptions.store'), [
        'email' => 'sub@example.com',
        'filters' => [
            'neighbourhoods' => [],
            'hide_taken_properties' => false,
            'bedrooms_range' => [1, 10],
            'furnished' => '',
            'type' => '',
            'available_from' => '',
            'available_to' => '',
        ],
    ])->assertRedirect(route('login'));
    $this->delete(route('admin.subscriptions.destroy', $subscription))->assertRedirect(route('login'));
    $this->post(route('admin.subscriptions.unsubscribe', $subscription))->assertRedirect(route('login'));
    $this->post(route('admin.subscriptions.subscribe', $subscription))->assertRedirect(route('login'));
});
