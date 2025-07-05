<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Illuminate\Session\TokenMismatchException; // Peut être utile pour gérer le 419 spécifiquement

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $exception
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Throwable
     */
    public function render($request, Throwable $exception)
    {
        // Pour gérer spécifiquement le 419 (TokenMismatchException)
        if ($exception instanceof TokenMismatchException) {
            // Option 1: Rediriger vers la page de login avec un message d'erreur
            // Ceci est une bonne pratique pour les requêtes non-API
            if (!$request->expectsJson()) {
                return redirect()->route('login')->withErrors(['session_expired' => 'Votre session a expiré ou le jeton de sécurité est invalide. Veuillez vous reconnecter.']);
            }
            // Pour les requêtes API (ou JSON), renvoyer une réponse JSON
            return response()->json(['message' => 'Token Mismatch: Session expired or invalid security token.'], 419);
        }

        // Si APP_DEBUG est true, affiche la page d'erreur détaillée
        if (config('app.debug')) {
            return parent::render($request, $exception);
        }

        // Pour toutes les autres exceptions en production, utiliser la logique par défaut de Laravel
        return parent::render($request, $exception);
    }
}