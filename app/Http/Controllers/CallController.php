<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Call; // <-- NOUVEAU: Assurez-vous d'importer votre modèle Call
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
     * Affiche la page des appels avec l'historique de l'utilisateur connecté.
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $searchQuery = $request->input('search');

        // Initialise une collection vide pour les appels.
        // Si le modèle Call n'existe pas ou n'est pas configuré, cette collection restera vide.
        $calls = collect([]);

        // --- IMPORTANT : Assurez-vous que votre modèle 'App\Models\Call' existe et que la migration a été exécutée. ---
        // Si le modèle Call n'existe pas ou n'est pas configuré, cette partie causera une erreur.
        // Vous devez créer une migration et un modèle Call.
        //
        // Exemple de migration (dans database/migrations/YYYY_MM_DD_HHMMSS_create_calls_table.php):
        /*
            <?php
            use Illuminate\Database\Migrations\Migration;
            use Illuminate\Database\Schema\Blueprint;
            use Illuminate\Support\Facades\Schema;

            return new class extends Migration
            {
                public function up(): void
                {
                    Schema::create('calls', function (Blueprint $table) {
                        $table->id();
                        $table->string('call_id')->unique(); // L'ID unique généré par initiate()
                        $table->foreignId('caller_id')->constrained('users')->onDelete('cascade');
                        $table->foreignId('receiver_id')->constrained('users')->onDelete('cascade');
                        $table->string('call_type'); // 'audio', 'video'
                        $table->string('status')->default('initiated'); // 'initiated', 'accepted', 'rejected', 'ended', 'missed'
                        $table->integer('duration')->nullable(); // Durée en secondes
                        $table->timestamps();
                    });
                }

                public function down(): void
                {
                    Schema::dropIfExists('calls');
                }
            };
        */
        //
        // Exemple de modèle App\Models\Call.php:
        /*
            <?php
            namespace App\Models;

            use Illuminate\Database\Eloquent\Factories\HasFactory;
            use Illuminate\Database\Eloquent\Model;
            use Illuminate\Support\Facades\Auth; // Pour l'accesseur otherParticipant

            class Call extends Model
            {
                use HasFactory;

                protected $fillable = [
                    'call_id',
                    'caller_id',
                    'receiver_id',
                    'call_type',
                    'status',
                    'duration',
                ];

                // Relation avec l'appelant
                public function caller()
                {
                    return $this->belongsTo(User::class, 'caller_id');
                }

                // Relation avec le destinataire
                public function receiver()
                {
                    return $this->belongsTo(User::class, 'receiver_id');
                }

                // Accesseur pour obtenir l'autre participant (pour la vue calls.index)
                public function getOtherParticipantAttribute()
                {
                    if (Auth::check()) {
                        return $this->caller_id === Auth::id() ? $this->receiver : $this->caller;
                    }
                    return null;
                }

                // Accesseur pour le texte du statut (pour la vue calls.index)
                public function getStatusTextAttribute()
                {
                    switch ($this->status) {
                        case 'initiated':
                            return 'Appel initié';
                        case 'accepted':
                            return 'Appel accepté';
                        case 'rejected':
                            return 'Appel rejeté';
                        case 'ended':
                            return 'Appel terminé';
                        case 'missed':
                            return 'Appel manqué';
                        default:
                            return 'Statut inconnu';
                    }
                }
            }
        */

        // Tente de récupérer les appels si le modèle Call existe
        try {
            // Récupère les appels où l'utilisateur est soit l'appelant, soit le destinataire
            $callsQuery = Call::where('caller_id', $user->id)
                              ->orWhere('receiver_id', $user->id)
                              ->with(['caller', 'receiver']) // Charge les relations pour éviter N+1
                              ->latest(); // Tri par les plus récents

            // Applique le filtre de recherche si une requête est présente
            if ($searchQuery) {
                $callsQuery->where(function ($query) use ($searchQuery) {
                    // Recherche par nom de l'appelant ou du destinataire
                    $query->whereHas('caller', function ($q) use ($searchQuery) {
                        $q->where('name', 'like', '%' . $searchQuery . '%');
                    })->orWhereHas('receiver', function ($q) use ($searchQuery) {
                        $q->where('name', 'like', '%' . $searchQuery . '%');
                    });
                });
            }

            $calls = $callsQuery->get();

        } catch (\Exception $e) {
            // Log l'erreur si le modèle Call n'est pas trouvé ou s'il y a un problème de base de données
            Log::error("Erreur lors de la récupération des appels dans CallController@index: " . $e->getMessage());
            // Laisse $calls comme une collection vide, ce qui affichera le message "Aucun appel dans votre historique"
            // Vous pouvez aussi passer un message d'erreur spécifique à la vue si vous le souhaitez.
        }

        return view('calls.index', compact('calls'));
    }


    /**
     * Initie un nouvel appel vers un utilisateur.
     * Enregistre l'appel dans la base de données.
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

        // Enregistrer l'appel dans la base de données
        try {
            Call::create([
                'call_id' => $callId,
                'caller_id' => $caller->id,
                'receiver_id' => $receiver->id,
                'call_type' => $request->call_type,
                'status' => 'initiated', // Statut initial
            ]);
        } catch (\Exception $e) {
            Log::error("Erreur lors de l'enregistrement de l'appel initié: " . $e->getMessage());
            return response()->json(['message' => 'Erreur lors de l\'enregistrement de l\'appel.'], 500);
        }


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
     * Met à jour le statut de l'appel dans la base de données.
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

        // Mettre à jour le statut de l'appel dans la base de données
        try {
            $call = Call::where('call_id', $request->call_id)
                        ->where('receiver_id', $receiver->id) // S'assurer que c'est bien l'appel pour cet utilisateur
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

        // Diffuser un événement pour notifier l'appelant que l'appel a été accepté
        event(new CallAccepted($request->call_id, $caller, $receiver));

        return response()->json(['message' => 'Appel accepté.'], 200);
    }

    /**
     * Rejette un appel entrant.
     * Met à jour le statut de l'appel dans la base de données.
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

        // Mettre à jour le statut de l'appel dans la base de données
        try {
            $call = Call::where('call_id', $request->call_id)
                        ->where('receiver_id', $receiver->id) // S'assurer que c'est bien l'appel pour cet utilisateur
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

        // Diffuser un événement pour notifier l'appelant que l'appel a été rejeté
        event(new CallRejected($request->call_id, $caller, $receiver));

        return response()->json(['message' => 'Appel rejeté.'], 200);
    }

    /**
     * Termine un appel en cours.
     * Peut être appelé par l'appelant ou le destinataire.
     * Met à jour le statut et la durée de l'appel dans la base de données.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function end(Request $request)
    {
        $request->validate([
            'call_id' => 'required|string',
            'participant_id' => 'required|exists:users,id', // L'ID de l'autre participant à l'appel
            'duration' => 'nullable|integer|min:0', // Durée de l'appel en secondes
        ]);

        $ender = Auth::user(); // Celui qui met fin à l'appel
        $otherParticipant = User::find($request->participant_id);

        if (!$otherParticipant) {
            return response()->json(['message' => 'Autre participant non trouvé.'], 404);
        }

        // Mettre à jour le statut et la durée de l'appel dans la base de données
        try {
            // Trouver l'appel où l'utilisateur actuel est l'appelant OU le destinataire
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

                // Logique pour marquer un appel comme "manqué" si le destinataire met fin à un appel initié non accepté
                // Cette logique peut être affinée selon les besoins précis de votre application.
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

        // Diffuser un événement pour notifier l'autre participant que l'appel est terminé
        event(new CallEnded($request->call_id, $ender, $otherParticipant));

        return response()->json(['message' => 'Appel terminé.'], 200);
    }
}
