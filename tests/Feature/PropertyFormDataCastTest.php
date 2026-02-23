<?php

use App\Enums\Neighbourhood;
use App\Enums\PropertyFurnished;
use App\Enums\PropertyLeaseType;
use App\Models\Property;
use App\Models\Street;
use App\Models\User;

use function Pest\Laravel\actingAs;

test('property store accepts empty image_media_ids', function () {
    /** @var User $user */
    $user = User::factory()->create();

    $street = Street::factory()->create([
        'neighbourhood' => Neighbourhood::Sanhedria,
    ]);

    $response = actingAs($user)->post(route('properties.store'), [
        'contact_name' => 'Form Data Contact',
        'contact_phone' => '0501234500',
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
        'street' => $street->id,
        'floor' => 2,
        'type' => PropertyLeaseType::LongTerm->value,
        'available_from' => now()->toIso8601String(),
        'available_to' => null,
        'bedrooms' => 1,
        'furnished' => PropertyFurnished::No->value,
        'image_media_ids' => [],
        'temp_upload_id' => null,
        'main_image_media_id' => null,
    ]);

    $response->assertSessionHasNoErrors();

    $property = Property::query()->latest()->first();

    expect($property)->not->toBeNull();
    $response->assertRedirect(route('properties.edit', $property));
});
