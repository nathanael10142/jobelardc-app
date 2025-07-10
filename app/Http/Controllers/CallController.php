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
use App\Events\CallSignal; // Cet événement va gérer l'échange de SDP et ICE
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpFoundation\Response; // Pour les codes HTTP plus lisibles

class CallController extends Controller
{
    public function __construct()
    {
        // Middleware pour les routes API (utilisant Sanctum pour l'authentification API)
        // 'searchContacts' a été retiré car cette méthode a été déplacée vers UserController
        $this->middleware('auth:sanctum')->only(['initiate', 'accept', 'reject', 'end', 'signal', 'indexApi']);
        // Middleware pour les routes web (utilisant l'authentification basée sur la session)
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

        $calls = collect([]); // Initialiser avec une collection vide

        try {
            $callsQuery = Call::where(function ($query) use ($user) {
                                $query->where('caller_id', $user->id)
                                      ->orWhere('receiver_id', $user->id);
                            })
                            ->with(['caller', 'receiver']) // Charger les relations pour l'affichage
                            ->latest(); // Trier par les appels les plus récents

            if ($searchQuery) {
                $callsQuery->where(function ($query) use ($searchQuery) {
                    $query->whereHas('caller', function ($q) use ($searchQuery) {
                        $q->where('name', 'like', '%' . $searchQuery . '%');
                    })->orWhereHas('receiver', function ($q) use ($searchQuery) {
                        $q->where('name', 'like', '%' . $searchQuery . '%');
                    });
                });
            }

            $calls = $callsQuery->get(); // Récupérer les appels

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
                            ->with(['caller', 'receiver']) // Charger les relations pour l'affichage
                            ->latest(); // Trier par les appels les plus récents

            if ($searchQuery) {
                $callsQuery->where(function ($query) use ($searchQuery) {
                    $query->whereHas('caller', function ($q) use ($searchQuery) {
                        $q->where('name', 'like', '%' . $searchQuery . '%');
                    })->orWhereHas('receiver', function ($q) use ($searchQuery) {
                        $q->where('name', 'like', '%' . $searchQuery . '%');
                    });
                });
            }

            $calls = $callsQuery->get(); // Récupérer les appels

            // Transformer les appels pour inclure les informations nécessaires au frontend
            $formattedCalls = $calls->map(function ($call) use ($user) {
                // Déterminer l'autre participant pour l'affichage
                $otherParticipant = null;
                if ($call->caller_id === $user->id) {
                    $otherParticipant = $call->receiver;
                } else {
                    $otherParticipant = $call->caller;
                }

                // Ajouter des drapeaux pour la logique d'affichage côté client
                // Un appel manqué est un appel où l'utilisateur actuel est le destinataire,
                // le statut est 'initiated' (pas répondu) et l'appel a été 'ended' ou 'cancelled' (par timeout ou raccrochage de l'appelant)
                // ou spécifiquement 'missed' si ce statut est utilisé.
                $isMissedCall = ($call->receiver_id === $user->id && $call->status === 'missed');
                // Un appel sortant est un appel où l'utilisateur actuel est l'appelant
                $isOutgoingCall = ($call->caller_id === $user->id);

                return [
                    'id' => $call->id,
                    'call_uuid' => $call->call_uuid,
                    'caller_id' => $call->caller_id, // Inclure pour la logique côté client
                    'receiver_id' => $call->receiver_id, // Inclure pour la logique côté client
                    'call_type' => $call->call_type,
                    'status' => $call->status,
                    'duration' => $call->duration,
                    // Formater les dates en ISO string pour une manipulation facile en JS
                    'created_at' => $call->created_at->toISOString(),
                    'started_at' => $call->started_at ? $call->started_at->toISOString() : null,
                    'ended_at' => $call->ended_at ? $call->ended_at->toISOString() : null,
                    'created_at_for_humans' => $call->created_at->diffForHumans(), // Pour l'affichage "il y a X temps"
                    'is_missed_call' => $isMissedCall,
                    'is_outgoing_call' => $isOutgoingCall,
                    // Informations sur l'autre participant, avec sélection des champs pertinents
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

    // La méthode searchContacts a été déplacée vers UserController.
    // Elle ne doit plus exister ici dans CallController.

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
            ], Response::HTTP_CONFLICT); // 409 Conflict
        }

        try {
            $call = Call::create([
                // 'call_uuid' est généré automatiquement par le modèle Call grâce à la méthode boot()
                'caller_id' => $caller->id,
                'receiver_id' => $receiver->id,
                'call_type' => $request->call_type,
                'status' => 'initiated',
                // 'started_at' et 'ended_at' sont nulls à l'initiation
            ]);

            // Charger les relations 'caller' et 'receiver' pour la réponse JSON et l'événement
            $call->load(['caller', 'receiver']);

            // Dispatch l'événement en passant l'objet Call complet
            event(new CallInitiated($call));

            Log::info('Appel créé et événement CallInitiated dispatché.', ['call_db_id' => $call->id, 'call_uuid' => $call->call_uuid]);

            return response()->json([
                'message' => 'Appel initié avec succès. En attente de réponse...',
                'call' => $call->toArray(), // Retourner l'objet Call complet comme tableau
            ], Response::HTTP_OK); // 200 OK

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
        // La validation 'call_uuid' est implicite via le paramètre de route et le middleware 'exists'
        // Cependant, on peut la laisser pour une validation explicite du format UUID.
        $request->validate([
            'call_uuid' => 'uuid|exists:calls,call_uuid', // Valider sur 'call_uuid'
        ]);

        $receiver = Auth::user();

        try {
            // Utiliser 'call_uuid' du paramètre de route pour trouver l'appel
            $call = Call::where('call_uuid', $call_uuid)
                        ->where('receiver_id', $receiver->id)
                        ->first();

            if (!$call) {
                Log::warning("Accept: Appel non trouvé ou non correspondant pour le receiver. Call UUID: {$call_uuid}, Receiver ID: {$receiver->id}");
                return response()->json(['message' => 'Appel non trouvé ou non valide.'], Response::HTTP_NOT_FOUND);
            }

            if ($call->status !== 'initiated') {
                Log::warning("Accept: Tentative d'accepter un appel qui n'est pas en statut 'initiated'. Call UUID: {$call_uuid}, Current Status: {$call->status}");
                // Si l'appel est déjà accepté, rejeté ou terminé, informer le frontend
                if ($call->status === 'accepted') {
                    return response()->json(['message' => 'Cet appel est déjà accepté.', 'call_status' => $call->status], Response::HTTP_CONFLICT);
                }
                if ($call->status === 'rejected') {
                    return response()->json(['message' => 'Cet appel a déjà été rejeté.', 'call_status' => $call->status], Response::HTTP_GONE); // 410 Gone
                }
                if (in_array($call->status, ['ended', 'cancelled', 'missed'])) {
                    return response()->json(['message' => 'Cet appel est déjà terminé ou annulé.', 'call_status' => $call->status], Response::HTTP_GONE);
                }
                return response()->json(['message' => 'L\'appel n\'est pas dans un état valide pour être accepté.'], Response::HTTP_CONFLICT);
            }

            $call->update([
                'status' => 'accepted',
                'started_at' => now(), // Enregistrer l'heure de début de l'appel
            ]);

            Log::info("Appel accepté: Call UUID {$call_uuid}, Receiver ID {$receiver->id}, Caller ID {$call->caller_id}");

            // Passer l'objet Call complet à l'événement CallAccepted
            $call->load(['caller', 'receiver']); // Charger pour l'événement
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
            'call_uuid' => 'uuid|exists:calls,call_uuid', // Valider sur 'call_uuid'
        ]);

        $receiver = Auth::user();

        try {
            $call = Call::where('call_uuid', $call_uuid) // Utiliser $call_uuid du paramètre de route
                        ->where('receiver_id', $receiver->id)
                        ->first();

            if (!$call) {
                Log::warning("Reject: Appel non trouvé ou non correspondant pour le rejet: Call UUID {$call_uuid}, Receiver ID: {$receiver->id}");
                return response()->json(['message' => 'Appel non trouvé ou non valide.'], Response::HTTP_NOT_FOUND);
            }

            if ($call->status !== 'initiated') {
                Log::warning("Reject: Tentative de rejeter un appel qui n'est pas en statut 'initiated'. Call UUID: {$call_uuid}, Current Status: {$call->status}");
                // Si l'appel est déjà accepté, rejeté ou terminé, informer le frontend
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
                'ended_at' => now(), // Marquer la fin du rejet
            ]);

            Log::info("Appel rejeté: Call UUID {$call_uuid}, Receiver ID {$receiver->id}, Caller ID {$call->caller_id}");

            // Passer l'objet Call complet à l'événement CallRejected
            $call->load(['caller', 'receiver']); // Charger pour l'événement
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
            'duration' => 'nullable|integer|min:0', // Durée envoyée par le frontend si l'appel était connecté
        ]);

