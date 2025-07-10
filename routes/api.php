<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

use App\Http\Controllers\CallController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\StatusController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Ici, vous pouvez enregistrer les routes API pour votre application. Ces
| routes sont chargées par le RouteServiceProvider et toutes seront
| assignées au groupe de middleware "api". Faites quelque chose de génial !
|
*/

// Routes protégées par authentification Sanctum
Route::middleware('auth:sanctum')->group(function () {

    // Récupérer l'utilisateur authentifié
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Route pour la recherche de contacts (utilisée par la modale d'appel)
    // Cette route est déplacée HORS du groupe de préfixe 'calls' pour avoir une URL propre comme '/api/users/search'
    Route::get('/users/search', [UserController::class, 'searchApi'])->name('api.users.search');


    // Gestion des appels
    Route::prefix('calls')->group(function () {
        // Route pour récupérer l'historique des appels
        Route::get('/history', [CallController::class, 'indexApi'])->name('api.calls.history');

        // Initier un nouvel appel
        Route::post('/initiate', [CallController::class, 'initiate'])->name('api.calls.initiate');

        // Utilisation de {call_uuid} comme paramètre de route pour cibler un appel spécifique
        Route::post('/{call_uuid}/accept', [CallController::class, 'accept'])->name('api.calls.accept');
        Route::post('/{call_uuid}/reject', [CallController::class, 'reject'])->name('api.calls.reject');
        Route::post('/{call_uuid}/end', [CallController::class, 'end'])->name('api.calls.end');

        // La route de signalisation utilise {call_uuid}
        Route::post('/{call_uuid}/signal', [CallController::class, 'signal'])->name('api.calls.signal');
    });


    // Comptages non lus (messages, statuts, appels)
    // Ces routes appellent directement les méthodes sur l'objet Auth::user()
    Route::get('/unread/chats', function (Request $request) {
        $count = 0;
        if (Auth::check()) {
            $count = Auth::user()->unreadMessagesCount();
        }
        Log::debug('API: /unread/chats - Count: ' . $count . ' for user ' . (Auth::check() ? Auth::id() : 'Guest'));
        return response()->json(['count' => $count]);
    })->name('api.unread.chats');

    Route::get('/unread/status', function (Request $request) {
        $count = 0;
        if (Auth::check()) {
            $count = Auth::user()->unreadStatusUpdatesCount();
        }
        Log::debug('API: /unread/status - Count: ' . $count . ' for user ' . (Auth::check() ? Auth::id() : 'Guest'));
        return response()->json(['count' => $count]);
    })->name('api.unread.status');

    Route::get('/unread/calls', function (Request $request) {
        $count = 0;
        if (Auth::check()) {
            $count = Auth::user()->missedCallsCount();
        }
        Log::debug('API: /unread/calls - Count: ' . $count . ' for user ' . (Auth::check() ? Auth::id() : 'Guest'));
        return response()->json(['count' => $count]);
    })->name('api.unread.calls');

});