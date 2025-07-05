<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Call;
use App\Events\CallInitiated;
use App\Events\CallAccepted;
use App\Events\CallRejected;
use App\Events\CallEnded;
use App\Events\CallSignal; // NOUVEAU: Import de l'événement de signalisation
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CallController extends Controller
{
    public function __construct()
    {
        // Le middleware 'auth:sanctum' est généralement pour les API.
        // Si vos requêtes frontend utilisent le middleware 'web' (sessions),
        // alors 'auth' est suffisant. Je laisse 'sanctum' pour l'instant si c'est votre setup.
        $this->middleware('auth:sanctum')->only(['initiate', 'accept', 'reject', 'end', 'signal']); // Ajout de 'signal'
        $this->middleware('auth')->only(['index']);
    }

    /**
     * Affiche l'historique des appels pour l'utilisateur authentifié.
     * Permet la recherche par nom de l'appelant ou du destinataire.
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $searchQuery = $request->input('search');

        $calls = collect([]); // Initialise une collection vide pour les appels

        try {
            $callsQuery = Call::where(function ($query) use ($user) {
                                $query->where('caller_id', $user->id)
                                      ->orWhere('receiver_id', $user->id);
                            })
                            ->with(['caller', 'receiver'])
                            ->latest(); // Tri par les appels les plus récents

            if ($searchQuery) {
                $callsQuery->where(function ($query) use ($searchQuery) {
                    $query->whereHas('caller', function ($q) use ($searchQuery) {
                        $q->where('name', 'like', '%' . $searchQuery . '%');
                    })->orWhereHas('receiver', function ($q) use ($searchQuery) {
                        $q->where('name', 'like', '%' . $searchQuery . '%');
                    });
                });
            }

            $calls = $callsQuery->get();

        } catch (\Exception $e) {
            Log::error("Erreur lors de la récupération des appels dans CallController@index: " . $e->getMessage(), ['exception' => $e]);
            session()->flash('error', 'Une erreur est survenue lors du chargement de l\'historique des appels.');
        }

        return view('calls.index', compact('calls'));
    }

    /**
     * Initie un nouvel appel.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function initiate(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'call_type' => 'required|in:audio,video',
        ]);

        $caller = Auth::user();
        $receiver = User::find($request->receiver_id);

        if (!$receiver) {
            Log::warning("Tentative d'initiation d'appel vers un destinataire non trouvé. Receiver ID: {$request->receiver_id}");
            return response()->json(['message' => 'Destinataire non trouvé.'], 404);
        }

        if ($caller->id === $receiver->id) {
            return response()->json(['message' => 'Vous ne pouvez pas vous appeler vous-même.'], 400);
        }

        $callId = uniqid('call_');

        Log::info("Appel initié: Caller ID {$caller->id}, Receiver ID {$receiver->id}, Type {$request->call_type}, Call ID {$callId}");

        try {
            $call = Call::create([
                'call_id' => $callId,
                'caller_id' => $caller->id,
                'receiver_id' => $receiver->id,
                'call_type' => $request->call_type,
                'status' => 'initiated',
            ]);

            event(new CallInitiated($call->call_id, $caller, $receiver, $request->call_type));

            return response()->json([
                'message' => 'Appel initié avec succès. En attente de réponse...',
                'call_id' => $call->call_id,
                'caller' => $caller->only(['id', 'name', 'profile_picture']),
                'receiver' => $receiver->only(['id', 'name', 'profile_picture']),
                'call_type' => $request->call_type,
            ], 200);

        } catch (\Exception $e) {
            Log::error("Erreur lors de l'initiation de l'appel: " . $e->getMessage(), ['exception' => $e, 'request_data' => $request->all()]);
            return response()->json(['message' => 'Erreur lors de l\'initiation de l\'appel. Veuillez réessayer.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Accepte un appel.
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
            Log::warning("Tentative d'accepter un appel d'un appelant non trouvé. Caller ID: {$request->caller_id}");
            return response()->json(['message' => 'Appelant non trouvé.'], 404);
        }

        try {
            $call = Call::where('call_id', $request->call_id)
                        ->where('receiver_id', $receiver->id)
                        ->where('caller_id', $caller->id)
                        ->first();

            if (!$call) {
                Log::warning("Appel non trouvé ou non correspondant pour l'acceptation. Call ID: {$request->call_id}, Receiver ID: {$receiver->id}, Caller ID: {$caller->id}");
                return response()->json(['message' => 'Appel non trouvé ou non valide pour l\'acceptation.'], 404);
            }

            if ($call->status !== 'initiated') {
                Log::warning("Tentative d'accepter un appel qui n'est pas à l'état 'initiated'. Call ID: {$request->call_id}, Current Status: {$call->status}");
                return response()->json(['message' => 'L\'appel ne peut pas être accepté dans son état actuel.'], 409);
            }

            $call->update(['status' => 'accepted']);

            Log::info("Appel accepté: Call ID {$request->call_id}, Receiver ID {$receiver->id}, Caller ID {$caller->id}");

            event(new CallAccepted($request->call_id, $caller, $receiver));

            return response()->json(['message' => 'Appel accepté avec succès.'], 200);

        } catch (\Exception $e) {
            Log::error("Erreur lors de l'acceptation de l'appel: " . $e->getMessage(), ['exception' => $e, 'request_data' => $request->all()]);
            return response()->json(['message' => 'Erreur lors de l\'enregistrement de l\'acceptation de l\'appel. Veuillez réessayer.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Rejette un appel.
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
            Log::warning("Tentative de rejeter un appel d'un appelant non trouvé. Caller ID: {$request->caller_id}");
            return response()->json(['message' => 'Appelant non trouvé.'], 404);
        }

        try {
            $call = Call::where('call_id', $request->call_id)
                        ->where('receiver_id', $receiver->id)
                        ->where('caller_id', $caller->id)
                        ->first();

            if (!$call) {
                Log::warning("Appel non trouvé ou non correspondant pour le rejet: Call ID {$request->call_id}, Receiver ID: {$receiver->id}, Caller ID: {$caller->id}");
                return response()->json(['message' => 'Appel non trouvé ou non valide pour le rejet.'], 404);
            }

            if ($call->status !== 'initiated') {
                Log::warning("Tentative de rejeter un appel qui n'est pas à l'état 'initiated'. Call ID: {$request->call_id}, Current Status: {$call->status}");
                return response()->json(['message' => 'L\'appel ne peut pas être rejeté dans son état actuel.'], 409);
            }

            $call->update(['status' => 'rejected']);

            Log::info("Appel rejeté: Call ID {$request->call_id}, Receiver ID {$receiver->id}, Caller ID {$caller->id}");

            event(new CallRejected($request->call_id, $caller, $receiver));

            return response()->json(['message' => 'Appel rejeté avec succès.'], 200);

        } catch (\Exception $e) {
            Log::error("Erreur lors du rejet de l'appel: " . $e->getMessage(), ['exception' => $e, 'request_data' => $request->all()]);
            return response()->json(['message' => 'Erreur lors de l\'enregistrement du rejet de l\'appel. Veuillez réessayer.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Termine un appel. Peut être appelé par l'appelant ou le destinataire.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function end(Request $request)
    {
        $request->validate([
            'call_id' => 'required|string',
            'participant_id' => 'required|exists:users,id', // L'ID de l'autre participant
            'duration' => 'nullable|integer|min:0', // Durée de l'appel en secondes
        ]);

        $ender = Auth::user(); // L'utilisateur qui met fin à l'appel
        $otherParticipant = User::find($request->participant_id);

        if (!$otherParticipant) {
            Log::warning("Tentative de terminer un appel avec un autre participant non trouvé. Participant ID: {$request->participant_id}");
            return response()->json(['message' => 'Autre participant non trouvé.'], 404);
        }

        try {
            $call = Call::where('call_id', $request->call_id)
                        ->where(function($query) use ($ender) {
                            $query->where('caller_id', $ender->id)
                                  ->orWhere('receiver_id', $ender->id);
                        })
                        ->first();

            if (!$call) {
                Log::warning("Appel non trouvé ou non correspondant pour la fin: Call ID {$request->call_id}, Ender ID: {$ender->id}");
                return response()->json(['message' => 'Appel non trouvé ou non valide pour la fin.'], 404);
            }

            $updateData = ['status' => 'ended'];
            if ($request->has('duration')) {
                $updateData['duration'] = $request->duration;
            }

            // Si l'appel était initié et que le récepteur a mis fin (n'a pas accepté), le statut devient 'missed'
            if ($call->status === 'initiated' && $call->receiver_id === $ender->id) {
                $updateData['status'] = 'missed';
            }

            $call->update($updateData);

            Log::info("Appel terminé: Call ID {$request->call_id}, Ender ID {$ender->id}, Other Participant ID {$otherParticipant->id}, Final Status: {$call->status}");

            // Déterminer qui est l'appelant et le destinataire pour l'événement CallEnded
            $actualCaller = $call->caller;
            $actualReceiver = $call->receiver;

            event(new CallEnded($request->call_id, $actualCaller, $actualReceiver));

            return response()->json(['message' => 'Appel terminé avec succès.', 'final_status' => $call->status], 200);

        } catch (\Exception $e) {
            Log::error("Erreur lors de la fin de l'appel: " . $e->getMessage(), ['exception' => $e, 'request_data' => $request->all()]);
            return response()->json(['message' => 'Erreur lors de l\'enregistrement de la fin de l\'appel. Veuillez réessayer.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Gère l'échange de messages de signalisation WebRTC (Offer, Answer, ICE Candidates)
     * entre les participants d'un appel.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function signal(Request $request)
    {
        $request->validate([
            'call_id' => 'required|string',
            'receiver_id' => 'required|exists:users,id', // L'ID de l'autre participant à qui envoyer le signal
            'type' => 'required|in:offer,answer,ice-candidate', // Type de signal WebRTC
            'payload' => 'required|array', // Les données SDP ou ICE Candidate
        ]);

        $sender = Auth::user(); // L'utilisateur qui envoie le signal
        $receiver = User::find($request->receiver_id); // L'utilisateur qui doit recevoir le signal

        if (!$receiver) {
            Log::warning("Signalisation: Destinataire non trouvé. Receiver ID: {$request->receiver_id}");
            return response()->json(['message' => 'Destinataire du signal non trouvé.'], 404);
        }

        // Vérifier que l'appel existe et que les participants sont bien ceux de l'appel
        $call = Call::where('call_id', $request->call_id)
                    ->where(function($query) use ($sender, $receiver) {
                        $query->where(function($q) use ($sender, $receiver) {
                            $q->where('caller_id', $sender->id)
                              ->where('receiver_id', $receiver->id);
                        })->orWhere(function($q) use ($sender, $receiver) {
                            $q->where('caller_id', $receiver->id)
                              ->where('receiver_id', $sender->id);
                        });
                    })
                    ->first();

        if (!$call) {
            Log::warning("Signalisation: Appel non trouvé ou participants incorrects. Call ID: {$request->call_id}, Sender ID: {$sender->id}, Receiver ID: {$receiver->id}");
            return response()->json(['message' => 'Appel non trouvé ou participants non valides pour la signalisation.'], 404);
        }

        try {
            // Diffuser l'événement de signalisation à l'autre participant
            event(new CallSignal(
                $request->call_id,
                $sender,
                $receiver, // Le destinataire réel du signal
                $request->type,
                $request->payload
            ));

            Log::info("Signalisation envoyée: Call ID {$request->call_id}, Sender ID {$sender->id}, Receiver ID {$receiver->id}, Type: {$request->type}");

            return response()->json(['message' => 'Signal envoyé avec succès.'], 200);

        } catch (\Exception $e) {
            Log::error("Erreur lors de l'envoi du signal: " . $e->getMessage(), ['exception' => $e, 'request_data' => $request->all()]);
            return response()->json(['message' => 'Erreur lors de l\'envoi du signal.'], 500);
        }
    }
}
