<?php

use App\Mail\ContactMail;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;

test('contact page renders successfully', function () {
    $this->get(route('contact.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Contact'));
});

test('contact form can be submitted with valid data', function () {
    Mail::fake();

    $this->post(route('contact.store'), [
        'subject' => 'Test Subject',
        'email' => 'test@example.com',
        'is_about_dira' => false,
        'property_id' => null,
        'message' => 'This is a test message.',
    ])->assertRedirect(route('home'));

    Mail::assertSent(ContactMail::class, function (ContactMail $mail) {
        return $mail->hasTo('shaulispitzer@gmail.com')
            && $mail->contactSubject === 'Test Subject'
            && $mail->email === 'test@example.com'
            && $mail->isAboutDira === false
            && $mail->contactMessage === 'This is a test message.';
    });
});

test('contact form can be submitted with dira details', function () {
    Mail::fake();

    $this->post(route('contact.store'), [
        'subject' => 'Dira Question',
        'email' => 'test@example.com',
        'is_about_dira' => true,
        'property_id' => '42',
        'message' => 'Question about this dira.',
    ])->assertRedirect(route('home'));

    Mail::assertSent(ContactMail::class, function (ContactMail $mail) {
        return $mail->isAboutDira === true
            && $mail->propertyId === '42';
    });
});

test('contact form requires subject', function () {
    $this->post(route('contact.store'), [
        'subject' => '',
        'email' => 'test@example.com',
        'is_about_dira' => false,
        'message' => 'Test message.',
    ])->assertSessionHasErrors('subject');
});

test('contact form requires email', function () {
    $this->post(route('contact.store'), [
        'subject' => 'Test',
        'email' => '',
        'is_about_dira' => false,
        'message' => 'Test message.',
    ])->assertSessionHasErrors('email');
});

test('contact form requires message', function () {
    $this->post(route('contact.store'), [
        'subject' => 'Test',
        'email' => 'test@example.com',
        'is_about_dira' => false,
        'message' => '',
    ])->assertSessionHasErrors('message');
});

test('contact form requires property_id when is_about_dira is true', function () {
    $this->post(route('contact.store'), [
        'subject' => 'Test',
        'email' => 'test@example.com',
        'is_about_dira' => true,
        'property_id' => null,
        'message' => 'Test message.',
    ])->assertSessionHasErrors('property_id');
});
