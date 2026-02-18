<?php

use App\Models\Property;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

test('owners can list property images for edit page', function () {
    $owner = User::factory()->create();
    $property = Property::factory()->create([
        'user_id' => $owner->id,
    ]);

    $main = $property
        ->addMediaFromString('main-image')
        ->usingFileName('main.jpg')
        ->toMediaCollection('main_image');

    $additional = $property
        ->addMediaFromString('extra-image')
        ->usingFileName('extra.jpg')
        ->toMediaCollection('images');

    $response = $this->actingAs($owner)->getJson(route('properties.images.index', $property));

    $response
        ->assertOk()
        ->assertJsonPath('images.0.id', $main->id)
        ->assertJsonPath('images.0.is_main', true)
        ->assertJsonPath('images.1.id', $additional->id)
        ->assertJsonPath('images.1.is_main', false);
});

test('deleting main image immediately promotes next image to main', function () {
    $owner = User::factory()->create();
    $property = Property::factory()->create([
        'user_id' => $owner->id,
    ]);

    $main = $property
        ->addMediaFromString('main-image')
        ->usingFileName('main.jpg')
        ->toMediaCollection('main_image');

    $next = $property
        ->addMediaFromString('next-image')
        ->usingFileName('next.jpg')
        ->toMediaCollection('images');

    $this->actingAs($owner)
        ->deleteJson(route('properties.images.destroy', [$property, $main]))
        ->assertOk()
        ->assertJsonCount(1, 'images')
        ->assertJsonPath('images.0.is_main', true);

    expect(Media::query()->find($main->id))->toBeNull();
    expect($property->fresh()->getFirstMedia('main_image'))->not->toBeNull();
    expect($property->fresh()->getMedia('images'))->toHaveCount(0);
});

test('owners can upload a property image immediately', function () {
    $owner = User::factory()->create();
    $property = Property::factory()->create([
        'user_id' => $owner->id,
    ]);

    $response = $this->actingAs($owner)->post(
        route('properties.images.store', $property),
        [
            'image' => UploadedFile::fake()->image('upload.jpg'),
        ],
        [
            'Accept' => 'application/json',
        ],
    );

    $response
        ->assertOk()
        ->assertJsonCount(1, 'images')
        ->assertJsonPath('images.0.is_main', true);

    expect($property->fresh()->getFirstMedia('main_image'))->not->toBeNull();
});
