<?php // Cette balise doit être la toute première chose dans le fichier, sans aucun espace ou caractère avant.

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Events\MessageSent;
use App\Events\MessageRead;

class ChatController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'verified']);
    }

    public function index(Request $request)
    {
        $user = Auth::user();

        $query = $user->conversations()
            ->with(['users', 'lastMessage']);

        if ($request->has('search') && !empty($request->search)) {
            $searchTerm = $request->search;

            $query->where(function ($q) use ($searchTerm, $user) {
                $q->where('name', 'like', '%' . $searchTerm . '%')
                    ->orWhereHas('users', function ($subQuery) use ($searchTerm, $user) {
                        $subQuery->where('users.id', '!=', $user->id)
                                 ->where('name', 'like', '%' . $searchTerm . '%');
                    })
                    ->orWhereHas('lastMessage', function ($subQuery) use ($searchTerm) {
                        $subQuery->where('body', 'like', '%' . $searchTerm . '%');
                    });
            });
        }

        $conversations = $query->latest('updated_at')->get();

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
                broadcast(new MessageRead($message->id, $conversation->id, $user->id))->toOthers();
            }
        }

        return view('chats.show', compact('conversation', 'messages'));
    }

    public function sendMessage(Request $request, Conversation $conversation)
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
        $conversation->touch();

        $message->load('user'); // Load user relationship for the event

        broadcast(new MessageSent($message))->toOthers(); // Broadcast to all except sender

        return response()->json([
            'success' => true,
            'message' => $message->toArray()
        ]);
    }

    public function markAsRead(Message $message)
    {
        $user = Auth::user();

        if (!$message->conversation->users->contains($user->id)) {
            return response()->json(['error' => 'Accès non autorisé au message.'], 403);
        }

        if ($message->user_id === $user->id) {
            return response()->json(['status' => 'self_read', 'message' => 'Vous ne pouvez pas marquer votre propre message comme lu par vous-même.']);
        }

        if (!$message->readBy->contains($user->id)) {
            $message->readBy()->attach($user->id);
            broadcast(new MessageRead($message->id, $message->conversation_id, $user->id))->toOthers();
            return response()->json(['status' => 'success', 'message' => 'Message marqué comme lu']);
        }

        return response()->json(['status' => 'already_read', 'message' => 'Message déjà lu']);
    }

    public function getMessages(Conversation $conversation)
    {
        if (!$conversation->users->contains(Auth::id())) {
            return response()->json(['error' => 'Accès non autorisé à cette discussion.'], 403);
        }

        $messages = $conversation->messages()
            ->with('user')
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json(['messages' => $messages]);
    }

    public function searchUsers(Request $request)
    {
        $query = $request->input('query');
        $currentUserId = Auth::id();

        $users = User::where('id', '!=', $currentUserId)
            ->when(!empty($query) && strlen($query) >= 2, function ($q) use ($query) {
                $q->where('name', 'like', '%' . $query . '%')
                    ->orWhere('email', 'like', '%' . $query . '%');
            })
            ->orderBy('name')
            ->take(50)
            ->get(['id', 'name', 'email', 'profile_picture', 'user_type']);

        $users->each(function ($user) {
            $user->initials = collect(explode(' ', $user->name))
                ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
                ->take(2)
                ->implode('');

            // Using a more consistent hash-based color generation for avatars
            // This is better than modulo on ID for consistent colors across sessions/users
            $hash = md5($user->email ?? $user->id);
            $user->avatar_bg_color = '#' . substr($hash, 0, 6);
        });

        return response()->json($users);
    }

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