        $ender = Auth::user(); // L'utilisateur qui met fin à l'appel

        try {
            $call = Call::where('call_uuid', $call_uuid) // Utiliser $call_uuid du paramètre de route
                        ->where(function($query) use ($ender) {
                            $query->where('caller_id', $ender->id)
                                  ->orWhere('receiver_id', $ender->id);
                        })
                        ->first();

            if (!$call) {
                Log::warning("End: Appel non trouvé ou non correspondant pour la fin: Call UUID {$call_uuid}, Ender ID: {$ender->id}");
                return response()->json(['message' => 'Appel non trouvé ou non valide pour la fin.'], Response::HTTP_NOT_FOUND);
            }

            // Si l'appel est déjà dans un état final, ne rien faire
            if (in_array($call->status, ['ended', 'rejected', 'cancelled', 'missed'])) {
                Log::info("End: Tentative de terminer un appel déjà dans un état final. Call UUID: {$call_uuid}, Current Status: {$call->status}");
                return response()->json([
                    'message' => 'L\'appel est déjà terminé ou dans un état final.',
                    'final_status' => $call->status
                ], Response::HTTP_OK); // OK car l'action est "accomplie"
            }

            $updateData = ['ended_at' => now()]; // Toujours définir ended_at lors de la fin

            // Logique pour déterminer le statut final de l'appel
            if ($call->status === 'initiated') {
                if ($call->caller_id === $ender->id) {
                    // L'appelant a annulé l'appel avant qu'il ne soit accepté
                    $updateData['status'] = 'cancelled';
                } elseif ($call->receiver_id === $ender->id) {
                    // Le destinataire raccroche avant d'accepter (équivalent à rejeter mais via le bouton "fin")
                    $updateData['status'] = 'rejected'; // Utilisation de 'rejected' pour la clarté
                }
            } else if ($call->status === 'accepted') {
                // L'appel était accepté et est maintenant terminé
                $updateData['status'] = 'ended';
                if ($request->has('duration')) {
                    $updateData['duration'] = $request->duration;
                } else {
                    // Calculer la durée si elle n'est pas fournie et started_at existe
                    if ($call->started_at) {
                        $updateData['duration'] = now()->diffInSeconds($call->started_at);
                    }
                }
            }
            // Aucun autre statut ne devrait être géré ici car les autres sont des états finaux.

            $call->update($updateData);

            Log::info("Appel terminé: Call UUID {$call_uuid}, Ender ID {$ender->id}, Final Status: {$call->status}");

            // Charger les relations pour l'événement CallEnded
            $call->load(['caller', 'receiver']);
            event(new CallEnded($call)); // Passer l'objet Call complet

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
            'call_uuid' => 'uuid|exists:calls,call_uuid', // Valider sur 'call_uuid'
            'receiver_id' => 'required|exists:users,id',
            'type' => 'required|in:offer,answer,ice-candidate', // 'ice-candidate' est correct
            'payload' => 'required|array', // Le payload contient les données SDP ou ICE
        ]);

        $sender = Auth::user();
        // Le receiver est déjà récupéré par User::find($request->receiver_id)
        $receiver = User::find($request->receiver_id);

        if (!$receiver) {
            Log::warning("Signalisation: Destinataire non trouvé. Receiver ID: {$request->receiver_id}");
            return response()->json(['message' => 'Destinataire du signal non trouvé.'], Response::HTTP_NOT_FOUND);
        }

        $call = Call::where('call_uuid', $call_uuid)->first(); // Utiliser $call_uuid du paramètre de route

        if (!$call) {
            Log::warning("Signalisation: Appel non trouvé. Call UUID: {$call_uuid}");
            return response()->json(['message' => 'Appel non trouvé.'], Response::HTTP_NOT_FOUND);
        }

        // Vérifier que l'expéditeur du signal est bien un participant de l'appel
        if ($sender->id !== $call->caller_id && $sender->id !== $call->receiver_id) {
            Log::warning("Signalisation: Expéditeur non participant à l'appel. Call UUID: {$call->call_uuid}, Sender ID: {$sender->id}");
            return response()->json(['message' => 'Vous n\'êtes pas un participant valide pour cet appel.'], Response::HTTP_FORBIDDEN); // 403 Forbidden
        }

        // Vérifier que le destinataire du signal est bien l'autre participant
        $expectedReceiverId = ($sender->id === $call->caller_id) ? $call->receiver_id : $call->caller_id;
        if ($receiver->id != $expectedReceiverId) {
            Log::warning("Signalisation: Destinataire du signal incorrect. Call UUID: {$call->call_uuid}, Expected Receiver ID: {$expectedReceiverId}, Actual Receiver ID: {$receiver->id}");
            return response()->json(['message' => 'Destinataire du signal incorrect pour cet appel.'], Response::HTTP_BAD_REQUEST);
        }

        // Logique pour s'assurer que le signal n'est envoyé que si l'appel est dans un état valide pour ce type de signal.
        switch ($request->type) {
            case 'offer':
                // Une offre ne devrait être envoyée que si l'appel est 'initiated'.
                if ($call->status !== 'initiated') {
                    Log::warning("Signalisation: Offre reçue pour un appel non initié. Call UUID: {$call->call_uuid}, Status: {$call->status}");
                    return response()->json(['message' => 'L\'appel n\'est pas dans un état valide pour recevoir une offre.'], Response::HTTP_CONFLICT);
                }
                break;

            case 'answer':
                // Une réponse ne devrait être envoyée que si l'appel est 'initiated'.
                if ($call->status !== 'initiated') {
                    Log::warning("Signalisation: Réponse reçue pour un appel non initié. Call UUID: {$call->call_uuid}, Status: {$call->status}");
                    return response()->json(['message' => 'L\'appel n\'est pas dans un état valide pour recevoir une réponse.'], Response::HTTP_CONFLICT);
                }
                break;

            case 'ice-candidate':
                // Les ICE candidates peuvent être échangés tant que l'appel est 'initiated' ou 'accepted'.
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
            // CORRECTION ICI : Passer les objets User complets ($sender, $receiver) au lieu de leurs IDs
            event(new CallSignal(
                $call->call_uuid, // L'identifiant unique de l'appel
                $sender,          // <-- Objet User complet
                $receiver,        // <-- Objet User complet
                $request->type,   // 'offer', 'answer', 'ice-candidate'
                $request->payload // Les données SDP ou ICE
            ));

            Log::info("Signalisation envoyée: Call UUID {$call_uuid}, Type: {$request->type}, Sender ID: {$sender->id}, Receiver ID: {$receiver->id}");

            return response()->json(['message' => 'Signal envoyé avec succès.'], Response::HTTP_OK);

        } catch (\Exception $e) {
            Log::error("Erreur lors de l'envoi du signal: " . $e->getMessage(), ['exception' => $e, 'request_data' => $request->all()]);
            return response()->json(['message' => 'Erreur lors de l\'envoi du signal.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
