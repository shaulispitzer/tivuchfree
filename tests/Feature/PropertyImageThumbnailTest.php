<?php

use App\Models\Property;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia as Assert;

test('properties index exposes card thumbnail urls instead of full image urls', function () {
    $property = Property::factory()->create();

    $mainImage = $property
        ->addMedia(UploadedFile::fake()->image('main.jpg', 1200, 900))
        ->toMediaCollection('main_image');

    $additionalImage = $property
        ->addMedia(UploadedFile::fake()->image('extra.jpg', 1200, 900))
        ->toMediaCollection('images');

    expect($mainImage->hasGeneratedConversion('card'))->toBeTrue();
    expect($additionalImage->hasGeneratedConversion('card'))->toBeTrue();

    $response = $this->get(route('properties.index'));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('properties/List')
            ->has('properties.data', 1)
            ->where('properties.data.0.id', $property->id)
            ->where('properties.data.0.main_image_thumb_url', $mainImage->getUrl('card'))
            ->where('properties.data.0.image_thumb_urls', [$additionalImage->getUrl('card')])
            ->missing('properties.data.0.main_image_url')
            ->missing('properties.data.0.image_urls')
        );
});

test('property show still exposes full resolution image urls', function () {
    $property = Property::factory()->create();

    $mainImage = $property
        ->addMedia(UploadedFile::fake()->image('main.jpg', 1200, 900))
        ->toMediaCollection('main_image');

    $response = $this
        ->withoutMiddleware(\App\Http\Middleware\HandleInertiaRequests::class)
        ->withHeaders([
            'X-Inertia' => 'true',
            'X-Requested-With' => 'XMLHttpRequest',
        ])
        ->get(route('properties.show', $property));

    $response
        ->assertOk()
        ->assertJsonPath('props.modal.props.property.main_image_url', $mainImage->getUrl())
        ->assertJsonPath('props.modal.props.property.main_image_thumb_url', $mainImage->getUrl('card'));
});
