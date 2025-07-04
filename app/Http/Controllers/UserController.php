<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash; // Pour la création/mise à jour d'utilisateurs si nécessaire
use Spatie\Permission\Models\Role; // Pour la gestion des rôles

class UserController extends Controller
{
    public function __construct()
    {
        // Applique le middleware 'auth' à toutes les méthodes du contrôleur
        $this->middleware('auth');
        // Applique le middleware de permission pour l'accès au panneau d'administration
        $this->middleware('permission:access admin panel')->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
    }

    /**
     * Affiche une liste de tous les utilisateurs (pour l'administration).
     */
    public function index()
    {
        $users = User::all();
        return view('admin.users.index', compact('users'));
    }

    /**
     * Affiche le formulaire de création d'un nouvel utilisateur.
     */
    public function create()
    {
        $roles = Role::all(); // Récupère tous les rôles pour le formulaire
        return view('admin.users.create', compact('roles'));
    }

    /**
     * Stocke un nouvel utilisateur dans la base de données.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone_number' => 'nullable|string|max:20',
            'bio' => 'nullable|string|max:1000',
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'location' => 'nullable|string|max:255',
            'user_type' => 'required|string|in:prestataire,demandeur,both,employer,candidate',
            'roles' => 'array', // Assurez-vous que c'est un tableau
            'roles.*' => 'exists:roles,name', // Chaque rôle doit exister
        ]);

        $profilePicturePath = null;
        if ($request->hasFile('profile_picture')) {
            $profilePicturePath = $request->file('profile_picture')->store('profile_pictures', 'public');
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone_number' => $request->phone_number,
            'bio' => $request->bio,
            'profile_picture' => $profilePicturePath,
            'location' => $request->location,
            'user_type' => $request->user_type,
        ]);

        if ($request->has('roles')) {
            $user->syncRoles($request->roles);
        } else {
            // Assigner un rôle par défaut si aucun n'est spécifié
            $user->assignRole('user');
        }

        return redirect()->route('admin.users.index')->with('success', 'Utilisateur créé avec succès.');
    }

    /**
     * Affiche les détails d'un utilisateur spécifique.
     */
    public function show(User $user)
    {
        return view('admin.users.show', compact('user'));
    }

    /**
     * Affiche le formulaire d'édition d'un utilisateur.
     */
    public function edit(User $user)
    {
        $roles = Role::all();
        return view('admin.users.edit', compact('user', 'roles'));
    }

    /**
     * Met à jour un utilisateur dans la base de données.
     */
    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'phone_number' => 'nullable|string|max:20',
            'bio' => 'nullable|string|max:1000',
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'location' => 'nullable|string|max:255',
            'user_type' => 'required|string|in:prestataire,demandeur,both,employer,candidate',
            'password' => 'nullable|string|min:8|confirmed',
            'roles' => 'array',
            'roles.*' => 'exists:roles,name',
        ]);

        $userData = $request->only(['name', 'email', 'phone_number', 'bio', 'location', 'user_type']);

        if ($request->hasFile('profile_picture')) {
            // Supprimer l'ancienne image si elle existe
            if ($user->profile_picture) {
                // Storage::disk('public')->delete($user->profile_picture); // Décommenter si vous gérez le stockage
            }
            $userData['profile_picture'] = $request->file('profile_picture')->store('profile_pictures', 'public');
        }

        if ($request->filled('password')) {
            $userData['password'] = Hash::make($request->password);
        }

        $user->update($userData);

        if ($request->has('roles')) {
            $user->syncRoles($request->roles);
        } else {
            $user->syncRoles([]); // Supprime tous les rôles si aucun n'est sélectionné
        }

        return redirect()->route('admin.users.index')->with('success', 'Utilisateur mis à jour avec succès.');
    }

    /**
     * Supprime un utilisateur de la base de données.
     */
    public function destroy(User $user)
    {
        // Empêcher la suppression de l'utilisateur actuellement authentifié
        if ($user->id === Auth::id()) {
            return redirect()->back()->with('error', 'Vous ne pouvez pas supprimer votre propre compte.');
        }

        // Supprimer l'image de profil si elle existe
        if ($user->profile_picture) {
            // Storage::disk('public')->delete($user->profile_picture); // Décommenter si vous gérez le stockage
        }

        $user->delete();
        return redirect()->route('admin.users.index')->with('success', 'Utilisateur supprimé avec succès.');
    }

    /**
     * API: Retourne une liste de tous les utilisateurs (sauf l'utilisateur authentifié).
     * Inclut les informations nécessaires pour l'affichage dans l'application d'appel.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function indexApi(Request $request)
    {
        $currentUserId = Auth::id();

        // Récupère tous les utilisateurs sauf l'utilisateur actuel
        $users = User::where('id', '!=', $currentUserId)
            ->orderBy('name')
            ->get(['id', 'name', 'profile_picture', 'email']); // Sélectionnez uniquement les champs nécessaires

        // Ajoute les initiales et une couleur de fond pour l'avatar si pas d'image de profil
        $users->each(function ($user) {
            $initials = '';
            if ($user->name) {
                $words = explode(' ', $user->name);
                foreach ($words as $word) {
                    $initials .= strtoupper(substr($word, 0, 1));
                }
                $user->initials = substr($initials, 0, 2); // Limiter à deux initiales
            } else {
                $user->initials = '??';
            }

            // Générer une couleur cohérente basée sur l'email ou l'ID de l'utilisateur
            // pour les avatars d'initiales
            $hash = md5($user->email ?? $user->id);
            $user->avatar_bg_color = '#' . substr($hash, 0, 6);
        });

        return response()->json(['users' => $users]);
    }
}
