<?php

use App\Models\Property;
use Inertia\Testing\AssertableInertia as Assert;

test('properties list page loads for subscribe discovery surfaces', function () {
    Property::factory()->create();

    $this->get(route('properties.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('properties/List')
        );
});

test('properties map page loads for subscribe discovery surfaces', function () {
    Property::factory()->create([
        'lat' => 31.7780,
        'lon' => 35.2345,
    ]);

    $this->get(route('properties.index', ['view' => 'map']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('properties/Map')
            ->where('filters.view', 'map')
        );
});

test('subscribe page is available from the discovery call to action', function () {
    $this->get(route('subscribe'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('subscription/Subscribe')
        );
});
