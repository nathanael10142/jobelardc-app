<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Log; // <-- AJOUTEZ CETTE LIGNE

class LoginController extends Controller
{
    use AuthenticatesUsers;


    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    protected function redirectTo()
    {
        $user = auth()->user();

        if ($user->hasRole('super_admin') || $user->hasRole('admin')) {
            return route('admin.dashboard');
        }

        if ($user->hasAnyRole(['employer', 'candidate'])) {
            return route('listings.index');
        }

        return route('home');
    }

    public function redirectToGoogle()
    {
        Log::info('Redirecting to Google for authentication.'); // <-- AJOUT DE LOG
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback()
    {
        Log::info('Handling Google callback.'); // <-- AJOUT DE LOG
        try {
            $googleUser = Socialite::driver('google')->user();
            Log::info('Google user retrieved successfully.', ['email' => $googleUser->getEmail(), 'google_id' => $googleUser->getId()]); // <-- AJOUT DE LOG
        } catch (\Exception $e) {
            Log::error('Google authentication failed during user retrieval: ' . $e->getMessage(), ['exception' => $e]); // <-- AJOUT DE LOG PLUS DÉTAILLÉ
            return redirect('/login')->withErrors('Google authentication failed: ' . $e->getMessage());
        }

        $authUser = User::where('google_id', $googleUser->id)->first();

        if ($authUser) {
            Log::info('User found by google_id. Attempting login.', ['user_id' => $authUser->id]); // <-- AJOUT DE LOG
            Auth::login($authUser, true);
            // Vérification après login pour s'assurer que l'utilisateur est bien logué
            if (Auth::check()) {
                Log::info('User successfully logged in with existing Google ID. Redirecting to: ' . $this->redirectTo()); // <-- AJOUT DE LOG
                return redirect($this->redirectTo());
            } else {
                Log::error('Auth::login failed to persist for existing Google ID user: ' . $authUser->id); // <-- AJOUT DE LOG CRITIQUE
                return redirect('/login')->withErrors('La connexion a échoué après l\'authentification Google (session non persistante).');
            }
        }

        $existingUser = User::where('email', $googleUser->getEmail())->first();
        if ($existingUser) {
            Log::warning('Account with this email already exists: ' . $googleUser->getEmail()); // <-- AJOUT DE LOG
            return redirect('/login')->withErrors('Un compte avec cette adresse e-mail existe déjà. Veuillez vous connecter normalement ou lier votre compte Google depuis votre profil.');
        }

        // Si l'utilisateur est nouveau
        Log::info('New Google user. Storing data in session for registration completion.'); // <-- AJOUT DE LOG
        session()->put('google_user_data', [
            'name'      => $googleUser->getName(),
            'email'     => $googleUser->getEmail(),
            'google_id' => $googleUser->getId(),
            'avatar'    => $googleUser->getAvatar(),
        ]);

        // Vérification si les données ont bien été mises en session
        if (!session()->has('google_user_data')) {
            Log::error('Failed to store Google user data in session.'); // <-- AJOUT DE LOG CRITIQUE
            return redirect('/login')->withErrors('Erreur lors de la préparation de l\'inscription. Veuillez réessayer.');
        }

        Log::info('Redirecting to Google registration completion form.'); // <-- AJOUT DE LOG
        return redirect()->route('register.google.complete');
    }
}