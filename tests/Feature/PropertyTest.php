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
use App\Models\Street;
use App\Models\User;
use App\Services\PropertyGeocoder;
use Inertia\Testing\AssertableInertia as Assert;
use Mockery\MockInterface;

function propertyPayload(array $overrides = []): array
{
    return array_merge([
        'contact_name' => 'Test Contact',
        'contact_phone' => '0501234567',
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

beforeEach(function () {
    $this->mock(PropertyGeocoder::class, function (MockInterface $mock): void {
        $mock->shouldReceive('geocode')->andReturn(null);
    });
});

test('authenticated users can create properties', function () {
    $this->mock(PropertyGeocoder::class, function (MockInterface $mock): void {
        $mock->shouldReceive('geocode')
            ->once()
            ->andReturn([
                'lat' => 31.8078717,
                'lon' => 35.2148620,
            ]);
    });

    $user = User::factory()->create();
    $street = Street::factory()->create([
        'name' => [
            'en' => 'Jabotinsky',
            'he' => 'ז׳בוטינסקי',
        ],
        'neighbourhood' => Neighbourhood::Sanhedria,
    ]);

    $payload = propertyPayload([
        'street' => $street->id,
    ]);

    $response = $this->actingAs($user)->post(route('properties.store'), $payload);

    $property = Property::query()->first();

    $response->assertRedirect(route('properties.edit', $property));
    expect($property)->not->toBeNull();
    expect($property->user_id)->toBe($user->id);
    expect($property->street)->toBe($street->getTranslation('name', 'he'));
    expect($property->lat)->toBe(31.8078717);
    expect($property->lon)->toBe(35.214862);
    expect($property->getFirstMedia('main_image'))->toBeNull();
});

test('property streets endpoint loads streets by selected neighbourhoods', function () {
    $user = User::factory()->create();

    $street = Street::factory()->create([
        'name' => [
            'en' => 'Bar Ilan Street',
            'he' => 'בר אילן',
        ],
        'neighbourhood' => Neighbourhood::BarIlan,
    ]);

    $this->actingAs($user)
        ->getJson(route('properties.streets'))
        ->assertOk()
        ->assertJson([
            'streets' => [],
        ]);

    $this->actingAs($user)
        ->getJson(route('properties.streets', [
            'neighbourhoods' => [Neighbourhood::BarIlan->value],
        ]))
        ->assertOk()
        ->assertJsonCount(1, 'streets')
        ->assertJsonPath('streets.0.id', $street->id)
        ->assertJsonPath('streets.0.name', $street->getTranslation('name', 'en'));
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
    $this->mock(PropertyGeocoder::class, function (MockInterface $mock): void {
        $mock->shouldReceive('geocode')
            ->once()
            ->andReturn([
                'lat' => 31.7801000,
                'lon' => 35.2174000,
            ]);
    });

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
    expect($property->fresh()->lat)->toBe(31.7801);
    expect($property->fresh()->lon)->toBe(35.2174);
});

test('property update still succeeds when geocoding fails', function () {
    $owner = User::factory()->create();
    $property = Property::factory()->create([
        'user_id' => $owner->id,
        'lat' => 31.7,
        'lon' => 35.2,
    ]);

    $response = $this->actingAs($owner)->put(
        route('properties.update', $property),
        propertyPayload([
            'street' => 'HaNeviim',
            'building_number' => null,
        ]),
    );

    $response->assertRedirect(route('properties.edit', $property));
    expect($property->fresh()->lat)->toBeNull();
    expect($property->fresh()->lon)->toBeNull();
});

test('property update succeeds without changing street selection', function () {
    $this->mock(PropertyGeocoder::class, function (MockInterface $mock): void {
        $mock->shouldReceive('geocode')
            ->once()
            ->andReturn([
                'lat' => 31.8123,
                'lon' => 35.2012,
            ]);
    });

    $owner = User::factory()->create();
    $street = Street::factory()->create([
        'name' => [
            'en' => 'Bar Ilan Street',
            'he' => 'בר אילן',
        ],
        'neighbourhood' => Neighbourhood::BarIlan,
    ]);
    $property = Property::factory()->create([
        'user_id' => $owner->id,
        'street' => $street->getTranslation('name', 'he'),
        'neighbourhoods' => [Neighbourhood::BarIlan->value],
    ]);

    $payload = propertyPayload([
        'neighbourhoods' => [Neighbourhood::BarIlan->value],
        'building_number' => '18',
        'contact_phone' => '0509999999',
    ]);
    unset($payload['street']);

    $response = $this->actingAs($owner)->put(route('properties.update', $property), $payload);

    $response->assertRedirect(route('properties.edit', $property));
    expect($property->fresh()->street)->toBe($street->getTranslation('name', 'he'));
    expect($property->fresh()->contact_phone)->toBe('0509999999');
    expect($property->fresh()->lat)->toBe(31.8123);
    expect($property->fresh()->lon)->toBe(35.2012);
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
    $property = Property::factory()->create([
        'lat' => 31.7780,
        'lon' => 35.2345,
    ]);

    $response = $this->get(route('properties.index'));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('properties/List')
            ->has('properties.data', 1)
            ->where('properties.data.0.id', $property->id)
            ->where('properties.data.0.street', $property->street)
            ->where('properties.data.0.lat', 31.778)
            ->where('properties.data.0.lon', 35.2345)
            ->missing('properties.data.0.user')
            ->missing('properties.data.0.user_id')
        );
});

test('property show is returned as a modal with full details except user fields', function () {
    $property = Property::factory()->create();

    $response = $this
        ->withoutMiddleware(\App\Http\Middleware\HandleInertiaRequests::class)
        ->withHeaders([
            'X-Inertia' => 'true',
            'X-Requested-With' => 'XMLHttpRequest',
        ])
        ->get(route('properties.show', $property));

    $response
        ->assertOk()
        ->assertHeader('X-Inertia-Modal', 'true')
        ->assertJsonPath('props.modal.component', 'properties/Show')
        ->assertJsonPath('props.modal.props.property.id', $property->id)
        ->assertJsonPath('props.modal.props.property.street', $property->street)
        ->assertJsonPath('props.modal.props.property.building_number', (int) $property->building_number)
        ->assertJsonMissingPath('props.modal.props.property.user')
        ->assertJsonMissingPath('props.modal.props.property.user_id');
});

test('properties index does not include owner details for owner listings', function () {
    $owner = User::factory()->create();
    $property = Property::factory()->create(['user_id' => $owner->id]);

    $response = $this->actingAs($owner)->get(route('properties.index'));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('properties/List')
            ->has('properties.data', 1)
            ->where('properties.data.0.id', $property->id)
            ->missing('properties.data.0.user')
            ->missing('properties.data.0.user_id')
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
            ->component('properties/List')
            ->has('properties.data', 1)
            ->where('properties.data.0.id', $matchingProperty->id)
        );
});

test('properties index can filter only available listings', function () {
    $availableProperty = Property::factory()->create([
        'taken' => false,
    ]);

    Property::factory()->create([
        'taken' => true,
    ]);

    $response = $this->get(route('properties.index', [
        'availability' => 'available',
    ]));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('properties/List')
            ->has('properties.data', 1)
            ->where('properties.data.0.id', $availableProperty->id)
        );
});

