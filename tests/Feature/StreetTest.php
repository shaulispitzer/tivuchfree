<?php

use App\Enums\Neighbourhood;
use App\Models\Street;
use App\Models\User;

test('admins can manage streets', function () {
    $admin = User::factory()->admin()->create();

    $payload = [
        'neighbourhood' => Neighbourhood::BarIlan->value,
        'name' => [
            'en' => 'Eli HaCohen',
            'he' => 'עלי הכהן',
        ],
    ];

    $response = $this->actingAs($admin)->post(route('streets.store'), $payload);

    $street = Street::query()->first();

    $response->assertRedirect(route('streets.edit', $street));
    expect($street)->not->toBeNull();
    expect($street->getTranslation('name', 'en'))->toBe('Eli HaCohen');

    $updateResponse = $this->actingAs($admin)->put(route('streets.update', $street), [
        'neighbourhood' => Neighbourhood::Geula->value,
        'name' => [
            'en' => 'Updated Street',
            'he' => 'רחוב מעודכן',
        ],
    ]);

    $updateResponse->assertRedirect(route('streets.edit', $street));
    expect($street->fresh()->neighbourhood)->toBe(Neighbourhood::Geula);
});

test('non admins cannot manage streets', function () {
    $user = User::factory()->create();
    $street = Street::factory()->create();

    $this->actingAs($user)->get(route('streets.index'))->assertForbidden();

    $this->actingAs($user)->post(route('streets.store'), [
        'neighbourhood' => Neighbourhood::BarIlan->value,
        'name' => [
            'en' => 'Example Street',
            'he' => 'רחוב לדוגמה',
        ],
    ])->assertForbidden();

    $this->actingAs($user)->put(route('streets.update', $street), [
        'neighbourhood' => Neighbourhood::Belz->value,
        'name' => [
            'en' => 'Other Street',
            'he' => 'רחוב אחר',
        ],
    ])->assertForbidden();

    $this->actingAs($user)->delete(route('streets.destroy', $street))
        ->assertForbidden();
});
