<?php

use App\Enums\Neighbourhood;
use App\Enums\PropertyAccess;
use App\Enums\PropertyAirConditioning;
use App\Enums\PropertyApartmentCondition;
use App\Enums\PropertyFurnished;
use App\Enums\PropertyKitchenDiningRoom;
use App\Enums\PropertyLeaseType;
use App\Enums\PropertyPorchGarden;
use App\Models\Property;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function propertyPayload(array $overrides = []): array
{
    return array_merge([
        'neighbourhoods' => [Neighbourhood::Sanhedria->value],
        'price' => 12000,
        'street' => 'Jabotinsky',
        'building_number' => '10',
        'floor' => 3,
        'type' => PropertyLeaseType::LongTerm->value,
        'available_from' => now()->addWeek()->toIso8601String(),
        'available_to' => null,
        'bedrooms' => 3,
        'square_meter' => 120,
        'furnished' => PropertyFurnished::Partially->value,
        'taken' => false,
        'bathrooms' => 2,
        'access' => PropertyAccess::ElevatorNonShabbos->value,
        'kitchen_dining_room' => PropertyKitchenDiningRoom::Separate->value,
        'porch_garden' => PropertyPorchGarden::Porch->value,
        'succah_porch' => true,
        'air_conditioning' => PropertyAirConditioning::FullyAirconditioned->value,
        'apartment_condition' => PropertyApartmentCondition::Excellent->value,
        'additional_info' => 'Close to transit.',
        'has_dud_shemesh' => true,
        'has_machsan' => false,
        'has_parking_spot' => true,
    ], $overrides);
}

test('authenticated users can create properties', function () {
    $user = User::factory()->create();

    $payload = propertyPayload();

    $response = $this->actingAs($user)->post(route('properties.store'), $payload);

    $property = Property::query()->first();

    $response->assertRedirect(route('properties.edit', $property));
    expect($property)->not->toBeNull();
    expect($property->user_id)->toBe($user->id);
    expect($property->getFirstMedia('main_image'))->toBeNull();
});

test('non owners cannot update properties', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $property = Property::factory()->create(['user_id' => $owner->id]);

    $response = $this->actingAs($otherUser)->put(
        route('properties.update', $property),
        propertyPayload(),
    );

    $response->assertForbidden();
});

test('admins can update any property', function () {
    $admin = User::factory()->admin()->create();
    $property = Property::factory()->create();

    $response = $this->actingAs($admin)->put(
        route('properties.update', $property),
        propertyPayload([
            'street' => 'Herzl',
        ]),
    );

    $response->assertRedirect(route('properties.edit', $property));
    expect($property->fresh()->street)->toBe('Herzl');
});

test('property update rejects non-numeric floor values', function () {
    $owner = User::factory()->create();
    $property = Property::factory()->create(['user_id' => $owner->id]);

    $response = $this->actingAs($owner)
        ->from(route('properties.edit', $property))
        ->put(route('properties.update', $property), propertyPayload([
            'floor' => 'ground',
        ]));

    $response
        ->assertRedirect(route('properties.edit', $property))
        ->assertSessionHasErrors(['floor']);
});

test('properties index is displayed', function () {
    $property = Property::factory()->create();

    $response = $this->get(route('properties.index'));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('properties/Index')
            ->has('properties', 1)
            ->where('properties.0.id', $property->id)
            ->where('properties.0.street', $property->street)
        );
});

test('properties index can filter by a single neighbourhood from multi-neighbourhood properties', function () {
    $matchingProperty = Property::factory()->create([
        'neighbourhoods' => [Neighbourhood::Geula->value, Neighbourhood::MekorBaruch->value],
    ]);

    Property::factory()->create([
        'neighbourhoods' => [Neighbourhood::Belz->value],
    ]);

    $response = $this->get(route('properties.index', [
        'neighbourhood' => Neighbourhood::Geula->value,
    ]));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('properties/Index')
            ->has('properties', 1)
            ->where('properties.0.id', $matchingProperty->id)
        );
});

test('admins can view admin properties index', function () {
    $admin = User::factory()->admin()->create();
    $property = Property::factory()->create();

    $response = $this->actingAs($admin)->get(route('admin.properties.index'));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/Properties')
            ->has('properties', 1)
            ->where('properties.0.id', $property->id)
            ->where('properties.0.street', $property->street)
            ->where('properties.0.user.name', $property->user->name)
        );
});

test('admins can delete properties', function () {
    $admin = User::factory()->admin()->create();
    $property = Property::factory()->create();

    $response = $this->actingAs($admin)->delete(route('admin.properties.destroy', $property));

    $response->assertRedirect(route('admin.properties.index'));
    expect(Property::find($property->id))->toBeNull();
});

test('non admins cannot access admin properties', function () {
    $user = User::factory()->create();
    $property = Property::factory()->create();

    $this->actingAs($user)->get(route('admin.properties.index'))->assertForbidden();
    $this->actingAs($user)->delete(route('admin.properties.destroy', $property))
        ->assertForbidden();
});

test('guests cannot access admin properties', function () {
    $property = Property::factory()->create();

    $this->get(route('admin.properties.index'))->assertRedirect(route('login'));
    $this->delete(route('admin.properties.destroy', $property))
        ->assertRedirect(route('login'));
});
