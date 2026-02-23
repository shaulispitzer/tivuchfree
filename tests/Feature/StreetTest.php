<?php

use App\Enums\Neighbourhood;
use App\Models\Street;
use App\Models\User;
use Illuminate\Http\UploadedFile;

use function Pest\Laravel\actingAs;

test('admins can manage streets', function () {
    /** @var User $admin */
    $admin = User::factory()->admin()->create();

    $payload = [
        'neighbourhood' => Neighbourhood::BarIlan->value,
        'name' => [
            'en' => 'Eli HaCohen',
            'he' => 'עלי הכהן',
        ],
    ];

    $response = actingAs($admin)->post(route('admin.streets.store'), $payload);

    $street = Street::query()->first();

    $response->assertRedirect(route('admin.streets.edit', $street));
    expect($street)->not->toBeNull();
    expect($street->getTranslation('name', 'en'))->toBe('Eli HaCohen');

    $updateResponse = actingAs($admin)->put(route('admin.streets.update', $street), [
        'neighbourhood' => Neighbourhood::Geula->value,
        'name' => [
            'en' => 'Updated Street',
            'he' => 'רחוב מעודכן',
        ],
    ]);

    $updateResponse->assertRedirect(route('admin.streets.edit', $street));
    expect($street->fresh()->neighbourhood)->toBe(Neighbourhood::Geula);
});

test('non admins cannot manage streets', function () {
    /** @var User $user */
    $user = User::factory()->create();
    $street = Street::factory()->create();

    actingAs($user)->get(route('admin.streets.index'))->assertForbidden();

    actingAs($user)->post(route('admin.streets.store'), [
        'neighbourhood' => Neighbourhood::BarIlan->value,
        'name' => [
            'en' => 'Example Street',
            'he' => 'רחוב לדוגמה',
        ],
    ])->assertForbidden();

    actingAs($user)->put(route('admin.streets.update', $street), [
        'neighbourhood' => Neighbourhood::Belz->value,
        'name' => [
            'en' => 'Other Street',
            'he' => 'רחוב אחר',
        ],
    ])->assertForbidden();

    actingAs($user)->delete(route('admin.streets.destroy', $street))
        ->assertForbidden();
});

test('admins can import streets from csv', function () {
    /** @var User $admin */
    $admin = User::factory()->admin()->create();

    $csv = "neighbourhood,name_en,name_he\n".
        "Bar Ilan,Eli HaCohen,עלי הכהן\n".
        'Geula,Malchei Yisrael,מלכי ישראל';

    $file = UploadedFile::fake()->createWithContent('streets.csv', $csv);

    $response = actingAs($admin)->post(route('admin.streets.import'), [
        'file' => $file,
    ]);

    $response->assertRedirect(route('admin.streets.index'));

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
    /** @var User $user */
    $user = User::factory()->create();

    $csv = "neighbourhood,name_en,name_he\nBar Ilan,Test,מבחן";
    $file = UploadedFile::fake()->createWithContent('streets.csv', $csv);

    actingAs($user)->post(route('admin.streets.import'), ['file' => $file])
        ->assertForbidden();

    expect(Street::count())->toBe(0);
});
