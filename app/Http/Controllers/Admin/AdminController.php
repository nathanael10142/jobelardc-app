<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

use App\Models\User;
use App\Models\Job;
use App\Models\Category;
use App\Models\JobListing;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:super_admin|admin']);
    }

    /**
     * Display the admin dashboard.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function dashboard(): \Illuminate\Contracts\View\View
    {
        $totalUsers = User::count();
        $totalJobs = Job::count();
        $activeJobs = Job::where('status', 'published')->count();
        $pendingJobs = Job::where('status', 'pending_approval')->count();

        $verifiedEmployers = User::where('user_type', 'employer')
                                 ->whereNotNull('email_verified_at')
                                 ->count();
        $totalCategories = Category::count();
        $totalJobListings = JobListing::count();

        $recentUsers = User::latest()->take(5)->get()->map(function($user) {
            return [
                'type' => 'user',
                'activity' => __('Nouvelle inscription'),
                'title' => $user->name,
                'link' => route('admin.users.show', $user->id),
                'date' => $user->created_at->diffForHumans(),
                'raw_date' => $user->created_at,
            ];
        });

        $recentJobListings = JobListing::latest()->take(5)->get()->map(function($jobListing) {
            return [
                'type' => 'listing',
                'activity' => __('Nouvelle annonce publiée'),
                'title' => $jobListing->title,
                'link' => route('admin.listings.show', $jobListing->id),
                'date' => $jobListing->created_at->diffForHumans(),
                'raw_date' => $jobListing->created_at,
            ];
        });

        $recentActivities = $recentUsers->merge($recentJobListings)->sortByDesc('raw_date')->take(10);

        return view('admin.dashboard', compact(
            'totalUsers',
            'totalJobs',
            'activeJobs',
            'pendingJobs',
            'verifiedEmployers',
            'totalCategories',
            'totalJobListings',
            'recentActivities'
        ));
    }

    //---

    ## User Management

    /**
     * Display a listing of the users.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function usersIndex(): \Illuminate\Contracts\View\View
    {
        $users = User::latest()->paginate(10);
        return view('admin.users.index', compact('users'));
    }

    /**
     * Show the form for creating a new user.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function usersCreate(): \Illuminate\Contracts\View\View
    {
        $roles = Role::all();
        return view('admin.users.create', compact('roles'));
    }

    /**
     * Store a newly created user in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function usersStore(Request $request): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone_number' => 'nullable|string|max:20|unique:users',
            'location' => 'nullable|string|max:255',
            'user_type' => 'required|string|in:candidate,employer',
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,name',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone_number' => $validated['phone_number'],
            'location' => $validated['location'],
            'user_type' => $validated['user_type'],
            'email_verified_at' => now(), // Assuming newly created users are verified
        ]);

        if (isset($validated['roles'])) {
            $user->syncRoles($validated['roles']);
        } else {
            // Assign default role based on user_type if no roles are specified
            $user->assignRole($validated['user_type']);
        }

        return redirect()->route('admin.users.index')->with('success', 'Utilisateur créé avec succès.');
    }

    /**
     * Display the specified user.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Contracts\View\View
     */
    public function usersShow(User $user): \Illuminate\Contracts\View\View
    {
        return view('admin.users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified user.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Contracts\View\View
     */
    public function usersEdit(User $user): \Illuminate\Contracts\View\View
    {
        $roles = Role::all();
        $userRoles = $user->roles->pluck('name')->toArray();
        return view('admin.users.edit', compact('user', 'roles', 'userRoles'));
    }

    /**
     * Update the specified user in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\RedirectResponse
     */
    public function usersUpdate(Request $request, User $user): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone_number' => ['nullable', 'string', 'max:20', Rule::unique('users')->ignore($user->id)],
            'location' => 'nullable|string|max:255',
            'bio' => 'nullable|string|max:1000',
            'user_type' => 'required|string|in:candidate,employer',
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,name',
            'password' => 'nullable|string|min:8|confirmed',
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->phone_number = $validated['phone_number'] ?? null;
        $user->location = $validated['location'] ?? null;
        $user->bio = $validated['bio'] ?? null;
        $user->user_type = $validated['user_type'];

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        if ($request->hasFile('profile_picture')) {
            if ($user->profile_picture && !Str::startsWith($user->profile_picture, ['http://', 'https://'])) {
                Storage::disk('public')->delete($user->profile_picture);
            }
            $user->profile_picture = $request->file('profile_picture')->store('avatars', 'public');
        }

        $user->save();

        if (isset($validated['roles'])) {
            $user->syncRoles($validated['roles']);
        } else {
            $user->syncRoles([]); // Remove all roles if none are selected
        }

        return redirect()->route('admin.users.index')->with('success', 'Utilisateur mis à jour avec succès.');
    }

    /**
     * Remove the specified user from storage.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\RedirectResponse
     */
    public function usersDestroy(User $user): \Illuminate\Http\RedirectResponse
    {
        try {
            if ($user->profile_picture && !Str::startsWith($user->profile_picture, ['http://', 'https://'])) {
                Storage::disk('public')->delete($user->profile_picture);
            }
            $user->delete();
            return redirect()->route('admin.users.index')->with('success', 'Utilisateur supprimé avec succès.');
        } catch (\Exception $e) {
            Log::error("Error deleting user {$user->id}: " . $e->getMessage());
            return redirect()->route('admin.users.index')->with('error', 'Erreur lors de la suppression de l\'utilisateur.');
        }
    }

    //---

    ## Job Management

    /**
     * Display a listing of the jobs.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function jobsIndex(): \Illuminate\Contracts\View\View
    {
        $jobs = Job::latest()->paginate(10);
        return view('admin.jobs.index', compact('jobs'));
    }

    /**
     * Display the specified job.
     *
     * @param  \App\Models\Job  $job
     * @return \Illuminate\Contracts\View\View
     */
    public function jobsShow(Job $job): \Illuminate\Contracts\View\View
    {
        return view('admin.jobs.show', compact('job'));
    }

    /**
     * Update the status of the specified job.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Job  $job
     * @return \Illuminate\Http\RedirectResponse
     */
    public function jobsUpdateStatus(Request $request, Job $job): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'status' => 'required|string|in:published,pending_approval,archived,rejected',
        ]);

        $job->status = $request->status;
        $job->save();

        return redirect()->route('admin.jobs.index')->with('success', 'Statut de l\'offre mis à jour avec succès.');
    }

    /**
     * Remove the specified job from storage.
     *
     * @param  \App\Models\Job  $job
     * @return \Illuminate\Http\RedirectResponse
     */
    public function jobsDestroy(Job $job): \Illuminate\Http\RedirectResponse
    {
        try {
            $job->delete();
            return redirect()->route('admin.jobs.index')->with('success', 'Offre d\'emploi supprimée avec succès.');
        } catch (\Exception $e) {
            Log::error("Error deleting job {$job->id}: " . $e->getMessage());
            return redirect()->route('admin.jobs.index')->with('error', 'Erreur lors de la suppression de l\'offre.');
        }
    }

    //---

    ## Category Management

    /**
     * Display a listing of the categories.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function categoriesIndex(): \Illuminate\Contracts\View\View
    {
        $categories = Category::latest()->paginate(10);
        return view('admin.categories.index', compact('categories'));
    }

    /**
     * Show the form for creating a new category.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function categoriesCreate(): \Illuminate\Contracts\View\View
    {
        return view('admin.categories.create');
    }

    /**
     * Store a newly created category in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function categoriesStore(Request $request): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories',
            'description' => 'nullable|string|max:1000',
        ]);

        Category::create($validated);

        return redirect()->route('admin.categories.index')->with('success', 'Catégorie créée avec succès.');
    }

    /**
     * Show the form for editing the specified category.
     *
     * @param  \App\Models\Category  $category
     * @return \Illuminate\Contracts\View\View
     */
    public function categoriesEdit(Category $category): \Illuminate\Contracts\View\View
    {
        return view('admin.categories.edit', compact('category'));
    }

    /**
     * Update the specified category in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Category  $category
     * @return \Illuminate\Http\RedirectResponse
     */
    public function categoriesUpdate(Request $request, Category $category): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('categories')->ignore($category->id)],
            'description' => 'nullable|string|max:1000',
        ]);

        $category->update($validated);

        return redirect()->route('admin.categories.index')->with('success', 'Catégorie mise à jour avec succès.');
    }

    /**
     * Remove the specified category from storage.
     *
     * @param  \App\Models\Category  $category
     * @return \Illuminate\Http\RedirectResponse
     */
    public function categoriesDestroy(Category $category): \Illuminate\Http\RedirectResponse
    {
        try {
            $category->delete();
            return redirect()->route('admin.categories.index')->with('success', 'Catégorie supprimée avec succès.');
        } catch (\Exception $e) {
            Log::error("Error deleting category {$category->id}: " . $e->getMessage());
            return redirect()->route('admin.categories.index')->with('error', 'Erreur lors de la suppression de la catégorie.');
        }
    }

    //---

    ## Role Management

    /**
     * Display a listing of the roles.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function rolesIndex(): \Illuminate\Contracts\View\View
    {
        $roles = Role::latest()->paginate(10);
        return view('admin.roles.index', compact('roles'));
    }

    /**
     * Show the form for creating a new role.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function rolesCreate(): \Illuminate\Contracts\View\View
    {
        $permissions = Permission::all();
        return view('admin.roles.create', compact('permissions'));
    }

    /**
     * Store a newly created role in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function rolesStore(Request $request): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        $role = Role::create(['name' => $validated['name']]);
        if (isset($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        return redirect()->route('admin.roles.index')->with('success', 'Rôle créé avec succès.');
    }

    /**
     * Show the form for editing the specified role.
     *
     * @param  \Spatie\Permission\Models\Role  $role
     * @return \Illuminate\Contracts\View\View
     */
    public function rolesEdit(Role $role): \Illuminate\Contracts\View\View
    {
        $permissions = Permission::all();
        $rolePermissions = $role->permissions->pluck('name')->toArray();
        return view('admin.roles.edit', compact('role', 'permissions', 'rolePermissions'));
    }

    /**
     * Update the specified role in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Spatie\Permission\Models\Role  $role
     * @return \Illuminate\Http\RedirectResponse
     */
    public function rolesUpdate(Request $request, Role $role): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('roles', 'name')->ignore($role->id)],
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        $role->update(['name' => $validated['name']]);
        if (isset($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        } else {
            $role->syncPermissions([]); // Remove all permissions if none are selected
        }

        return redirect()->route('admin.roles.index')->with('success', 'Rôle mis à jour avec succès.');
    }

    /**
     * Remove the specified role from storage.
     *
     * @param  \Spatie\Permission\Models\Role  $role
     * @return \Illuminate\Http\RedirectResponse
     */
    public function rolesDestroy(Role $role): \Illuminate\Http\RedirectResponse
    {
        try {
            $role->delete();
            return redirect()->route('admin.roles.index')->with('success', 'Rôle supprimé avec succès.');
        } catch (\Exception $e) {
            Log::error("Error deleting role {$role->id}: " . $e->getMessage());
            return redirect()->route('admin.roles.index')->with('error', 'Erreur lors de la suppression du rôle.');
        }
    }

    //---

    ## Permission Management

    /**
     * Display a listing of the permissions.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function permissionsIndex(): \Illuminate\Contracts\View\View
    {
        $permissions = Permission::latest()->paginate(10);
        return view('admin.permissions.index', compact('permissions'));
    }

    /**
     * Show the form for creating a new permission.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function permissionsCreate(): \Illuminate\Contracts\View\View
    {
        return view('admin.permissions.create');
    }

    /**
     * Store a newly created permission in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function permissionsStore(Request $request): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name',
        ]);

        Permission::create(['name' => $validated['name']]);

        return redirect()->route('admin.permissions.index')->with('success', 'Permission créée avec succès.');
    }

    /**
     * Show the form for editing the specified permission.
     *
     * @param  \Spatie\Permission\Models\Permission  $permission
     * @return \Illuminate\Contracts\View\View
     */
    public function permissionsEdit(Permission $permission): \Illuminate\Contracts\View\View
    {
        return view('admin.permissions.edit', compact('permission'));
    }

    /**
     * Update the specified permission in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Spatie\Permission\Models\Permission  $permission
     * @return \Illuminate\Http\RedirectResponse
     */
    public function permissionsUpdate(Request $request, Permission $permission): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('permissions', 'name')->ignore($permission->id)],
        ]);

        $permission->update(['name' => $validated['name']]);

        return redirect()->route('admin.permissions.index')->with('success', 'Permission mise à jour avec succès.');
    }

    /**
     * Remove the specified permission from storage.
     *
     * @param  \Spatie\Permission\Models\Permission  $permission
     * @return \Illuminate\Http\RedirectResponse
     */
    public function permissionsDestroy(Permission $permission): \Illuminate\Http\RedirectResponse
    {
        try {
            $permission->delete();
            return redirect()->route('admin.permissions.index')->with('success', 'Permission supprimée avec succès.');
        } catch (\Exception $e) {
            Log::error("Error deleting permission {$permission->id}: " . $e->getMessage());
            return redirect()->route('admin.permissions.index')->with('error', 'Erreur lors de la suppression de la permission.');
        }
    }

    //---

    ## Job Listings Management

    /**
     * Display a listing of the job listings.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function listingsIndex(): \Illuminate\Contracts\View\View
    {
        $listings = JobListing::latest()->paginate(10);
        return view('admin.listings.index', compact('listings'));
    }

    /**
     * Show the form for creating a new job listing.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function listingsCreate(): \Illuminate\Contracts\View\View
    {
        $categories = Category::all();
        $users = User::all();
        return view('admin.listings.create', compact('categories', 'users'));
    }

    /**
     * Store a newly created job listing in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function listingsStore(Request $request): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'location' => 'nullable|string|max:255',
            'salary' => 'nullable|string|max:255',
            'job_type' => 'required|string|in:full_time,part_time,contract,temporary,internship',
            'experience_level' => 'required|string|in:entry_level,mid_level,senior_level',
            'application_deadline' => 'nullable|date',
            'status' => 'required|string|in:published,pending_approval,archived,rejected',
            'user_id' => 'nullable|exists:users,id',
        ]);

        JobListing::create($validated);

        return redirect()->route('admin.listings.index')->with('success', 'Annonce créée avec succès.');
    }

    /**
     * Display the specified job listing.
     *
     * @param  \App\Models\JobListing  $listing
     * @return \Illuminate\Contracts\View\View
     */
    public function listingsShow(JobListing $listing): \Illuminate\Contracts\View\View
    {
        return view('admin.listings.show', compact('listing'));
    }

    /**
     * Show the form for editing the specified job listing.
     *
     * @param  \App\Models\JobListing  $listing
     * @return \Illuminate\Contracts\View\View
     */
    public function listingsEdit(JobListing $listing): \Illuminate\Contracts\View\View
    {
        $categories = Category::all();
        $users = User::all();
        return view('admin.listings.edit', compact('listing', 'categories', 'users'));
    }

    /**
     * Update the specified job listing in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\JobListing  $listing
     * @return \Illuminate\Http\RedirectResponse
     */
    public function listingsUpdate(Request $request, JobListing $listing): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'location' => 'nullable|string|max:255',
            'salary' => 'nullable|string|max:255',
            'job_type' => 'required|string|in:full_time,part_time,contract,temporary,internship',
            'experience_level' => 'required|string|in:entry_level,mid_level,senior_level',
            'application_deadline' => 'nullable|date',
            'status' => 'required|string|in:published,pending_approval,archived,rejected',
            'user_id' => 'nullable|exists:users,id',
        ]);

        $listing->update($validated);

        return redirect()->route('admin.listings.index')->with('success', 'Annonce mise à jour avec succès.');
    }

    /**
     * Remove the specified job listing from storage.
     *
     * @param  \App\Models\JobListing  $listing
     * @return \Illuminate\Http\RedirectResponse
     */
    public function listingsDestroy(JobListing $listing): \Illuminate\Http\RedirectResponse
    {
        try {
            $listing->delete();
            return redirect()->route('admin.listings.index')->with('success', 'Annonce supprimée avec succès.');
        } catch (\Exception $e) {
            Log::error("Error deleting listing {$listing->id}: " . $e->getMessage());
            return redirect()->route('admin.listings.index')->with('error', 'Erreur lors de la suppression de l\'annonce.');
        }
    }

    // You might want to add general settings or other admin-specific methods here later.
}
