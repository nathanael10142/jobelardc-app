<?php

namespace App\Http\Controllers;

use App\Models\Call;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
// use App\Events\CallInitiated; // Commenté temporairement
// use App\Events\CallAccepted; // Commenté temporairement
// use App\Events\CallRejected; // Commenté temporairement
// use App\Events\CallEnded; // Commenté temporairement

class CallController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum')->only(['initiate', 'accept', 'reject', 'end']); // Appliquer pour les API
        $this->middleware('auth')->only(['index']); // Appliquer pour la vue web
    }

    /**
     * Affiche l'historique des appels de l'utilisateur authentifié.
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
            $callsQuery = Call::where('caller_id', $user->id)
                               ->orWhere('receiver_id', $user->id)
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
            Log::error("Erreur lors de la récupération des appels dans CallController@index: " . $e->getMessage());
        }

        return view('calls.index', compact('calls'));
    }

    /**
     * Gère l'initiation d'un nouvel appel.
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

            // TEMPORAIREMENT COMMENTÉ POUR LE DÉBOGAGE DE L'ERREUR "queue"
            // event(new CallInitiated($callId, $caller, $receiver, $request->call_type));

            return response()->json([
                'message' => 'Appel initié avec succès. (Événement de diffusion désactivé pour le test)',
                'call_id' => $call->call_id,
                'caller' => $caller->only(['id', 'name', 'profile_picture']),
                'receiver' => $receiver->only(['id', 'name', 'profile_picture']),
                'call_type' => $request->call_type,
            ], 200);

        } catch (\Exception $e) {
            Log::error("Erreur lors de l'initiation de l'appel (sans événement): " . $e->getMessage(), ['exception' => $e]);
            return response()->json(['message' => 'Erreur lors de l\'initiation de l\'appel. Veuillez réessayer.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Gère l'acceptation d'un appel.
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

        try {
            $call = Call::where('call_id', $request->call_id)
                        ->where('receiver_id', $receiver->id)
                        ->first();

            if ($call) {
                $call->update(['status' => 'accepted']);
            } else {
                Log::warning("Appel non trouvé ou non correspondant pour l'acceptation: Call ID {$request->call_id}, Receiver ID {$receiver->id}");
                return response()->json(['message' => 'Appel non trouvé ou non valide pour l\'acceptation.'], 404);
            }
        } catch (\Exception $e) {
            Log::error("Erreur lors de la mise à jour de l'appel pour l'acceptation: " . $e->getMessage());
            return response()->json(['message' => 'Erreur lors de l\'enregistrement de l\'acceptation de l\'appel.'], 500);
        }

        Log::info("Appel accepté: Call ID {$request->call_id}, Receiver ID {$receiver->id}, Caller ID {$caller->id}");

        // TEMPORAIREMENT COMMENTÉ POUR LE DÉBOGAGE DE L'ERREUR "queue"
        // event(new CallAccepted($request->call_id, $caller, $receiver));

        return response()->json(['message' => 'Appel accepté.'], 200);
    }

    /**
     * Gère le rejet d'un appel.
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

        try {
            $call = Call::where('call_id', $request->call_id)
                        ->where('receiver_id', $receiver->id)
                        ->first();

            if ($call) {
                $call->update(['status' => 'rejected']);
            } else {
                Log::warning("Appel non trouvé ou non correspondant pour le rejet: Call ID {$request->call_id}, Receiver ID {$receiver->id}");
                return response()->json(['message' => 'Appel non trouvé ou non valide pour le rejet.'], 404);
            }
        } catch (\Exception $e) {
            Log::error("Erreur lors de la mise à jour de l'appel pour le rejet: " . $e->getMessage());
            return response()->json(['message' => 'Erreur lors de l\'enregistrement du rejet de l\'appel.'], 500);
        }

        Log::info("Appel rejeté: Call ID {$request->call_id}, Receiver ID {$receiver->id}, Caller ID {$caller->id}");

        // TEMPORAIREMENT COMMENTÉ POUR LE DÉBOGAGE DE L'ERREUR "queue"
        // event(new CallRejected($request->call_id, $caller, $receiver));

        return response()->json(['message' => 'Appel rejeté.'], 200);
    }

    /**
     * Termine un appel en cours.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function end(Request $request)
    {
        $request->validate([
            'call_id' => 'required|string',
            'participant_id' => 'required|exists:users,id',
            'duration' => 'nullable|integer|min:0',
        ]);

        $ender = Auth::user();
        $otherParticipant = User::find($request->participant_id);

        if (!$otherParticipant) {
            return response()->json(['message' => 'Autre participant non trouvé.'], 404);
        }

        try {
            $call = Call::where('call_id', $request->call_id)
                        ->where(function($query) use ($ender) {
                            $query->where('caller_id', $ender->id)
                                  ->orWhere('receiver_id', $ender->id);
                        })
                        ->first();

            if ($call) {
                $updateData = ['status' => 'ended'];
                if ($request->has('duration')) {
                    $updateData['duration'] = $request->duration;
                }
                $call->update($updateData);

                if ($call->status === 'initiated' && $call->receiver_id === $ender->id) {
                    $call->update(['status' => 'missed']);
                }

            } else {
                Log::warning("Appel non trouvé ou non correspondant pour la fin: Call ID {$request->call_id}, Ender ID {$ender->id}");
                return response()->json(['message' => 'Appel non trouvé ou non valide pour la fin.'], 404);
            }
        } catch (\Exception $e) {
            Log::error("Erreur lors de la mise à jour de l'appel pour la fin: " . $e->getMessage());
            return response()->json(['message' => 'Erreur lors de l\'enregistrement de la fin de l\'appel.'], 500);
        }

        Log::info("Appel terminé: Call ID {$request->call_id}, Ender ID {$ender->id}, Other Participant ID {$otherParticipant->id}");

        // TEMPORAIREMENT COMMENTÉ POUR LE DÉBOGAGE DE L'ERREUR "queue"
        // event(new CallEnded($request->call_id, $ender, $otherParticipant));

        return response()->json(['message' => 'Appel terminé.'], 200);
    }
}
