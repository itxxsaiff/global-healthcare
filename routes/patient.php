<?php

use App\Http\Controllers\Patient\BookingController;
use App\Http\Controllers\Patient\DashboardController;
use Illuminate\Support\Facades\Route;

// Prefix /patient, names patient.*, middleware auth+verified+role:patient (bootstrap/app.php)

Route::get('/', DashboardController::class)->name('dashboard');

Route::controller(BookingController::class)->prefix('bookings')->name('bookings.')->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/{booking:reference}', 'show')->name('show');
    Route::post('/{booking:reference}/pay', 'pay')->middleware('throttle:10,1')->name('pay');
    Route::post('/{booking:reference}/cancel', 'cancel')->name('cancel');
});
