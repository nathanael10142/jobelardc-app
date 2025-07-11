<?php // Cette balise doit être la toute première chose dans le fichier, sans aucun espace ou caractère avant.

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Call;
use App\Events\CallInitiated;
use App\Events\CallAccepted;
use App\Events\CallRejected;
use App\Events\CallEnded;
use App\Events\CallSignal;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpFoundation\Response;

class CallController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum')->only(['initiate', 'accept', 'reject', 'end', 'signal', 'indexApi']);
        $this->middleware('auth')->only(['index']);
    }

    /**
     * Affiche l'historique des appels pour l'utilisateur authentifié.
     * Permet la recherche par nom de l'appelant ou du destinataire.
     * Cette méthode est pour l'affichage de la vue web.
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $searchQuery = $request->input('search');

        $calls = collect([]);

        try {
            $callsQuery = Call::where(function ($query) use ($user) {
                                $query->where('caller_id', $user->id)
                                    ->orWhere('receiver_id', $user->id);
                            })
                            ->with(['caller', 'receiver'])
                            ->latest();

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
     * Retourne l'historique des appels pour l'utilisateur authentifié sous format JSON.
     * Permet la recherche par nom de l'appelant ou du destinataire.
     * Cette méthode est pour l'API utilisée par le frontend JavaScript.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function indexApi(Request $request)
    {
        $user = Auth::user();
        $searchQuery = $request->input('search');

        try {
            $callsQuery = Call::where(function ($query) use ($user) {
                                $query->where('caller_id', $user->id)
                                    ->orWhere('receiver_id', $user->id);
                            })
                            ->with(['caller', 'receiver'])
                            ->latest();

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

            $formattedCalls = $calls->map(function ($call) use ($user) {
                $otherParticipant = null;
                if ($call->caller_id === $user->id) {
                    $otherParticipant = $call->receiver;
                } else {
                    $otherParticipant = $call->caller;
                }

                $isMissedCall = ($call->receiver_id === $user->id && $call->status === 'missed');
                $isOutgoingCall = ($call->caller_id === $user->id);

                return [
                    'id' => $call->id,
                    'call_uuid' => $call->call_uuid,
                    'caller_id' => $call->caller_id,
                    'receiver_id' => $call->receiver_id,
                    'call_type' => $call->call_type,
                    'status' => $call->status,
                    'duration' => $call->duration,
                    'created_at' => $call->created_at->toISOString(),
                    'started_at' => $call->started_at ? $call->started_at->toISOString() : null,
                    'ended_at' => $call->ended_at ? $call->ended_at->toISOString() : null,
                    'created_at_for_humans' => $call->created_at->diffForHumans(),
                    'is_missed_call' => $isMissedCall,
                    'is_outgoing_call' => $isOutgoingCall,
                    'caller' => $call->caller ? [
                        'id' => $call->caller->id,
                        'name' => $call->caller->name,
                        'profile_picture' => $call->caller->profile_picture,
                        'email' => $call->caller->email,
                        'user_type' => $call->caller->user_type,
                    ] : null,
                    'receiver' => $call->receiver ? [
                        'id' => $call->receiver->id,
                        'name' => $call->receiver->name,
                        'profile_picture' => $call->receiver->profile_picture,
                        'email' => $call->receiver->email,
                        'user_type' => $call->receiver->user_type,
                    ] : null,
                ];
            });

            return response()->json($formattedCalls, Response::HTTP_OK);

        } catch (\Exception $e) {
            Log::error("Erreur lors de la récupération des appels dans CallController@indexApi: " . $e->getMessage(), ['exception' => $e]);
            return response()->json(['message' => 'Erreur lors du chargement de l\'historique des appels.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
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
            return response()->json(['message' => 'Destinataire non trouvé.'], Response::HTTP_NOT_FOUND);
        }

        if ($caller->id === $receiver->id) {
            return response()->json(['message' => 'Vous ne pouvez pas vous appeler vous-même.'], Response::HTTP_BAD_REQUEST);
        }

        // Vérifier s'il existe déjà un appel "actif" (initié, accepté) entre ces deux utilisateurs
        $existingActiveCall = Call::where(function($query) use ($caller, $receiver) {
                $query->where('caller_id', $caller->id)->where('receiver_id', $receiver->id);
            })->orWhere(function($query) use ($caller, $receiver) {
                $query->where('caller_id', $receiver->id)->where('receiver_id', $caller->id);
            })
            ->whereIn('status', ['initiated', 'accepted'])
            ->first();

        if ($existingActiveCall) {
            Log::warning("Tentative d'initier un appel alors qu'un appel actif existe déjà. Call UUID: {$existingActiveCall->call_uuid}, Status: {$existingActiveCall->status}");
            return response()->json([
                'message' => 'Un appel est déjà en cours ou en attente avec cet utilisateur.',
                'call_status' => $existingActiveCall->status,
                'existing_call_uuid' => $existingActiveCall->call_uuid,
            ], Response::HTTP_CONFLICT);
        }

        try {
            // Un appel est créé avec le statut 'initiated' dès le début.
            // Cela est nécessaire pour que le serveur puisse suivre l'appel
            // et permettre au destinataire de le rejeter ou de l'accepter,
            // même si la notification initiale n'arrive pas instantanément.
            // La gestion des appels non répondus (timeout) sera gérée par
            // un processus en arrière-plan (tâche planifiée) qui marquera
            // les appels 'initiated' non acceptés comme 'missed' après un délai.
            $call = Call::create([
                'caller_id' => $caller->id,
                'receiver_id' => $receiver->id,
                'call_type' => $request->call_type,
                'status' => 'initiated',
            ]);

            $call->load(['caller', 'receiver']);

            event(new CallInitiated($call));

            Log::info('Appel créé et événement CallInitiated dispatché.', ['call_db_id' => $call->id, 'call_uuid' => $call->call_uuid]);

            return response()->json([
                'message' => 'Appel initié avec succès. En attente de réponse...',
                'call' => $call->toArray(),
                'call_status' => $call->status, // Ajout du statut de l'appel pour plus de clarté
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            Log::error("Erreur lors de l'initiation de l'appel: " . $e->getMessage(), ['exception' => $e, 'request_data' => $request->all()]);
            return response()->json(['message' => 'Erreur lors de l\'initiation de l\'appel. Veuillez réessayer.', 'error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Accepte un appel.
     *
     * @param Request $request
     * @param string $call_uuid Le UUID de l'appel à accepter (via paramètre de route)
     * @return \Illuminate\Http\JsonResponse
     */
    public function accept(Request $request, $call_uuid)
    {
        $request->validate([
            'call_uuid' => 'uuid|exists:calls,call_uuid',
        ]);

        $receiver = Auth::user();

        try {
            $call = Call::where('call_uuid', $call_uuid)
                        ->where('receiver_id', $receiver->id)
                        ->first();

            if (!$call) {
                Log::warning("Accept: Appel non trouvé ou non correspondant pour le receiver. Call UUID: {$call_uuid}, Receiver ID: {$receiver->id}");
                return response()->json(['message' => 'Appel non trouvé ou non valide.'], Response::HTTP_NOT_FOUND);
            }

            if ($call->status !== 'initiated') {
                Log::warning("Accept: Tentative d'accepter un appel qui n'est pas en statut 'initiated'. Call UUID: {$call_uuid}, Current Status: {$call->status}");
                if ($call->status === 'accepted') {
                    return response()->json(['message' => 'Cet appel est déjà accepté.', 'call_status' => $call->status], Response::HTTP_CONFLICT);
                }
                if ($call->status === 'rejected') {
                    return response()->json(['message' => 'Cet appel a déjà été rejeté.', 'call_status' => $call->status], Response::HTTP_GONE);
                }
                if (in_array($call->status, ['ended', 'cancelled', 'missed'])) {
                    return response()->json(['message' => 'Cet appel est déjà terminé ou annulé.', 'call_status' => $call->status], Response::HTTP_GONE);
                }
                return response()->json(['message' => 'L\'appel n\'est pas dans un état valide pour être accepté.'], Response::HTTP_CONFLICT);
            }

            $call->update([
                'status' => 'accepted',
                'started_at' => now(),
            ]);

            Log::info("Appel accepté: Call UUID {$call_uuid}, Receiver ID {$receiver->id}, Caller ID {$call->caller_id}");

            $call->load(['caller', 'receiver']);
            event(new CallAccepted($call));

            return response()->json(['message' => 'Appel accepté avec succès.'], Response::HTTP_OK);

        } catch (\Exception $e) {
            Log::error("Erreur lors de l'acceptation de l'appel: " . $e->getMessage(), ['exception' => $e, 'request_data' => $request->all()]);
            return response()->json(['message' => 'Erreur lors de l\'enregistrement de l\'acceptation de l\'appel. Veuillez réessayer.', 'error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Rejette un appel.
     *
     * @param Request $request
     * @param string $call_uuid Le UUID de l'appel à rejeter (via paramètre de route)
     * @return \Illuminate\Http\JsonResponse
     */
    public function reject(Request $request, $call_uuid)
    {
        $request->validate([
            'call_uuid' => 'uuid|exists:calls,call_uuid',
        ]);

        $receiver = Auth::user();

        try {
            $call = Call::where('call_uuid', $call_uuid)
                        ->where('receiver_id', $receiver->id)
                        ->first();

            if (!$call) {
                Log::warning("Reject: Appel non trouvé ou non correspondant pour le rejet: Call UUID {$call_uuid}, Receiver ID: {$receiver->id}");
                return response()->json(['message' => 'Appel non trouvé ou non valide.'], Response::HTTP_NOT_FOUND);
            }

            if ($call->status !== 'initiated') {
                Log::warning("Reject: Tentative de rejeter un appel qui n'est pas en statut 'initiated'. Call UUID: {$call_uuid}, Current Status: {$call->status}");
                if ($call->status === 'accepted') {
                    return response()->json(['message' => 'Cet appel est déjà accepté et ne peut plus être rejeté.', 'call_status' => $call->status], Response::HTTP_CONFLICT);
                }
                if ($call->status === 'rejected') {
                    return response()->json(['message' => 'Cet appel a déjà été rejeté.', 'call_status' => $call->status], Response::HTTP_GONE);
                }
                if (in_array($call->status, ['ended', 'cancelled', 'missed'])) {
                    return response()->json(['message' => 'Cet appel est déjà terminé ou annulé.', 'call_status' => $call->status], Response::HTTP_GONE);
                }
                return response()->json(['message' => 'L\'appel n\'est pas dans un état valide pour être rejeté.'], Response::HTTP_CONFLICT);
            }

            $call->update([
                'status' => 'rejected',
                'ended_at' => now(),
            ]);

            Log::info("Appel rejeté: Call UUID {$call_uuid}, Receiver ID {$receiver->id}, Caller ID {$call->caller_id}");

            $call->load(['caller', 'receiver']);
            event(new CallRejected($call));

            return response()->json(['message' => 'Appel rejeté avec succès.'], Response::HTTP_OK);

        } catch (\Exception $e) {
            Log::error("Erreur lors du rejet de l'appel: " . $e->getMessage(), ['exception' => $e, 'request_data' => $request->all()]);
            return response()->json(['message' => 'Erreur lors de l\'enregistrement du rejet de l\'appel. Veuillez réessayer.', 'error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Termine un appel. Peut être appelé par l'appelant ou le destinataire.
     *
     * @param Request $request
     * @param string $call_uuid Le UUID de l'appel à terminer (via paramètre de route)
     * @return \Illuminate\Http\JsonResponse
     */
    public function end(Request $request, $call_uuid)
    {
        $request->validate([
            'call_uuid' => 'uuid|exists:calls,call_uuid',
            'duration' => 'nullable|integer|min:0',
        ]);

        $ender = Auth::user();

        try {
            $call = Call::where('call_uuid', $call_uuid)
                        ->where(function($query) use ($ender) {
                            $query->where('caller_id', $ender->id)
                                    ->orWhere('receiver_id', $ender->id);
                        })
                        ->first();

            if (!$call) {
                Log::warning("End: Appel non trouvé ou non correspondant pour la fin: Call UUID {$call_uuid}, Ender ID: {$ender->id}");
                return response()->json(['message' => 'Appel non trouvé ou non valide pour la fin.'], Response::HTTP_NOT_FOUND);
            }

            if (in_array($call->status, ['ended', 'rejected', 'cancelled', 'missed'])) {
                Log::info("End: Tentative de terminer un appel déjà dans un état final. Call UUID: {$call_uuid}, Current Status: {$call->status}");
                return response()->json([
                    'message' => 'L\'appel est déjà terminé ou dans un état final.',
                    'final_status' => $call->status
                ], Response::HTTP_OK);
            }

            $updateData = ['ended_at' => now()];

            if ($call->status === 'initiated') {
                if ($call->caller_id === $ender->id) {
                    $updateData['status'] = 'cancelled';
                } elseif ($call->receiver_id === $ender->id) {
                    $updateData['status'] = 'rejected';
                }
            } else if ($call->status === 'accepted') {
                $updateData['status'] = 'ended';
                if ($request->has('duration')) {
                    $updateData['duration'] = $request->duration;
                } else {
                    if ($call->started_at) {
                        $updateData['duration'] = now()->diffInSeconds($call->started_at);
                    }
                }
            }

            $call->update($updateData);

            Log::info("Appel terminé: Call UUID {$call_uuid}, Ender ID {$ender->id}, Final Status: {$call->status}");

            $call->load(['caller', 'receiver']);
            event(new CallEnded($call));

            return response()->json(['message' => 'Appel terminé avec succès.', 'final_status' => $call->status], Response::HTTP_OK);

        } catch (\Exception $e) {
            Log::error("Erreur lors de la fin de l'appel: " . $e->getMessage(), ['exception' => $e, 'request_data' => $request->all()]);
            return response()->json(['message' => 'Erreur lors de l\'enregistrement de la fin de l\'appel. Veuillez réessayer.', 'error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Gère l'échange de messages de signalisation WebRTC (Offer, Answer, ICE Candidates)
     * entre les participants d'un appel.
     *
     * @param Request $request
     * @param string $call_uuid Le UUID de l'appel (via paramètre de route)
     * @return \Illuminate\Http\JsonResponse
     */
    public function signal(Request $request, $call_uuid)
    {
        $request->validate([
            'call_uuid' => 'uuid|exists:calls,call_uuid',
            'receiver_id' => 'required|exists:users,id',
            'type' => 'required|in:offer,answer,ice-candidate',
            'payload' => 'required|array',
        ]);

        $sender = Auth::user();
        $receiver = User::find($request->receiver_id);

        if (!$receiver) {
            Log::warning("Signalisation: Destinataire non trouvé. Receiver ID: {$request->receiver_id}");
            return response()->json(['message' => 'Destinataire du signal non trouvé.'], Response::HTTP_NOT_FOUND);
        }

        $call = Call::where('call_uuid', $call_uuid)->first();

        if (!$call) {
            Log::warning("Signalisation: Appel non trouvé. Call UUID: {$call_uuid}");
            return response()->json(['message' => 'Appel non trouvé.'], Response::HTTP_NOT_FOUND);
        }

        if ($sender->id !== $call->caller_id && $sender->id !== $call->receiver_id) {
            Log::warning("Signalisation: Expéditeur non participant à l'appel. Call UUID: {$call->call_uuid}, Sender ID: {$sender->id}");
            return response()->json(['message' => 'Vous n\'êtes pas un participant valide pour cet appel.'], Response::HTTP_FORBIDDEN);
        }

        $expectedReceiverId = ($sender->id === $call->caller_id) ? $call->receiver_id : $call->caller_id;
        if ($receiver->id != $expectedReceiverId) {
            Log::warning("Signalisation: Destinataire du signal incorrect. Call UUID: {$call->call_uuid}, Expected Receiver ID: {$expectedReceiverId}, Actual Receiver ID: {$receiver->id}");
            return response()->json(['message' => 'Destinataire du signal incorrect pour cet appel.'], Response::HTTP_BAD_REQUEST);
        }

        switch ($request->type) {
            case 'offer':
                if ($call->status !== 'initiated') {
                    Log::warning("Signalisation: Offre reçue pour un appel non initié. Call UUID: {$call->call_uuid}, Status: {$call->status}");
                    return response()->json(['message' => 'L\'appel n\'est pas dans un état valide pour recevoir une offre.'], Response::HTTP_CONFLICT);
                }
                break;

            case 'answer':
                if ($call->status !== 'initiated') {
                    Log::warning("Signalisation: Réponse reçue pour un appel non initié. Call UUID: {$call->call_uuid}, Status: {$call->status}");
                    return response()->json(['message' => 'L\'appel n\'est pas dans un état valide pour recevoir une réponse.'], Response::HTTP_CONFLICT);
                }
                break;

            case 'ice-candidate':
                if ($call->status !== 'initiated' && $call->status !== 'accepted') {
                    Log::warning("Signalisation: ICE Candidate reçu pour un appel non actif. Call UUID: {$call->call_uuid}, Status: {$call->status}");
                    return response()->json(['message' => 'L\'appel n\'est pas dans un état valide pour échanger des ICE candidates.'], Response::HTTP_CONFLICT);
                }
                break;

            default:
                Log::warning("Signalisation: Type de signal inconnu. Type: {$request->type}");
                return response()->json(['message' => 'Type de signal invalide.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            event(new CallSignal(
                $call->call_uuid,
                $sender,
                $receiver,
                $request->type,
                $request->payload
            ));

            Log::info("Signalisation envoyée: Call UUID {$call_uuid}, Type: {$request->type}, Sender ID: {$sender->id}, Receiver ID: {$receiver->id}");

            return response()->json(['message' => 'Signal envoyé avec succès.'], Response::HTTP_OK);

        } catch (\Exception $e) {
            Log::error("Erreur lors de l'envoi du signal: " . $e->getMessage(), ['exception' => $e, 'request_data' => $request->all()]);
            return response()->json(['message' => 'Erreur lors de l\'envoi du signal.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
