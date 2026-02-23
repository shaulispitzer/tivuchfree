<?php

use App\Enums\Neighbourhood;
use App\Enums\PropertyFurnished;
use App\Enums\PropertyLeaseType;
use App\Models\Property;
use App\Models\Street;
use App\Models\User;
use App\Services\PropertyGeocoder;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\mock;

uses(RefreshDatabase::class);

beforeEach(function () {
    mock(PropertyGeocoder::class, function (MockInterface $mock): void {
        $mock->shouldReceive('geocode')->andReturn(null);
    });
});

function streetIdForNeighbourhood(Neighbourhood $neighbourhood): int
{
    return Street::factory()->create([
        'name' => [
            'en' => 'Test Street',
            'he' => 'רחוב בדיקה',
        ],
        'neighbourhood' => $neighbourhood,
    ])->id;
}

it('creates a property without images', function () {
    mock(PropertyGeocoder::class, function (MockInterface $mock): void {
        $mock->shouldReceive('geocode')
            ->once()
            ->andReturn([
                'lat' => 31.8078717,
                'lon' => 35.2148620,
            ]);
    });

    /** @var User $user */
    $user = User::factory()->create();

    $response = actingAs($user)->post(route('properties.store'), [
        'contact_name' => 'Test Contact',
        'contact_phone' => '0501234567',
        'access' => 'steps_only',
        'kitchen_dining_room' => 'separate',
        'porch_garden' => 'no',
        'air_conditioning' => 'not_airconditioned',
        'apartment_condition' => 'good',
        'succah_porch' => false,
        'has_dud_shemesh' => false,
        'has_machsan' => false,
        'has_parking_spot' => false,
        'neighbourhoods' => [Neighbourhood::Sanhedria->value],
        'street' => streetIdForNeighbourhood(Neighbourhood::Sanhedria),
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
    expect($property->contact_name)->toBe('Test Contact');
    expect($property->contact_phone)->toBe('0501234567');
    expect($property->lat)->toBe(31.8078717);
    expect($property->lon)->toBe(35.214862);
    expect($property->getFirstMedia('main_image'))->toBeNull();

    $response->assertRedirect(route('properties.edit', $property));
});

it('still creates a property when geocoding fails', function () {
    /** @var User $user */
    $user = User::factory()->create();

    $response = actingAs($user)->post(route('properties.store'), [
        'contact_name' => 'Fallback Contact',
        'contact_phone' => '0500000001',
        'access' => 'steps_only',
        'kitchen_dining_room' => 'separate',
        'porch_garden' => 'no',
        'air_conditioning' => 'not_airconditioned',
        'apartment_condition' => 'good',
        'succah_porch' => false,
        'has_dud_shemesh' => false,
        'has_machsan' => false,
        'has_parking_spot' => false,
        'neighbourhoods' => [Neighbourhood::Sanhedria->value],
        'street' => streetIdForNeighbourhood(Neighbourhood::Sanhedria),
        'floor' => 2,
        'type' => PropertyLeaseType::LongTerm->value,
        'available_from' => Carbon::parse('2024-01-01')->toIso8601String(),
        'bedrooms' => 2,
        'furnished' => PropertyFurnished::Yes->value,
    ]);

    $property = Property::query()->latest()->first();

    expect($property)->not->toBeNull();
    expect($property->lat)->toBeNull();
    expect($property->lon)->toBeNull();
    $response->assertRedirect(route('properties.edit', $property));
});

it('stores bedrooms with one decimal place', function () {
    /** @var User $user */
    $user = User::factory()->create();

    $response = actingAs($user)->post(route('properties.store'), [
        'contact_name' => 'Decimal Contact',
        'contact_phone' => '0500000002',
        'access' => 'steps_only',
        'kitchen_dining_room' => 'separate',
        'porch_garden' => 'no',
        'air_conditioning' => 'not_airconditioned',
        'apartment_condition' => 'good',
        'succah_porch' => false,
        'has_dud_shemesh' => false,
        'has_machsan' => false,
        'has_parking_spot' => false,
        'neighbourhoods' => [Neighbourhood::Gush80->value, Neighbourhood::BarIlan->value],
        'street' => streetIdForNeighbourhood(Neighbourhood::Gush80),
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

it('allows creating a property with a negative floor', function () {
    /** @var User $user */
    $user = User::factory()->create();

    $response = actingAs($user)->post(route('properties.store'), [
        'contact_name' => 'Basement Contact',
        'contact_phone' => '0500000005',
        'access' => 'steps_only',
        'kitchen_dining_room' => 'separate',
        'porch_garden' => 'no',
        'air_conditioning' => 'not_airconditioned',
        'apartment_condition' => 'good',
        'succah_porch' => false,
        'has_dud_shemesh' => false,
        'has_machsan' => false,
        'has_parking_spot' => false,
        'neighbourhoods' => [Neighbourhood::Sanhedria->value],
        'street' => streetIdForNeighbourhood(Neighbourhood::Sanhedria),
        'floor' => -1,
        'type' => PropertyLeaseType::LongTerm->value,
        'available_from' => Carbon::parse('2024-01-01')->toIso8601String(),
        'bedrooms' => 2,
        'furnished' => PropertyFurnished::No->value,
    ]);

    $response->assertStatus(302);

    $property = Property::query()->latest()->first();

    expect($property)->not->toBeNull();
    expect($property->floor)->toBe(-1.0);
});

it('rejects non-numeric floor values', function () {
    /** @var User $user */
    $user = User::factory()->create();

    $response = actingAs($user)
        ->from(route('properties.create'))
        ->post(route('properties.store'), [
            'contact_name' => 'Validation Contact',
            'contact_phone' => '0500000003',
            'access' => 'steps_only',
            'kitchen_dining_room' => 'separate',
            'porch_garden' => 'no',
            'air_conditioning' => 'not_airconditioned',
            'apartment_condition' => 'good',
            'succah_porch' => false,
            'has_dud_shemesh' => false,
            'has_machsan' => false,
            'has_parking_spot' => false,
            'neighbourhoods' => [Neighbourhood::Sanhedria->value],
            'street' => streetIdForNeighbourhood(Neighbourhood::Sanhedria),
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
            'contact_name' => 'Neighbourhood Validation Contact',
            'contact_phone' => '0500000004',
            'access' => 'steps_only',
            'kitchen_dining_room' => 'separate',
            'porch_garden' => 'no',
            'air_conditioning' => 'not_airconditioned',
            'apartment_condition' => 'good',
            'succah_porch' => false,
            'has_dud_shemesh' => false,
            'has_machsan' => false,
            'has_parking_spot' => false,
            'neighbourhoods' => [
                Neighbourhood::Sanhedria->value,
                Neighbourhood::BarIlan->value,
                Neighbourhood::Gush80->value,
                Neighbourhood::Geula->value,
            ],
            'street' => streetIdForNeighbourhood(Neighbourhood::Sanhedria),
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
