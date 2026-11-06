<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Public\ArticleController;
use App\Http\Controllers\Public\DirectoryController;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\PageController;
use App\Http\Controllers\Public\PractitionerController;
use App\Http\Controllers\Public\QuestionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public site
|--------------------------------------------------------------------------
*/

Route::get('/', HomeController::class)->name('home');

Route::get('/find-a-doctor', [DirectoryController::class, 'index'])->name('directory');
Route::get('/search/suggest', [DirectoryController::class, 'suggest'])->middleware('throttle:60,1')->name('search.suggest');
Route::get('/symptom/{condition:slug}', [DirectoryController::class, 'condition'])->name('conditions.show');
Route::get('/doctor/{slug}', [PractitionerController::class, 'show'])->name('practitioners.show');

Route::get('/ask-a-doctor', [QuestionController::class, 'index'])->name('questions.index');
Route::get('/ask-a-doctor/{question:slug}', [QuestionController::class, 'show'])->name('questions.show');

Route::get('/blog', [ArticleController::class, 'index'])->name('articles.index');
Route::get('/blog/{article:slug}', [ArticleController::class, 'show'])->name('articles.show');

Route::get('/for-practitioners', [PageController::class, 'forPractitioners'])->name('for-practitioners');
Route::get('/legal/{page:slug}', [PageController::class, 'show'])->name('pages.show');

Route::middleware('guest')->group(function () {
    Route::view('/register/patient', 'auth.register-patient')->name('register.patient');
    Route::view('/register/practitioner', 'auth.register-practitioner')->name('register.practitioner');
});

/*
|--------------------------------------------------------------------------
| Shared authenticated area
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/account', [AccountController::class, 'edit'])->name('account.edit');
});

/*
|--------------------------------------------------------------------------
| SEO landing pages: /dermatologists and /dermatologists/lahore
| Constrained to the 14 top-level specialty slugs so they never shadow other routes.
|--------------------------------------------------------------------------
*/

$specialtyPattern = implode('|', config('platform.specialty_slugs'));

Route::get('/{specialty}', [DirectoryController::class, 'specialty'])
    ->where('specialty', $specialtyPattern)->name('specialties.show');
Route::get('/{specialty}/{city}', [DirectoryController::class, 'specialtyCity'])
    ->where(['specialty' => $specialtyPattern, 'city' => '[a-z0-9-]+'])->name('specialties.city');
