<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User; // Assure-toi que ce modèle est importé
use App\Models\JobPost; // Assure-toi que ce modèle est importé
use App\Models\JobApplication; // Assure-toi que ce modèle est importé

class CandidateDashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:candidate']);
    }

    public function index(Request $request)
    {
        $user = Auth::user();

        // --- Données fictives pour les discussions (Recent Chats) ---
        // Les propriétés correspondent maintenant à celles utilisées dans la vue Blade
        $recentChats = [
            (object)[
                'id' => 1,
                'sender' => 'Félix Tshisekedi', // Correspond à $chat->sender
                'message' => 'Nouvelle offre d\'emploi disponible!', // Correspond à $chat->message
                'time' => '10 min',
                'avatar' => 'https://placehold.co/50x50/FFC107/FFF?text=F',
                // 'unread_count' => 1 // Non utilisé dans la vue actuelle, mais peut être ajouté si besoin
            ],
            (object)[
                'id' => 2,
                'sender' => 'Jobela Support', // Correspond à $chat->sender
                'message' => 'Bienvenue sur Jobela!', // Correspond à $chat->message
                'time' => '1h',
                'avatar' => 'https://placehold.co/50x50/28A745/FFF?text=J',
                // 'unread_count' => 0
            ],
        ];

        // --- Données fictives pour les actualités (Recent Statuses) ---
        // `$userStatus` doit être un objet pour pouvoir accéder à $userStatus->avatar
        $userStatus = (object)[
            'avatar' => $user->profile_picture ?? 'https://placehold.co/60x60/008069/FFF?text=' . substr($user->name, 0, 1),
            'status_text' => 'En ligne', // Une propriété pour un texte de statut si tu veux l'afficher
        ];

        $recentStatuses = [
            (object)[
                'id' => 1,
                'user_name' => 'Jean-Luc Bilongi', // Correspond à $status->user_name
                'avatar' => 'https://placehold.co/60x60/dc3545/FFF?text=J', // Avatar pour l'actualité
                'text' => 'Postulé à un nouveau poste.',
                'time' => '2 jours'
            ],
            (object)[
                'id' => 2,
                'user_name' => 'Marie Dupont',
                'avatar' => 'https://placehold.co/60x60/17a2b8/FFF?text=M',
                'text' => 'Mis à jour mon CV.',
                'time' => '1 semaine'
            ],
        ];

        // --- Données fictives pour l'historique des appels (Call History) ---
        // Les propriétés correspondent à celles utilisées dans la vue Blade
        $callHistory = [
            (object)[
                'id' => 1,
                'contact_name' => 'Fifi K.', // Correspond à $call->contact_name
                'time' => '15 juin, 14:30',
                'type' => 'missed', // 'missed' ou 'outgoing'
                'avatar' => 'https://placehold.co/50x50/6F42C1/FFF?text=F'
            ],
            (object)[
                'id' => 2,
                'contact_name' => 'Service Client Jobela', // Correspond à $call->contact_name
                'time' => '14 juin, 09:00',
                'type' => 'outgoing',
                'avatar' => 'https://placehold.co/50x50/0D6EFD/FFF?text=S'
            ],
        ];

        // Récupération des offres d'emploi (Job Posts)
        // Assurez-vous que JobPost a une colonne 'employer_id' ou une relation avec User
        $jobPosts = JobPost::latest()->take(2)->get();
        // Pour `employer_name` dans la vue, tu devras peut-être eager load la relation avec User
        // $jobPosts = JobPost::with('employer')->latest()->take(2)->get();
        // Et dans le modèle JobPost, ajouter:
        // public function employer() { return $this->belongsTo(User::class, 'employer_id'); }

        // Récupération des candidatures (My Applications)
        $myApplications = $user->jobApplications()->latest()->take(2)->get();
        // Pour `jobPost->title` dans la vue, tu devras eager load la relation avec JobPost
        // $myApplications = $user->jobApplications()->with('jobPost')->latest()->take(2)->get();
        // Et dans le modèle JobApplication, ajouter:
        // public function jobPost() { return $this->belongsTo(JobPost::class); }


        return view('candidate.dashboard', [
            'user' => $user,
            'recentChats' => $recentChats,
            'userStatus' => $userStatus,
            'recentStatuses' => $recentStatuses,
            'callHistory' => $callHistory,
            'jobPosts' => $jobPosts,
            'myApplications' => $myApplications,
        ]);
    }
}
