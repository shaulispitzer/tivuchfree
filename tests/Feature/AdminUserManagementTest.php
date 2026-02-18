<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('admins can view users table', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    $response = $this->actingAs($admin)->get(route('admin.users.index'));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/Users')
            ->has('users', 2)
            ->where('users.0.id', $user->id)
            ->where('users.0.email', $user->email)
            ->where('users.0.is_admin', false)
        );
});

test('admins can mark users as admin', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create(['is_admin' => false]);

    $response = $this->actingAs($admin)->patch(route('admin.users.make-admin', $user));

    $response->assertRedirect();
    expect($user->fresh()->is_admin)->toBeTrue();
});

test('admins can delete users', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    $response = $this->actingAs($admin)->delete(route('admin.users.destroy', $user));

    $response->assertRedirect();
    expect(User::query()->find($user->id))->toBeNull();
});

test('non admins cannot access users table', function () {
    $user = User::factory()->create();
    $target = User::factory()->create();

    $this->actingAs($user)->get(route('admin.users.index'))->assertForbidden();
    $this->actingAs($user)->patch(route('admin.users.make-admin', $target))->assertForbidden();
    $this->actingAs($user)->delete(route('admin.users.destroy', $target))->assertForbidden();
});

test('guests cannot access users table', function () {
    $user = User::factory()->create();

    $this->get(route('admin.users.index'))->assertRedirect(route('login'));
    $this->patch(route('admin.users.make-admin', $user))->assertRedirect(route('login'));
    $this->delete(route('admin.users.destroy', $user))->assertRedirect(route('login'));
});
