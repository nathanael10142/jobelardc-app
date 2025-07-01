<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;

class ChatController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'verified']);
    }

    /**
     * Affiche la liste des conversations de l'utilisateur.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = $user->conversations()
            ->with(['users', 'lastMessage']);

        // Appliquer la logique de recherche si un terme est présent
        if ($request->has('search') && !empty($request->search)) {
            $searchTerm = $request->search;

            $query->where(function ($q) use ($searchTerm, $user) {
                // Recherche par nom de conversation (pour les groupes, si vous en avez)
                $q->where('name', 'like', '%' . $searchTerm . '%')
                  // Recherche par nom d'utilisateur participant à la conversation
                  ->orWhereHas('users', function ($subQuery) use ($searchTerm, $user) {
                      // Exclure l'utilisateur actuel de la recherche de noms de participants
                      $subQuery->where('users.id', '!=', $user->id)
                               ->where('name', 'like', '%' . $searchTerm . '%');
                  })
                  // Recherche par contenu du dernier message
                  ->orWhereHas('lastMessage', function ($subQuery) use ($searchTerm) {
                      $subQuery->where('body', 'like', '%' . $searchTerm . '%');
                  });
            });
        }

        $conversations = $query->latest('updated_at')->get();

        // Compter les messages non lus pour chaque conversation
        $conversations->each(function ($conversation) use ($user) {
            $conversation->unread_messages_count = $conversation->messages()
                ->where('user_id', '!=', $user->id)
                ->whereDoesntHave('readBy', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->count();
        });

        return view('chats.index', compact('conversations'));
    }

    /**
     * Affiche une conversation spécifique et marque les messages comme lus.
     */
    public function show(Conversation $conversation)
    {
        if (!$conversation->users->contains(Auth::id())) {
            abort(403, 'Accès non autorisé à cette discussion.');
        }

        $user = Auth::user();

        $messages = $conversation->messages()
            ->with('user')
            ->orderBy('created_at', 'asc')
            ->get();

        foreach ($messages as $message) {
            if ($message->user_id !== $user->id && !$message->readBy->contains($user->id)) {
                $message->readBy()->attach($user->id);
            }
        }

        return view('chats.show', compact('conversation', 'messages'));
    }

    /**
     * Stocke un nouveau message dans une conversation.
     */
    public function store(Request $request, Conversation $conversation)
    {
        if (!$conversation->users->contains(Auth::id())) {
            return response()->json(['error' => 'Accès non autorisé à cette discussion.'], 403);
        }

        $request->validate([
            'body' => 'required|string|max:2000',
        ]);

        $message = $conversation->messages()->create([
            'user_id' => Auth::id(),
            'body' => $request->body,
        ]);

        $message->readBy()->attach(Auth::id());
        $conversation->touch(); // Mettre à jour `updated_at` de la conversation

        return response()->json([
            'success' => true,
            'message' => $message->load('user')
        ]);
    }

    /**
     * Recherche d'utilisateurs par nom ou email,
     * ou charge les utilisateurs par défaut si aucune recherche n'est spécifiée.
     */
    public function searchUsers(Request $request)
    {
        $query = $request->input('query');
        $currentUserId = Auth::id();

        $users = User::where('id', '!=', $currentUserId)
            // Utilisez `when` pour appliquer les conditions de recherche uniquement si `query` est non vide.
            // Si `query` est vide, cette clause est ignorée et le query builder continue sans filtre de nom/email.
            ->when(!empty($query) && strlen($query) >= 2, function ($q) use ($query) {
                $q->where('name', 'like', '%' . $query . '%')
                  ->orWhere('email', 'like', '%' . $query . '%');
            })
            ->orderBy('name')
            ->take(50) // Toujours limiter le nombre de résultats pour la performance
            ->get(['id', 'name', 'email', 'profile_picture', 'user_type']);

        $users->each(function ($user) {
            $user->initials = collect(explode(' ', $user->name))
                ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
                ->take(2)
                ->implode('');

            $colors = ['#FF5733', '#33FF57', '#3357FF', '#FF33A1', '#A133FF', '#33FFF3'];
            $user->avatar_bg_color = $colors[$user->id % count($colors)];
        });

        return response()->json(['users' => $users]);
    }

    /**
     * Crée une conversation 1-on-1 si elle n'existe pas déjà.
     */
    public function createConversation(Request $request)
    {
        $request->validate([
            'recipient_id' => 'required|exists:users,id',
        ]);

        $currentUserId = Auth::id();
        $recipientId = $request->input('recipient_id');

        if ($currentUserId == $recipientId) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez pas créer une discussion avec vous-même.'
            ], 400);
        }

        $existingConversation = Conversation::where('is_group', false)
            ->whereHas('users', fn ($q) => $q->where('user_id', $currentUserId))
            ->whereHas('users', fn ($q) => $q->where('user_id', $recipientId))
            ->withCount('users')
            ->get()
            ->filter(fn ($conv) => $conv->users_count === 2)
            ->first();

        if ($existingConversation) {
            return response()->json([
                'success' => true,
                'message' => 'Discussion existante trouvée.',
                'conversation_id' => $existingConversation->id,
                'redirect_to_existing_chat' => route('chats.show', $existingConversation->id)
            ]);
        }

        $conversation = Conversation::create(['is_group' => false]);
        $conversation->users()->attach([$currentUserId, $recipientId]);

        return response()->json([
            'success' => true,
            'message' => 'Nouvelle discussion créée.',
            'conversation_id' => $conversation->id,
            'redirect_to_existing_chat' => route('chats.show', $conversation->id)
        ]);
    }
}