test('properties index can filter bedrooms by an exact single number', function () {
    $matchingProperty = Property::factory()->create([
        'bedrooms' => 3,
    ]);

    Property::factory()->create([
        'bedrooms' => 2,
    ]);

    Property::factory()->create([
        'bedrooms' => 4,
    ]);

    $response = $this->get(route('properties.index', [
        'bedrooms_min' => 3,
    ]));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('properties/List')
            ->has('properties.data', 1)
            ->where('properties.data.0.id', $matchingProperty->id)
        );
});

test('properties index can filter bedrooms by range', function () {
    $matchingProperty = Property::factory()->create([
        'bedrooms' => 3,
    ]);

    Property::factory()->create([
        'bedrooms' => 1,
    ]);

    Property::factory()->create([
        'bedrooms' => 5,
    ]);

    $response = $this->get(route('properties.index', [
        'bedrooms_min' => 2,
        'bedrooms_max' => 4,
    ]));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('properties/List')
            ->has('properties.data', 1)
            ->where('properties.data.0.id', $matchingProperty->id)
        );
});

test('properties index can filter by furnished status', function () {
    $matchingProperty = Property::factory()->create([
        'furnished' => PropertyFurnished::Yes,
    ]);

    Property::factory()->create([
        'furnished' => PropertyFurnished::No,
    ]);

    $response = $this->get(route('properties.index', [
        'furnished' => PropertyFurnished::Yes->value,
    ]));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('properties/List')
            ->has('properties.data', 1)
            ->where('properties.data.0.id', $matchingProperty->id)
        );
});

