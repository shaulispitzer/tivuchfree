<?php

use App\Enums\Neighbourhood;
use App\Enums\PropertyFurnished;
use App\Enums\PropertyLeaseType;
use App\Models\Property;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

it('creates a property without images', function () {

    /** @var User $user */
    $user = User::factory()->create();

    $response = actingAs($user)->post(route('properties.store'), [
        'neighbourhoods' => [Neighbourhood::Sanhedria->value],
        'street' => 'Main Street',
        'floor' => 2,
        'type' => PropertyLeaseType::LongTerm->value,
        'available_from' => Carbon::parse('2024-01-01')->toIso8601String(),
        'bedrooms' => 2,
        'furnished' => PropertyFurnished::Yes->value,
    ]);

    $response->assertStatus(302);

    $property = Property::query()->first();

    expect($property)->not->toBeNull();
    expect($property->user_id)->toBe($user->id);
    expect($property->getFirstMedia('main_image'))->toBeNull();

    $response->assertRedirect(route('properties.edit', $property));
});

it('stores bedrooms with one decimal place', function () {
    /** @var User $user */
    $user = User::factory()->create();

    $response = actingAs($user)->post(route('properties.store'), [
        'neighbourhoods' => [Neighbourhood::Gush80->value, Neighbourhood::BarIlan->value],
        'street' => 'Decimal Street',
        'floor' => 4.5,
        'type' => PropertyLeaseType::LongTerm->value,
        'available_from' => Carbon::parse('2024-01-01')->toIso8601String(),
        'bedrooms' => 1.5,
        'furnished' => PropertyFurnished::No->value,
    ]);

    $response->assertStatus(302);

    $property = Property::query()->latest()->first();

    expect($property)->not->toBeNull();
    expect($property->floor)->toBe(4.5);
    expect($property->bedrooms)->toBe(1.5);
});

it('rejects non-numeric floor values', function () {
    /** @var User $user */
    $user = User::factory()->create();

    $response = actingAs($user)
        ->from(route('properties.create'))
        ->post(route('properties.store'), [
            'neighbourhoods' => [Neighbourhood::Sanhedria->value],
            'street' => 'Validation Street',
            'floor' => 'second',
            'type' => PropertyLeaseType::LongTerm->value,
            'available_from' => Carbon::parse('2024-01-01')->toIso8601String(),
            'bedrooms' => 2,
            'furnished' => PropertyFurnished::No->value,
        ]);

    $response
        ->assertRedirect(route('properties.create'))
        ->assertSessionHasErrors(['floor']);
});

it('validates that no more than three neighbourhoods can be selected', function () {
    /** @var User $user */
    $user = User::factory()->create();

    $response = actingAs($user)
        ->from(route('properties.create'))
        ->post(route('properties.store'), [
            'neighbourhoods' => [
                Neighbourhood::Sanhedria->value,
                Neighbourhood::BarIlan->value,
                Neighbourhood::Gush80->value,
                Neighbourhood::Geula->value,
            ],
            'street' => 'Validation Street',
            'floor' => 1,
            'type' => PropertyLeaseType::LongTerm->value,
            'available_from' => Carbon::parse('2024-01-01')->toIso8601String(),
            'bedrooms' => 2,
            'furnished' => PropertyFurnished::No->value,
        ]);

    $response
        ->assertRedirect(route('properties.create'))
        ->assertSessionHasErrors(['neighbourhoods']);
});
