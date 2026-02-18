<?php

use Inertia\Testing\AssertableInertia as Assert;

test('returns a successful response', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
});

test('about us route renders the about us page', function () {
    $response = $this->get(route('about-us'));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('AboutUs'));
});
