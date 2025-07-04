<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Events\CallInitiated;
use App\Events\CallAccepted;
use App\Events\CallRejected;
use App\Events\CallEnded;
use Illuminate\Support\Facades\Log;

class CallController extends Controller
{
    // Nécessite que l'utilisateur soit authentifié pour initier/gérer les appels
    public function __construct()
    {
        $this->middleware('auth:sanctum')->only(['initiate', 'accept', 'reject', 'end']); // Appliquer pour les API
        $this->middleware('auth')->only(['index']); // Appliquer pour la vue web
    }

    /**
     * Affiche la page des appels.
     * Pour l'instant, c'est une vue simple. Vous y ajouterez la logique pour l'historique des appels.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // Vous pouvez passer des données à la vue ici, par exemple un historique d'appels
        // $user = Auth::user();
        // $calls = $user->calls()->latest()->get(); // Nécessite une relation 'calls' sur le modèle User

        return view('calls.index'); // Assurez-vous de créer cette vue (e.g., resources/views/calls/index.blade.php)
    }


    /**
     * Initie un nouvel appel vers un utilisateur.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function initiate(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'call_type' => 'required|in:audio,video', // 'audio' ou 'video'
        ]);

        $caller = Auth::user();
        $receiver = User::find($request->receiver_id);

        if (!$receiver) {
            return response()->json(['message' => 'Destinataire non trouvé.'], 404);
        }

        if ($caller->id === $receiver->id) {
            return response()->json(['message' => 'Vous ne pouvez pas vous appeler vous-même.'], 400);
        }

        // Générer un ID d'appel unique
        $callId = uniqid('call_');

        Log::info("Appel initié: Caller ID {$caller->id}, Receiver ID {$receiver->id}, Type {$request->call_type}, Call ID {$callId}");

        // Diffuser un événement pour notifier le destinataire de l'appel entrant
        // L'événement CallInitiated sera écouté par le frontend du destinataire
        event(new CallInitiated($callId, $caller, $receiver, $request->call_type));

        return response()->json([
            'message' => 'Appel initié avec succès.',
            'call_id' => $callId,
            'caller' => $caller->only(['id', 'name', 'profile_picture']),
            'receiver' => $receiver->only(['id', 'name', 'profile_picture']),
            'call_type' => $request->call_type,
        ], 200);
    }

    /**
     * Accepte un appel entrant.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function accept(Request $request)
    {
        $request->validate([
            'call_id' => 'required|string',
            'caller_id' => 'required|exists:users,id',
        ]);

        $receiver = Auth::user();
        $caller = User::find($request->caller_id);

        if (!$caller) {
            return response()->json(['message' => 'Appelant non trouvé.'], 404);
        }

        Log::info("Appel accepté: Call ID {$request->call_id}, Receiver ID {$receiver->id}, Caller ID {$caller->id}");

        // Diffuser un événement pour notifier l'appelant que l'appel a été accepté
        event(new CallAccepted($request->call_id, $caller, $receiver));

        return response()->json(['message' => 'Appel accepté.'], 200);
    }

    /**
     * Rejette un appel entrant.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function reject(Request $request)
    {
        $request->validate([
            'call_id' => 'required|string',
            'caller_id' => 'required|exists:users,id',
        ]);

        $receiver = Auth::user();
        $caller = User::find($request->caller_id);

        if (!$caller) {
            return response()->json(['message' => 'Appelant non trouvé.'], 404);
        }

        Log::info("Appel rejeté: Call ID {$request->call_id}, Receiver ID {$receiver->id}, Caller ID {$caller->id}");

        // Diffuser un événement pour notifier l'appelant que l'appel a été rejeté
        event(new CallRejected($request->call_id, $caller, $receiver));

        return response()->json(['message' => 'Appel rejeté.'], 200);
    }

    /**
     * Termine un appel en cours.
     * Peut être appelé par l'appelant ou le destinataire.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function end(Request $request)
    {
        $request->validate([
            'call_id' => 'required|string',
            'participant_id' => 'required|exists:users,id', // L'ID de l'autre participant à l'appel
        ]);

        $ender = Auth::user(); // Celui qui met fin à l'appel
        $otherParticipant = User::find($request->participant_id);

        if (!$otherParticipant) {
            return response()->json(['message' => 'Autre participant non trouvé.'], 404);
        }

        Log::info("Appel terminé: Call ID {$request->call_id}, Ender ID {$ender->id}, Other Participant ID {$otherParticipant->id}");

        // Diffuser un événement pour notifier l'autre participant que l'appel est terminé
        event(new CallEnded($request->call_id, $ender, $otherParticipant));

        return response()->json(['message' => 'Appel terminé.'], 200);
    }
}
