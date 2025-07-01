<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

use App\Http\Controllers\HomeController;
use App\Http\Controllers\JobListingController; // Make sure this is imported
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\GoogleRegistrationController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Candidate\CandidateDashboardController;
use App\Http\Controllers\Employer\EmployerDashboardController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\CameraController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\StatusController;
use App\Http\Controllers\CallController;


Auth::routes(['verify' => true]);

Route::get('auth/google', [LoginController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('auth/google/callback', [LoginController::class, 'handleGoogleCallback']);

Route::get('/register/google/complete', [GoogleRegistrationController::class, 'showGoogleRegistrationForm'])->name('register.google.complete');
Route::post('/register/google/complete', [GoogleRegistrationController::class, 'completeGoogleRegistration'])->name('register.google.complete.post');
Route::get('/get-cities-by-province-google', [GoogleRegistrationController::class, 'getCitiesByProvince'])->name('get.cities.by.province.google');
Route::get('/get-cities', [RegisterController::class, 'getCitiesByProvince'])->name('get.cities.by.province');

Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/', function () {
        $user = Auth::user();

        if ($user->hasAnyRole(['super_admin', 'admin'])) {
            return redirect()->route('admin.dashboard');
        } elseif ($user->hasRole('employer')) {
            return redirect()->route('listings.index');
        } elseif ($user->hasRole('candidate')) {
            return redirect()->route('listings.index');
        }

        return view('dashboard.generic');
    })->name('home');

    // **GENERAL LISTINGS ROUTES (FOR ALL AUTHENTICATED USERS)**
    // These routes use JobListingController
    Route::get('/listings', [JobListingController::class, 'index'])->name('listings.index');
    Route::get('/listings/create', [JobListingController::class, 'create'])->name('listings.create');
    Route::post('/listings', [JobListingController::class, 'store'])->name('listings.store');
    Route::get('/listings/{listing}', [JobListingController::class, 'show'])->name('listings.show');
    // ADDED: Edit and Update routes for JobListingController
    Route::get('/listings/{listing}/edit', [JobListingController::class, 'edit'])->name('listings.edit');
    Route::put('/listings/{listing}', [JobListingController::class, 'update'])->name('listings.update');
    // ADDED: Destroy route for JobListingController (if not already handled by general listings logic)
    // Based on your blade, you have a delete button linked to listings.destroy.
    // If JobListingController handles the deletion for non-admin users, add this:
    Route::delete('/listings/{listing}', [JobListingController::class, 'destroy'])->name('listings.destroy');


    Route::get('/contact', [ContactController::class, 'showContactForm'])->name('contact.form');
    Route::post('/contact', [ContactController::class, 'submitContactForm'])->name('contact.submit');

    Route::get('/profile', [HomeController::class, 'profile'])->name('profile.show');
    Route::get('/profile/edit', [HomeController::class, 'editProfile'])->name('profile.edit');
    Route::put('/profile', [HomeController::class, 'updateProfile'])->name('profile.update');

    Route::prefix('candidate')->name('candidate.')->middleware('role:candidate')->group(function () {
        // If the 'candidate.listings' resource is distinct from the general 'listings' (i.e., different actions/views),
        // then keep this. Otherwise, if it's just a filtered view, you might not need a full resource here.
        // For now, assuming you still need it here for candidate-specific listing management if any.
        Route::resource('listings', JobListingController::class); // Still keep this if Candidate uses JobListingController for resource actions

        Route::get('/applications', [ApplicationController::class, 'index'])->name('applications.index');
        Route::get('/groups', [GroupController::class, 'index'])->name('groups.index');
        Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
        Route::get('/payment', [PaymentController::class, 'index'])->name('payment.index');
        Route::get('/camera', [CameraController::class, 'index'])->name('camera.index');
        Route::get('/status', [StatusController::class, 'index'])->name('status.index');
        Route::get('/calls', [CallController::class, 'index'])->name('calls.index');
    });

    Route::prefix('employer')->name('employer.')->middleware('role:employer')->group(function () {
        Route::resource('listings', EmployerDashboardController::class);
    });

    // **ADMIN ROUTES**
    // These routes use AdminController and have the 'admin.' prefix.
    Route::prefix('admin')->name('admin.')->middleware('role:super_admin|admin')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');

        Route::resource('users', UserController::class);

        Route::get('/jobs', [AdminController::class, 'jobsIndex'])->name('jobs.index');
        Route::get('/jobs/{job}', [AdminController::class, 'jobsShow'])->name('jobs.show');
        Route::put('/jobs/{job}/status', [AdminController::class, 'jobsUpdateStatus'])->name('jobs.updateStatus');
        Route::delete('/jobs/{job}', [AdminController::class, 'jobsDestroy'])->name('jobs.destroy');

        Route::resource('categories', AdminController::class)->except(['show']);
        Route::resource('roles', AdminController::class)->except(['show']);
        Route::resource('permissions', AdminController::class)->except(['show']);

        // Admin-specific listings routes (using AdminController)
        Route::get('/listings', [AdminController::class, 'listingsIndex'])->name('listings.index');
        Route::get('/listings/create', [AdminController::class, 'listingsCreate'])->name('listings.create');
        Route::post('/listings', [AdminController::class, 'listingsStore'])->name('listings.store');
        Route::get('/listings/{listing}', [AdminController::class, 'listingsShow'])->name('listings.show');
        Route::get('/listings/{listing}/edit', [AdminController::class, 'listingsEdit'])->name('listings.edit'); // This is admin.listings.edit
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

    Route::get('/applications', [ApplicationController::class, 'index'])->name('applications.index');
    Route::get('/groups', [GroupController::class, 'index'])->name('groups.index');
    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::get('/payment', [PaymentController::class, 'index'])->name('payment.index');
    Route::get('/camera', [CameraController::class, 'index'])->name('camera.index');
    Route::get('/status', [StatusController::class, 'index'])->name('status.index');
    Route::get('/calls', [CallController::class, 'index'])->name('calls.index');

    Route::prefix('chats')->name('chats.')->group(function () {
        Route::get('/', [ChatController::class, 'index'])->name('index');
        Route::get('/search-users', [ChatController::class, 'searchUsers'])->name('searchUsers');
        Route::post('/create', [ChatController::class, 'createConversation'])->name('createConversation');
        Route::get('/{conversation}', [ChatController::class, 'show'])->name('show');
        Route::post('/{conversation}/messages', [ChatController::class, 'store'])->name('messages.store');
    });

    Route::get('/redirection-test', fn () =>
        "<h1>Redirection de test : Si vous voyez ceci, la redirection fonctionne bien.</h1>"
    )->name('redirection-test');
});
