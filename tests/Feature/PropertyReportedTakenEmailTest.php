<?php

use App\Mail\PropertyReportedTaken;
use App\Models\Property;
use App\Models\PropertyStat;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;

test('reporting a property as taken queues an email with confirm and keep-available links', function () {
    Mail::fake();

    $owner = User::factory()->create();
    $property = Property::factory()->create(['user_id' => $owner->id]);

    $this->post(route('properties.report-taken', $property))
        ->assertRedirect();

    Mail::assertQueued(PropertyReportedTaken::class, function (PropertyReportedTaken $mail) use ($property) {
        return $mail->hasTo($property->user->email)
            && $mail->property->is($property);
    });

    $mailable = new PropertyReportedTaken($property->fresh());
    $html = $mailable->render();

    expect($html)
        ->toContain(route('properties.confirm-reported-taken', $property))
        ->toContain(route('properties.keep-available', $property))
        ->toContain(__('mail.reported_taken.button_confirm_taken'))
        ->toContain(__('mail.reported_taken.button_not_taken_yet'));
});

test('owner can open confirm reported taken modal when property is reported taken', function () {
    $owner = User::factory()->create();
    $property = Property::factory()->create([
        'user_id' => $owner->id,
        'reported_taken_at' => now(),
        'taken' => false,
    ]);

    $this->actingAs($owner)
        ->get(route('properties.confirm-reported-taken', $property))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('properties/Edit')
            ->where('modal.component', 'properties/ConfirmReportedTaken')
            ->where('modal.props.propertyId', $property->id)
        );
});

test('owner can open keep available modal when property is reported taken', function () {
    $owner = User::factory()->create();
    $property = Property::factory()->create([
        'user_id' => $owner->id,
        'reported_taken_at' => now(),
        'taken' => false,
    ]);

    $this->actingAs($owner)
        ->get(route('properties.keep-available', $property))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('properties/Edit')
            ->where('modal.component', 'properties/KeepPropertyAvailable')
            ->where('modal.props.propertyId', $property->id)
        );
});

test('reported taken modal urls are not available when property is not reported taken', function () {
    $owner = User::factory()->create();
    $property = Property::factory()->create([
        'user_id' => $owner->id,
        'reported_taken_at' => null,
        'taken' => false,
    ]);

    $this->actingAs($owner)
        ->get(route('properties.confirm-reported-taken', $property))
        ->assertNotFound();

    $this->actingAs($owner)
        ->get(route('properties.keep-available', $property))
        ->assertNotFound();
});

test('reported taken modal urls are not available when property is already taken', function () {
    $owner = User::factory()->create();
    $property = Property::factory()->taken()->create([
        'user_id' => $owner->id,
        'reported_taken_at' => now(),
    ]);

    $this->actingAs($owner)
        ->get(route('properties.confirm-reported-taken', $property))
        ->assertNotFound();

    $this->actingAs($owner)
        ->get(route('properties.keep-available', $property))
        ->assertNotFound();
});

test('non owner cannot open reported taken modal urls', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $property = Property::factory()->create([
        'user_id' => $owner->id,
        'reported_taken_at' => now(),
    ]);

    $this->actingAs($otherUser)
        ->get(route('properties.confirm-reported-taken', $property))
        ->assertForbidden();

    $this->actingAs($otherUser)
        ->get(route('properties.keep-available', $property))
        ->assertForbidden();
});

test('owner can confirm reported taken from email flow', function () {
    Mail::fake();

    $owner = User::factory()->create();
    $property = Property::factory()->create([
        'user_id' => $owner->id,
        'reported_taken_at' => now(),
        'taken' => false,
    ]);

    $this->actingAs($owner)
        ->post(route('properties.confirm-reported-taken.store', $property))
        ->assertRedirect(route('properties.edit', $property));

    $property->refresh();

    expect($property->taken)->toBeTrue()
        ->and($property->reported_taken_at)->toBeNull()
        ->and($property->taken_at)->not->toBeNull();

    expect(PropertyStat::query()->where('property_id', $property->id)->exists())->toBeTrue();
});

test('owner can keep property available from email flow', function () {
    $owner = User::factory()->create();
    $property = Property::factory()->create([
        'user_id' => $owner->id,
        'reported_taken_at' => now(),
        'taken' => false,
    ]);

    $this->actingAs($owner)
        ->post(route('properties.keep-available.store', $property))
        ->assertRedirect(route('properties.edit', $property));

    expect($property->fresh()->reported_taken_at)->toBeNull()
        ->and($property->fresh()->taken)->toBeFalse();
});

test('confirm and keep available actions are blocked when report is inactive', function () {
    $owner = User::factory()->create();
    $property = Property::factory()->create([
        'user_id' => $owner->id,
        'reported_taken_at' => null,
        'taken' => false,
    ]);

    $this->actingAs($owner)
        ->post(route('properties.confirm-reported-taken.store', $property))
        ->assertNotFound();

    $this->actingAs($owner)
        ->post(route('properties.keep-available.store', $property))
        ->assertNotFound();
});
