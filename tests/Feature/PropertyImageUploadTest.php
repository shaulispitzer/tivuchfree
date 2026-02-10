<?php

use App\Enums\PropertyFurnished;
use App\Enums\PropertyLeaseType;
use App\Models\Property;
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

    $payload = [
        'street' => 'Main Street',
        'floor' => '2',
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
