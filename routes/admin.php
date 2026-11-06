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
