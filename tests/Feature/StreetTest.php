<?php

use App\Models\Neighbourhood;
use App\Models\Street;
use App\Models\User;
use App\Services\OpenStreetMapStreetCsvGenerator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

use function Pest\Laravel\actingAs;

test('admins can generate streets csv from openstreetmap', function () {
    /** @var User $admin */
    $admin = User::factory()->admin()->create();

    $neighbourhood = Neighbourhood::query()->where('name->en', 'Bar Ilan')->firstOrFail();

    Http::fake([
        'https://overpass-api.de/api/interpreter' => Http::response([
            'elements' => [
                [
                    'type' => 'way',
                    'id' => 1,
                    'tags' => [
                        'highway' => 'residential',
                        'name' => 'גולדה מאיר',
                        'name:en' => 'Golda Meir',
                        'name:he' => 'גולדה מאיר',
                    ],
                ],
                [
                    'type' => 'way',
                    'id' => 2,
                    'tags' => [
                        'highway' => 'residential',
                        'name' => 'גולדה מאיר',
                        'name:en' => 'Golda Meir',
                        'name:he' => 'גולדה מאיר',
                    ],
                ],
                [
                    'type' => 'way',
                    'id' => 3,
                    'tags' => [
                        'highway' => 'tertiary',
                        'name' => 'יגאל ידין',
                        'name:en' => 'Yigael Yadin',
                        'name:he' => 'יגאל ידין',
                    ],
                ],
            ],
        ]),
    ]);

    $response = actingAs($admin)->post(route('admin.streets.generate-csv'), [
        'south' => 31.79,
        'west' => 35.20,
        'north' => 31.81,
        'east' => 35.22,
        'neighbourhood_id' => $neighbourhood->id,
    ]);

    $response->assertSuccessful();
    $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

    $csv = $response->getContent();

    expect($csv)->toContain('neighbourhood,name_en,name_he');
    expect($csv)->toContain('"Bar Ilan","Golda Meir","גולדה מאיר"');
    expect($csv)->toContain('"Bar Ilan","Yigael Yadin","יגאל ידין"');
    expect(substr_count($csv, 'Golda Meir'))->toBe(1);

    Http::assertSent(function ($request) {
        return $request->url() === 'https://overpass-api.de/api/interpreter'
            && str_contains($request->body(), 'way["highway"]["name"](31.79,35.2,31.81,35.22)');
    });
});

