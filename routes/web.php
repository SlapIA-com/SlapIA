<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\BlogArticleController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\AvatarController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public marketing site
|--------------------------------------------------------------------------
| Mêmes URLs "propres" que l'ancien site (déjà réécrites via .htaccess :
| /formations, /contact, etc.) plutôt que les anciens *.php.
*/
Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/formations', [PageController::class, 'courses'])->name('courses');
Route::get('/services-pc', [PageController::class, 'services'])->name('services');
Route::get('/tarifs', [PageController::class, 'pricing'])->name('pricing');
Route::get('/a-propos', [PageController::class, 'about'])->name('about');
Route::get('/mentions-legales', [PageController::class, 'legalMentions'])->name('legal.mentions');
Route::get('/confidentialite', [PageController::class, 'legalPrivacy'])->name('legal.privacy');
Route::get('/cgv', [PageController::class, 'legalCgv'])->name('legal.cgv');

Route::get('/contact', [ContactController::class, 'show'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');

Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('blog.show');

Route::get('/api/avatar/{client}', [AvatarController::class, 'show'])->name('avatar');

/*
|--------------------------------------------------------------------------
| Auth
|--------------------------------------------------------------------------
| Pas d'auto-inscription (règle métier reprise de l'ancien site) : les
| comptes sont créés uniquement depuis l'admin (voir AdminController::createClient).
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
    Route::get('/mot-de-passe-oublie', [PasswordResetController::class, 'create'])->name('password.request');
    Route::post('/mot-de-passe-oublie', [PasswordResetController::class, 'store'])->name('password.email');
    Route::get('/reinitialiser-mot-de-passe/{token}', [PasswordResetController::class, 'edit'])->name('password.edit');
    Route::post('/reinitialiser-mot-de-passe', [PasswordResetController::class, 'update'])->name('password.update');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')->name('logout');

/*
|--------------------------------------------------------------------------
| Espace client
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:particulier,entreprise,admin'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/dashboard/password', [DashboardController::class, 'updatePassword'])->name('dashboard.password');
    Route::post('/dashboard/linkedin', [DashboardController::class, 'updateLinkedin'])->name('dashboard.linkedin');
    Route::post('/dashboard/contact', [DashboardController::class, 'updateContact'])->name('dashboard.contact');
    Route::post('/dashboard/review', [DashboardController::class, 'updateReview'])->name('dashboard.review');
    Route::post('/dashboard/photo', [DashboardController::class, 'uploadPhoto'])->name('dashboard.photo');
    Route::get('/dashboard/factures/{index}', [DashboardController::class, 'viewInvoice'])->name('dashboard.invoice');
});

/*
|--------------------------------------------------------------------------
| Admin
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('index');
    Route::patch('/comptes/{client}', [AdminController::class, 'updateAccount'])->name('accounts.update');
    Route::patch('/comptes/{client}/profil', [AdminController::class, 'updateProfile'])->name('accounts.profile');
    Route::post('/comptes/reset-password', [AdminController::class, 'resetPassword'])->name('accounts.reset-password');
    Route::post('/comptes', [AdminController::class, 'createClient'])->name('accounts.create');
    Route::post('/comptes/{client}/prestations', [AdminController::class, 'storePrestation'])->name('prestations.store');
    Route::patch('/prestations/{prestation}', [AdminController::class, 'updatePrestation'])->name('prestations.update');
    Route::delete('/prestations/{prestation}', [AdminController::class, 'destroyPrestation'])->name('prestations.destroy');
    Route::patch('/avis/{avis}', [AdminController::class, 'updateReview'])->name('reviews.update');
    Route::delete('/avis/{avis}', [AdminController::class, 'destroyReview'])->name('reviews.destroy');
    Route::post('/comptes/{client}/factures', [AdminController::class, 'uploadInvoice'])->name('invoices.upload');
    Route::get('/factures/{facture}', [AdminController::class, 'viewInvoice'])->name('invoices.view');
    Route::post('/comptes/{client}/photo', [AdminController::class, 'uploadPhoto'])->name('accounts.photo');
    Route::post('/abonnes-rss', [AdminController::class, 'storeRssSubscriber'])->name('rss.store');
    Route::delete('/abonnes-rss/{subscriber}', [AdminController::class, 'destroyRssSubscriber'])->name('rss.destroy');
    Route::post('/articles', [BlogArticleController::class, 'store'])->name('articles.store');
    Route::patch('/articles/{article}', [BlogArticleController::class, 'update'])->name('articles.update');
    Route::delete('/articles/{article}', [BlogArticleController::class, 'destroy'])->name('articles.destroy');
});
