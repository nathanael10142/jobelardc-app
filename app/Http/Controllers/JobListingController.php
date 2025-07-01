<?php

namespace App\Http\Controllers;

use App\Models\JobListing;
use Illuminate\Http\Request; // Assurez-vous que cette ligne est présente
use Illuminate\Support\Facades\Auth;

class JobListingController extends Controller
{
    /**
     * Affiche toutes les annonces.
     * Inclut la fonctionnalité de recherche.
     */
    public function index(Request $request) // <-- IMPORTANT : Ajoutez Request $request ici
    {
        // Commencez par la requête de base
        $query = JobListing::latest()->with('user');

        // Appliquer la logique de recherche si un terme est présent dans la requête GET
        if ($request->has('search') && !empty($request->search)) {
            $searchTerm = $request->search;

            // Appliquer les filtres de recherche sur plusieurs colonnes
            $query->where(function ($q) use ($searchTerm) {
                $q->where('title', 'like', '%' . $searchTerm . '%')
                  ->orWhere('description', 'like', '%' . $searchTerm . '%')
                  ->orWhere('location', 'like', '%' . $searchTerm . '%')
                  ->orWhere('salary', 'like', '%' . $searchTerm . '%')
                  ->orWhere('posted_by_name', 'like', '%' . $searchTerm . '%');
            });
        }

        // Exécute la requête pour récupérer les annonces filtrées (ou toutes si pas de recherche)
        $jobListings = $query->get();

        return view('listings.index', compact('jobListings'));
    }

    /**
     * Affiche le formulaire de création.
     */
    public function create()
    {
        return view('listings.create');
    }

    /**
     * Enregistre une nouvelle annonce.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'location' => 'required|string|max:255',
            'salary' => 'nullable|string|max:255',
            'contact_info' => 'required|string|max:255',
            'is_job_offer' => 'nullable|boolean',
        ]);

        $jobListing = new JobListing($validatedData);

        if (Auth::check()) {
            $user = Auth::user();
            $jobListing->user_id = $user->id;
            $jobListing->posted_by_name = $user->name;
            $jobListing->posted_by_type = $user->getRoleNames()->first() ?? 'Utilisateur';
        } else {
            $jobListing->posted_by_name = 'Invité'; // Ou gérer autrement si un utilisateur non connecté peut poster
            $jobListing->posted_by_type = 'Invité';
        }

        $jobListing->save();

        return redirect()->route('listings.show', $jobListing)
                         ->with('success', 'Votre annonce a été publiée avec succès !');
    }

    /**
     * Affiche une annonce en détail.
     */
    public function show(JobListing $listing)
    {
        return view('listings.show', compact('listing'));
    }

    /**
     * Formulaire d'édition.
     */
    public function edit(JobListing $listing)
    {
        // À ajouter : vérification d'autorisation si nécessaire
        // Par exemple : if (Auth::id() !== $listing->user_id && !Auth::user()->hasRole('admin')) { abort(403); }
        return view('listings.edit', compact('listing'));
    }

    /**
     * Met à jour une annonce.
     */
    public function update(Request $request, JobListing $listing)
    {
        // Optionnel: vérifier que l'utilisateur est propriétaire ou admin avant de mettre à jour
        // if (Auth::id() !== $listing->user_id && !Auth::user()->hasRole('admin')) { abort(403); }

        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'location' => 'required|string|max:255',
            'salary' => 'nullable|string|max:255',
            'contact_info' => 'required|string|max:255',
            'is_job_offer' => 'nullable|boolean',
        ]);

        $listing->update($validatedData);

        return redirect()->route('listings.show', $listing)
                         ->with('success', 'Annonce mise à jour avec succès.');
    }

    /**
     * Supprime une annonce.
     */
    public function destroy(JobListing $listing)
    {
        // Optionnel: vérifier que l'utilisateur est propriétaire ou admin avant suppression
        if (Auth::check() && (Auth::id() === $listing->user_id || Auth::user()->hasAnyRole(['super_admin', 'admin']))) {
            $listing->delete();
            return redirect()->route('listings.index')
                             ->with('success', 'Annonce supprimée avec succès.');
        }

        abort(403, 'Action non autorisée.');
    }
}
