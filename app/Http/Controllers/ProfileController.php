<?php

namespace App\Http\Controllers;

use App\Models\User; // N'oubliez pas d'importer le modèle User si vous l'utilisez pour la méthode show(User $user)
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str; // Pour Str::startsWith dans la gestion de l'image

class ProfileController extends Controller
{
    /**
     * Display the authenticated user's profile.
     * This method will now render the 'profile.show' view.
     * Accessible via /profile
     */
    public function index()
    {
        // Récupère l'utilisateur authentifié
        $user = Auth::user();

        // Retourne la vue du profil avec les données de l'utilisateur
        // CHANGEMENT ICI : Pointe maintenant vers 'profile.show'
        return view('profile.show', compact('user'));
    }

    /**
     * Display a specific user's profile.
     * This method is typically used if you have a route like /profile/{user}
     * to view other users' profiles.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\View\View
     */
    public function show(User $user)
    {
        // Retourne la vue du profil avec les données de l'utilisateur spécifié
        return view('profile.show', compact('user'));
    }

    /**
     * Show the form for editing the authenticated user's profile.
     * Accessible via /profile/edit
     */
    public function edit()
    {
        // Récupère l'utilisateur authentifié
        $user = Auth::user();

        // Retourne la vue d'édition du profil
        return view('profile.edit', compact('user'));
    }

    /**
     * Update the authenticated user's profile in storage.
     * Accessible via PUT /profile
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        // Règles de validation
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                // S'assure que l'e-mail est unique sauf pour l'utilisateur actuel
                Rule::unique('users')->ignore($user->id),
            ],
            'phone_number' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:255'],
            'province' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'profile_picture' => ['nullable', 'image', 'max:2048'], // Max 2MB
            'user_type' => ['required', 'string', Rule::in(['candidate', 'employer'])],
        ];

        // Valide la requête
        $validatedData = $request->validate($rules);

        // Mise à jour des champs de texte
        $user->name = $validatedData['name'];
        $user->email = $validatedData['email'];
        $user->phone_number = $validatedData['phone_number'];
        $user->address = $validatedData['address'];
        $user->province = $validatedData['province'];
        $user->city = $validatedData['city'];
        $user->user_type = $validatedData['user_type'];

        // Gestion de l'upload de l'image de profil
        if ($request->hasFile('profile_picture')) {
            // Supprimer l'ancienne image si elle existe et n'est pas une URL externe
            if ($user->profile_picture && !Str::startsWith($user->profile_picture, ['http://', 'https://'])) {
                \Storage::disk('public')->delete($user->profile_picture);
            }
            // Stocker la nouvelle image
            $path = $request->file('profile_picture')->store('profile_pictures', 'public');
            $user->profile_picture = $path;
        }

        $user->save();

        // Après la mise à jour, redirige vers la vue 'profile.show' (qui est maintenant gérée par index())
        return redirect()->route('profile.index')->with('success', 'Votre profil a été mis à jour avec succès !');
    }
}
