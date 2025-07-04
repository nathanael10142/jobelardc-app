<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

// Importation de tous les contrôleurs nécessaires
use App\Http\Controllers\HomeController;
use App\Http\Controllers\JobListingController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\GoogleRegistrationController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ProfileController; // Utilisation d'un contrôleur de profil dédié
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\CameraController;
use App\Http\Controllers\StatusController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\CallController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\UserController as AdminUserController; // Alias pour éviter les conflits de nom
// Si vous avez des contrôleurs spécifiques pour employeur/candidat, importez-les ici
// use App\Http\Controllers\Candidate\CandidateDashboardController;
// use App\Http\Controllers\Employer\EmployerDashboardController;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Route d'accueil (racine de l'application)
// Redirige vers le bon tableau de bord si l'utilisateur est authentifié, sinon vers la page de bienvenue.
Route::get('/', function () {
    if (Auth::check()) {
        $user = Auth::user();
        if ($user->hasAnyRole(['super_admin', 'admin'])) {
            return redirect()->route('admin.dashboard');
        } elseif ($user->hasAnyRole(['employer', 'candidate', 'user'])) { // Inclure 'user' si c'est un rôle par défaut
            return redirect()->route('listings.index'); // Redirige vers la liste des annonces pour les utilisateurs standards
        }
    }
    return view('welcome');
})->name('home');

// Routes d'authentification standard de Laravel
// La vérification d'e-mail est désactivée par défaut ici, comme demandé.
Auth::routes();

// Route pour le dashboard Laravel par défaut (/home)
// Redirige toujours vers la route d'accueil ('/') pour éviter la duplication.
Route::get('/home', function () {
    return redirect()->route('home');
})->middleware('auth');


/*
|--------------------------------------------------------------------------
| Routes d'authentification Google OAuth
|--------------------------------------------------------------------------
*/
Route::prefix('auth/google')->group(function () {
    Route::get('/', [LoginController::class, 'redirectToGoogle'])->name('google.auth');
    Route::get('callback', [LoginController::class, 'handleGoogleCallback'])->name('google.callback');
});

// Routes pour compléter l'inscription Google (après le callback initial)
Route::middleware('web')->group(function () {
    Route::get('/register/google/complete', [GoogleRegistrationController::class, 'showGoogleRegistrationForm'])->name('register.google.complete');
    Route::post('/register/google/complete', [GoogleRegistrationController::class, 'completeGoogleRegistration'])->name('register.google.complete.post');
    // Route AJAX pour les villes dans le formulaire d'inscription Google
    Route::get('/get-cities-by-province-google', [GoogleRegistrationController::class, 'getCitiesByProvince'])->name('get.cities.by.province.google');
});

// Route AJAX générique pour les villes (utilisée par le formulaire d'inscription standard)
Route::get('/get-cities', [RegisterController::class, 'getCitiesByProvince'])->name('get.cities.by.province');