test('properties index can filter medium term properties by available date range', function () {
    $matchingProperty = Property::factory()->create([
        'type' => PropertyLeaseType::MediumTerm,
        'available_from' => '2026-03-01',
        'available_to' => '2026-04-30',
    ]);

    Property::factory()->create([
        'type' => PropertyLeaseType::MediumTerm,
        'available_from' => '2026-02-15',
        'available_to' => '2026-04-15',
    ]);

    Property::factory()->create([
        'type' => PropertyLeaseType::MediumTerm,
        'available_from' => '2026-03-05',
        'available_to' => '2026-05-10',
    ]);

    Property::factory()->create([
        'type' => PropertyLeaseType::LongTerm,
        'available_from' => '2026-03-01',
        'available_to' => null,
    ]);

    $response = $this->get(route('properties.index', [
        'type' => PropertyLeaseType::MediumTerm->value,
        'available_from' => '2026-03-01',
        'available_to' => '2026-04-30',
    ]));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('properties/List')
            ->has('properties.data', 1)
            ->where('properties.data.0.id', $matchingProperty->id)
        );
});

test('properties index can sort listings', function (string $sort, array $expectedOrder) {
    Property::factory()->create([
        'price' => 12000,
        'created_at' => now()->subDays(1),
    ]);

    Property::factory()->create([
        'price' => 8000,
        'created_at' => now()->subDays(3),
    ]);

    Property::factory()->create([
        'price' => 15000,
        'created_at' => now()->subDays(2),
    ]);

    $response = $this->get(route('properties.index', [
        'sort' => $sort,
    ]));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('properties/List')
            ->where('filters.sort', $sort)
            ->where('properties.data.0.price', $expectedOrder[0])
            ->where('properties.data.1.price', $expectedOrder[1])
            ->where('properties.data.2.price', $expectedOrder[2])
        );
})->with([
    'price ascending' => ['price_asc', [8000, 12000, 15000]],
    'price descending' => ['price_desc', [15000, 12000, 8000]],
    'newest first' => ['newest', [12000, 15000, 8000]],
    'oldest first' => ['oldest', [8000, 15000, 12000]],
]);

test('properties index returns paginated data', function () {
    Property::factory()->count(13)->create();

    $response = $this->get(route('properties.index'));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('properties/List')
            ->has('properties.data', 13)
            ->where('properties.total', 13)
            ->where('properties.per_page', 15)
            ->where('properties.current_page', 1)
            ->where('properties.last_page', 1)
            ->where('properties.from', 1)
            ->where('properties.to', 13)
        );
});

test('properties map view returns all filtered data with lightweight payload', function () {
    Property::factory()->count(13)->create();

    $response = $this->get(route('properties.index', [
        'view' => 'map',
    ]));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('properties/Map')
            ->where('filters.view', 'map')
            ->has('properties', 13)
            ->has('properties.0.id')
            ->missing('properties.0.available_from')
            ->missing('properties.0.type')
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
