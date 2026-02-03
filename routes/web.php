<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\PlaygroundController;
use App\Http\Controllers\PropertyController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::post('/locale', LocaleController::class)->name('locale');
Route::get('playground', [PlaygroundController::class, 'index'])->name('playground');

Route::get('properties', [PropertyController::class, 'index'])->name('properties.index');

Route::middleware('auth')->group(function () {
    Route::get('properties/create', [PropertyController::class, 'create'])->name('properties.create');
    Route::post('properties', [PropertyController::class, 'store'])->name('properties.store');
    Route::get('properties/{property}/edit', [PropertyController::class, 'edit'])->name('properties.edit');
    Route::put('properties/{property}', [PropertyController::class, 'update'])->name('properties.update');
});
// modale route
Route::get('sample-modale', function () {
    return Inertia::render('SampleModale');
})->name('sample-modale');
// modale out route
Route::get('sample-modale-out', [PlaygroundController::class, 'sampleModaleOut'])->name('sample-modale-out');
require __DIR__.'/settings.php';
