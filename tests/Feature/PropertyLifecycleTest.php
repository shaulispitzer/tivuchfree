<?php

use App\Mail\PropertyTakenWarning;
use App\Models\Property;
use App\Models\PropertyStat;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;

test('mark as taken sets taken_at timestamp', function () {
    $user = User::factory()->create();
    $property = Property::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->patch(route('my-properties.mark-as-taken', $property))
        ->assertRedirect();

    $property->refresh();
    expect($property->taken)->toBeTrue();
    expect($property->taken_at)->not->toBeNull();
    expect(PropertyStat::query()->where('property_id', $property->id)->exists())->toBeTrue();
});

test('non-owner cannot mark property as taken', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $property = Property::factory()->create(['user_id' => $owner->id]);

    $this->actingAs($otherUser)
        ->patch(route('my-properties.mark-as-taken', $property))
        ->assertForbidden();
});

test('owner can repost a taken property', function () {
    $user = User::factory()->create();
    $property = Property::factory()->taken()->create([
        'user_id' => $user->id,
        'created_at' => now()->subDays(35),
        'taken_warning_sent_at' => now()->subDays(5),
    ]);

    $this->actingAs($user)
        ->patch(route('my-properties.repost', $property))
        ->assertRedirect();

    $property->refresh();
    expect($property->taken)->toBeFalse();
    expect($property->taken_at)->toBeNull();
    expect($property->taken_warning_sent_at)->toBeNull();
    expect($property->created_at->isToday())->toBeTrue();
});

test('non-owner cannot repost a property', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $property = Property::factory()->taken()->create(['user_id' => $owner->id]);

    $this->actingAs($otherUser)
        ->patch(route('my-properties.repost', $property))
        ->assertForbidden();
});

test('repost does nothing if property is not taken', function () {
    $user = User::factory()->create();
    $originalCreatedAt = now()->subDays(10);
    $property = Property::factory()->create([
        'user_id' => $user->id,
        'created_at' => $originalCreatedAt,
    ]);

    $this->actingAs($user)
        ->patch(route('my-properties.repost', $property))
        ->assertRedirect();

    $property->refresh();
    expect($property->taken)->toBeFalse();
    expect($property->created_at->format('Y-m-d'))->toBe($originalCreatedAt->format('Y-m-d'));
});

test('lifecycle command marks properties as taken after 30 days', function () {
    $oldProperty = Property::factory()->create([
        'created_at' => now()->subDays(31),
    ]);

    $recentProperty = Property::factory()->create([
        'created_at' => now()->subDays(10),
    ]);

    $this->artisan('properties:process-lifecycle')->assertSuccessful();

    expect($oldProperty->fresh()->taken)->toBeTrue();
    expect($oldProperty->fresh()->taken_at)->not->toBeNull();
    expect($recentProperty->fresh()->taken)->toBeFalse();
});

test('lifecycle command deletes properties taken for 14+ days', function () {
    $deletableProperty = Property::factory()->taken()->create([
        'taken_at' => now()->subDays(15),
    ]);

    $recentlyTakenProperty = Property::factory()->taken()->create([
        'taken_at' => now()->subDays(5),
    ]);

    $availableProperty = Property::factory()->create();

    $this->artisan('properties:process-lifecycle')->assertSuccessful();

    expect(Property::find($deletableProperty->id))->toBeNull();
    expect(Property::find($recentlyTakenProperty->id))->not->toBeNull();
    expect(Property::find($availableProperty->id))->not->toBeNull();

    expect(PropertyStat::query()->where('property_id', $deletableProperty->id)->exists())->toBeTrue();
});

test('lifecycle command sends warning email 3 days before auto-mark', function () {
    Mail::fake();

    $user = User::factory()->create();
    $warningProperty = Property::factory()->create([
        'user_id' => $user->id,
        'created_at' => now()->subDays(28),
    ]);

    $tooNewProperty = Property::factory()->create([
        'user_id' => $user->id,
        'created_at' => now()->subDays(20),
    ]);

    $this->artisan('properties:process-lifecycle')->assertSuccessful();

    Mail::assertQueued(PropertyTakenWarning::class, function (PropertyTakenWarning $mail) use ($warningProperty) {
        return $mail->property->id === $warningProperty->id;
    });

    Mail::assertQueued(PropertyTakenWarning::class, 1);

    expect($warningProperty->fresh()->taken_warning_sent_at)->not->toBeNull();
    expect($tooNewProperty->fresh()->taken_warning_sent_at)->toBeNull();
});

test('lifecycle command does not send duplicate warning emails', function () {
    Mail::fake();

    $user = User::factory()->create();
    Property::factory()->create([
        'user_id' => $user->id,
        'created_at' => now()->subDays(28),
        'taken_warning_sent_at' => now()->subDay(),
    ]);

    $this->artisan('properties:process-lifecycle')->assertSuccessful();

    Mail::assertNothingQueued();
});

test('my properties page includes taken_at data', function () {
    $user = User::factory()->create();
    $takenProperty = Property::factory()->taken()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get(route('my-properties.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('properties/MyProperties')
            ->has('properties', 1)
            ->where('properties.0.taken', true)
            ->has('properties.0.taken_at')
        );
});

test('owner can delete their property', function () {
    $user = User::factory()->create();
    $property = Property::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->delete(route('my-properties.destroy', $property))
        ->assertRedirect(route('my-properties.index'));

    expect(Property::find($property->id))->toBeNull();
    expect(PropertyStat::query()->where('property_id', $property->id)->exists())->toBeTrue();
});

test('deleting a taken property does not create duplicate property stats', function () {
    $user = User::factory()->create();
    $property = Property::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->patch(route('my-properties.mark-as-taken', $property), [
            'how_got_taken' => 'agent',
        ])
        ->assertRedirect();

    $this->actingAs($user)
        ->delete(route('my-properties.destroy', $property))
        ->assertRedirect(route('my-properties.index'));

    expect(PropertyStat::query()->where('property_id', $property->id)->count())->toBe(1);
});

test('non-owner cannot delete a property', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $property = Property::factory()->create(['user_id' => $owner->id]);

    $this->actingAs($otherUser)
        ->delete(route('my-properties.destroy', $property))
        ->assertForbidden();

    expect(Property::find($property->id))->not->toBeNull();
});

test('edit page includes lifecycle info for available property', function () {
    $this->travelTo(now()->startOfDay()->addHours(12));

    $user = User::factory()->create();
    $property = Property::factory()->create([
        'user_id' => $user->id,
        'created_at' => now()->subDays(10),
    ]);

    $this->actingAs($user)
        ->get(route('properties.edit', $property))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('properties/Edit')
            ->has('lifecycle')
            ->where('lifecycle.taken', false)
            ->where('lifecycle.next_action', 'marked_as_taken')
            ->where('lifecycle.days_remaining', 20)
            ->has('lifecycle.posted_at')
        );
});

test('edit page includes lifecycle info for taken property', function () {
    $this->travelTo(now()->startOfDay()->addHours(12));

    $user = User::factory()->create();
    $property = Property::factory()->taken()->create([
        'user_id' => $user->id,
        'taken_at' => now()->subDays(5),
    ]);

    $this->actingAs($user)
        ->get(route('properties.edit', $property))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('properties/Edit')
            ->has('lifecycle')
            ->where('lifecycle.taken', true)
            ->where('lifecycle.next_action', 'deletion')
            ->where('lifecycle.days_remaining', 9)
        );
});
