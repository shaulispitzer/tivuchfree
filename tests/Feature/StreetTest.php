<?php

use App\Enums\Neighbourhood;
use App\Models\Street;
use App\Models\User;
use Illuminate\Http\UploadedFile;

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

test('admins can import streets from csv', function () {
    $admin = User::factory()->admin()->create();

    $csv = "neighbourhood,name_en,name_he\n".
        "Bar Ilan,Eli HaCohen,עלי הכהן\n".
        "Geula,Malchei Yisrael,מלכי ישראל";

    $file = UploadedFile::fake()->createWithContent('streets.csv', $csv);

    $response = $this->actingAs($admin)->post(route('streets.import'), [
        'file' => $file,
    ]);

    $response->assertRedirect(route('streets.index'));

    expect(Street::count())->toBe(2);

    $street1 = Street::query()->where('neighbourhood', Neighbourhood::BarIlan)->first();
    expect($street1)->not->toBeNull();
    expect($street1->getTranslation('name', 'en'))->toBe('Eli HaCohen');
    expect($street1->getTranslation('name', 'he'))->toBe('עלי הכהן');

    $street2 = Street::query()->where('neighbourhood', Neighbourhood::Geula)->first();
    expect($street2)->not->toBeNull();
    expect($street2->getTranslation('name', 'en'))->toBe('Malchei Yisrael');
});

test('non admins cannot import streets', function () {
    $user = User::factory()->create();

    $csv = "neighbourhood,name_en,name_he\nBar Ilan,Test,מבחן";
    $file = UploadedFile::fake()->createWithContent('streets.csv', $csv);

    $this->actingAs($user)->post(route('streets.import'), ['file' => $file])
        ->assertForbidden();

    expect(Street::count())->toBe(0);
});
