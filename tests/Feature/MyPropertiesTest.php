<?php

use App\Models\Property;
use App\Models\PropertyStat;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('authenticated users can view only their own properties on my properties table', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $ownerProperty = Property::factory()->create(['user_id' => $owner->id]);
    Property::factory()->create(['user_id' => $otherUser->id]);

    $response = $this->actingAs($owner)->get(route('my-properties.index'));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('properties/MyProperties')
            ->has('properties', 1)
            ->where('properties.0.id', $ownerProperty->id)
            ->where('properties.0.street', $ownerProperty->street)
            ->where('properties.0.taken', $ownerProperty->taken)
        );
});

test('guests cannot access my properties table', function () {
    $this->get(route('my-properties.index'))->assertRedirect(route('login'));
});

test('owners can mark their property as taken', function () {
    $owner = User::factory()->create();
    $property = Property::factory()->create([
        'user_id' => $owner->id,
        'taken' => false,
    ]);

    $response = $this->actingAs($owner)->patch(route('my-properties.mark-as-taken', $property), [
        'how_got_taken' => 'tivuchfree',
        'price_taken_at' => 1234,
    ]);

    $response->assertRedirect();
    expect($property->fresh()->taken)->toBeTrue();

    $stat = PropertyStat::query()->where('property_id', $property->id)->first();
    expect($stat)->not->toBeNull();
    expect($stat->how_got_taken)->toBe('tivuchfree');
    expect((string) $stat->price_taken_at)->toBe('1234.00');
});

test('users cannot mark other users properties as taken', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $property = Property::factory()->create([
        'user_id' => $owner->id,
        'taken' => false,
    ]);

    $this->actingAs($otherUser)
        ->patch(route('my-properties.mark-as-taken', $property))
        ->assertForbidden();

    expect($property->fresh()->taken)->toBeFalse();
});
