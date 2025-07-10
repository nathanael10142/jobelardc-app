<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash; // For creating/updating users if necessary
use Spatie\Permission\Models\Role; // For role management
use Illuminate\Support\Facades\Log; // Added for logging in API methods
use Symfony\Component\HttpFoundation\Response; // For HTTP status codes

class UserController extends Controller
{
    public function __construct()
    {
        // Apply 'auth' middleware for web routes (session-based authentication)
        $this->middleware('auth')->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);
        // Apply 'auth:sanctum' middleware for API routes
        // Ensure 'searchApi' and 'indexApi' are protected by sanctum
        $this->middleware('auth:sanctum')->only(['indexApi', 'searchApi']);
        // Apply permission middleware for admin panel access
        $this->middleware('permission:access admin panel')->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
    }

    /**
     * Display a listing of all users (for administration).
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $users = User::all();
        return view('admin.users.index', compact('users'));
    }

    /**
     * Show the form for creating a new user.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $roles = Role::all(); // Retrieve all roles for the form
        return view('admin.users.create', compact('roles'));
    }

    /**
     * Store a newly created user in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
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
            'roles' => 'array', // Ensure it's an array
            'roles.*' => 'exists:roles,name', // Each role must exist
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
            // Assign a default role if none is specified
            $user->assignRole('user');
        }

        return redirect()->route('admin.users.index')->with('success', 'Utilisateur créé avec succès.');
    }

    /**
     * Display the specified user.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\View\View
     */
    public function show(User $user)
    {
        return view('admin.users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified user.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\View\View
     */
    public function edit(User $user)
    {
        $roles = Role::all();
        return view('admin.users.edit', compact('user', 'roles'));
    }

    /**
     * Update the specified user in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\RedirectResponse
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
            // Delete old image if it exists
            // if ($user->profile_picture) {
            //     Storage::disk('public')->delete($user->profile_picture); // Uncomment if you manage storage
            // }
            $userData['profile_picture'] = $request->file('profile_picture')->store('profile_pictures', 'public');
        }

        if ($request->filled('password')) {
            $userData['password'] = Hash::make($request->password);
        }

        $user->update($userData);

        if ($request->has('roles')) {
            $user->syncRoles($request->roles);
        } else {
            $user->syncRoles([]); // Remove all roles if none selected
        }

        return redirect()->route('admin.users.index')->with('success', 'Utilisateur mis à jour avec succès.');
    }

    /**
     * Remove the specified user from storage.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(User $user)
    {
        // Prevent deletion of the currently authenticated user
        if ($user->id === Auth::id()) {
            return redirect()->back()->with('error', 'Vous ne pouvez pas supprimer votre propre compte.');
        }

        // Delete profile picture if it exists
        // if ($user->profile_picture) {
        //     Storage::disk('public')->delete($user->profile_picture); // Uncomment if you manage storage
        // }

        $user->delete();
        return redirect()->route('admin.users.index')->with('success', 'Utilisateur supprimé avec succès.');
    }

    /**
     * API: Returns a list of all users (excluding the authenticated user).
     * Includes necessary information for display in the calling application.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function indexApi(Request $request)
    {
        $currentUserId = Auth::id();

        // Retrieve all users except the current user
        $users = User::where('id', '!=', $currentUserId)
            ->orderBy('name')
            ->get(['id', 'name', 'profile_picture', 'email', 'user_type']); // Include user_type

        // Add initials and a background color for the avatar if no profile picture
        $users->each(function ($user) {
            $initials = '';
            if ($user->name) {
                $words = explode(' ', $user->name);
                foreach ($words as $word) {
                    $initials .= strtoupper(substr($word, 0, 1));
                }
                $user->initials = substr($initials, 0, 2); // Limit to two initials
            } else {
                $user->initials = '??';
            }

            // Generate a consistent color based on user's email or ID for initial avatars
            $hash = md5($user->email ?? $user->id);
            $user->avatar_bg_color = '#' . substr($hash, 0, 6);
        });

        return response()->json($users); // Return the collection directly, not wrapped in 'users'
    }

    /**
     * API: Searches for users (contacts) for call initiation.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchApi(Request $request)
    {
        $search = $request->input('query');
        $currentUserId = Auth::id();

        Log::info("API User Search: Query '{$search}' by user {$currentUserId}");

        if (empty($search) || strlen($search) < 2) { // Minimum 2 characters for search
            return response()->json([], Response::HTTP_OK);
        }

        try {
            $contacts = User::where('id', '!=', $currentUserId)
                            ->where(function($query) use ($search) {
                                $query->where('name', 'like', '%' . $search . '%')
                                      ->orWhere('email', 'like', '%' . $search . '%');
                            })
                            ->limit(10) // Limit results for performance
                            ->get(['id', 'name', 'profile_picture', 'email', 'user_type']); // Select only necessary fields

            return response()->json($contacts, Response::HTTP_OK);

        } catch (\Exception $e) {
            Log::error("Error in UserController@searchApi: " . $e->getMessage(), ['exception' => $e, 'search_query' => $search, 'user_id' => $currentUserId]);
            return response()->json(['message' => 'Erreur lors de la recherche des utilisateurs.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
