<?php

use App\Models\MapViewVisit;
use App\Models\User;
use App\Services\MapViewRecorder;
use Illuminate\Http\Request;
use Inertia\Testing\AssertableInertia as Assert;

test('map view records a unique daily visit', function () {
    $this->get(route('properties.index', ['view' => 'map']))->assertOk();

    expect(MapViewVisit::query()->count())->toBe(1);
});

test('same guest visitor is recorded only once per day', function () {
    $session = app('session.store');
    $session->start();

    $makeRequest = function () use ($session): Request {
        $request = Request::create('/properties', 'GET');
        $request->setLaravelSession($session);

        return $request;
    };

    $recorder = app(MapViewRecorder::class);
    $recorder->record($makeRequest());
    $recorder->record($makeRequest());

    expect(MapViewVisit::query()->count())->toBe(1);
    expect(MapViewVisit::query()->first()?->visitor_key)->toStartWith('session:');
});

test('authenticated user visiting map twice in one day creates only one visit', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('properties.index', ['view' => 'map']))->assertOk();
    $this->actingAs($user)->get(route('properties.index', ['view' => 'map']))->assertOk();

    expect(MapViewVisit::query()->count())->toBe(1);
    expect(MapViewVisit::query()->first()?->visitor_key)->toBe('user:'.$user->id);
    expect(MapViewVisit::query()->first()?->user_id)->toBe($user->id);
});

test('different users create separate map visits', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $this->actingAs($userA)->get(route('properties.index', ['view' => 'map']))->assertOk();
    $this->actingAs($userB)->get(route('properties.index', ['view' => 'map']))->assertOk();

    expect(MapViewVisit::query()->count())->toBe(2);
});

test('list view does not record a map visit', function () {
    $this->get(route('properties.index'))->assertOk();
    $this->get(route('properties.index', ['view' => 'list']))->assertOk();

    expect(MapViewVisit::query()->count())->toBe(0);
});

test('admins can view map views admin page', function () {
    $admin = User::factory()->admin()->create();

    MapViewVisit::factory()->count(2)->create([
        'visited_on' => now()->toDateString(),
    ]);
    MapViewVisit::factory()->create([
        'visited_on' => now()->subDays(3)->toDateString(),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.map-views.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/MapViews')
            ->where('summary.today', 2)
            ->where('summary.last_7_days', 3)
            ->where('summary.last_30_days', 3)
            ->where('summary.all_time', 3)
            ->has('daily', 2)
        );
});

test('non admins cannot access map views admin page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('admin.map-views.index'))->assertForbidden();
});

test('guests cannot access map views admin page', function () {
    $this->get(route('admin.map-views.index'))->assertRedirect(route('login'));
});
