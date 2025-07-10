<?php

namespace App\Models;

// Assurez-vous que SEULEMENT ces lignes 'use' sont présentes pour un modèle
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'user_id',       // C'est l'ID de l'expéditeur (sender)
        'receiver_id',   // <--- Assurez-vous que ceci est bien dans $fillable
        'body',
        'type',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    /**
     * Le message appartient à une conversation.
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * Le message a été envoyé par un utilisateur (l'expéditeur).
     * C'est la relation que votre code tente d'appeler via $message->user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Le message est destiné à un utilisateur spécifique (le destinataire).
     * Requis pour le comptage des messages non lus.
     */
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    /**
     * Utilisateurs qui ont lu ce message (pour les conversations de groupe, si applicable).
     */
    public function readBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'message_user_reads', 'message_id', 'user_id')
                     ->withPivot('read_at')
                     ->withTimestamps();
    }
}