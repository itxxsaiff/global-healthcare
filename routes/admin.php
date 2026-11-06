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

Route::get('/reviews', [\App\Http\Controllers\Admin\ReviewController::class, 'index'])->name('reviews.index');
Route::patch('/reviews/{review}', [\App\Http\Controllers\Admin\ReviewController::class, 'update'])->name('reviews.update');

Route::get('/questions', [\App\Http\Controllers\Admin\QuestionController::class, 'index'])->name('questions.index');
Route::patch('/questions/{question:id}', [\App\Http\Controllers\Admin\QuestionController::class, 'update'])->name('questions.update');
Route::patch('/answers/{answer}', [\App\Http\Controllers\Admin\QuestionController::class, 'answer'])->name('answers.update');

Route::get('/articles', [\App\Http\Controllers\Admin\ArticleController::class, 'index'])->name('articles.index');
Route::get('/articles/{article:id}', [\App\Http\Controllers\Admin\ArticleController::class, 'show'])->name('articles.show');
Route::patch('/articles/{article:id}', [\App\Http\Controllers\Admin\ArticleController::class, 'update'])->name('articles.update');

Route::get('/tags', [\App\Http\Controllers\Admin\TagController::class, 'index'])->name('tags.index');
Route::patch('/tags/{tag}', [\App\Http\Controllers\Admin\TagController::class, 'update'])->name('tags.update');

Route::get('/tickets', [\App\Http\Controllers\Admin\TicketController::class, 'index'])->name('tickets.index');
Route::get('/tickets/{ticket:reference}', [\App\Http\Controllers\Admin\TicketController::class, 'show'])->name('tickets.show');
Route::post('/tickets/{ticket:reference}/reply', [\App\Http\Controllers\Admin\TicketController::class, 'reply'])->name('tickets.reply');

Route::get('/bookings', [\App\Http\Controllers\Admin\BookingController::class, 'index'])->name('bookings.index');
Route::get('/bookings/{booking:reference}', [\App\Http\Controllers\Admin\BookingController::class, 'show'])->name('bookings.show');
Route::post('/bookings/{booking:reference}/refunded', [\App\Http\Controllers\Admin\BookingController::class, 'refunded'])->name('bookings.refunded');
Route::post('/bookings/{booking:reference}/cancel', [\App\Http\Controllers\Admin\BookingController::class, 'cancel'])->name('bookings.cancel');

Route::get('/wallet', [\App\Http\Controllers\Admin\WalletController::class, 'index'])->name('wallet.index');
Route::post('/wallet/payouts', [\App\Http\Controllers\Admin\WalletController::class, 'storePayout'])->name('wallet.payouts.store');
Route::get('/payments', [\App\Http\Controllers\Admin\PaymentController::class, 'index'])->name('payments.index');

Route::get('/plans', [\App\Http\Controllers\Admin\PlanController::class, 'index'])->name('plans.index');
Route::put('/plans/{plan}', [\App\Http\Controllers\Admin\PlanController::class, 'update'])->name('plans.update');

Route::get('/users', [\App\Http\Controllers\Admin\UserController::class, 'index'])->name('users.index');
Route::post('/users/{user}/toggle', [\App\Http\Controllers\Admin\UserController::class, 'toggle'])->name('users.toggle');

Route::controller(\App\Http\Controllers\Admin\TaxonomyController::class)->prefix('taxonomy')->name('taxonomy.')->group(function () {
    Route::get('/', 'index')->name('index');
    Route::post('/specialties/{specialty:id}/subs', 'storeSub')->name('subs.store');
    Route::post('/specialties/{specialty:id}/toggle', 'toggleSub')->name('subs.toggle');
    Route::post('/conditions', 'storeCondition')->name('conditions.store');
    Route::post('/conditions/{condition:id}/toggle', 'toggleCondition')->name('conditions.toggle');
});

Route::controller(\App\Http\Controllers\Admin\SettingsController::class)->group(function () {
    Route::get('/settings', 'edit')->name('settings.edit');
    Route::put('/settings', 'update')->name('settings.update');
    Route::get('/pages', 'pages')->name('pages.index');
    Route::put('/pages/{page}', 'updatePage')->name('pages.update');
    Route::get('/audit', 'audit')->name('audit.index');
});
