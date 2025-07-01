<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Database\QueryException;

class CategoryController extends Controller
{
    public function __construct()
    {
        // $this->middleware(['auth', 'role:super_admin|admin'])->except(['index', 'show']);
    }

    public function index(Request $request)
    {
        $query = Category::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('name', 'like', '%' . $search . '%');
        }

        $categories = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();

        return view('admin.categories.index', compact('categories'));
    }

    public function show(Category $category)
    {
        return view('categories.show', compact('category'));
    }

    public function create()
    {
        Gate::authorize('manage categories');

        return view('admin.categories.create');
    }

    public function store(Request $request)
    {
        Gate::authorize('manage categories');

        $validatedData = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name',
            'description' => 'nullable|string|max:1000',
        ]);
        $validatedData['slug'] = Str::slug($validatedData['name']);

        try {
            Category::create($validatedData);
            return redirect()->route('admin.categories.index')
                ->with('success', 'Catégorie créée avec succès !');
        } catch (QueryException $e) {
            // Log::error($e->getMessage()); // optionnel : logger l'erreur
            return back()->withInput()
                ->with('error', 'Une erreur est survenue lors de la création de la catégorie.');
        }
    }

    public function edit(Category $category)
    {
        Gate::authorize('manage categories');

        return view('admin.categories.edit', compact('category'));
    }

    public function update(Request $request, Category $category)
    {
        Gate::authorize('manage categories');

        $validatedData = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,' . $category->id,
            'description' => 'nullable|string|max:1000',
        ]);
        $validatedData['slug'] = Str::slug($validatedData['name']);

        try {
            $category->update($validatedData);
            return redirect()->route('admin.categories.index')
                ->with('success', 'Catégorie mise à jour avec succès !');
        } catch (QueryException $e) {
            // Log::error($e->getMessage());
            return back()->withInput()
                ->with('error', 'Une erreur est survenue lors de la mise à jour de la catégorie.');
        }
    }

    public function destroy(Category $category)
    {
        Gate::authorize('manage categories');

        try {
            $category->delete();
            return redirect()->route('admin.categories.index')
                ->with('success', 'Catégorie supprimée avec succès !');
        } catch (QueryException $e) {
            // Log::error($e->getMessage());
            return redirect()->route('admin.categories.index')
                ->with('error', 'Une erreur est survenue lors de la suppression de la catégorie.');
        }
    }
}
