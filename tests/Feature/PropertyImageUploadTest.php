<?php

use App\Enums\Neighbourhood;
use App\Enums\PropertyFurnished;
use App\Enums\PropertyLeaseType;
use App\Models\Property;
use App\Models\Street;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;

it('uploads images immediately and attaches them on property creation', function () {
    Storage::fake('public');
    config()->set('media-library.disk_name', 'public');

    /** @var User $user */
    $user = User::factory()->create();

    $upload1 = actingAs($user)
        ->withHeader('Accept', 'application/json')
        ->post(route('property-image-uploads.store'), [
            'image' => UploadedFile::fake()->image('one.jpg', 1200, 800),
        ])
        ->assertOk()
        ->json();

    $upload2 = actingAs($user)
        ->withHeader('Accept', 'application/json')
        ->post(route('property-image-uploads.store'), [
            'temp_upload_id' => (int) $upload1['temp_upload_id'],
            'image' => UploadedFile::fake()->image('two.jpg', 1200, 800),
        ])
        ->assertOk()
        ->json();

    $upload3 = actingAs($user)
        ->withHeader('Accept', 'application/json')
        ->post(route('property-image-uploads.store'), [
            'temp_upload_id' => (int) $upload1['temp_upload_id'],
            'image' => UploadedFile::fake()->image('three.jpg', 1200, 800),
        ])
        ->assertOk()
        ->json();

    $street = Street::factory()->create([
        'neighbourhood' => Neighbourhood::Sanhedria,
    ]);

    $payload = [
        'contact_name' => 'Upload Contact',
        'contact_phone' => '0501234568',
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
        'bedrooms' => 2,
        'furnished' => PropertyFurnished::Yes->value,
        'temp_upload_id' => (int) $upload1['temp_upload_id'],
        'image_media_ids' => [
            $upload1['media']['id'],
            $upload2['media']['id'],
            $upload3['media']['id'],
        ],
        'main_image_media_id' => $upload2['media']['id'],
    ];

    $response = actingAs($user)->post(route('properties.store'), $payload);

    $property = Property::query()->first();

    $response->assertRedirect(route('properties.edit', $property));

    expect($property)->not->toBeNull();
    expect($property->getFirstMedia('main_image'))->not->toBeNull();
    expect($property->getFirstMedia('main_image')?->name)->toBe('two');

    $images = $property->getMedia('images');
    expect($images)->toHaveCount(2);
    expect($images[0]->name)->toBe('one');
    expect($images[1]->name)->toBe('three');
});
