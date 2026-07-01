<?php

use App\Models\Neighbourhood;
use App\Models\Street;
use App\Models\User;

use function Pest\Laravel\actingAs;

test('admins can manage neighbourhoods', function () {
    /** @var User $admin */
    $admin = User::factory()->admin()->create();

    $payload = [
        'name' => [
            'en' => 'Test Neighbourhood',
            'he' => 'שכונת בדיקה',
        ],
    ];

    $response = actingAs($admin)->post(route('admin.neighbourhoods.store'), $payload);

    $neighbourhood = Neighbourhood::query()
        ->where('name->en', 'Test Neighbourhood')
        ->first();

    $response->assertRedirect(route('admin.neighbourhoods.edit', $neighbourhood));
    expect($neighbourhood)->not->toBeNull();
    expect($neighbourhood->getTranslation('name', 'en'))->toBe('Test Neighbourhood');

    $updateResponse = actingAs($admin)->put(route('admin.neighbourhoods.update', $neighbourhood), [
        'name' => [
            'en' => 'Updated Neighbourhood',
            'he' => 'שכונה מעודכנת',
        ],
    ]);

    $updateResponse->assertRedirect(route('admin.neighbourhoods.edit', $neighbourhood));
    expect($neighbourhood->fresh()->getTranslation('name', 'en'))->toBe('Updated Neighbourhood');
});

test('admins cannot delete neighbourhoods that still have streets', function () {
    /** @var User $admin */
    $admin = User::factory()->admin()->create();

    $neighbourhood = Neighbourhood::query()->where('name->en', 'Sanhedria')->firstOrFail();
    Street::factory()->create(['neighbourhood_id' => $neighbourhood->id]);

    actingAs($admin)
        ->delete(route('admin.neighbourhoods.destroy', $neighbourhood))
        ->assertRedirect();

    expect(Neighbourhood::query()->whereKey($neighbourhood->id)->exists())->toBeTrue();
});

test('non admins cannot manage neighbourhoods', function () {
    /** @var User $user */
    $user = User::factory()->create();
    $neighbourhood = Neighbourhood::query()->firstOrFail();

    actingAs($user)->get(route('admin.neighbourhoods.index'))->assertForbidden();

    actingAs($user)->post(route('admin.neighbourhoods.store'), [
        'name' => [
            'en' => 'Example',
            'he' => 'דוגמה',
        ],
    ])->assertForbidden();

    actingAs($user)->put(route('admin.neighbourhoods.update', $neighbourhood), [
        'name' => [
            'en' => 'Other',
            'he' => 'אחר',
        ],
    ])->assertForbidden();

    actingAs($user)->delete(route('admin.neighbourhoods.destroy', $neighbourhood))
        ->assertForbidden();
});
