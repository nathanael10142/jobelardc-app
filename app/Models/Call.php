<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth; // Pour l'accesseur otherParticipant
use Illuminate\Support\Str; // Pour générer le UUID

class Call extends Model
{
    use HasFactory;

    protected $fillable = [
        // Removed 'call_id' from fillable as 'id' is the primary key and auto-incrementing
        'caller_id',
        'receiver_id',
        'call_type',
        'status',
        'duration',
        'call_uuid', // Added for WebRTC signaling
        'started_at', // Added to track when the call actually started
        'ended_at',   // Added to track when the call ended
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        // Generate a UUID before creating the model
        static::creating(function ($call) {
            if (empty($call->call_uuid)) {
                $call->call_uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Relation avec l'appelant.
     */
    public function caller()
    {
        return $this->belongsTo(User::class, 'caller_id');
    }

    /**
     * Relation avec le destinataire.
     */
    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    /**
     * Accesseur pour obtenir l'autre participant (utile pour la vue calls.index).
     */
    public function getOtherParticipantAttribute()
    {
        if (Auth::check()) {
            // Si l'utilisateur connecté est l'appelant, l'autre participant est le destinataire, et vice-versa.
            return $this->caller_id === Auth::id() ? $this->receiver : $this->caller;
        }
        return null;
    }

    /**
     * Accesseur pour le texte lisible du statut de l'appel.
     * Note: La logique de la vue Blade gère déjà cela de manière plus sophistiquée
     * en fonction de qui est l'appelant/receveur et de la durée.
     * Cet accesseur peut être utile pour d'autres parties de l'application ou pour les logs.
     */
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
            case 'cancelled': // Added this status
                return 'Appel annulé';
            default:
                return 'Statut inconnu';
        }
    }
}