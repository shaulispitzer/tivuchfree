<?php

use App\Http\Controllers\Admin\PropertyController as AdminPropertyController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\PlaygroundController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\PropertyImageUploadController;
use App\Http\Controllers\StreetController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::post('/locale', LocaleController::class)->name('locale');
Route::get('playground', [PlaygroundController::class, 'index'])->name('playground');

Route::get('properties', [PropertyController::class, 'index'])->name('properties.index');

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('properties', [AdminPropertyController::class, 'index'])->name('properties.index');
    Route::delete('properties/{property}', [AdminPropertyController::class, 'destroy'])->name('properties.destroy');
    Route::get('users', [AdminUserController::class, 'index'])->name('users.index');
    Route::patch('users/{user}/make-admin', [AdminUserController::class, 'makeAdmin'])->name('users.make-admin');
    Route::delete('users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');
});

Route::middleware('auth')->group(function () {
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

    Route::get('streets', [StreetController::class, 'index'])->name('streets.index');
    Route::get('streets/create', [StreetController::class, 'create'])->name('streets.create');
    Route::post('streets', [StreetController::class, 'store'])->name('streets.store');
    Route::get('streets/{street}/edit', [StreetController::class, 'edit'])->name('streets.edit');
    Route::put('streets/{street}', [StreetController::class, 'update'])->name('streets.update');
    Route::delete('streets/{street}', [StreetController::class, 'destroy'])->name('streets.destroy');
});

Route::get('properties/{property}', [PropertyController::class, 'show'])->name('properties.show');
// modale route
Route::get('sample-modale', function () {
    return Inertia::render('SampleModale');
})->name('sample-modale');
// modale out route
Route::get('sample-modale-out', [PlaygroundController::class, 'sampleModaleOut'])->name('sample-modale-out');
require __DIR__.'/settings.php';
