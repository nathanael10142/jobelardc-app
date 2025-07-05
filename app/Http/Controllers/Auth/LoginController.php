<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session; // <-- AJOUTEZ CETTE LIGNE

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
        Log::info('Google OAuth: Redirecting to Google.');
        // Vérifiez l'état initial de la session avant redirection
        Log::info('Google OAuth: Session ID before redirect: ' . Session::getId()); //
        Log::info('Google OAuth: Session data before redirect:', Session::all()); //
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback()
    {
        Log::info('Google OAuth: Handling Google callback.'); //
        Log::info('Google OAuth: Current Session ID on callback: ' . Session::getId()); //
        Log::info('Google OAuth: Session data on callback:', Session::all()); //

        try {
            // Cette ligne est très critique. Si le state ne correspond pas, elle lancera une exception.
            $googleUser = Socialite::driver('google')->user();
            Log::info('Google OAuth: Google user retrieved successfully.', ['email' => $googleUser->getEmail(), 'google_id' => $googleUser->getId()]); //
        } catch (\Exception $e) {
            Log::error('Google OAuth: Failed to retrieve Google user or state mismatch: ' . $e->getMessage(), ['exception' => $e]); //
            // Le 419 peut survenir ici si la session est perdue et que Socialite ne peut pas valider le 'state'.
            return redirect('/login')->withErrors('Google authentication failed: ' . $e->getMessage());
        }

        $authUser = User::where('google_id', $googleUser->id)->first();

        if ($authUser) {
            Log::info('Google OAuth: User found by google_id. Attempting login.', ['user_id' => $authUser->id]); //
            Auth::login($authUser, true); // True pour "remember me"
            Log::info('Google OAuth: User after Auth::login check: ' . (Auth::check() ? 'LoggedIn' : 'NotLoggedIn')); //

            if (Auth::check()) {
                Log::info('Google OAuth: User successfully logged in with existing Google ID. Redirecting to: ' . $this->redirectTo()); //
                return redirect($this->redirectTo());
            } else {
                Log::error('Google OAuth: Auth::login failed to persist for existing Google ID user: ' . $authUser->id . '. Redirecting to login.'); //
                // Ceci peut aussi être une cause du 419 ou d'une redirection vers login
                return redirect('/login')->withErrors('La connexion a échoué après l\'authentification Google (session non persistante).');
            }
        }

        $existingUser = User::where('email', $googleUser->getEmail())->first();
        if ($existingUser) {
            Log::warning('Google OAuth: Account with this email already exists: ' . $googleUser->getEmail() . '. Redirecting to login with error.'); //
            return redirect('/login')->withErrors('Un compte avec cette adresse e-mail existe déjà. Veuillez vous connecter normalement ou lier votre compte Google depuis votre profil.');
        }

        Log::info('Google OAuth: New Google user. Storing data in session for registration completion.'); //
        session()->put('google_user_data', [
            'name'      => $googleUser->getName(),
            'email'     => $googleUser->getEmail(),
            'google_id' => $googleUser->getId(),
            'avatar'    => $googleUser->getAvatar(),
        ]);

        if (!session()->has('google_user_data')) {
            Log::error('Google OAuth: Failed to store Google user data in session. Redirecting to login.'); //
            return redirect('/login')->withErrors('Erreur lors de la préparation de l\'inscription. Veuillez réessayer.');
        }

        Log::info('Google OAuth: Redirecting to Google registration completion form.'); //
        return redirect()->route('register.google.complete');
    }
}