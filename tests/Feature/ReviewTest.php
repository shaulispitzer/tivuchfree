<?php

use App\Mail\ReviewMail;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;

test('anyone can submit a review', function () {
    Mail::fake();

    $this->post(route('reviews.store'), [
        'name' => 'Chaya R.',
        'email' => 'chaya@example.com',
        'role' => 'Property Owner',
        'message' => 'Great platform, highly recommend!',
    ])->assertRedirect();

    expect(Review::query()->count())->toBe(1);
    expect(Review::query()->first()->name)->toBe('Chaya R.');

    Mail::assertSent(ReviewMail::class, function (ReviewMail $mail) {
        return $mail->hasTo('info@tivuchfree.com')
            && $mail->reviewerName === 'Chaya R.'
            && $mail->reviewerEmail === 'chaya@example.com'
            && $mail->reviewerRole === 'Property Owner'
            && $mail->reviewMessage === 'Great platform, highly recommend!';
    });
});

test('review can be submitted without a role', function () {
    Mail::fake();

    $this->post(route('reviews.store'), [
        'name' => 'Dovid L.',
        'email' => 'dovid@example.com',
        'role' => null,
        'message' => 'Clean listings, direct communication.',
    ])->assertRedirect();

    expect(Review::query()->first()->role)->toBeNull();
});

test('review requires name', function () {
    $this->post(route('reviews.store'), [
        'name' => '',
        'email' => 'test@example.com',
        'message' => 'A great review.',
    ])->assertSessionHasErrors('name');
});

test('review requires valid email', function () {
    $this->post(route('reviews.store'), [
        'name' => 'Test User',
        'email' => 'not-an-email',
        'message' => 'A great review.',
    ])->assertSessionHasErrors('email');
});

test('review requires message', function () {
    $this->post(route('reviews.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'message' => '',
    ])->assertSessionHasErrors('message');
});

test('admins can view the reviews page', function () {
    $admin = User::factory()->admin()->create();
    Review::factory()->count(3)->create();

    $this->actingAs($admin)
        ->get(route('admin.reviews.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/Reviews')
            ->has('reviews.data', 3)
            ->where('reviews.total', 3)
        );
});

test('admins can delete a review', function () {
    $admin = User::factory()->admin()->create();
    $review = Review::factory()->create();

    $this->actingAs($admin)
        ->delete(route('admin.reviews.destroy', $review))
        ->assertRedirect();

    expect(Review::query()->find($review->id))->toBeNull();
});

test('non admins cannot access reviews admin page', function () {
    $user = User::factory()->create();
    $review = Review::factory()->create();

    $this->actingAs($user)->get(route('admin.reviews.index'))->assertForbidden();
    $this->actingAs($user)->delete(route('admin.reviews.destroy', $review))->assertForbidden();
});

test('guests cannot access reviews admin page', function () {
    $review = Review::factory()->create();

    $this->get(route('admin.reviews.index'))->assertRedirect(route('login'));
    $this->delete(route('admin.reviews.destroy', $review))->assertRedirect(route('login'));
});
