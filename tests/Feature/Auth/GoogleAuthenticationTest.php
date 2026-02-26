<?php

use App\Mail\WelcomeEmail;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

test('google redirect route redirects to google', function () {
    Socialite::fake('google');

    $response = $this->get(route('auth.google.redirect'));

    $response->assertRedirect();
});

test('google callback creates a new user and authenticates', function () {
    Event::fake([Registered::class]);

    Socialite::fake('google', (new SocialiteUser)->map([
        'id' => 'google-123',
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'avatar' => 'https://example.com/avatar.jpg',
    ]));

    $response = $this->get(route('auth.google.callback'));

    $response->assertRedirect(route('home'));

    $this->assertAuthenticated();

    $this->assertDatabaseHas('users', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'google_id' => 'google-123',
        'google_avatar' => 'https://example.com/avatar.jpg',
    ]);

    expect(User::where('email', 'john@example.com')->first())
        ->email_verified_at->not->toBeNull();

    Event::assertDispatched(Registered::class);
});

test('google callback authenticates existing user and updates google fields', function () {
    $user = User::factory()->create([
        'email' => 'existing@example.com',
        'google_id' => null,
        'google_avatar' => null,
    ]);

    Socialite::fake('google', (new SocialiteUser)->map([
        'id' => 'google-456',
        'name' => 'Existing User',
        'email' => 'existing@example.com',
        'avatar' => 'https://example.com/avatar.jpg',
    ]));

    $response = $this->get(route('auth.google.callback'));

    $response->assertRedirect(route('home'));

    $this->assertAuthenticatedAs($user);

    $user->refresh();

    expect($user)
        ->google_id->toBe('google-456')
        ->google_avatar->toBe('https://example.com/avatar.jpg');
});

test('welcome email is sent when new user signs up with google', function () {
    Mail::fake();

    Socialite::fake('google', (new SocialiteUser)->map([
        'id' => 'google-new',
        'name' => 'New Google User',
        'email' => 'newgoogle@example.com',
        'avatar' => 'https://example.com/avatar.jpg',
    ]));

    $this->get(route('auth.google.callback'));

    Mail::assertQueued(WelcomeEmail::class, function (WelcomeEmail $mail) {
        return $mail->user->email === 'newgoogle@example.com'
            && $mail->hasTo('newgoogle@example.com');
    });
});

test('welcome email is not sent when existing user logs in with google', function () {
    Mail::fake();

    User::factory()->create(['email' => 'existing@example.com']);

    Socialite::fake('google', (new SocialiteUser)->map([
        'id' => 'google-789',
        'name' => 'Existing User',
        'email' => 'existing@example.com',
        'avatar' => 'https://example.com/avatar.jpg',
    ]));

    $this->get(route('auth.google.callback'));

    Mail::assertNotQueued(WelcomeEmail::class);
});

test('google callback does not dispatch registered event for existing user', function () {
    Event::fake([Registered::class]);

    User::factory()->create(['email' => 'existing@example.com']);

    Socialite::fake('google', (new SocialiteUser)->map([
        'id' => 'google-789',
        'name' => 'Existing User',
        'email' => 'existing@example.com',
        'avatar' => 'https://example.com/avatar.jpg',
    ]));

    $this->get(route('auth.google.callback'));

    Event::assertNotDispatched(Registered::class);
});

test('google auth routes are only accessible to guests', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('auth.google.redirect'))
        ->assertRedirect();
});
