<?php

use App\Enums\PropertyFurnished;
use App\Enums\PropertyLeaseType;
use App\Models\Property;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

it('creates a property without images', function () {

    /** @var User $user */
    $user = User::factory()->create();

    $response = actingAs($user)->post(route('properties.store'), [
        'street' => 'Main Street',
        'floor' => '2',
        'type' => PropertyLeaseType::LongTerm->value,
        'available_from' => Carbon::parse('2024-01-01')->toIso8601String(),
        'bedrooms' => 2,
        'furnished' => PropertyFurnished::Yes->value,
    ]);

    $response->assertStatus(302);
    
    $property = Property::query()->first();

    expect($property)->not->toBeNull();
    expect($property->user_id)->toBe($user->id);
    expect($property->getFirstMedia('main_image'))->toBeNull();

    $response->assertRedirect(route('properties.edit', $property));
});
