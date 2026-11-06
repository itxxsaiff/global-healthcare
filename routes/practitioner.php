<?php

use App\Http\Controllers\Practitioner\DashboardController;
use App\Http\Controllers\Practitioner\OnboardingController;
use Illuminate\Support\Facades\Route;

// Prefix /practitioner, names practitioner.*, middleware auth+verified+role:practitioner (bootstrap/app.php)

Route::get('/', DashboardController::class)->name('dashboard');

Route::controller(OnboardingController::class)->prefix('profile')->name('onboarding.')->group(function () {
    Route::get('/', 'show')->name('show');
    Route::put('/details', 'details')->name('details');
    Route::put('/focus', 'focus')->name('focus');
    Route::post('/about', 'about')->middleware('throttle:uploads')->name('about');
    Route::post('/documents', 'documents')->middleware('throttle:uploads')->name('documents');
    Route::post('/submit', 'submit')->name('submit');
});

Route::controller(\App\Http\Controllers\Practitioner\BookingController::class)->prefix('bookings')->name('bookings.')->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/calendar', 'calendar')->name('calendar');
    Route::get('/{booking:reference}', 'show')->name('show');
    Route::post('/{booking:reference}/accept', 'accept')->name('accept');
    Route::post('/{booking:reference}/reject', 'reject')->name('reject');
    Route::post('/{booking:reference}/meeting', 'meeting')->name('meeting');
    Route::post('/{booking:reference}/cancel', 'cancel')->name('cancel');
    Route::post('/{booking:reference}/complete', 'complete')->name('complete');
});

Route::controller(\App\Http\Controllers\Practitioner\AvailabilityController::class)->prefix('availability')->name('availability.')->group(function () {
    Route::get('/', 'edit')->name('edit');
    Route::put('/', 'update')->name('update');
    Route::post('/exceptions', 'storeException')->name('exceptions.store');
    Route::delete('/exceptions/{id}', 'destroyException')->name('exceptions.destroy');
});

Route::controller(\App\Http\Controllers\Practitioner\MembershipController::class)->prefix('membership')->name('membership.')->group(function () {
    Route::get('/', 'show')->name('show');
    Route::post('/plans/{plan:code}', 'choose')->middleware('throttle:10,1')->name('choose');
    Route::get('/billing', 'portal')->name('portal');
});
