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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

function propertyPayload(array $overrides = []): array
{
    return array_merge([
        'neighbourhood' => Neighbourhood::Sanhedria->value,
        'price' => 12000,
        'street' => 'Jabotinsky',
        'building_number' => '10',
        'floor' => '3',
        'type' => PropertyLeaseType::LongTerm->value,
        'available_from' => now()->addWeek()->toDateString(),
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
    Storage::fake(config('media-library.disk_name', 'public'));

    $user = User::factory()->create();

    $payload = propertyPayload([
        'main_image' => UploadedFile::fake()->image('main.jpg'),
    ]);

    $response = $this->actingAs($user)->post(route('properties.store'), $payload);

    $property = Property::query()->first();

    $response->assertRedirect(route('properties.edit', $property));
    expect($property)->not->toBeNull();
    expect($property->user_id)->toBe($user->id);
    expect($property->getFirstMedia('main_image'))->not->toBeNull();
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
