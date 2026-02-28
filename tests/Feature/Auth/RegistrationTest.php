<?php

use App\Mail\WelcomeEmail;
use Illuminate\Support\Facades\Mail;

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('new users can register and are redirected to email verification', function () {
    Mail::fake();

    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('verification.notice', absolute: false));
});

test('welcome email is sent when a user verifies their email', function () {
    Mail::fake();

    $user = \App\Models\User::factory()->unverified()->create([
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
    ]);

    $verificationUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)]
    );

    $this->actingAs($user)->get($verificationUrl);

    Mail::assertQueued(WelcomeEmail::class, function (WelcomeEmail $mail) {
        return $mail->user->email === 'jane@example.com'
            && $mail->hasTo('jane@example.com');
    });
});

test('welcome email is not sent on registration', function () {
    Mail::fake();

    $this->post(route('register.store'), [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    Mail::assertNotQueued(WelcomeEmail::class);
});

test('welcome email contains expected content', function () {
    $user = \App\Models\User::factory()->create(['name' => 'Mario']);

    $mailable = new WelcomeEmail($user);

    $mailable->assertHasSubject(__('mail.welcome.subject', ['app' => config('app.name')]))
        ->assertSeeInOrderInHtml([
            'Mario',
            'Browse Properties',
            'Create a Listing',
        ]);
});
