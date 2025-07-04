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
            default:
                return 'Statut inconnu';
        }
    }
}