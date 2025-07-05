<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CallController;
use App\Http\Controllers\UserController; // Importez le UserController

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Routes pour la gestion des appels
Route::prefix('calls')->middleware('auth:sanctum')->group(function () {
    Route::post('/initiate', [CallController::class, 'initiate']);
    Route::post('/accept', [CallController::class, 'accept']);
    Route::post('/reject', [CallController::class, 'reject']);
    Route::post('/end', [CallController::class, 'end']);
    // NOUVELLE ROUTE POUR LA SIGNALISATION WEBRTC
    Route::post('/signal', [CallController::class, 'signal']); // Ajout de la route signal
});

// NOUVELLE ROUTE API POUR RÉCUPÉRER TOUS LES UTILISATEURS (sauf l'utilisateur actuel)
// J'ai ajouté un nom pour cette route pour faciliter son utilisation dans le frontend.
Route::middleware('auth:sanctum')->get('/users', [UserController::class, 'indexApi'])->name('api.users.index');

