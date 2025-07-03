<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Auth\RegisterController; // Assurez-vous que ce chemin est correct si RegisterController n'est pas dans le même namespace
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Log; // Ajouté pour la cohérence avec RegisterController

class GoogleRegistrationController extends Controller
{
    /**
     * Affiche le formulaire pour compléter l'inscription Google.
     */
    public function showGoogleRegistrationForm(Request $request)
    {
        $googleUserData = session('google_user_data');

        if (
            !$googleUserData ||
            !isset($googleUserData['name'], $googleUserData['email'], $googleUserData['google_id'])
        ) {
            return redirect()->route('login')->with('error', 'Les données Google sont manquantes ou incomplètes. Veuillez vous reconnecter.');
        }

        $provincesVilles = RegisterController::getProvincesAndCitiesData();
        $provinces = array_keys($provincesVilles);

        return view('auth.google-register-complete', compact('googleUserData', 'provinces'));
    }

    /**
     * Finalise l'inscription après Google OAuth.
     */
    public function completeGoogleRegistration(Request $request)
    {
        $googleUserData = session('google_user_data');

        if (
            !$googleUserData ||
            !isset($googleUserData['name'], $googleUserData['email'], $googleUserData['google_id'])
        ) {
            return redirect()->route('login')->with('error', 'Session expirée ou données manquantes. Veuillez vous reconnecter avec Google.');
        }

        $provincesVilles = RegisterController::getProvincesAndCitiesData();
        $validProvinces = array_keys($provincesVilles);

        $validator = Validator::make($request->all(), [
            'user_type'     => ['required', 'in:candidate,employer'],
            'phone_number'  => ['nullable', 'string', 'max:20', 'regex:/^(\+243|0)[8-9]\d{8}$/'],
            'province'      => ['required', 'string', 'in:' . implode(',', $validProvinces)],
            'city'          => [
                'required', 'string',
                function ($attribute, $value, $fail) use ($request, $provincesVilles) {
                    $province = $request->input('province');
                    if (!isset($provincesVilles[$province]) || !in_array($value, $provincesVilles[$province])) {
                        $fail("La ville sélectionnée n'est pas valide pour la province choisie.");
                    }
                }
            ],
            'bio'           => ['nullable', 'string', 'max:1000'],
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $user = User::create([
            'name'          => $googleUserData['name'],
            'email'         => $googleUserData['email'],
            'google_id'     => $googleUserData['google_id'],
            'password'      => Hash::make(uniqid()), // Mot de passe aléatoire car connexion via Google
            'phone_number'  => $request->input('phone_number'),
            'bio'           => $request->input('bio'),
            'profile_picture' => $googleUserData['avatar'] ?? null,
            'location'      => $request->input('city') . ', ' . $request->input('province'),
            'user_type'     => $request->input('user_type'),
        ]);

        $role = Role::where('name', $request->input('user_type'))->first();
        if ($role) {
            $user->assignRole($role);
        } else {
            // Utiliser Log::warning pour la cohérence
            Log::warning("Rôle introuvable pour {$user->email} : {$request->input('user_type')}");
        }

        event(new Registered($user));
        Auth::login($user, true);
        session()->forget('google_user_data');

        // --- SECTION DE REDIRECTION MISE À JOUR POUR ÊTRE IDENTIQUE AU REGISTERCONTROLLER ---
        if ($user->hasRole('super_admin') || $user->hasRole('admin')) {
            return redirect()->route('admin.dashboard');
        }

        if ($user->hasAnyRole(['employer', 'candidate'])) {
            // Redirection vers la page liste des annonces au lieu du dashboard candidat/employeur
            return redirect()->route('listings.index');
        }

        // MODIFICATION ICI : Utiliser route('home') au lieu de '/dashboard'
        return redirect()->route('home'); // Ceci redirigera vers la route nommée 'home' (qui est '/')
        // --- FIN DE LA SECTION DE REDIRECTION MISE À JOUR ---
    }

    /**
     * AJAX : Retourne les villes selon la province.
     */
    public function getCitiesByProvince(Request $request)
    {
        $province = $request->input('province');
        $provincesVilles = RegisterController::getProvincesAndCitiesData();
        $cities = $provincesVilles[$province] ?? [];

        return response()->json($cities);
    }
}