/*
|--------------------------------------------------------------------------
| Routes protégées par l'authentification (pour tous les utilisateurs connectés)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {

    // Routes du profil utilisateur (utilisant ProfileController)
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // Routes pour les annonces d'emploi et services (Job Listings) - Ressource complète
    // Ces routes sont accessibles par tous les utilisateurs authentifiés (pour voir, créer, etc.)
    Route::resource('listings', JobListingController::class);
    // Route spécifique pour "booster" une annonce (si applicable)
    Route::post('/listings/{listing}/boost', [JobListingController::class, 'boost'])->name('listings.boost');


    // Routes de contact
    Route::get('/contact', [ContactController::class, 'showContactForm'])->name('contact.form');
    Route::post('/contact', [ContactController::class, 'submitContactForm'])->name('contact.submit');

    // Routes pour les applications (candidatures)
    Route::get('/applications', [ApplicationController::class, 'index'])->name('applications.index');

    // Routes pour les groupes
    Route::get('/groups', [GroupController::class, 'index'])->name('groups.index');

    // Routes pour les paramètres
    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');

    // Routes pour les paiements
    Route::get('/payment', [PaymentController::class, 'index'])->name('payment.index');

    // Routes pour la caméra (si vous avez une fonctionnalité de caméra)
    Route::get('/camera', [CameraController::class, 'index'])->name('camera.index');

    // Routes pour le statut (si vous avez une fonctionnalité de statut)
    Route::get('/status', [StatusController::class, 'index'])->name('status.index');

    /*
    |--------------------------------------------------------------------------
    | Routes de Chat
    |--------------------------------------------------------------------------
    */
    Route::prefix('chats')->name('chats.')->group(function () {
        Route::get('/', [ChatController::class, 'index'])->name('index');
        Route::get('/search-users', [ChatController::class, 'searchUsers'])->name('searchUsers');
        Route::post('/create', [ChatController::class, 'createConversation'])->name('createConversation');
        Route::get('/{conversation}', [ChatController::class, 'show'])->name('show');
        Route::post('/{conversation}/messages', [ChatController::class, 'sendMessage'])->name('sendMessage'); // Renommé pour clarté
        Route::get('/{conversation}/messages', [ChatController::class, 'getMessages'])->name('getMessages');
    });

    /*
    |--------------------------------------------------------------------------
    | Routes d'Appels (Audio/Vidéo)
    |--------------------------------------------------------------------------
    */
    Route::prefix('calls')->name('calls.')->group(function () {
        Route::get('/', [CallController::class, 'index'])->name('index'); // Affiche l'historique des appels
        Route::post('/initiate', [CallController::class, 'initiate'])->name('initiate');
        Route::post('/accept', [CallController::class, 'accept'])->name('accept');
        Route::post('/reject', [CallController::class, 'reject'])->name('reject');
        Route::post('/end', [CallController::class, 'end'])->name('end');
    });


    /*
    |--------------------------------------------------------------------------
    | Routes du tableau de bord Admin (Protégées par le rôle)
    |--------------------------------------------------------------------------
    */
    Route::prefix('admin')->name('admin.')->middleware('role:super_admin|admin')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');

        // Gestion des utilisateurs par l'Admin
        Route::resource('users', AdminUserController::class); // Utilise l'alias AdminUserController

        // Gestion des jobs (annonces d'emploi) par l'Admin
        Route::get('/jobs', [AdminController::class, 'jobsIndex'])->name('jobs.index');
        Route::get('/jobs/{job}', [AdminController::class, 'jobsShow'])->name('jobs.show');
        Route::put('/jobs/{job}/status', [AdminController::class, 'jobsUpdateStatus'])->name('jobs.updateStatus');
        Route::delete('/jobs/{job}', [AdminController::class, 'jobsDestroy'])->name('jobs.destroy');

        // Gestion des catégories par l'Admin
        Route::resource('categories', AdminController::class)->except(['show']); // Assurez-vous que AdminController gère ces méthodes

        // Gestion des rôles et permissions par l'Admin
        Route::resource('roles', AdminController::class)->except(['show']); // Assurez-vous que AdminController gère ces méthodes
        Route::resource('permissions', AdminController::class)->except(['show']); // Assurez-vous que AdminController gère ces méthodes

        // Gestion des Job Listings par l'Admin (si différent de 'jobs')
        Route::get('/listings', [AdminController::class, 'listingsIndex'])->name('listings.index');
        Route::get('/listings/create', [AdminController::class, 'listingsCreate'])->name('listings.create');
        Route::post('/listings', [AdminController::class, 'listingsStore'])->name('listings.store');
        Route::get('/listings/{listing}', [AdminController::class, 'listingsShow'])->name('listings.show');
        Route::get('/listings/{listing}/edit', [AdminController::class, 'listingsEdit'])->name('listings.edit');
        Route::put('/listings/{listing}', [AdminController::class, 'listingsUpdate'])->name('listings.update');
        Route::delete('/listings/{listing}', [AdminController::class, 'listingsDestroy'])->name('listings.destroy');
        Route::put('/listings/{listing}/status', [AdminController::class, 'listingsUpdateStatus'])->name('listings.updateStatus');

        // Gestion des messages/conversations par l'Admin
        Route::prefix('messages')->name('messages.')->group(function () {
            Route::get('/', [AdminController::class, 'messagesIndex'])->name('index');
            Route::get('{conversation}', [AdminController::class, 'messagesShow'])->name('show');
            Route::put('{conversation}/status', [AdminController::class, 'messagesUpdateStatus'])->name('updateStatus');
            Route::delete('{conversation}', [AdminController::class, 'messagesDestroy'])->name('destroy');
        });
    });

    // Route de test pour la redirection (peut être supprimée après vérification)
    Route::get('/redirection-test', fn () =>
        "<h1>Redirection de test : Si vous voyez ceci, la redirection fonctionne bien.</h1>"
    )->name('redirection-test');
});

