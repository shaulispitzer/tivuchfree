<?php

use App\Enums\Neighbourhood;
use App\Enums\PropertyFurnished;
use App\Enums\PropertyLeaseType;
use App\Models\Property;
use App\Models\User;

use function Pest\Laravel\actingAs;

test('property store accepts empty image_media_ids', function () {
    /** @var User $user */
    $user = User::factory()->create();

    $response = actingAs($user)->post(route('properties.store'), [
        'neighbourhoods' => [Neighbourhood::Sanhedria->value],
        'street' => 'Main Street',
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
