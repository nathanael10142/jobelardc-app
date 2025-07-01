<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule; // Added this line, as it's often used for unique validation rules
use Illuminate\Support\Str;    // <--- THIS IS THE CRUCIAL LINE TO ADD

class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Redirige vers le tableau de bord selon le rôle.
     */
    public function index()
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        if ($user->hasAnyRole(['super_admin', 'admin'])) {
            return redirect()->route('admin.dashboard');
        }

        if ($user->hasRole('candidate')) {
            return redirect()->route('candidate.dashboard');
        }

        if ($user->hasRole('employer')) {
            return redirect()->route('employer.dashboard');
        }

        return view('home');
    }

    /**
     * Affiche le tableau de bord admin.
     */
    public function adminDashboard()
    {
        return view('admin.dashboard');
    }

    /**
     * Affiche la page de profil utilisateur.
     */
    public function profile()
    {
        return view('profile.show', ['user' => Auth::user()]);
    }

    /**
     * Affiche le formulaire d'édition du profil utilisateur.
     */
    public function editProfile()
    {
        $user = Auth::user();
        return view('profile.edit', compact('user'));
    }

    /**
     * Met à jour le profil utilisateur.
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            // Added Rule import if 'phone_number' needs a unique check ignoring current user
            'phone_number' => ['nullable', 'string', 'max:20', Rule::unique('users')->ignore($user->id)],
            'location' => 'nullable|string|max:255',
            'bio' => 'nullable|string|max:1000',
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $user->name = $validated['name'];
        $user->phone_number = $validated['phone_number'] ?? null;
        $user->location = $validated['location'] ?? null;
        $user->bio = $validated['bio'] ?? null;

        if ($request->hasFile('profile_picture')) {
            // Supprime l’ancienne photo si elle est locale (non externe)
            // Using Str::startsWith() now, which accepts an array for prefixes
            if ($user->profile_picture && !Str::startsWith($user->profile_picture, ['http://', 'https://'])) {
                Storage::disk('public')->delete($user->profile_picture);
            }

            $path = $request->file('profile_picture')->store('avatars', 'public');
            $user->profile_picture = $path;
        }

        $user->save();

        return redirect()->route('profile.show')->with('success', 'Profil mis à jour avec succès.');
    }
}
