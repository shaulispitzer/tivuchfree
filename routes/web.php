<?php

use App\Http\Controllers\Admin\PropertyController as AdminPropertyController;
use App\Http\Controllers\Admin\PropertyStatController as AdminPropertyStatController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Auth\SocialiteController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\PlaygroundController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\PropertyImageUploadController;
use App\Http\Controllers\PropertySubscriptionController;
use App\Http\Controllers\StreetController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('about-us', function () {
    return Inertia::render('AboutUs');
})->name('about-us');
Route::get('contact', [ContactController::class, 'create'])->name('contact.create');
Route::post('contact', [ContactController::class, 'store'])->name('contact.store');

Route::post('/locale', LocaleController::class)->name('locale');
Route::get('playground', [PlaygroundController::class, 'index'])->name('playground');

Route::get('properties', [PropertyController::class, 'index'])->name('properties.index');

Route::get('subscribe', [PropertySubscriptionController::class, 'create'])->name('subscribe');
Route::post('property-subscriptions', [PropertySubscriptionController::class, 'store'])->name('property-subscriptions.store');
Route::post('property-subscriptions/verify-otp', [PropertySubscriptionController::class, 'verifyOtp'])->name('property-subscriptions.verify-otp');
Route::get('subscriptions/unsubscribe/{token}', [PropertySubscriptionController::class, 'unsubscribe'])->name('subscriptions.unsubscribe');
Route::post('subscriptions/unsubscribe/{token}', [PropertySubscriptionController::class, 'confirmUnsubscribe'])->name('subscriptions.confirm-unsubscribe');
Route::get('subscriptions/update-filters/{token}', [PropertySubscriptionController::class, 'updateFilters'])->name('subscriptions.update-filters');
Route::post('subscriptions/update-filters/{token}', [PropertySubscriptionController::class, 'saveFilters'])->name('subscriptions.save-filters');

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('properties', [AdminPropertyController::class, 'index'])->name('properties.index');
    Route::delete('properties/{property}', [AdminPropertyController::class, 'destroy'])->name('properties.destroy');
    Route::get('property-stats', [AdminPropertyStatController::class, 'index'])->name('property-stats.index');
    Route::delete('property-stats/{propertyStat}', [AdminPropertyStatController::class, 'destroy'])->name('property-stats.destroy');
    Route::get('users', [AdminUserController::class, 'index'])->name('users.index');
    Route::patch('users/{user}/make-admin', [AdminUserController::class, 'makeAdmin'])->name('users.make-admin');
    Route::delete('users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');
    Route::get('streets', [StreetController::class, 'index'])->name('streets.index');
    Route::get('streets/create', [StreetController::class, 'create'])->name('streets.create');
    Route::post('streets', [StreetController::class, 'store'])->name('streets.store');
    Route::post('streets/import', [StreetController::class, 'import'])->name('streets.import');
    Route::get('streets/{street}/edit', [StreetController::class, 'edit'])->name('streets.edit');
    Route::put('streets/{street}', [StreetController::class, 'update'])->name('streets.update');
    Route::delete('streets/{street}', [StreetController::class, 'destroy'])->name('streets.destroy');
});

Route::middleware('auth', 'verified')->group(function () {
    Route::post('property-image-uploads', [PropertyImageUploadController::class, 'store'])
        ->name('property-image-uploads.store');
    Route::delete('property-image-uploads/{tempUpload}/media/{media}', [PropertyImageUploadController::class, 'destroy'])
        ->name('property-image-uploads.destroy');

    Route::get('properties/create', [PropertyController::class, 'create'])->name('properties.create');
    Route::get('properties/streets', [PropertyController::class, 'streets'])->name('properties.streets');
    Route::post('properties', [PropertyController::class, 'store'])->name('properties.store');
    Route::get('properties/{property}/edit', [PropertyController::class, 'edit'])->name('properties.edit');
    Route::get('properties/{property}/images', [PropertyController::class, 'images'])->name('properties.images.index');
    Route::post('properties/{property}/images', [PropertyController::class, 'storeImage'])->name('properties.images.store');
    Route::delete('properties/{property}/images/{media}', [PropertyController::class, 'destroyImage'])->name('properties.images.destroy');
    Route::put('properties/{property}', [PropertyController::class, 'update'])->name('properties.update');
    Route::get('my-properties', [PropertyController::class, 'myProperties'])->name('my-properties.index');
    Route::patch('my-properties/{property}/mark-as-taken', [PropertyController::class, 'markAsTaken'])
        ->name('my-properties.mark-as-taken');
    Route::patch('my-properties/{property}/repost', [PropertyController::class, 'repost'])
        ->name('my-properties.repost');
    Route::delete('my-properties/{property}', [PropertyController::class, 'destroyMyProperty'])
        ->name('my-properties.destroy');

});

// mails routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/mailable-taken-warning', function () {
        $property = \App\Models\Property::findOrFail(34);

        return new \App\Mail\PropertyTakenWarning($property);
    });
    Route::get('/mailable-welcome', function () {
        return new \App\Mail\WelcomeEmail(\App\Models\User::firstOrFail());
    });
    Route::get('/mailable/subscription/notification', function () {
        $property = \App\Models\Property::findOrFail(34);
        $subscription = \App\Models\PropertySubscription::firstOrFail();

        return new \App\Mail\PropertySubscriptionNotification($property, $subscription, route('properties.show', $property), route('subscriptions.unsubscribe', $subscription->token), route('subscriptions.update-filters', $subscription->token));
    });
    Route::get('/mailable/subscription/confirmation', function () {
        $subscription = \App\Models\PropertySubscription::firstOrFail();

        return new \App\Mail\PropertySubscriptionConfirmation($subscription, route('subscriptions.unsubscribe', $subscription->token), route('subscriptions.update-filters', $subscription->token));
    });
    Route::get('/mailable/subscription/expired', function () {
        $subscription = \App\Models\PropertySubscription::firstOrFail();

        return new \App\Mail\PropertySubscriptionExpired($subscription, route('properties.index'));
    });
    Route::get('/mailable/listing-status-change', function () {
        $user = \App\Models\User::firstOrFail();
        $property = \App\Models\Property::firstOrFail();

        return new \App\Mail\PropertyListingStatusChange(
            $user->name,
            trim($property->street.($property->building_number ? ' '.$property->building_number : '')),
            request()->query('action', 'marked_as_taken'),
            request()->query('method', 'manually'),
        );
    });
});
Route::middleware('guest')->group(function () {
    Route::get('auth/google/redirect', [SocialiteController::class, 'create'])->name('auth.google.redirect');
    Route::get('auth/google/callback', [SocialiteController::class, 'store'])->name('auth.google.callback');
});
Route::get('properties/{property}', [PropertyController::class, 'show'])->name('properties.show');

// modale route PLAYGROUND
Route::get('sample-modale', function () {
    return Inertia::render('SampleModale');
})->name('sample-modale');
// modale out route
Route::get('sample-modale-out', [PlaygroundController::class, 'sampleModaleOut'])->name('sample-modale-out');
require __DIR__.'/settings.php';
