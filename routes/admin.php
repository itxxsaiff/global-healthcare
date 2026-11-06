<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PractitionerController;
use App\Http\Controllers\Admin\VerificationController;
use Illuminate\Support\Facades\Route;

// Prefix /admin, names admin.*, middleware auth+verified+role:admin+admin.2fa (bootstrap/app.php)

Route::get('/', DashboardController::class)->name('dashboard');

Route::controller(VerificationController::class)->prefix('verifications')->name('verifications.')->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/{profile}', 'show')->name('show');
    Route::post('/{profile}/approve', 'approve')->name('approve');
    Route::post('/{profile}/reject', 'reject')->name('reject');
    Route::post('/{profile}/suspend', 'suspend')->name('suspend');
    Route::post('/{profile}/reinstate', 'reinstate')->name('reinstate');
});

Route::get('/practitioners', [PractitionerController::class, 'index'])->name('practitioners.index');