test('generate streets csv rejects bounding boxes that are too large', function () {
    /** @var User $admin */
    $admin = User::factory()->admin()->create();

    $neighbourhood = Neighbourhood::query()->where('name->en', 'Bar Ilan')->firstOrFail();

    actingAs($admin)
        ->postJson(route('admin.streets.generate-csv'), [
            'south' => 31.0,
            'west' => 35.0,
            'north' => 32.0,
            'east' => 36.0,
            'neighbourhood_id' => $neighbourhood->id,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['south']);
});

test('non admins cannot generate streets csv', function () {
    /** @var User $user */
    $user = User::factory()->create();

    $neighbourhood = Neighbourhood::query()->where('name->en', 'Bar Ilan')->firstOrFail();

    actingAs($user)
        ->post(route('admin.streets.generate-csv'), [
            'south' => 31.79,
            'west' => 35.20,
            'north' => 31.81,
            'east' => 35.22,
            'neighbourhood_id' => $neighbourhood->id,
        ])
        ->assertForbidden();
});

test('openstreetmap street csv generator deduplicates and sorts streets', function () {
    $generator = app(OpenStreetMapStreetCsvGenerator::class);

    $csv = $generator->buildCsv('Bar Ilan', [
        ['name_en' => 'Yigael Yadin', 'name_he' => 'יגאל ידין'],
        ['name_en' => 'Golda Meir', 'name_he' => 'גולדה מאיר'],
    ]);

    expect($csv)->toContain('"Bar Ilan","Golda Meir","גולדה מאיר"');
    expect($csv)->toContain('"Bar Ilan","Yigael Yadin","יגאל ידין"');
    expect(strpos($csv, 'Golda Meir'))->toBeLessThan(strpos($csv, 'Yigael Yadin'));
});

test('admins can manage streets', function () {
    /** @var User $admin */
    $admin = User::factory()->admin()->create();

    $neighbourhood = Neighbourhood::query()->where('name->en', 'Bar Ilan')->firstOrFail();

    $payload = [
        'neighbourhood_id' => $neighbourhood->id,
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

    $otherNeighbourhood = Neighbourhood::query()->where('name->en', 'Geula')->firstOrFail();

    $updateResponse = actingAs($admin)->put(route('admin.streets.update', $street), [
        'neighbourhood_id' => $otherNeighbourhood->id,
        'name' => [
            'en' => 'Updated Street',
            'he' => 'רחוב מעודכן',
        ],
    ]);

    $updateResponse->assertRedirect(route('admin.streets.edit', $street));
    expect($street->fresh()->neighbourhood_id)->toBe($otherNeighbourhood->id);
});

test('non admins cannot manage streets', function () {
    /** @var User $user */
    $user = User::factory()->create();
    $street = Street::factory()->create();

    actingAs($user)->get(route('admin.streets.index'))->assertForbidden();

    actingAs($user)->post(route('admin.streets.store'), [
        'neighbourhood_id' => $street->neighbourhood_id,
        'name' => [
            'en' => 'Example Street',
            'he' => 'רחוב לדוגמה',
        ],
    ])->assertForbidden();

    actingAs($user)->put(route('admin.streets.update', $street), [
        'neighbourhood_id' => $street->neighbourhood_id,
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

    $barIlan = Neighbourhood::query()->where('name->en', 'Bar Ilan')->firstOrFail();
    $geula = Neighbourhood::query()->where('name->en', 'Geula')->firstOrFail();

    $street1 = Street::query()->where('neighbourhood_id', $barIlan->id)->first();
    expect($street1)->not->toBeNull();
    expect($street1->getTranslation('name', 'en'))->toBe('Eli HaCohen');
    expect($street1->getTranslation('name', 'he'))->toBe('עלי הכהן');

    $street2 = Street::query()->where('neighbourhood_id', $geula->id)->first();
    expect($street2)->not->toBeNull();
    expect($street2->getTranslation('name', 'en'))->toBe('Malchei Yisrael');
});

test('admins can filter and paginate streets index', function () {
    /** @var User $admin */
    $admin = User::factory()->admin()->create();

    $barIlan = Neighbourhood::query()->where('name->en', 'Bar Ilan')->firstOrFail();
    $geula = Neighbourhood::query()->where('name->en', 'Geula')->firstOrFail();

    Street::factory()->count(51)->create(['neighbourhood_id' => $barIlan->id]);
    Street::factory()->create([
        'neighbourhood_id' => $geula->id,
        'name' => [
            'en' => 'Unique Search Street',
            'he' => 'רחוב ייחודי',
        ],
    ]);

    actingAs($admin)
        ->get(route('admin.streets.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('streets/Index')
            ->has('streets.data', 50)
            ->where('streets.per_page', 50)
            ->where('streets.total', 52)
        );

    actingAs($admin)
        ->get(route('admin.streets.index', ['neighbourhood' => $geula->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('streets.data', 1)
            ->where('streets.data.0.name.en', 'Unique Search Street')
            ->where('filters.neighbourhood', $geula->id)
        );

    actingAs($admin)
        ->get(route('admin.streets.index', ['search' => 'unique search']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('streets.data', 1)
            ->where('streets.data.0.name.en', 'Unique Search Street')
            ->where('filters.search', 'unique search')
        );
});

test('deleting a street redirects back with active filters', function () {
    /** @var User $admin */
    $admin = User::factory()->admin()->create();

    $geula = Neighbourhood::query()->where('name->en', 'Geula')->firstOrFail();

    $street = Street::factory()->create([
        'neighbourhood_id' => $geula->id,
        'name' => [
            'en' => 'Delete Me Street',
            'he' => 'רחוב למחיקה',
        ],
    ]);

    actingAs($admin)
        ->from(route('admin.streets.index', [
            'search' => 'delete me',
            'neighbourhood' => $geula->id,
        ]))
        ->delete(route('admin.streets.destroy', [
            'street' => $street,
            'search' => 'delete me',
            'neighbourhood' => $geula->id,
        ]))
        ->assertRedirect(route('admin.streets.index', [
            'search' => 'delete me',
            'neighbourhood' => $geula->id,
        ]));

    expect(Street::query()->whereKey($street->id)->exists())->toBeFalse();
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
