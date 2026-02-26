<?php

use App\Models\Property;
use App\Models\PropertyStat;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia as Assert;

test('home page renders with tivuchfree stats and money saved', function () {
    Cache::forget('home.tivuchfree_properties_count');
    Cache::forget('home.money_saved_by_community');

    $response = $this->get(route('home'));

    $response->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Home')
            ->where('tivuchfreePropertiesCount', 0)
            ->where('moneySavedByCommunity', 0));
});

test('home page tivuchfree count matches property stats where how_got_taken is tivuchfree', function () {
    Cache::forget('home.tivuchfree_properties_count');
    Cache::forget('home.money_saved_by_community');

    $property1 = Property::factory()->create();
    $property2 = Property::factory()->create();
    PropertyStat::factory()->create([
        'property_id' => $property1->id,
        'how_got_taken' => 'tivuchfree',
    ]);
    PropertyStat::factory()->create([
        'property_id' => $property2->id,
        'how_got_taken' => 'tivuchfree',
    ]);
    PropertyStat::factory()->create([
        'property_id' => Property::factory()->create()->id,
        'how_got_taken' => 'agent',
    ]);

    Cache::forget('home.tivuchfree_properties_count');
    Cache::forget('home.money_saved_by_community');

    $response = $this->get(route('home'));

    $response->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Home')
            ->where('tivuchfreePropertiesCount', 2));
});

test('home page money saved is sum of price_taken_at or price_advertised for tivuchfree stats', function () {
    Cache::forget('home.tivuchfree_properties_count');
    Cache::forget('home.money_saved_by_community');

    $property1 = Property::factory()->create();
    $property2 = Property::factory()->create();
    PropertyStat::factory()->create([
        'property_id' => $property1->id,
        'how_got_taken' => 'tivuchfree',
        'price_taken_at' => 5000,
        'price_advertised' => 4000,
    ]);
    PropertyStat::factory()->create([
        'property_id' => $property2->id,
        'how_got_taken' => 'tivuchfree',
        'price_taken_at' => null,
        'price_advertised' => 6000,
    ]);
    PropertyStat::factory()->create([
        'property_id' => Property::factory()->create()->id,
        'how_got_taken' => 'agent',
        'price_advertised' => 10000,
    ]);

    Cache::forget('home.tivuchfree_properties_count');
    Cache::forget('home.money_saved_by_community');

    $response = $this->get(route('home'));

    $response->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Home')
            ->where('tivuchfreePropertiesCount', 2)
            ->where('moneySavedByCommunity', 11000));
});
