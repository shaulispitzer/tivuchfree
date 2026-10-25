<?php

use App\Models\Advertisement;
use App\Models\Property;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    Storage::fake(Advertisement::diskName());
});

test('admins can create update and delete advertisements', function () {
    /** @var User $admin */
    $admin = User::factory()->admin()->create();

    $storeResponse = actingAs($admin)->post(route('admin.ads.store'), [
        'position' => 1,
        'url' => 'https://example.com/promo',
        'is_active' => true,
        'image' => UploadedFile::fake()->image('ad.jpg', 1200, 800),
    ]);

    $advertisement = Advertisement::query()->first();

    expect($advertisement)->not->toBeNull();
    $storeResponse->assertRedirect(route('admin.ads.edit', $advertisement));
    expect($advertisement->position)->toBe(1)
        ->and($advertisement->url)->toBe('https://example.com/promo')
        ->and($advertisement->is_active)->toBeTrue();
    Storage::disk(Advertisement::diskName())->assertExists($advertisement->image_path);

    $updateResponse = actingAs($admin)->put(route('admin.ads.update', $advertisement), [
        'position' => 2,
        'url' => 'https://example.com/updated',
        'is_active' => false,
    ]);

    $updateResponse->assertRedirect(route('admin.ads.edit', $advertisement));
    $advertisement->refresh();
    expect($advertisement->position)->toBe(2)
        ->and($advertisement->url)->toBe('https://example.com/updated')
        ->and($advertisement->is_active)->toBeFalse();

    actingAs($admin)
        ->delete(route('admin.ads.destroy', $advertisement))
        ->assertRedirect(route('admin.ads.index'));

    expect(Advertisement::query()->count())->toBe(0);
});

test('admins cannot create more than four advertisements', function () {
    /** @var User $admin */
    $admin = User::factory()->admin()->create();

    foreach (range(1, 4) as $position) {
        Advertisement::factory()->position($position)->create();
    }

    actingAs($admin)->post(route('admin.ads.store'), [
        'position' => 1,
        'url' => 'https://example.com/too-many',
        'is_active' => true,
        'image' => UploadedFile::fake()->image('ad.jpg', 1200, 800),
    ])->assertSessionHasErrors('image');

    expect(Advertisement::query()->count())->toBe(4);
});

test('admins cannot reuse an advertisement position', function () {
    /** @var User $admin */
    $admin = User::factory()->admin()->create();

    Advertisement::factory()->position(1)->create();

    actingAs($admin)->post(route('admin.ads.store'), [
        'position' => 1,
        'url' => 'https://example.com/duplicate',
        'is_active' => true,
        'image' => UploadedFile::fake()->image('ad.jpg', 1200, 800),
    ])->assertSessionHasErrors('position');
});

test('non admins cannot manage advertisements', function () {
    /** @var User $user */
    $user = User::factory()->create();
    $advertisement = Advertisement::factory()->position(1)->create();

    actingAs($user)->get(route('admin.ads.index'))->assertForbidden();

    actingAs($user)->post(route('admin.ads.store'), [
        'position' => 2,
        'url' => 'https://example.com',
        'is_active' => true,
        'image' => UploadedFile::fake()->image('ad.jpg', 1200, 800),
    ])->assertForbidden();

    actingAs($user)->put(route('admin.ads.update', $advertisement), [
        'position' => 2,
        'url' => 'https://example.com/updated',
        'is_active' => false,
    ])->assertForbidden();

    actingAs($user)->delete(route('admin.ads.destroy', $advertisement))
        ->assertForbidden();
});

test('advertisement images are stored on the media library disk', function () {
    config()->set('media-library.disk_name', 's3');
    Storage::fake('s3');

    /** @var User $admin */
    $admin = User::factory()->admin()->create();

    actingAs($admin)->post(route('admin.ads.store'), [
        'position' => 1,
        'url' => 'https://example.com/promo',
        'is_active' => true,
        'image' => UploadedFile::fake()->image('ad.jpg', 1200, 800),
    ])->assertRedirect();

    $advertisement = Advertisement::query()->first();

    expect($advertisement)->not->toBeNull();
    Storage::disk('s3')->assertExists($advertisement->image_path);
    expect($advertisement->imageUrl())->toStartWith(Storage::disk('s3')->url(''));
});

test('property list includes only active ads ordered by position', function () {
    $second = Advertisement::factory()->position(2)->active()->create([
        'url' => 'https://example.com/two',
    ]);
    $first = Advertisement::factory()->position(1)->active()->create([
        'url' => 'https://example.com/one',
    ]);
    Advertisement::factory()->position(3)->create([
        'url' => 'https://example.com/inactive',
    ]);

    get(route('properties.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('properties/List')
            ->has('ads', 2)
            ->where('ads.0.position', 1)
            ->where('ads.0.url', 'https://example.com/one')
            ->where('ads.0.image_url', $first->imageUrl())
            ->where('ads.1.position', 2)
            ->where('ads.1.url', 'https://example.com/two')
            ->where('ads.1.image_url', $second->imageUrl())
        );
});

test('property list omits ads when none are active', function () {
    Advertisement::factory()->position(1)->create();

    get(route('properties.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('properties/List')
            ->has('ads', 0)
        );
});

test('property list sends no ad insert indexes when there are no properties', function () {
    Advertisement::factory()->position(1)->active()->create();

    get(route('properties.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('properties/List')
            ->has('properties.data', 0)
            ->where('ad_insert_after', [])
        );
});

test('property list inserts an ad after the last property when fewer than five match', function () {
    Advertisement::factory()->position(1)->active()->create();
    Property::factory()->count(3)->create();

    get(route('properties.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('properties/List')
            ->has('properties.data', 3)
            ->where('ad_insert_after', [2])
        );
});
