<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\Public\ArticleController;
use App\Http\Controllers\Public\BookingController;
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

Route::get('/robots.txt', function () {
    $lines = app()->isProduction()
        ? ['User-agent: *', 'Disallow: /admin', 'Disallow: /patient', 'Disallow: /practitioner', 'Disallow: /account',
            'Disallow: /documents', 'Disallow: /payments', 'Disallow: /support', 'Disallow: /notifications', 'Disallow: /search/',
            'Disallow: /*/book', '', 'Sitemap: '.url('/sitemap.xml')]
        : ['User-agent: *', 'Disallow: /']; // never index staging

    return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain']);
})->name('robots');

Route::get('/find-a-doctor', [DirectoryController::class, 'index'])->name('directory');
Route::get('/search/suggest', [DirectoryController::class, 'suggest'])->middleware('throttle:60,1')->name('search.suggest');
Route::get('/symptom/{condition:slug}', [DirectoryController::class, 'condition'])->name('conditions.show');
Route::get('/doctor/{slug}', [PractitionerController::class, 'show'])->name('practitioners.show');
Route::get('/doctor/{slug}/book', [BookingController::class, 'create'])->name('booking.create');
Route::post('/doctor/{slug}/book', [BookingController::class, 'store'])
    ->middleware(['auth', 'verified', 'role:patient', 'throttle:booking-requests'])->name('booking.store');

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
    Route::get('/notifications', [\App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/{id}', [\App\Http\Controllers\NotificationController::class, 'open'])->name('notifications.open');
    Route::post('/notifications/read', [\App\Http\Controllers\NotificationController::class, 'readAll'])->name('notifications.read');

    Route::middleware('role:patient,practitioner')->controller(\App\Http\Controllers\TicketController::class)->prefix('support')->name('tickets.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/new', 'create')->name('create');
        Route::post('/', 'store')->middleware('throttle:tickets')->name('store');
        Route::get('/{ticket:reference}', 'show')->name('show');
        Route::post('/{ticket:reference}/reply', 'reply')->middleware('throttle:tickets')->name('reply');
    });
    Route::get('/support/attachments/{message}', [\App\Http\Controllers\TicketController::class, 'attachment'])->name('tickets.attachment');

    Route::get('/documents/{document}', [\App\Http\Controllers\DocumentController::class, 'show'])
        ->middleware('throttle:60,1')->name('documents.show');
});

/*
|--------------------------------------------------------------------------
| Payments: gateway return URLs and the local test gateway
|--------------------------------------------------------------------------
*/

Route::match(['get', 'post'], '/payments/{payment:uuid}/return/{gateway}', [PaymentController::class, 'return'])
    ->middleware('throttle:30,1')->name('payments.return');
Route::get('/payments/{payment:uuid}/test', [PaymentController::class, 'fakeShow'])->name('payments.fake.show');
Route::post('/payments/{payment:uuid}/test', [PaymentController::class, 'fakeComplete'])->name('payments.fake.complete');

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
