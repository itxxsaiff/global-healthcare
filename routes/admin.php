<?php

use App\Http\Controllers\Admin\ArticleController;
use App\Http\Controllers\Admin\BookingController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\PlanController;
use App\Http\Controllers\Admin\PractitionerController;
use App\Http\Controllers\Admin\QuestionController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\TagController;
use App\Http\Controllers\Admin\TaxonomyController;
use App\Http\Controllers\Admin\TicketController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\VerificationController;
use App\Http\Controllers\Admin\WalletController;
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
    Route::delete('/{profile}/video', 'removeVideo')->name('video.destroy');
});

Route::get('/practitioners', [PractitionerController::class, 'index'])->name('practitioners.index');

Route::get('/reviews', [ReviewController::class, 'index'])->name('reviews.index');
Route::patch('/reviews/{review}', [ReviewController::class, 'update'])->name('reviews.update');

Route::get('/questions', [QuestionController::class, 'index'])->name('questions.index');
Route::patch('/questions/{question:id}', [QuestionController::class, 'update'])->name('questions.update');
Route::patch('/answers/{answer}', [QuestionController::class, 'answer'])->name('answers.update');

Route::get('/articles', [ArticleController::class, 'index'])->name('articles.index');
Route::get('/articles/{article:id}', [ArticleController::class, 'show'])->name('articles.show');
Route::patch('/articles/{article:id}', [ArticleController::class, 'update'])->name('articles.update');

Route::get('/tags', [TagController::class, 'index'])->name('tags.index');
Route::patch('/tags/{tag}', [TagController::class, 'update'])->name('tags.update');

Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
Route::get('/tickets/{ticket:reference}', [TicketController::class, 'show'])->name('tickets.show');
Route::post('/tickets/{ticket:reference}/reply', [TicketController::class, 'reply'])->name('tickets.reply');

Route::get('/bookings', [BookingController::class, 'index'])->name('bookings.index');
Route::get('/bookings/{booking:reference}', [BookingController::class, 'show'])->name('bookings.show');
Route::post('/bookings/{booking:reference}/refunded', [BookingController::class, 'refunded'])->name('bookings.refunded');
Route::post('/bookings/{booking:reference}/cancel', [BookingController::class, 'cancel'])->name('bookings.cancel');

Route::get('/wallet', [WalletController::class, 'index'])->name('wallet.index');
Route::post('/wallet/payouts', [WalletController::class, 'storePayout'])->name('wallet.payouts.store');
Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');

Route::get('/plans', [PlanController::class, 'index'])->name('plans.index');
Route::put('/plans/{plan}', [PlanController::class, 'update'])->name('plans.update');

Route::get('/users', [UserController::class, 'index'])->name('users.index');
Route::post('/users/{user}/toggle', [UserController::class, 'toggle'])->name('users.toggle');

Route::controller(TaxonomyController::class)->prefix('taxonomy')->name('taxonomy.')->group(function () {
    Route::get('/', 'index')->name('index');
    Route::post('/specialties/{specialty:id}/subs', 'storeSub')->name('subs.store');
    Route::post('/specialties/{specialty:id}/toggle', 'toggleSub')->name('subs.toggle');
    Route::post('/conditions', 'storeCondition')->name('conditions.store');
    Route::post('/conditions/{condition:id}/toggle', 'toggleCondition')->name('conditions.toggle');
});

Route::controller(SettingsController::class)->group(function () {
    Route::get('/settings', 'edit')->name('settings.edit');
    Route::put('/settings', 'update')->name('settings.update');
    Route::get('/pages', 'pages')->name('pages.index');
    Route::put('/pages/{page}', 'updatePage')->name('pages.update');
    Route::get('/audit', 'audit')->name('audit.index');
});
