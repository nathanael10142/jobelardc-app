<?php

namespace App\Http\Controllers;

use App\Models\Job;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class JobController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth')->except(['index', 'show']);
    }

    public function index()
    {
        // Charge la relation 'user' et 'category' (pas 'employer' qui n'existe pas)
        $jobs = Job::where('status', 'published')
                    ->with('user', 'category')
                    ->latest()
                    ->paginate(10);

        return view('jobs.index', compact('jobs'));
    }

    public function show(Job $job)
    {
        $job->load('user', 'category');

        return view('jobs.show', compact('job'));
    }

    public function create()
    {
        if (Gate::denies('create job')) {
            abort(403, 'Vous n\'êtes pas autorisé à créer une annonce d\'emploi.');
        }

        $categories = Category::all();

        return view('jobs.create', compact('categories'));
    }

    public function store(Request $request)
    {
        if (Gate::denies('create job')) {
            abort(403, 'Vous n\'êtes pas autorisé à créer cette annonce d\'emploi.');
        }

        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'location' => 'nullable|string|max:255',
            'salary_range' => 'nullable|string|max:100',
            'employment_type' => 'required|string|in:full-time,part-time,contract,temporary,internship',
            'status' => 'required|string|in:draft,published,archived',
        ]);

        // Création via la relation 'jobs' définie dans User
        $job = Auth::user()->jobs()->create($validatedData);

        return redirect()->route('jobs.show', $job->id)->with('success', 'Annonce d\'emploi créée avec succès !');
    }

    public function edit(Job $job)
    {
        if (Gate::denies('edit job', $job)) {
            abort(403, 'Vous n\'êtes pas autorisé à modifier cette annonce d\'emploi.');
        }

        $categories = Category::all();

        return view('jobs.edit', compact('job', 'categories'));
    }

    public function update(Request $request, Job $job)
    {
        if (Gate::denies('edit job', $job)) {
            abort(403, 'Vous n\'êtes pas autorisé à mettre à jour cette annonce d\'emploi.');
        }

        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'location' => 'nullable|string|max:255',
            'salary_range' => 'nullable|string|max:100',
            'employment_type' => 'required|string|in:full-time,part-time,contract,temporary,internship',
            'status' => 'required|string|in:draft,published,archived',
        ]);

        $job->update($validatedData);

        return redirect()->route('jobs.show', $job->id)->with('success', 'Annonce d\'emploi mise à jour avec succès !');
    }

    public function destroy(Job $job)
    {
        if (Gate::denies('delete job', $job)) {
            abort(403, 'Vous n\'êtes pas autorisé à supprimer cette annonce d\'emploi.');
        }

        $job->delete();

        return redirect()->route('jobs.index')->with('success', 'Annonce d\'emploi supprimée avec succès !');
    }
}
