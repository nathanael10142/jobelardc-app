<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    // Supprimez la propriété $redirectTo si elle est vide ou non utilisée,
    // car la méthode redirectTo() la remplacera.
    // protected $redirectTo; // Vous pouvez commenter ou supprimer cette ligne

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    /**
     * Get the post-login redirect path.
     * This method ensures users are redirected to their appropriate dashboard after login.
     *
     * @return string
     */
    protected function redirectTo()
    {
        $user = auth()->user();

        if ($user->hasRole('super_admin') || $user->hasRole('admin')) {
            return route('admin.dashboard'); // Redirection pour les admins
        }

        if ($user->hasAnyRole(['employer', 'candidate'])) {
            // Redirection vers la page liste des annonces pour employeurs/candidats
            return route('listings.index');
        }

        // MODIFICATION ICI : Redirection par défaut vers la route nommée 'home' (qui est '/')
        return route('home'); // Ceci générera l'URL correcte pour la route nommée 'home' (qui est '/')
    }

    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Exception $e) {
            return redirect('/login')->withErrors('Google authentication failed: ' . $e->getMessage());
        }

        $authUser = User::where('google_id', $googleUser->id)->first();

        if ($authUser) {
            Auth::login($authUser, true);
            // Utilisez la méthode redirectTo() du contrôleur pour la cohérence
            return redirect($this->redirectTo());
        }

        $existingUser = User::where('email', $googleUser->getEmail())->first();
        if ($existingUser) {
            return redirect('/login')->withErrors('Un compte avec cette adresse e-mail existe déjà. Veuillez vous connecter normalement ou lier votre compte Google depuis votre profil.');
        }

        session()->put('google_user_data', [
            'name'      => $googleUser->getName(),
            'email'     => $googleUser->getEmail(),
            'google_id' => $googleUser->getId(),
            'avatar'    => $googleUser->getAvatar(),
        ]);

        return redirect()->route('register.google.complete');
    }
}
