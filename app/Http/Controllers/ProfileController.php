<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule; // N'oubliez pas d'importer Rule si vous l'utilisez pour les règles de validation uniques

class ProfileController extends Controller
{
    /**
     * Display the user's profile.
     */
    public function index()
    {
        // Récupère l'utilisateur authentifié
        $user = Auth::user();

        // Retourne la vue du profil avec les données de l'utilisateur
        return view('profile.index', compact('user'));
    }

    /**
     * Show the form for editing the user's profile.
     */
    public function edit()
    {
        // Récupère l'utilisateur authentifié
        $user = Auth::user();

        // Retourne la vue d'édition du profil
        return view('profile.edit', compact('user'));
    }

    /**
     * Update the user's profile in storage.
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
            // Supprimer l'ancienne image si elle existe
            if ($user->profile_picture && !Str::startsWith($user->profile_picture, ['http://', 'https://'])) {
                \Storage::disk('public')->delete($user->profile_picture);
            }
            // Stocker la nouvelle image
            $path = $request->file('profile_picture')->store('profile_pictures', 'public');
            $user->profile_picture = $path;
        }

        $user->save();

        return redirect()->route('profile.index')->with('success', 'Votre profil a été mis à jour avec succès !');
    }
}
