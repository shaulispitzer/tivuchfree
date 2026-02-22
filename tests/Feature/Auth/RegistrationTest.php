<?php

use App\Mail\WelcomeEmail;
use Illuminate\Support\Facades\Mail;

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('new users can register', function () {
    Mail::fake();

    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('welcome email is sent when a user registers', function () {
    Mail::fake();

    $this->post(route('register.store'), [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    Mail::assertQueued(WelcomeEmail::class, function (WelcomeEmail $mail) {
        return $mail->user->email === 'jane@example.com'
            && $mail->hasTo('jane@example.com');
    });
});

test('welcome email contains expected content', function () {
    $user = \App\Models\User::factory()->create(['name' => 'Mario']);

    $mailable = new WelcomeEmail($user);

    $mailable->assertHasSubject('Welcome to Tivuch Free!')
        ->assertSeeInOrderInHtml([
            'Mario',
            'Browse Properties',
            'Create a Listing',
        ]);
});
