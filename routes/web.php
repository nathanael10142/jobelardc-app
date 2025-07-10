<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log; // Importation de la façade Log
use Illuminate\Http\Request; // Pour les requêtes API

use App\Http\Controllers\HomeController;
use App\Http\Controllers\JobListingController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\GoogleRegistrationController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\CameraController;
use App\Http\Controllers\StatusController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\CallController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\UserController as AdminUserController;

Route::get('/', function () {
    Log::debug('WEB: Route principale (/) atteinte.');
    if (Auth::check()) {
        $user = Auth::user();
        Log::debug('WEB: Utilisateur authentifié sur la route principale : ' . $user->email . ' (ID: ' . $user->id . ')');
        if ($user->hasAnyRole(['super_admin', 'admin'])) {
            return redirect()->route('admin.dashboard');
        }
        return redirect()->route('listings.index');
    }
    Log::debug('WEB: Aucun utilisateur authentifié sur la route principale, redirection vers login.');
    return redirect()->route('login');
})->name('home');

Auth::routes();

Route::get('/home', function () {
    return redirect()->route('home');
})->middleware('auth');

Route::prefix('auth/google')->group(function () {
    Route::get('/', [LoginController::class, 'redirectToGoogle'])->name('google.auth');
    Route::get('callback', [LoginController::class, 'handleGoogleCallback'])->name('google.callback');
});

Route::middleware('web')->group(function () {
    Route::get('/register/google/complete', [GoogleRegistrationController::class, 'showGoogleRegistrationForm'])->name('register.google.complete');
    Route::post('/register/google/complete', [GoogleRegistrationController::class, 'completeGoogleRegistration'])->name('register.google.complete.post');
    Route::get('/get-cities-by-province-google', [GoogleRegistrationController::class, 'getCitiesByProvince'])->name('get.cities.by.province.google');
});

Route::get('/get-cities', [RegisterController::class, 'getCitiesByProvince'])->name('get.cities.by.province');

Route::middleware(['auth'])->group(function () {

    // IMPORTANT: protège la route d'authentification broadcasting
    Broadcast::routes(['middleware' => ['auth']]); // Ou 'auth:sanctum' selon ta config

    // Profil utilisateur
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // Annonces
    Route::resource('listings', JobListingController::class);
    Route::post('/listings/{listing}/boost', [JobListingController::class, 'boost'])->name('listings.boost');

    // Contact
    Route::get('/contact', [ContactController::class, 'showContactForm'])->name('contact.form');
    Route::post('/contact', [ContactController::class, 'submitContactForm'])->name('contact.submit');

    // Applications
    Route::get('/applications', [ApplicationController::class, 'index'])->name('applications.index');

    // Groupes
    Route::get('/groups', [GroupController::class, 'index'])->name('groups.index');

    // Paramètres
    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');

    // Paiements
    Route::get('/payment', [PaymentController::class, 'index'])->name('payment.index');

    // Caméra
    Route::get('/camera', [CameraController::class, 'index'])->name('camera.index');

    // Statut
    Route::get('/status', [StatusController::class, 'index'])->name('status.index');

    // Chats
    Route::prefix('chats')->name('chats.')->group(function () {
        Route::get('/', [ChatController::class, 'index'])->name('index');
        Route::get('/search-users', [ChatController::class, 'searchUsers'])->name('searchUsers');
        Route::post('/create', [ChatController::class, 'createConversation'])->name('createConversation');
        Route::get('/{conversation}', [ChatController::class, 'show'])->name('show');
        Route::post('/{conversation}/messages', [ChatController::class, 'sendMessage'])->name('sendMessage');
        Route::get('/{conversation}/messages', [ChatController::class, 'getMessages'])->name('getMessages');
    });

    // Appels
    Route::prefix('calls')->name('calls.')->group(function () {
        Route::get('/', [CallController::class, 'index'])->name('index');
        Route::post('/initiate', [CallController::class, 'initiate'])->name('initiate');
        Route::post('/accept', [CallController::class, 'accept'])->name('accept');
        Route::post('/reject', [CallController::class, 'reject'])->name('reject');
        Route::post('/end', [CallController::class, 'end'])->name('end');
        Route::post('/signal', [CallController::class, 'signal'])->name('signal');
    });

    // Routes API pour les comptages non lus
    Route::prefix('api')->name('api.')->group(function () {
        Route::get('/unread/chats', function (Request $request) {
            $count = Auth::user() ? Auth::user()->unreadMessagesCount() : 0;
            Log::debug('API: unread/chats count: ' . $count);
            return response()->json(['count' => $count]);
        })->name('unread.chats');

        Route::get('/unread/status', function (Request $request) {
            $count = Auth::user() ? Auth::user()->unreadStatusUpdatesCount() : 0;
            Log::debug('API: unread/status count: ' . $count);
            return response()->json(['count' => $count]);
        })->name('unread.status');

        Route::get('/unread/calls', function (Request $request) {
            $count = Auth::user() ? Auth::user()->missedCallsCount() : 0;
            Log::debug('API: unread/calls count: ' . $count);
            return response()->json(['count' => $count]);
        })->name('unread.calls');
    });

    // Admin - accès restreint
    Route::prefix('admin')->name('admin.')->middleware('role:super_admin|admin')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
        Route::resource('users', AdminUserController::class);
        Route::get('/jobs', [AdminController::class, 'jobsIndex'])->name('jobs.index');
        Route::get('/jobs/{job}', [AdminController::class, 'jobsShow'])->name('jobs.show');
        Route::put('/jobs/{job}/status', [AdminController::class, 'jobsUpdateStatus'])->name('jobs.updateStatus');
        Route::delete('/jobs/{job}', [AdminController::class, 'jobsDestroy'])->name('jobs.destroy');
        Route::resource('categories', AdminController::class)->except(['show']);
        Route::resource('roles', AdminController::class)->except(['show']);
        Route::resource('permissions', AdminController::class)->except(['show']);
        Route::get('/listings', [AdminController::class, 'listingsIndex'])->name('listings.index');
        Route::get('/listings/create', [AdminController::class, 'listingsCreate'])->name('listings.create');
        Route::post('/listings', [AdminController::class, 'listingsStore'])->name('listings.store');
        Route::get('/listings/{listing}', [AdminController::class, 'listingsShow'])->name('listings.show');
        Route::get('/listings/{listing}/edit', [AdminController::class, 'listingsEdit'])->name('listings.edit');
        Route::put('/listings/{listing}', [AdminController::class, 'listingsUpdate'])->name('listings.update');
        Route::delete('/listings/{listing}', [AdminController::class, 'listingsDestroy'])->name('listings.destroy');
        Route::put('/listings/{listing}/status', [AdminController::class, 'listingsUpdateStatus'])->name('listings.updateStatus');
        Route::prefix('messages')->name('messages.')->group(function () {
            Route::get('/', [AdminController::class, 'messagesIndex'])->name('index');
            Route::get('{conversation}', [AdminController::class, 'messagesShow'])->name('show');
            Route::put('{conversation}/status', [AdminController::class, 'messagesUpdateStatus'])->name('updateStatus');
            Route::delete('{conversation}', [AdminController::class, 'messagesDestroy'])->name('destroy');
        });
    });

    Route::get('/redirection-test', fn () =>
        "<h1>Redirection de test : Si vous voyez ceci, la redirection fonctionne bien.</h1>"
    )->name('redirection-test');
});
