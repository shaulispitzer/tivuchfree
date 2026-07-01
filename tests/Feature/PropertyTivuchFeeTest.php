<?php

use App\Mail\PropertyReportedTivuchFee;
use App\Models\Property;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

test('guest can report a property as having a tivuch fee', function () {
    Mail::fake();

    $property = Property::factory()->create();

    $this->post(route('properties.report-tivuch-fee', $property))
        ->assertRedirect();

    expect($property->fresh()->reported_tivuch_fee_at)->not->toBeNull();

    Mail::assertQueued(PropertyReportedTivuchFee::class, function (PropertyReportedTivuchFee $mail) use ($property) {
        return $mail->hasTo('info@tivuchfree.com')
            && $mail->property->is($property);
    });

    $mailable = new PropertyReportedTivuchFee($property->fresh());
    expect($mailable->render())->toContain(route('admin.properties.edit', $property));
});

test('property owner cannot report their own property as having a tivuch fee', function () {
    Mail::fake();

    $owner = User::factory()->create();
    $property = Property::factory()->create(['user_id' => $owner->id]);

    $this->actingAs($owner)
        ->post(route('properties.report-tivuch-fee', $property))
        ->assertRedirect();

    expect($property->fresh()->reported_tivuch_fee_at)->toBeNull();

    Mail::assertNothingQueued();
});

test('reporting tivuch fee does not send duplicate emails', function () {
    Mail::fake();

    $property = Property::factory()->create([
        'reported_tivuch_fee_at' => now()->subDay(),
    ]);

    $this->post(route('properties.report-tivuch-fee', $property))
        ->assertRedirect();

    Mail::assertNothingQueued();
});

test('admin edit page is accessible and includes admin edit flag', function () {
    $admin = User::factory()->admin()->create();
    $property = Property::factory()->create([
        'reported_tivuch_fee_at' => now(),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.properties.edit', $property))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('properties/Edit')
            ->where('adminEdit', true)
            ->where('property.id', $property->id));
});

test('non-admin cannot access admin property edit page', function () {
    $user = User::factory()->create();
    $property = Property::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get(route('admin.properties.edit', $property))
        ->assertForbidden();
});

test('admin can mark a property as tivuch fee', function () {
    $admin = User::factory()->admin()->create();
    $property = Property::factory()->create([
        'reported_tivuch_fee_at' => now(),
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.properties.mark-tivuch-fee', $property))
        ->assertRedirect();

    $property->refresh();

    expect($property->tivuch_fee)->toBeTrue();
    expect($property->reported_tivuch_fee_at)->toBeNull();
});

test('non-admin cannot mark a property as tivuch fee', function () {
    $user = User::factory()->create();
    $property = Property::factory()->create();

    $this->actingAs($user)
        ->patch(route('admin.properties.mark-tivuch-fee', $property))
        ->assertForbidden();

    expect($property->fresh()->tivuch_fee)->toBeFalse();
});

test('tivuch fee property show returns not found', function () {
    $property = Property::factory()->tivuchFee()->create();

    $this->get(route('properties.show', $property))->assertNotFound();
});

test('properties list includes tivuch_fee flag', function () {
    Property::factory()->tivuchFee()->create();

    $this->get(route('properties.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('properties/List')
            ->has('properties.data', 1)
            ->where('properties.data.0.tivuch_fee', true));
});
